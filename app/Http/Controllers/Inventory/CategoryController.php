<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Category::query()
            ->withCount('products')
            ->when($request->search, function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->boolean('active'), fn ($query) => $query->where('status', true));

        if ($request->wantsJson()) {
            return response()->json($query->orderBy('name')->get());
        }

        $categories = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')
            ->with('success', __('Category created successfully.'));
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', __('Category deleted successfully.'));
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $categoryId = $category?->id ?? 'NULL';

        return $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:categories,name,{$categoryId}"],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ]);
    }
}
