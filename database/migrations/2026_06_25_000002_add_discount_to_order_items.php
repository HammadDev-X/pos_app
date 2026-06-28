<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            if (!Schema::hasColumn('order_items', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('price');
            }
        });

        if (Schema::hasColumn('orders', 'discount')) {
            DB::table('orders')
                ->where('discount', '>', 0)
                ->orderBy('id')
                ->get(['id', 'discount'])
                ->each(function ($order): void {
                    $items = DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->get(['id', 'price']);

                    $gross = (float) $items->sum('price');

                    if ($gross <= 0) {
                        return;
                    }

                    $remainingDiscount = min((float) $order->discount, $gross);
                    $lastIndex = $items->count() - 1;

                    foreach ($items->values() as $index => $item) {
                        $discount = $index === $lastIndex
                            ? $remainingDiscount
                            : round(min((float) $item->price, ((float) $item->price / $gross) * (float) $order->discount), 2);

                        $remainingDiscount -= $discount;

                        DB::table('order_items')
                            ->where('id', $item->id)
                            ->update(['discount' => max($discount, 0)]);
                    }

                    DB::table('orders')->where('id', $order->id)->update(['discount' => 0]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            if (Schema::hasColumn('order_items', 'discount')) {
                $table->dropColumn('discount');
            }
        });
    }
};
