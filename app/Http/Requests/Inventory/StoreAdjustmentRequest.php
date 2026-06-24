<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentRequest extends FormRequest
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
            'new_stock' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ];
    }
}