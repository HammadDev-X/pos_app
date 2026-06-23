<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
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

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => Expense::query()->distinct()->orderBy('category')->pluck('category'),
            'total' => (clone $expenseQuery)->sum('amount'),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'categories' => ['Rent', 'Salary', 'Electricity', 'Transport', 'Repair', 'Marketing', 'Other'],
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
