<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|View|JsonResponse
    {
        $search = trim((string) $request->input('search'));

        $query = Customer::query()
            ->when($request->user()?->isSalesman(), function ($query): void {
                $query->where('is_visible_to_salesman', true);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            });

        if ($request->wantsJson() && ! $request->boolean('table')) {
            $customers = $query->with(['orders.items', 'orders.payments'])->latest()->get();
            $customers->each(function (Customer $customer): void {
                $customer->setAttribute('total_pending_balance', $customer->totalPendingBalance());
            });

            return response($customers);
        }

        $canManageCustomers = $request->user()?->isManager();
        $customers = $query->latest()->paginate(10)->withQueryString();
        $customers->load(['orders.items', 'orders.payments']);
        $customers->getCollection()->each(function (Customer $customer): void {
            $customer->setAttribute('total_pending_balance', $customer->totalPendingBalance());
        });

        if ($request->boolean('table')) {
            return response()->json([
                'html' => view('customers._table_rows', [
                    'customers' => $customers,
                    'canManageCustomers' => $canManageCustomers,
                ])->render(),
                'pagination' => $customers->render()->toHtml(),
            ]);
        }

        return view('customers.index', [
            'customers' => $customers,
            'canManageCustomers' => $canManageCustomers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View|Factory
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(CustomerStoreRequest $request)
    {
        $customerData = $request->validated();
        $customerData = $this->normalizeCustomerData($customerData);
        $customerData['user_id'] = $request->user()->id;

        Customer::create($customerData);

        return redirect()->route('customers.index')
            ->with('success', __('customer.success_creating'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): Factory|View
    {
        return view('customers.edit', ['customer' => $customer]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => fn ($query) => $query->with(['items.product', 'payments'])->latest()]);

        $orders = $customer->orders;
        $openingPayments = AuditLog::query()
            ->where('action', 'customer.opening_payment')
            ->where('auditable_type', Customer::class)
            ->where('auditable_id', $customer->id)
            ->latest()
            ->get()
            ->filter(fn (AuditLog $log): bool => (float) ($log->properties['amount'] ?? 0) > 0)
            ->values();
        $totalSales = $orders->sum(fn ($order): float => $order->total());
        $openingRecovered = $openingPayments->sum(fn (AuditLog $log): float => (float) ($log->properties['amount'] ?? 0));
        $totalPaid = $orders->sum(fn ($order): float => $order->receivedAmount()) + $openingRecovered;
        $openingBalance = (float) $customer->pending_amount;
        $balance = $openingBalance + $orders->sum(fn ($order): float => max($order->remainingBalance(), 0));

        return view('customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'openingBalance' => $openingBalance,
            'openingRecovered' => $openingRecovered,
            'openingPayments' => $openingPayments,
            'totalSales' => $totalSales,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(CustomerUpdateRequest $request, Customer $customer)
    {
        $customerData = $request->validated();
        $customerData = $this->normalizeCustomerData($customerData);

        $customer->update($customerData);

        return redirect()->route('customers.index')
            ->with('success', __('customer.success_updating'));
    }

    public function toggleSalesmanVisibility(Customer $customer): RedirectResponse
    {
        $customer->update([
            'is_visible_to_salesman' => ! $customer->is_visible_to_salesman,
        ]);

        return redirect()->route('customers.index', request()->query())
            ->with('success', __('Customer salesman visibility updated.'));
    }

    public function receiveOpeningPayment(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'jazzcash', 'easypaisa'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($customer, $validated): void {
                $lockedCustomer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
                $currentPending = round((float) $lockedCustomer->pending_amount, 2);
                $amount = round((float) $validated['amount'], 2);
                $orders = $lockedCustomer->orders()
                    ->where('status', '!=', 'cancelled')
                    ->oldest()
                    ->lockForUpdate()
                    ->get();
                $orders->load(['items', 'payments']);
                $orderPending = round((float) $orders->sum(fn (Order $order): float => max($order->remainingBalance(), 0)), 2);
                $totalPending = round($currentPending + $orderPending, 2);

                if ($totalPending <= 0) {
                    throw new \Exception('This customer has no pending amount to receive.');
                }

                if ($amount > $totalPending + 0.00001) {
                    throw new \Exception('Payment cannot be greater than the customer total pending amount.');
                }

                $remainingAmount = $amount;
                $openingApplied = min($remainingAmount, $currentPending);

                if ($openingApplied > 0) {
                    $lockedCustomer->update([
                        'pending_amount' => max(round($currentPending - $openingApplied, 2), 0),
                    ]);
                    $remainingAmount = round($remainingAmount - $openingApplied, 2);
                }

                $invoiceAllocations = [];

                foreach ($orders as $order) {
                    if ($remainingAmount <= 0) {
                        break;
                    }

                    $orderRemaining = max(round($order->remainingBalance(), 2), 0);

                    if ($orderRemaining <= 0) {
                        continue;
                    }

                    $invoiceApplied = min($remainingAmount, $orderRemaining);
                    $order->payments()->create([
                        'amount' => $invoiceApplied,
                        'method' => $validated['payment_method'],
                        'user_id' => auth()->id(),
                    ]);

                    AuditLog::record('payment.received', $order, [
                        'amount' => $invoiceApplied,
                        'payment_method' => $validated['payment_method'],
                        'source' => 'customer_balance_payment',
                    ]);

                    $invoiceAllocations[] = [
                        'order_id' => $order->id,
                        'amount' => $invoiceApplied,
                    ];
                    $remainingAmount = round($remainingAmount - $invoiceApplied, 2);
                }

                AuditLog::record('customer.opening_payment', $lockedCustomer, [
                    'amount' => $openingApplied,
                    'total_amount' => $amount,
                    'invoice_amount' => round($amount - $openingApplied, 2),
                    'invoice_allocations' => $invoiceAllocations,
                    'payment_method' => $validated['payment_method'],
                    'note' => $validated['note'] ?? null,
                    'previous_pending_amount' => $currentPending,
                    'remaining_pending_amount' => (float) $lockedCustomer->pending_amount,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->getMessage());
        }

        return redirect()->back()
            ->with('success', config('settings.currency_symbol') . number_format((float) $validated['amount'], 2) . ' received from customer balance.');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'success' => true
        ]);
    }

    private function normalizeCustomerData(array $customerData): array
    {
        if (array_key_exists('phone', $customerData) && filled($customerData['phone'])) {
            $customerData['phone'] = '+92' . preg_replace('/\D+/', '', (string) $customerData['phone']);
        }

        return $customerData;
    }
}
