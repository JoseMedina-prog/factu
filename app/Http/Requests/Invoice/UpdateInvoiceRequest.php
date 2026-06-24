<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'client_id' => [
                'sometimes',
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'sometimes|in:draft,pending,sent,approved,rejected,cancelled,error',
            'notes' => 'nullable|string|max:1000',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0|max:100',
        ];
    }
}

