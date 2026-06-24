<?php

namespace App\Http\Requests\NumberingRange;

use App\Models\NumberingRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNumberingRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('settings.numbering');
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in([NumberingRange::TYPE_INVOICE, NumberingRange::TYPE_CREDIT_NOTE])],
            'prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'from_number' => ['required', 'integer', 'min:1'],
            'to_number' => ['required', 'integer', 'gt:from_number', 'max:999999999'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'resolution_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:resolution_date'],
            'technical_key' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}