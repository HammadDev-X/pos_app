<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|View
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

        if ($request->wantsJson()) {
            return response(
                $query->latest()->get()
            );
        }

        $customers = $query->latest()->paginate(10)->withQueryString();
        $customers->load(['orders.items', 'orders.payments']);
        $customers->getCollection()->each(function (Customer $customer): void {
            $orderBalance = $customer->orders->sum(fn (object $order): float => max($order->remainingBalance(), 0));
            $customer->setAttribute('total_pending_balance', (float) $customer->pending_amount + $orderBalance);
        });

        return view('customers.index')->with('customers', $customers);
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
        $totalSales = $orders->sum(fn ($order): float => $order->total());
        $totalPaid = $orders->sum(fn ($order): float => $order->receivedAmount());
        $openingBalance = (float) $customer->pending_amount;
        $balance = $openingBalance + $orders->sum(fn ($order): float => max($order->remainingBalance(), 0));

        return view('customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'openingBalance' => $openingBalance,
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
