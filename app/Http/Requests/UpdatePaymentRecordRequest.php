<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trx_datetime' => ['required', 'date'],
            'trx_id' => ['required', 'string', 'max:100'],
            'amount_received_datetime' => ['required', 'date'],

            'sent_amount' => ['required', 'numeric', 'min:0.01'],
            'sent_datetime' => ['required', 'date'],
            'sent_via' => ['required', Rule::in(['MFS', 'BANK'])],

            'sent_trx_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn() => $this->input('sent_via') === 'MFS'),
            ],
            'sent_bank_acc' => [
                'nullable',
                'string',
                'max:191',
                Rule::requiredIf(fn() => $this->input('sent_via') === 'BANK'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sent_trx_id.required' => 'Bkash/MFS Trx ID is required when sent via MFS.',
            'sent_bank_acc.required' => 'Bank account is required when sent via BANK.',
        ];
    }
}
