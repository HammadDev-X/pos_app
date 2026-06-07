<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function index()
    {
        return view('reports.exports');
    }

    public function export(string $type): StreamedResponse
    {
        [$filename, $headers, $rows] = match ($type) {
            'products' => ['products.csv', ['ID', 'Name', 'Barcode', 'Category', 'Price', 'Purchase Price', 'Quantity', 'Status'], Product::with('category')->get()->map(fn ($p): array => [$p->id, $p->name, $p->barcode, $p->category?->name, $p->price, $p->purchase_price, $p->quantity, $p->status ? 'Active' : 'Inactive'])],
            'customers' => ['customers.csv', ['ID', 'Name', 'Email', 'Phone', 'Address'], Customer::all()->map(fn ($c): array => [$c->id, $c->full_name, $c->email, $c->phone, $c->address])],
            'suppliers' => ['suppliers.csv', ['ID', 'Name', 'Email', 'Phone', 'Address'], Supplier::all()->map(fn ($s): array => [$s->id, $s->full_name, $s->email, $s->phone, $s->address])],
            'orders' => ['orders.csv', ['ID', 'Customer', 'Total', 'Received', 'Balance', 'Created At'], Order::with(['customer', 'items', 'payments'])->get()->map(fn ($o): array => [$o->id, $o->getCustomerName(), $o->total(), $o->receivedAmount(), $o->remainingBalance(), $o->created_at])],
            'purchases' => ['purchases.csv', ['ID', 'Supplier', 'Total', 'Status', 'Date'], Purchase::with('supplier')->get()->map(fn ($p): array => [$p->id, $p->supplier->full_name, $p->total_amount, $p->status, $p->purchase_date])],
            'expenses' => ['expenses.csv', ['ID', 'Date', 'Category', 'Amount', 'Description'], Expense::all()->map(fn ($e): array => [$e->id, $e->expense_date?->toDateString(), $e->category, $e->amount, $e->description])],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
