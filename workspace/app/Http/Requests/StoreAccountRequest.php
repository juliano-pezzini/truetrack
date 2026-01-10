<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(AccountType::values())],
            'description' => ['nullable', 'string', 'max:1000'],
            'balance' => ['required', 'numeric', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
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
            'name.required' => 'The account name is required.',
            'name.max' => 'The account name must not exceed 255 characters.',
            'type.required' => 'The account type is required.',
            'type.in' => 'The selected account type is invalid.',
            'balance.required' => 'The initial balance is required.',
            'balance.numeric' => 'The balance must be a valid number.',
            'balance.decimal' => 'The balance must have at most 2 decimal places.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }
}
