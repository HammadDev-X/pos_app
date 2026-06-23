<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class GenerateSkus extends Command
{
    protected $signature = 'generate:skus {--prefix=SKU-}';
    protected $description = 'Generate missing SKUs for products in the format PREFIX000001';

    public function handle(): int
    {
        $prefix = $this->option('prefix') ?: 'SKU-';
        $products = Product::whereNull('sku')->orWhere('sku', '')->get();

        foreach ($products as $product) {
            $counter = $product->id;
            $sku = $prefix . str_pad((string) $counter, 6, '0', STR_PAD_LEFT);
            // ensure unique
            while (Product::where('sku', $sku)->exists()) {
                $counter++;
                $sku = $prefix . str_pad((string) $counter, 6, '0', STR_PAD_LEFT);
            }
            $product->sku = $sku;
            $product->save();
            $this->info("Generated SKU {$sku} for product id {$product->id}");
        }

        $this->info('SKU generation complete.');
        return 0;
    }
}
