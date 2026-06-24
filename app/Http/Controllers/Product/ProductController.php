<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): View
    {
        $products = Product::withCount('invoiceItems')
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->orderBy('name')
            ->paginate(15);

        return view('product.index', compact('products'));
    }

    public function create(Request $request): View
    {
        return view('product.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create($request);
        return redirect()->route('products.index')->with('success', 'Producto/Servicio creado correctamente');
    }

    public function show(Request $request, Product $product): View
    {
        $product->load('invoiceItems');
        return view('product.show', compact('product'));
    }

    public function edit(Request $request, Product $product): View
    {
        return view('product.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request);
        return redirect()->route('products.index')->with('success', 'Producto/Servicio actualizado correctamente');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($this->productService->delete($product)) {
            return redirect()->route('products.index')->with('success', 'Producto/Servicio eliminado correctamente');
        }

        return redirect()->route('products.index')->with('error', 'No se puede eliminar porque está asociado a facturas');
    }
}
