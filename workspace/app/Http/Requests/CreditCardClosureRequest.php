<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditCardClosureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'credit_card_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric', 'decimal:0,2'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'transaction_ids' => ['nullable', 'array'],
            'transaction_ids.*' => ['integer', 'exists:transactions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'credit_card_account_id.required' => 'The credit card account is required.',
            'credit_card_account_id.exists' => 'The selected credit card account does not exist.',
            'bank_account_id.required' => 'The bank account is required.',
            'bank_account_id.exists' => 'The selected bank account does not exist.',
            'payment_amount.required' => 'The payment amount is required.',
            'payment_amount.gt' => 'The payment amount must be greater than zero.',
            'payment_date.required' => 'The payment date is required.',
            'statement_balance.required' => 'The statement balance is required.',
        ];
    }
}
