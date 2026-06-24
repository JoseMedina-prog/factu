<?php

namespace App\Http\Requests\CreditNote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreditNoteRequest extends FormRequest
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
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'invoice_id' => [
                'nullable',
                Rule::exists('invoices', 'id')->where('tenant_id', $tenantId),
            ],
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'nullable',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
