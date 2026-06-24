<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('payments.create');
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')->where('tenant_id', $tenantId),
            ],
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'El monto debe ser mayor a cero.',
            'payment_date.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}