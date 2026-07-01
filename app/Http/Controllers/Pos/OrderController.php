<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Http\Requests\Order\PartialPaymentRequest;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $orders = Order::query()
            ->with(['items.product', 'payments', 'customer'])
            ->when($request->input('start_date'), function ($query, $startDate): void {
                $query->where('created_at', '>=', $startDate);
            })
            ->when($request->input('end_date'), function ($query, string $endDate): void {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            })
            ->latest()
            ->paginate(10);

        $total = $orders->sum(fn($order) => $order->total());
        $receivedAmount = $orders->sum(fn($order) => $order->receivedAmount());

        return view('orders.index', ['orders' => $orders, 'total' => $total, 'receivedAmount' => $receivedAmount]);
    }

    public function store(OrderStoreRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $order = DB::transaction(function () use ($request) {
                // Create order
                $order = Order::create([
                    'customer_id' => $request->customer_id,
                    'user_id' => $request->user()->id,
                    'discount' => 0,
                    'due_date' => $request->input('due_date'),
                    'status' => 'active',
                ]);

                // Get cart items
                $cartItems = $request->user()->cart()->get();
                $customItems = collect($request->input('custom_items', []))
                    ->filter(fn (array $item): bool => filled($item['name'] ?? null));

                if ($cartItems->isEmpty() && $customItems->isEmpty()) {
                    throw new \Exception(__('cart.empty'));
                }

                // Create order items and update product quantities
                foreach ($cartItems as $item) {
                    $this->createOrderItem($order, $item, (float) $request->input("item_discounts.{$item->id}", 0));
                    $this->reduceProductStock($item);
                }

                foreach ($customItems as $item) {
                    $this->createCustomOrderItem($order, $item);
                }

                // Clear cart
                $request->user()->cart()->detach();

                $payments = $this->paymentsFromRequest($request);
                $order->load('items');
                $orderTotal = $order->total();
                $isLoanSale = $request->input('payment_method') === 'loan';
                $paymentTotal = round($payments->sum('amount'), 2);
                $accountTotal = round($payments
                    ->whereIn('method', Payment::ACCOUNT_METHODS)
                    ->sum('amount'), 2);

                if ($isLoanSale && !$order->customer_id) {
                    throw new \Exception('Select a customer before creating a loan sale.');
                }

                if ($accountTotal > 0 && !$order->customer_id) {
                    throw new \Exception('Select a customer before putting any amount on account.');
                }

                if (!$isLoanSale && $paymentTotal <= 0) {
                    throw new \Exception(__('order.validation.amount_required'));
                }

                if ($paymentTotal > $orderTotal + 0.00001) {
                    throw new \Exception(__('order.amount_exceeds_balance'));
                }

                foreach ($payments as $payment) {
                    $order->payments()->create([
                        'amount' => $payment['amount'],
                        'method' => $payment['method'],
                        'user_id' => $request->user()->id,
                    ]);
                }

                $order->load('payments');

                AuditLog::record('order.created', $order, [
                    'total' => $orderTotal,
                    'paid' => $order->receivedAmount(),
                    'account' => $order->accountAmount(),
                    'payment_methods' => $payments->pluck('method')->unique()->values()->all(),
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => __('order.created_successfully'),
                'order_id' => $order->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(Order $order): \Illuminate\Contracts\View\View
    {
        $order->load(['customer', 'items.product', 'payments.user']);

        return view('orders.receipt', ['order' => $order]);
    }

    public function pdf(Order $order)
    {
        $order->load(['customer', 'items.product', 'payments.user']);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('orders.receipt-pdf', ['order' => $order]);
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream("invoice-{$order->id}.pdf");
    }

    public function partialPayment(PartialPaymentRequest $request)
    {
        $receivedAmount = 0.0;

        try {
            DB::transaction(function () use ($request, &$receivedAmount): void {
                $order = Order::whereKey($request->input('order_id'))
                    ->lockForUpdate()
                    ->firstOrFail();
                $order->load(['items', 'payments']);
                $remainingAmount = max(round($order->total() - $order->receivedAmount(), 2), 0);
                $amount = round((float) $request->amount, 2);

                if ($amount > $remainingAmount + 0.00001) {
                    throw new \Exception(__('order.amount_exceeds_balance'));
                }

                if ($remainingAmount <= 0) {
                    throw new \Exception('This order is already fully paid.');
                }

                $receivedAmount = min($amount, $remainingAmount);

                $order->payments()->create([
                    'amount' => $receivedAmount,
                    'method' => $request->payment_method,
                    'user_id' => auth()->id(),
                ]);

                AuditLog::record('payment.received', $order, [
                    'amount' => $receivedAmount,
                    'payment_method' => $request->payment_method,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('orders.index')
                ->withErrors($e->getMessage());
        }

        return redirect()->route('orders.index')
            ->with('success', __('order.partial_payment_success', [
                'amount' => config('settings.currency_symbol') . number_format($receivedAmount, 2)
            ]));
    }

    /**
     * Create an order item from cart item.
     */
    private function createOrderItem(Order $order, $item, float $discount): void
    {
        $lineTotal = (float) $item->price * (float) $item->pivot->quantity;

        $order->items()->create([
            'price' => $lineTotal,
            'discount' => min(max($discount, 0), $lineTotal),
            'quantity' => $item->pivot->quantity,
            'unit_cost' => $item->purchase_price ?? 0,
            'product_id' => $item->id,
        ]);
    }

    private function paymentsFromRequest(OrderStoreRequest $request): \Illuminate\Support\Collection
    {
        if ($request->input('payment_method') === 'loan') {
            return collect();
        }

        $payments = collect($request->input('payments', []))
            ->map(fn (array $payment): array => [
                'method' => $payment['method'] ?? 'cash',
                'amount' => round((float) ($payment['amount'] ?? 0), 2),
            ])
            ->filter(fn (array $payment): bool => $payment['amount'] > 0)
            ->values();

        if ($payments->isNotEmpty()) {
            return $payments;
        }

        return collect([[
            'method' => $request->input('payment_method', 'cash'),
            'amount' => round((float) $request->input('amount', 0), 2),
        ]]);
    }

    private function createCustomOrderItem(Order $order, array $item): void
    {
        $quantity = (int) $item['quantity'];
        $unitPrice = (float) $item['price'];
        $lineTotal = $unitPrice * $quantity;
        $discount = min(max((float) ($item['discount'] ?? 0), 0), $lineTotal);

        $order->items()->create([
            'custom_item_name' => $item['name'],
            'price' => $lineTotal,
            'discount' => $discount,
            'quantity' => $quantity,
            'unit_cost' => 0,
            'product_id' => null,
        ]);
    }

    /**
     * Reduce product stock based on cart quantity.
     */
    private function reduceProductStock($item): void
    {
        $product = Product::whereKey($item->id)->lockForUpdate()->firstOrFail();
        $quantity = (float) $item->pivot->quantity;

        if (!$product->track_stock) {
            return;
        }

        if ((float) $product->quantity < $quantity) {
            throw new \Exception(__('cart.available', ['quantity' => $product->quantity]));
        }

        $product->decrement('quantity', $quantity);
    }

    public function returnForm(Order $order): View
    {
        $order->load(['customer', 'items.product', 'payments', 'returns.items']);

        return view('orders.return', ['order' => $order]);
    }

    public function processReturn(Request $request, Order $order): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:partial,full'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($order, $data, $request): void {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $order->load('items.product');

                $return = $order->returns()->create([
                    'user_id' => $request->user()->id,
                    'type' => $data['type'],
                    'reason' => $data['reason'],
                    'total_amount' => 0,
                ]);

                $totalReturnAmount = 0;

                foreach ($order->items as $item) {
                    $requestedQuantity = $data['type'] === 'full'
                        ? $item->returnableQuantity()
                        : (float) ($data['items'][$item->id] ?? 0);

                    if ($requestedQuantity <= 0) {
                        continue;
                    }

                    if ($requestedQuantity > $item->returnableQuantity()) {
                        throw new \Exception("Return quantity is higher than sold quantity for {$this->itemName($item)}.");
                    }

                    $unitPrice = $item->netUnitPrice();
                    $lineTotal = $unitPrice * $requestedQuantity;

                    $return->items()->create([
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $requestedQuantity,
                        'unit_price' => $unitPrice,
                        'unit_cost' => $item->unit_cost,
                        'total_amount' => $lineTotal,
                    ]);

                    $item->increment('returned_quantity', $requestedQuantity);

                    if ($item->product_id && $item->product?->track_stock) {
                        Product::whereKey($item->product_id)->lockForUpdate()->increment('quantity', $requestedQuantity);
                    }

                    $totalReturnAmount += $lineTotal;
                }

                if ($totalReturnAmount <= 0) {
                    throw new \Exception('Select at least one item quantity to return.');
                }

                $return->update(['total_amount' => $totalReturnAmount]);

                $order->refresh()->load('items');
                $order->update([
                    'status' => $order->items->sum(fn (OrderItem $item): float => $item->returnableQuantity()) <= 0
                        ? 'cancelled'
                        : 'active',
                ]);

                AuditLog::record('order.returned', $order, [
                    'return_id' => $return->id,
                    'type' => $return->type,
                    'reason' => $return->reason,
                    'amount' => $totalReturnAmount,
                ]);
            });

            return redirect()->route('orders.index')
                ->with('success', __('Return processed successfully. Stock, customer balance, sales, and profit reports were adjusted.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    private function itemName(OrderItem $item): string
    {
        return $item->product?->name ?? $item->custom_item_name ?? 'item';
    }
}
