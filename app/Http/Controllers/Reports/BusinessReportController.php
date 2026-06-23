<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessReportController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->date_from ?: now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?: now()->toDateString();

        $orders = Order::with(['items.product', 'payments'])
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $grossSales = $orders->sum(fn (Order $order): float => $order->grossTotal());
        $salesReturns = $orders->sum(fn (Order $order): float => $order->returnedAmount());
        $discounts = $orders->sum(fn (Order $order): float => $order->discountAmount());
        $sales = $orders->sum(fn (Order $order): float => $order->total());
        $received = $orders->sum(fn (Order $order): float => $order->receivedAmount());
        $due = max(0, $sales - $received);
        $cost = $orders->sum(fn (Order $order): float => $order->costOfGoodsSold());
        $expenses = (float) Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('amount');
        $grossProfit = $sales - $cost;
        $netProfit = $grossProfit - $expenses;

        $paymentBreakdown = $orders
            ->flatMap->payments
            ->groupBy('method')
            ->map(fn ($payments): float => (float) $payments->sum('amount'));

        $topProducts = OrderItem::query()
            ->selectRaw('
                product_id,
                SUM(quantity - COALESCE(returned_quantity, 0)) as total_quantity,
                SUM(price - CASE WHEN quantity > 0 THEN (price / quantity) * COALESCE(returned_quantity, 0) ELSE 0 END) as total_sales
            ')
            ->with('product')
            ->whereNotNull('product_id')
            ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']))
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity - COALESCE(returned_quantity, 0)) > 0')
            ->orderByDesc('total_quantity')
            ->limit(8)
            ->get();

        $openItems = OrderItem::query()
            ->selectRaw('
                custom_item_name,
                SUM(quantity - COALESCE(returned_quantity, 0)) as total_quantity,
                SUM(price - CASE WHEN quantity > 0 THEN (price / quantity) * COALESCE(returned_quantity, 0) ELSE 0 END) as total_sales
            ')
            ->whereNull('product_id')
            ->whereNotNull('custom_item_name')
            ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']))
            ->groupBy('custom_item_name')
            ->havingRaw('SUM(quantity - COALESCE(returned_quantity, 0)) > 0')
            ->orderByDesc('total_sales')
            ->limit(8)
            ->get();

        $inventoryProducts = Product::with('category')
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        $inventoryQuantity = $inventoryProducts->sum(fn (Product $product): float => (float) $product->quantity);
        $inventoryCostValue = $inventoryProducts->sum(fn (Product $product): float => (float) $product->quantity * (float) $product->purchase_price);
        $inventorySellingValue = $inventoryProducts->sum(fn (Product $product): float => (float) $product->quantity * (float) $product->price);
        $inventoryEstimatedMargin = $inventorySellingValue - $inventoryCostValue;
        $productStockValues = $inventoryProducts
            ->map(fn (Product $product): array => [
                'name' => $product->name,
                'category' => $product->category?->name ?? 'Uncategorized',
                'quantity' => (float) $product->quantity,
                'cost_value' => (float) $product->quantity * (float) $product->purchase_price,
                'selling_value' => (float) $product->quantity * (float) $product->price,
                'estimated_margin' => ((float) $product->quantity * (float) $product->price) - ((float) $product->quantity * (float) $product->purchase_price),
            ])
            ->sortByDesc('cost_value')
            ->values();
        $categoryStockValues = $productStockValues
            ->groupBy('category')
            ->map(fn ($rows, string $category): array => [
                'category' => $category,
                'quantity' => $rows->sum('quantity'),
                'cost_value' => $rows->sum('cost_value'),
                'selling_value' => $rows->sum('selling_value'),
                'estimated_margin' => $rows->sum('estimated_margin'),
            ])
            ->sortByDesc('cost_value')
            ->values();

        return view('reports.business', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sales' => $sales,
            'grossSales' => $grossSales,
            'salesReturns' => $salesReturns,
            'discounts' => $discounts,
            'received' => $received,
            'due' => $due,
            'cost' => $cost,
            'expenses' => $expenses,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'ordersCount' => $orders->count(),
            'paymentBreakdown' => $paymentBreakdown,
            'topProducts' => $topProducts,
            'openItems' => $openItems,
            'lowStockProducts' => Product::lowStock()->orderBy('quantity')->limit(10)->get(),
            'purchaseTotal' => Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])->sum('total_amount'),
            'inventoryQuantity' => $inventoryQuantity,
            'inventoryCostValue' => $inventoryCostValue,
            'inventorySellingValue' => $inventorySellingValue,
            'inventoryEstimatedMargin' => $inventoryEstimatedMargin,
            'productStockValues' => $productStockValues->take(10),
            'categoryStockValues' => $categoryStockValues,
        ]);
    }
}
