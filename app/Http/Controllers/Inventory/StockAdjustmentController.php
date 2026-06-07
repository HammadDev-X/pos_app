<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
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
        ]);
    }

    public function create(): View
    {
        return view('stock-adjustments.create', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:increase,decrease,set'],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $before = $product->quantity;

            $after = match ($data['type']) {
                'increase' => $before + $data['quantity'],
                'decrease' => max(0, $before - $data['quantity']),
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
        });

        return redirect()->route('stock-adjustments.index')
            ->with('success', __('Stock adjusted successfully.'));
    }
}
