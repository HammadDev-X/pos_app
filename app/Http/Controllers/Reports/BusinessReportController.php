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

        $sales = $orders->sum(fn (Order $order): float => $order->total());
        $received = $orders->sum(fn (Order $order): float => $order->receivedAmount());
        $due = max(0, $sales - $received);
        $cost = $orders->sum(function (Order $order): float {
            return $order->items->sum(function ($item): float {
                return ((float) ($item->product?->purchase_price ?? 0)) * (int) $item->quantity;
            });
        });
        $expenses = (float) Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('amount');
        $grossProfit = $sales - $cost;
        $netProfit = $grossProfit - $expenses;

        $paymentBreakdown = $orders
            ->flatMap->payments
            ->groupBy('method')
            ->map(fn ($payments): float => (float) $payments->sum('amount'));

        $topProducts = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(price) as total_sales')
            ->with('product')
            ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(8)
            ->get();

        return view('reports.business', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sales' => $sales,
            'received' => $received,
            'due' => $due,
            'cost' => $cost,
            'expenses' => $expenses,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'ordersCount' => $orders->count(),
            'paymentBreakdown' => $paymentBreakdown,
            'topProducts' => $topProducts,
            'lowStockProducts' => Product::lowStock()->orderBy('quantity')->limit(10)->get(),
            'purchaseTotal' => Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])->sum('total_amount'),
        ]);
    }
}
