<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $salesReturns = $orders->sum(fn (Order $order): float => $order->grossReturnedAmount());
        $discounts = $orders->sum(fn (Order $order): float => $order->discountAmount());
        $sales = $orders->sum(fn (Order $order): float => $order->total());
        $received = $orders->sum(fn (Order $order): float => $order->receivedAmount());
        $openingCustomerBalances = (float) Customer::sum('pending_amount');
        $due = $openingCustomerBalances + max(0, $sales - $received);
        $creditSales = $openingCustomerBalances + $orders->sum(fn (Order $order): float => max($order->remainingBalance(), 0));
        $cost = $orders->sum(fn (Order $order): float => $order->costOfGoodsSold());
        $expenses = $this->expenseTotal($dateFrom, $dateTo);
        $grossProfit = $sales - $cost;
        $netProfit = $grossProfit - $expenses;

        $paymentBreakdown = $orders
            ->flatMap->payments
            ->groupBy('method')
            ->map(fn ($payments): float => (float) $payments->sum('amount'));
        $cashSales = (float) ($paymentBreakdown->get('cash') ?? 0);
        $accountSales = (float) $paymentBreakdown
            ->reject(fn ($amount, string $method): bool => $method === 'cash')
            ->sum();
        $recoveryPayments = (float) $orders
            ->flatMap->payments
            ->filter(fn ($payment): bool => $payment->order && $payment->created_at->gt($payment->order->created_at))
            ->sum('amount');
        $creditSalesHistory = $orders
            ->filter(fn (Order $order): bool => $order->customer_id !== null && ($order->remainingBalance() > 0 || $order->receivedAmount() < $order->total()))
            ->map(fn (Order $order): array => [
                'order_id' => $order->id,
                'customer' => $order->getCustomerName(),
                'date' => $order->created_at?->toDateString(),
                'due_date' => $order->due_date?->toDateString(),
                'total' => $order->total(),
                'paid' => $order->receivedAmount(),
                'balance' => max($order->remainingBalance(), 0),
            ])
            ->values();
        $recoveryEntries = $orders
            ->flatMap(fn (Order $order) => $order->payments->map(fn ($payment): array => [
                'customer' => $order->getCustomerName(),
                'date' => $payment->created_at?->toDateString(),
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'order_id' => $order->id,
                'is_recovery' => $payment->created_at?->gt($order->created_at) ?? false,
            ]))
            ->filter(fn (array $row): bool => $row['is_recovery'])
            ->values();
        $recoveryAlerts = $orders
            ->filter(fn (Order $order): bool => $order->customer_id !== null && $order->remainingBalance() > 0)
            ->sortBy('due_date')
            ->map(fn (Order $order): array => [
                'order_id' => $order->id,
                'customer' => $order->getCustomerName(),
                'phone' => $order->customer?->phone,
                'due_date' => $order->due_date?->toDateString(),
                'balance' => max($order->remainingBalance(), 0),
                'is_overdue' => $order->due_date ? $order->due_date->isPast() : false,
            ])
            ->values();

        $topProducts = OrderItem::query()
            ->selectRaw('
                product_id,
                SUM(quantity - COALESCE(returned_quantity, 0)) as total_quantity,
                SUM((price - COALESCE(discount, 0)) * CASE WHEN quantity > 0 THEN (quantity - COALESCE(returned_quantity, 0)) / quantity ELSE 0 END) as total_sales
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
                SUM((price - COALESCE(discount, 0)) * CASE WHEN quantity > 0 THEN (quantity - COALESCE(returned_quantity, 0)) / quantity ELSE 0 END) as total_sales
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
        $profitOrders = Order::with(['items.product.category', 'payments', 'customer'])
            ->where('created_at', '>=', now()->startOfYear())
            ->get();
        $dailyReport = $this->periodReport(today(), today());
        $weeklyReport = $this->periodReport(now()->startOfWeek(), now()->endOfWeek());
        $monthlyReport = $this->periodReport(now()->startOfMonth(), now()->endOfMonth());
        $dailyProfit = $this->profitSummary(
            $profitOrders->filter(fn (Order $order): bool => $order->created_at->isToday()),
            today(),
            today()
        );
        $monthlyProfit = $this->profitSummary(
            $profitOrders->filter(fn (Order $order): bool => $order->created_at->isSameMonth(now())),
            now()->startOfMonth(),
            now()->endOfMonth()
        );
        $yearlyProfit = $this->profitSummary($profitOrders, now()->startOfYear(), now()->endOfYear());
        $productWiseProfit = $this->itemProfitRows($orders, 'product')->take(10);
        $categoryWiseProfit = $this->itemProfitRows($orders, 'category');
        $customerWiseProfit = $orders
            ->groupBy(fn (Order $order): string => $order->getCustomerName())
            ->map(fn (Collection $customerOrders, string $customer): array => [
                'customer' => $customer,
                'sales' => $customerOrders->sum(fn (Order $order): float => $order->total()),
                'cost' => $customerOrders->sum(fn (Order $order): float => $order->costOfGoodsSold()),
                'gross_profit' => $customerOrders->sum(fn (Order $order): float => $order->total() - $order->costOfGoodsSold()),
                'balance' => $customerOrders->sum(fn (Order $order): float => max($order->remainingBalance(), 0)),
            ])
            ->sortByDesc('gross_profit')
            ->values();
        $inventoryReport = $this->inventoryReport();

        return view('reports.business', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sales' => $sales,
            'totalSales' => $grossSales,
            'netSales' => $sales,
            'grossSales' => $grossSales,
            'salesReturns' => $salesReturns,
            'discounts' => $discounts,
            'received' => $received,
            'due' => $due,
            'cashSales' => $cashSales,
            'accountSales' => $accountSales,
            'creditSales' => $creditSales,
            'recoveryPayments' => $recoveryPayments,
            'creditSalesHistory' => $creditSalesHistory,
            'recoveryEntries' => $recoveryEntries,
            'recoveryAlerts' => $recoveryAlerts,
            'cost' => $cost,
            'expenses' => $expenses,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'dailyReport' => $dailyReport,
            'weeklyReport' => $weeklyReport,
            'monthlyReport' => $monthlyReport,
            'dailyGrossProfit' => $dailyProfit['gross_profit'],
            'dailyNetProfit' => $dailyProfit['net_profit'],
            'monthlyGrossProfit' => $monthlyProfit['gross_profit'],
            'monthlyNetProfit' => $monthlyProfit['net_profit'],
            'yearlyProfit' => $yearlyProfit,
            'productWiseProfit' => $productWiseProfit,
            'categoryWiseProfit' => $categoryWiseProfit,
            'customerWiseProfit' => $customerWiseProfit,
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
            'inventoryReport' => $inventoryReport,
            'availableStockItems' => $inventoryReport['available_stock'],
            'lowStockItems' => $inventoryReport['low_stock_items'],
            'outOfStockItems' => $inventoryReport['out_of_stock_items'],
            'expiringSoonItems' => $inventoryReport['expiring_soon_items'],
            'productStockValues' => $productStockValues->take(10),
            'categoryStockValues' => $categoryStockValues,
        ]);
    }

    private function periodReport(Carbon $dateFrom, Carbon $dateTo): array
    {
        $orders = Order::with(['items', 'payments'])
            ->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->get();
        $sales = $orders->sum(fn (Order $order): float => $order->total());
        $received = $orders->sum(fn (Order $order): float => $order->receivedAmount());
        $cost = $orders->sum(fn (Order $order): float => $order->costOfGoodsSold());
        $expenses = $this->expenseTotal($dateFrom->toDateString(), $dateTo->toDateString());
        $grossProfit = $sales - $cost;

        return [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'orders_count' => $orders->count(),
            'sales' => $sales,
            'received' => $received,
            'credit' => $orders->sum(fn (Order $order): float => max($order->remainingBalance(), 0)),
            'cost' => $cost,
            'expenses' => $expenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit - $expenses,
        ];
    }

    private function profitSummary(Collection $orders, Carbon $dateFrom, Carbon $dateTo): array
    {
        $sales = $orders->sum(fn (Order $order): float => $order->total());
        $cost = $orders->sum(fn (Order $order): float => $order->costOfGoodsSold());
        $expenses = $this->expenseTotal($dateFrom->toDateString(), $dateTo->toDateString());
        $grossProfit = $sales - $cost;

        return [
            'sales' => $sales,
            'cost' => $cost,
            'expenses' => $expenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit - $expenses,
        ];
    }

    private function itemProfitRows(Collection $orders, string $groupBy): Collection
    {
        return $orders
            ->flatMap->items
            ->map(function (OrderItem $item) use ($groupBy): array {
                $label = match ($groupBy) {
                    'category' => $item->product?->category?->name ?? 'Uncategorized',
                    default => $item->product?->name ?? $item->custom_item_name ?? 'Open Item',
                };

                $sales = $item->netSales();
                $cost = $item->netCost();

                return [
                    'label' => $label,
                    'sales' => $sales,
                    'cost' => $cost,
                    'gross_profit' => $sales - $cost,
                ];
            })
            ->groupBy('label')
            ->map(fn (Collection $rows, string $label): array => [
                'label' => $label,
                'sales' => $rows->sum('sales'),
                'cost' => $rows->sum('cost'),
                'gross_profit' => $rows->sum('gross_profit'),
            ])
            ->sortByDesc('gross_profit')
            ->values();
    }

    private function expenseTotal(string $dateFrom, string $dateTo): float
    {
        return (float) Expense::query()
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->sum('amount');
    }

    private function inventoryReport(): array
    {
        $warningQuantity = (float) config('settings.warning_quantity', 10);
        $products = Product::with('category')
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();
        $expiringSoonItems = PurchaseItem::with(['product.category', 'purchase'])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today()->toDateString(), today()->addDays(30)->toDateString()])
            ->whereHas('product', fn ($query) => $query->where('track_stock', true)->where('quantity', '>', 0))
            ->orderBy('expiry_date')
            ->get();

        return [
            'available_stock' => $products
                ->filter(fn (Product $product): bool => (float) $product->quantity > 0)
                ->values(),
            'low_stock_items' => $products
                ->filter(fn (Product $product): bool => (float) $product->quantity > 0 && (float) $product->quantity <= $warningQuantity)
                ->values(),
            'out_of_stock_items' => $products
                ->filter(fn (Product $product): bool => (float) $product->quantity <= 0)
                ->values(),
            'expiring_soon_items' => $expiringSoonItems,
            'available_stock_count' => $products->filter(fn (Product $product): bool => (float) $product->quantity > 0)->count(),
            'low_stock_count' => $products->filter(fn (Product $product): bool => (float) $product->quantity > 0 && (float) $product->quantity <= $warningQuantity)->count(),
            'out_of_stock_count' => $products->filter(fn (Product $product): bool => (float) $product->quantity <= 0)->count(),
            'expiring_soon_count' => $expiringSoonItems->count(),
            'stock_quantity' => $products->sum(fn (Product $product): float => (float) $product->quantity),
            'stock_cost_value' => $products->sum(fn (Product $product): float => (float) $product->quantity * (float) $product->purchase_price),
            'stock_selling_value' => $products->sum(fn (Product $product): float => (float) $product->quantity * (float) $product->price),
        ];
    }
}
