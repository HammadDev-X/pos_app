<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `user_cart` MODIFY `quantity` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 1');
        } else {
            Schema::table('user_cart', function (Blueprint $table): void {
                $table->decimal('quantity', 10, 2)->unsigned()->default(1)->change();
            });
        }
    }

    public function down(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `user_cart` MODIFY `quantity` INT UNSIGNED NOT NULL');
        }
    }
};
