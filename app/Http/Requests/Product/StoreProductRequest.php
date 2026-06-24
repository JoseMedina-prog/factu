<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('products.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0|max:100',
            'type' => ['sometimes', Rule::in(['product', 'service'])],
            'is_active' => 'sometimes|boolean',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'track_inventory' => 'sometimes|boolean',
            'unit_of_measure' => ['nullable', 'string', Rule::in(array_keys(\App\Models\Product::UNITS))],
            'sku' => 'nullable|string|max:50',
        ];
    }
}