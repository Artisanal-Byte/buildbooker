<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('customers')
            ->select('mobile')
            ->whereNotNull('mobile')
            ->groupBy('mobile')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('mobile')
            ->all();

        if ($duplicates !== []) {
            throw new RuntimeException(
                'Cannot add customers.mobile unique constraint. Duplicate mobile numbers: '
                . implode(', ', $duplicates)
            );
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->unique('mobile', 'customers_mobile_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_mobile_unique');
        });
    }
};
