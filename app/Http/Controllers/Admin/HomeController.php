<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function __invoke(): Factory|View|\Illuminate\View\View
    {
        $orders = Order::with(['items.product', 'payments'])->get();
        $todayOrders = $orders->where('created_at', '>=', today());
        $todaySales = $todayOrders->sum(fn($order): float => min($order->receivedAmount(), $order->total()));
        $todayCost = $todayOrders->sum(function (Order $order): float {
            return $order->items->sum(fn ($item): float => ((float) ($item->product?->purchase_price ?? 0)) * (int) $item->quantity);
        });
        $todayExpenses = (float) Expense::whereDate('expense_date', today())->sum('amount');

        return view('home', [
            'orders_count' => $orders->count(),
            'income' => $orders->sum(fn($order): float => min($order->receivedAmount(), $order->total())),
            'income_today' => $todaySales,
            'expenses_today' => $todayExpenses,
            'net_profit_today' => $todaySales - $todayCost - $todayExpenses,
            'customers_count' => Customer::count(),
            'products_count' => Product::count(),
            'active_products_count' => Product::active()->count(),
            'low_stock_count' => Product::lowStock()->count(),
            'out_of_stock_count' => Product::where('quantity', '<=', 0)->count(),
            'unpaid_orders_count' => $orders
                ->filter(fn(Order $order): bool => $order->remainingBalance() > 0)
                ->count(),
            'latest_orders' => Order::with(['customer', 'items', 'payments'])->latest()->limit(6)->get(),
            'low_stock_products' => Product::lowStock()->latest()->limit(8)->get(),
            'best_selling_products' => Product::bestSelling()->get(),
            'current_month_products' => Product::currentMonthBestSelling()->get(),
            'past_months_products' => Product::pastMonthsHotProducts()->get(),
        ]);
    }
}
