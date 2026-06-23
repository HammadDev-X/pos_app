<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

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
        $todayCost = $todayOrders->sum(fn (Order $order): float => $order->costOfGoodsSold());
        $todayExpenses = (float) Expense::whereDate('expense_date', today())->sum('amount');
        $monthOrders = $orders->where('created_at', '>=', now()->startOfMonth());
        $monthSales = $monthOrders->sum(fn (Order $order): float => $order->total());
        $cashSales = $orders->sum(fn (Order $order): float => (float) $order->payments->where('method', 'cash')->sum('amount'));
        $creditSales = $orders->sum(fn (Order $order): float => max($order->remainingBalance(), 0));
        $recoveryPayments = $orders
            ->flatMap->payments
            ->filter(fn ($payment): bool => $payment->order && $payment->created_at->gt($payment->order->created_at))
            ->sum('amount');
        $monthlyCalendar = $this->monthlySalesCalendar($orders);
        $expenseBreakdown = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('home', [
            'orders_count' => $orders->count(),
            'income' => $orders->sum(fn($order): float => min($order->receivedAmount(), $order->total())),
            'income_today' => $todaySales,
            'sales_this_month' => $monthSales,
            'cash_sales' => $cashSales,
            'credit_sales' => $creditSales,
            'recovery_payments' => $recoveryPayments,
            'expenses_today' => $todayExpenses,
            'net_profit_today' => $todaySales - $todayCost - $todayExpenses,
            'customers_count' => Customer::count(),
            'active_customers_count' => Customer::has('orders')->count(),
            'customers_with_balance_count' => Customer::whereHas('orders')->get()
                ->filter(fn (Customer $customer): bool => $customer->orders->sum(fn (Order $order): float => max($order->remainingBalance(), 0)) > 0)
                ->count(),
            'total_receivable' => $creditSales,
            'total_expenses' => (float) Expense::sum('amount'),
            'expense_breakdown' => $expenseBreakdown,
            'monthly_calendar' => $monthlyCalendar,
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

    private function monthlySalesCalendar(Collection $orders): array
    {
        $days = [];
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[] = [
                'date' => $day->toDateString(),
                'day' => $day->format('d'),
                'sales' => $orders
                    ->filter(fn (Order $order): bool => $order->created_at->isSameDay($day))
                    ->sum(fn (Order $order): float => $order->total()),
            ];
        }

        return $days;
    }
}
