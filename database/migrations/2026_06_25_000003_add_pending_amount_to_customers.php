<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (!Schema::hasColumn('customers', 'pending_amount')) {
                $table->decimal('pending_amount', 10, 2)->default(0)->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'pending_amount')) {
                $table->dropColumn('pending_amount');
            }
        });
    }
};
