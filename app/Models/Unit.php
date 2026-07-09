<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    // Relations
    protected $with = ['customer', 'transactions'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Transactions shown in history screens.
     *
     * Keep deleted transactions here so the UI can show them with a strikethrough
     * and receipt numbers are not reused. Do not use this relation for received /
     * due amount calculations.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->withTrashed();
    }

    /**
     * Only active transactions.
     *
     * Use this relation for payment totals so soft-deleted receipts do not affect
     * Base / GST / Total received and due amounts.
     */
    public function activeTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Accessors
    protected $appends = [
        'base_received_amount',
        'gst_received_amount',
        'formatted_total_amount',
        'formatted_base_amount',
        'formatted_gst_amount',
        'formatted_total_received_amount',
        'formatted_base_received_amount',
        'formatted_gst_received_amount',
        'formatted_total_due_amount',
        'formatted_base_due_amount',
        'formatted_gst_due_amount',
    ];

    protected function baseReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->baseReceivedTotal(),
        );
    }

    protected function gstReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->gstReceivedTotal(),
        );
    }

    protected function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->total_amount);
            },
        );
    }

    protected function formattedBaseAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->base_amount);
            },
        );
    }

    protected function formattedGstAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->gst_amount);
            },
        );
    }

    protected function formattedTotalReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->totalReceivedTotal());
            },
        );
    }

    protected function formattedBaseReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->baseReceivedTotal());
            },
        );
    }

    protected function formattedGstReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->gstReceivedTotal());
            },
        );
    }

    protected function formattedBaseDueAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency((float) $this->base_amount - $this->baseReceivedTotal());
            },
        );
    }

    protected function formattedGstDueAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency((float) $this->gst_amount - $this->gstReceivedTotal());
            },
        );
    }

    protected function formattedTotalDueAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency((float) $this->total_amount - $this->totalReceivedTotal());
            },
        );
    }


    private function baseReceivedTotal(): float
    {
        return $this->receivedTotal(false);
    }

    private function gstReceivedTotal(): float
    {
        return $this->receivedTotal(true);
    }

    private function totalReceivedTotal(): float
    {
        return (float) $this->activeTransactions()->sum('transaction_amount');
    }

    private function receivedTotal(bool $gst): float
    {
        return (float) $this->activeTransactions()
            ->where('gst', $gst)
            ->sum('transaction_amount');
    }

    // Managing Soft Deletes
    protected static function booted()
    {
        // When a Project is soft-deleted
        static::deleting(function ($unit): void {
            if (!$unit->isForceDeleting()) {
                $unit->transactions()->delete();
            }
        });

        // When a Project is restored
        static::restoring(function ($unit): void {
            $unit->transactions()->withTrashed()->restore();
        });
    }
}
