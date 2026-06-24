<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreExitRequest extends FormRequest
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
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}