<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns if they do not exist and adjust existing columns
        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->unique()->after('barcode');
            }
            if (!Schema::hasColumn('products', 'short_code')) {
                $table->string('short_code')->nullable()->unique()->after('sku');
            }
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->default('pcs')->after('quantity');
            }
            if (!Schema::hasColumn('products', 'track_stock')) {
                $table->boolean('track_stock')->default(true)->after('unit');
            }
            if (!Schema::hasColumn('products', 'is_quick_item')) {
                $table->boolean('is_quick_item')->default(false)->after('track_stock');
            }
        });

        // Make barcode nullable and ensure unique when provided
        // Use raw statements to avoid requiring doctrine/dbal for column changes
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            // Make barcode nullable
            DB::statement('ALTER TABLE `products` MODIFY `barcode` VARCHAR(255) NULL');

            // Change quantity from integer to decimal(10,2)
            if (Schema::hasColumn('products', 'quantity')) {
                DB::statement('ALTER TABLE `products` MODIFY `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1');
            }
        } else {
            // For other drivers, attempt change() (may require doctrine/dbal)
            Schema::table('products', function (Blueprint $table): void {
                if (Schema::hasColumn('products', 'quantity')) {
                    $table->decimal('quantity', 10, 2)->default(1)->change();
                }
                $table->string('barcode')->nullable()->change();
            });
        }

        DB::table('products')
            ->whereNull('sku')
            ->orWhere('sku', '')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($product): void {
                $next = (int) $product->id;
                $sku = 'SKU-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);

                while (DB::table('products')->where('sku', $sku)->where('id', '!=', $product->id)->exists()) {
                    $next++;
                    $sku = 'SKU-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
                }

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['sku' => $sku]);
            });

        DB::table('products')
            ->whereNull('unit')
            ->orWhere('unit', '')
            ->update(['unit' => 'pcs']);

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `products` MODIFY `sku` VARCHAR(255) NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'is_quick_item')) {
                $table->dropColumn('is_quick_item');
            }
            if (Schema::hasColumn('products', 'track_stock')) {
                $table->dropColumn('track_stock');
            }
            if (Schema::hasColumn('products', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('products', 'short_code')) {
                $table->dropUnique(['short_code']);
                $table->dropColumn('short_code');
            }
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropUnique(['sku']);
                $table->dropColumn('sku');
            }
        });

        // Revert barcode and quantity changes if possible
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `products` MODIFY `barcode` VARCHAR(255) NOT NULL');
            if (Schema::hasColumn('products', 'quantity')) {
                DB::statement('ALTER TABLE `products` MODIFY `quantity` INT NOT NULL DEFAULT 1');
            }
        }
    }
};
