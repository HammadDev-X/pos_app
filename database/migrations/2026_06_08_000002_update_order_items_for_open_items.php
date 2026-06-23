<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make quantity decimal and product_id nullable, add custom_item_name
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `order_items` MODIFY `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE `order_items` MODIFY `product_id` BIGINT UNSIGNED NULL');
        } else {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->decimal('quantity', 10, 2)->default(1)->change();
                $table->foreignId('product_id')->nullable()->change();
            });
        }

        Schema::table('order_items', function (Blueprint $table): void {
            if (!Schema::hasColumn('order_items', 'custom_item_name')) {
                $table->string('custom_item_name')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            if (Schema::hasColumn('order_items', 'custom_item_name')) {
                $table->dropColumn('custom_item_name');
            }
        });

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `order_items` MODIFY `quantity` INT NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE `order_items` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
        }
    }
};
