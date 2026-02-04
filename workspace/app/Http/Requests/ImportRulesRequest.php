<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportRulesRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'import_file' => [
                'required',
                'file',
                'mimes:json,csv',
                'max:1024', // 1MB max
            ],
            'merge_strategy' => [
                'required',
                'in:replace,merge,skip_duplicates',
            ],
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
            'import_file.required' => 'Import file is required',
            'import_file.file' => 'Upload must be a file',
            'import_file.mimes' => 'File must be JSON or CSV format',
            'import_file.max' => 'File must not exceed 1MB',
            'merge_strategy.required' => 'Merge strategy is required',
            'merge_strategy.in' => 'Merge strategy must be one of: replace, merge, skip_duplicates',
        ];
    }
}
