<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOfxImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated and have permission to manage reconciliations
        return Auth::check() && Auth::user()->hasPermission('manage-reconciliations');
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
