<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\PublicImageUrl;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductAnalyticsController extends Controller
{
    /**
     * Show analytics dashboard.
     */
    public function index(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $products = $this->productAnalytics($dateFrom, $dateTo);
        $totalRevenue = (float) $products->sum('revenue');
        $totalUnitsSold = (float) $products->sum('total_sold');
        $totalProfit = (float) $products->sum('profit');
        $productsWithSales = $products->where('total_sold', '>', 0)->count();
        $warningQuantity = (float) config('settings.warning_quantity', 10);

        return view('reports.product_analytics', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'products' => $products,
            'mostSellingProduct' => $products->sortByDesc('total_sold')->first(),
            'highestRevenueProduct' => $products->sortByDesc('revenue')->first(),
            'highestProfitProduct' => $products->sortByDesc('profit')->first(),
            'lowStockProducts' => $products
                ->filter(fn ($product): bool => $product->track_stock && (float) $product->stock > 0 && (float) $product->stock <= $warningQuantity)
                ->sortBy('stock')
                ->take(10)
                ->values(),
            'deadStockProducts' => $products
                ->filter(fn ($product): bool => (float) $product->total_sold <= 0 && (float) $product->stock > 0)
                ->sortByDesc('stock_value')
                ->take(10)
                ->values(),
            'topSoldProducts' => $products->sortByDesc('total_sold')->take(10)->values(),
            'topRevenueProducts' => $products->sortByDesc('revenue')->take(10)->values(),
            'summary' => [
                'total_products' => $products->count(),
                'active_products' => $products->where('status', true)->count(),
                'products_with_sales' => $productsWithSales,
                'total_units_sold' => $totalUnitsSold,
                'total_revenue' => $totalRevenue,
                'total_cost' => (float) $products->sum('cost'),
                'total_profit' => $totalProfit,
                'average_unit_price' => $totalUnitsSold > 0 ? $totalRevenue / $totalUnitsSold : 0,
                'inventory_value' => (float) $products->sum('stock_value'),
                'inventory_cost_value' => (float) $products->sum('stock_cost_value'),
                'low_stock_count' => $products->filter(fn ($product): bool => $product->track_stock && (float) $product->stock > 0 && (float) $product->stock <= $warningQuantity)->count(),
                'out_of_stock_count' => $products->filter(fn ($product): bool => $product->track_stock && (float) $product->stock <= 0)->count(),
                'dead_stock_count' => $products->filter(fn ($product): bool => (float) $product->total_sold <= 0 && (float) $product->stock > 0)->count(),
                'profit_margin' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0,
            ],
        ]);
    }

    /**
     * Return analytics data as JSON.
     */
    public function data(Request $request)
    {
        [$dateFrom, $dateTo] = $this->dateRange($request);
        $products = $this->productAnalytics($dateFrom, $dateTo);
        $most = $products->sortByDesc('total_sold')->first();

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'most_selling' => $most,
            'products' => $products->values(),
            'chart' => [
                'labels' => $products->sortByDesc('total_sold')->take(12)->pluck('name')->values(),
                'sold' => $products->sortByDesc('total_sold')->take(12)->pluck('total_sold')->values(),
                'revenue' => $products->sortByDesc('total_sold')->take(12)->pluck('revenue')->values(),
                'profit' => $products->sortByDesc('total_sold')->take(12)->pluck('profit')->values(),
            ],
        ]);
    }

    private function productAnalytics(string $dateFrom, string $dateTo)
    {
        $dateStart = $dateFrom . ' 00:00:00';
        $dateEnd = $dateTo . ' 23:59:59';

        return DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy(
                'products.id',
                'products.name',
                'products.price',
                'products.purchase_price',
                'products.image',
                'products.quantity',
                'products.sku',
                'products.short_code',
                'products.unit',
                'products.track_stock',
                'products.is_quick_item',
                'products.status',
                'categories.name'
            )
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.short_code',
                'products.unit',
                'products.price',
                'products.purchase_price',
                'products.image',
                'products.quantity as stock',
                'products.track_stock',
                'products.is_quick_item',
                'products.status',
                'categories.name as category_name',
                DB::raw("COALESCE(SUM(CASE WHEN orders.created_at BETWEEN '{$dateStart}' AND '{$dateEnd}' THEN order_items.quantity - COALESCE(order_items.returned_quantity, 0) ELSE 0 END), 0) as total_sold"),
                DB::raw("COALESCE(SUM(CASE WHEN orders.created_at BETWEEN '{$dateStart}' AND '{$dateEnd}' THEN (order_items.price - COALESCE(order_items.discount, 0)) * CASE WHEN order_items.quantity > 0 THEN (order_items.quantity - COALESCE(order_items.returned_quantity, 0)) / order_items.quantity ELSE 0 END ELSE 0 END), 0) as revenue"),
                DB::raw("COALESCE(SUM(CASE WHEN orders.created_at BETWEEN '{$dateStart}' AND '{$dateEnd}' THEN COALESCE(order_items.unit_cost, 0) * (order_items.quantity - COALESCE(order_items.returned_quantity, 0)) ELSE 0 END), 0) as cost"),
                DB::raw("COUNT(DISTINCT CASE WHEN orders.created_at BETWEEN '{$dateStart}' AND '{$dateEnd}' THEN orders.id END) as orders_count"),
                DB::raw("MAX(CASE WHEN orders.created_at BETWEEN '{$dateStart}' AND '{$dateEnd}' THEN orders.created_at END) as last_sold_at")
            )
            ->get()
            ->map(function ($product) {
                $product->price = (float) $product->price;
                $product->purchase_price = (float) ($product->purchase_price ?? 0);
                $product->stock = (float) $product->stock;
                $product->total_sold = (float) $product->total_sold;
                $product->revenue = (float) $product->revenue;
                $product->orders_count = (int) $product->orders_count;
                $product->cost = (float) $product->cost;
                $product->profit = $product->revenue - $product->cost;
                $product->profit_margin = $product->revenue > 0 ? ($product->profit / $product->revenue) * 100 : 0;
                $product->average_unit_price = $product->total_sold > 0 ? $product->revenue / $product->total_sold : 0;
                $product->stock_value = $product->stock * $product->price;
                $product->stock_cost_value = $product->stock * $product->purchase_price;
                $product->sell_through_rate = ($product->total_sold + $product->stock) > 0
                    ? ($product->total_sold / ($product->total_sold + $product->stock)) * 100
                    : 0;
                $product->track_stock = (bool) $product->track_stock;
                $product->is_quick_item = (bool) $product->is_quick_item;
                $product->status = (bool) $product->status;
                $product->image_url = PublicImageUrl::make($product->image, 'images/img-placeholder.jpg');

                return $product;
            })
            ->sortByDesc('total_sold')
            ->values();
    }

    private function dateRange(Request $request): array
    {
        $dateFrom = $this->dateOrDefault($request->date_from, now()->startOfMonth());
        $dateTo = $this->dateOrDefault($request->date_to, now());

        if ($dateFrom > $dateTo) {
            return [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function dateOrDefault(?string $date, Carbon $default): string
    {
        if (!$date) {
            return $default->toDateString();
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return $default->toDateString();
        }
    }
}
