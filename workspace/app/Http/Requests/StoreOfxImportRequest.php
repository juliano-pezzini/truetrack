<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfxImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated
        if (! $this->user()) {
            return false;
        }

        // Check if user owns the account
        $accountId = $this->input('account_id');

        if (! $accountId) {
            // If no account_id provided yet, let validation handle it
            return true;
        }

        // Guard against non-scalar or non-numeric input (e.g., account_id[]=1)
        if (! is_scalar($accountId) || ! ctype_digit((string) $accountId)) {
            return false;
        }

        return Account::whereKey((int) $accountId)
            ->where('user_id', $this->user()->id)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
            ],
            'account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],
            'force_reimport' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'An OFX file is required.',
            'file.mimes' => 'The file must be in OFX format.',
            'file.max' => 'The file size must not exceed 10MB.',
            'account_id.required' => 'An account must be selected.',
            'account_id.exists' => 'The selected account does not exist.',
        ];
    }
}
