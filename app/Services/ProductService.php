<?php

namespace App\Services;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(StoreProductRequest $request): Product
    {
        $tenantId = auth()->user()->tenant_id;
        $product = DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();
            $data['tenant_id'] = $tenantId;
            return Product::create($data);
        });

        Cache::forget("tenant:{$tenantId}:active_products");
        Cache::forget("tenant:{$tenantId}:products:type:{$product->type}");

        return $product;
    }

    public function update(Product $product, UpdateProductRequest $request): Product
    {
        $tenantId = $product->tenant_id;
        $oldType = $product->type;
        $product = DB::transaction(function () use ($product, $request) {
            $product->update($request->validated());
            return $product->fresh();
        });

        Cache::forget("tenant:{$tenantId}:active_products");
        Cache::forget("tenant:{$tenantId}:products:type:{$oldType}");
        Cache::forget("tenant:{$tenantId}:products:type:{$product->type}");

        return $product;
    }

    public function delete(Product $product): bool
    {
        $tenantId = $product->tenant_id;
        $result = DB::transaction(function () use ($product) {
            return $product->delete();
        });

        if ($result) {
            Cache::forget("tenant:{$tenantId}:active_products");
            Cache::forget("tenant:{$tenantId}:products:type:product");
            Cache::forget("tenant:{$tenantId}:products:type:service");
        }

        return $result;
    }

    public function getActiveProducts()
    {
        $tenantId = auth()->user()->tenant_id;

        return Cache::remember("tenant:{$tenantId}:active_products", 3600, function () {
            return Product::active()->orderBy('name')->get();
        });
    }

    public function getProductsByType(string $type)
    {
        $tenantId = auth()->user()->tenant_id;

        return Cache::remember("tenant:{$tenantId}:products:type:{$type}", 3600, function () use ($type) {
            return Product::active()->where('type', $type)->orderBy('name')->get();
        });
    }
}
