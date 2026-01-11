<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['sometimes', 'integer', Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'transaction_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'settled_date' => ['nullable', 'date', 'after_or_equal:transaction_date'],
            'type' => ['sometimes', Rule::enum(TransactionType::class)],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')->where('user_id', $this->user()->id)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'account_id' => 'account',
            'category_id' => 'category',
            'transaction_date' => 'transaction date',
            'settled_date' => 'settled date',
            'tag_ids' => 'tags',
            'tag_ids.*' => 'tag',
        ];
    }
}
