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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->withTrashed();
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
            get: function () {
                return $this->transactions()->where('gst', false)->sum('transaction_amount');
            },
        );
    }

    protected function gstReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->transactions()->where('gst', true)->sum('transaction_amount');
            },
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
                return formatCurrency($this->transactions()->sum('transaction_amount'));
            },
        );
    }

    protected function formattedBaseReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->transactions()->where('gst', false)->sum('transaction_amount'));
            },
        );
    }

    protected function formattedGstReceivedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->transactions()->where('gst', true)->sum('transaction_amount'));
            },
        );
    }

    protected function formattedBaseDueAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->base_amount - ($this->transactions()->where('gst', false)->sum('transaction_amount')));
            },
        );
    }

    protected function formattedGstDueAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->gst_amount - ($this->transactions()->where('gst', true)->sum('transaction_amount')));
            },
        );
    }

    protected function formattedTotalDueAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return formatCurrency($this->total_amount - ($this->transactions()->sum('transaction_amount')));
            },
        );
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
