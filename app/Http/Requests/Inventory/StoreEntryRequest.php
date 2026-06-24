<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('products.update');
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'product_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}