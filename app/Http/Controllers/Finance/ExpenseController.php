<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    private const CATEGORIES = [
        'Petrol / Fuel',
        'Packaging',
        'Delivery',
        'Salary',
        'Rent',
        'Repairs & Maintenance',
        'Marketing',
        'Other Expenses',
    ];

    public function index(Request $request): View
    {
        $expenseQuery = Expense::with('user')
            ->when($request->category, fn ($query, $category) => $query->where('category', $category))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('expense_date', '<=', $date));

        $expenses = (clone $expenseQuery)
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $allMatchingExpenses = (clone $expenseQuery)->get();
        $dailyExpense = (float) Expense::whereDate('expense_date', today())->sum('amount');
        $monthlyExpense = (float) Expense::whereBetween('expense_date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ])->sum('amount');
        $categoryWiseExpense = $allMatchingExpenses
            ->groupBy('category')
            ->map(fn ($rows, string $category): array => [
                'category' => $category,
                'amount' => (float) $rows->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->values();
        $expenseComparisonByMonth = Expense::query()
            ->where('expense_date', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->get()
            ->groupBy(fn (Expense $expense): string => $expense->expense_date->format('Y-m'))
            ->map(fn ($rows, string $month): array => [
                'month' => $month,
                'label' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                'amount' => (float) $rows->sum('amount'),
            ])
            ->sortBy('month')
            ->values();
        $expenseVsSalesComparison = $expenseComparisonByMonth
            ->map(function (array $row): array {
                $start = Carbon::createFromFormat('Y-m', $row['month'])->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $sales = Order::with(['items', 'payments'])
                    ->whereBetween('created_at', [$start, $end])
                    ->get()
                    ->sum(fn (Order $order): float => $order->total());

                return $row + [
                    'sales' => (float) $sales,
                    'difference' => (float) $sales - (float) $row['amount'],
                ];
            });

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => collect(self::CATEGORIES)
                ->merge(Expense::query()->distinct()->orderBy('category')->pluck('category'))
                ->unique()
                ->values(),
            'total' => (clone $expenseQuery)->sum('amount'),
            'dailyExpense' => $dailyExpense,
            'monthlyExpense' => $monthlyExpense,
            'categoryWiseExpense' => $categoryWiseExpense,
            'expenseComparisonByMonth' => $expenseComparisonByMonth,
            'expenseVsSalesComparison' => $expenseVsSalesComparison,
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $expense = Expense::create($data + ['user_id' => $request->user()->id]);
        AuditLog::record('expense.created', $expense, [
            'category' => $expense->category,
            'amount' => (float) $expense->amount,
        ]);

        return redirect()->route('expenses.index')
            ->with('success', __('Expense recorded successfully.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        AuditLog::record('expense.deleted', $expense, [
            'category' => $expense->category,
            'amount' => (float) $expense->amount,
        ]);
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', __('Expense deleted successfully.'));
    }
}
