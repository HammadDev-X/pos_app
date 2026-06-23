<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (!Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code')->nullable()->after('id');
            }
        });

        DB::table('customers')
            ->whereNull('customer_code')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($customer): void {
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['customer_code' => 'CUST-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT)]);
            });

        Schema::table('customers', function (Blueprint $table): void {
            $table->unique('customer_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['customer_code']);
            $table->dropColumn('customer_code');
        });
    }
};
