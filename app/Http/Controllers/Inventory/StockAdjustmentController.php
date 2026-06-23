<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    private const TYPES = [
        'stock_in' => 'Stock in',
        'stock_out' => 'Stock out',
        'customer_return' => 'Customer return',
        'disposed' => 'Disposed stock',
        'increase' => 'Manual stock increase',
        'decrease' => 'Manual stock decrease',
        'set' => 'Set exact stock',
        'damage' => 'Damage / wastage',
        'expired_disposal' => 'Expired product disposal',
    ];

    public function index(Request $request): View
    {
        $adjustments = StockAdjustment::with(['product', 'user'])
            ->when($request->product_id, fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('stock-adjustments.index', [
            'adjustments' => $adjustments,
            'products' => Product::orderBy('name')->get(),
            'types' => self::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('stock-adjustments.create', [
            'products' => Product::orderBy('name')->get(),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $before = $product->quantity;

            $after = match ($data['type']) {
                'increase', 'stock_in', 'customer_return' => $before + $data['quantity'],
                'decrease', 'stock_out', 'damage', 'disposed', 'expired_disposal' => max(0, $before - $data['quantity']),
                default => $data['quantity'],
            };

            $product->update(['quantity' => $after]);

            StockAdjustment::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $data['reason'],
            ]);

            AuditLog::record('stock.adjusted', $product, [
                'type' => $data['type'],
                'quantity' => (float) $data['quantity'],
                'before' => (float) $before,
                'after' => (float) $after,
                'reason' => $data['reason'],
            ]);
        });

        return redirect()->route('stock-adjustments.index')
            ->with('success', __('Stock adjusted successfully.'));
    }
}
