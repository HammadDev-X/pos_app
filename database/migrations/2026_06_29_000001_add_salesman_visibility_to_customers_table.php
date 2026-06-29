<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'is_visible_to_salesman')) {
                $table->boolean('is_visible_to_salesman')->default(true)->after('pending_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'is_visible_to_salesman')) {
                $table->dropColumn('is_visible_to_salesman');
            }
        });
    }
};
