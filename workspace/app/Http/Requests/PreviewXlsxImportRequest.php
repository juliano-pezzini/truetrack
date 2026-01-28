<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewXlsxImportRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
            'mapping_config' => [
                'required',
                'array',
            ],
            'mapping_config.date_column' => 'required|string',
            'mapping_config.description_column' => 'required|string',
            'mapping_config.amount_strategy' => 'required|in:single,separate,type_column',
            'mapping_config.amount_column' => 'required_if:mapping_config.amount_strategy,single,type_column|nullable|string',
            'mapping_config.debit_column' => 'required_if:mapping_config.amount_strategy,separate|nullable|string',
            'mapping_config.credit_column' => 'required_if:mapping_config.amount_strategy,separate|nullable|string',
            'mapping_config.type_column' => 'required_if:mapping_config.amount_strategy,type_column|nullable|string',
            'mapping_config.category_column' => 'nullable|string',
            'mapping_config.settled_date_column' => 'nullable|string',
            'mapping_config.tags_column' => 'nullable|string',
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
            'mapping_config.required' => 'Column mapping configuration is required.',
            'mapping_config.date_column.required' => 'Transaction date column must be mapped.',
            'mapping_config.description_column.required' => 'Description column must be mapped.',
            'mapping_config.amount_strategy.required' => 'Amount detection strategy must be specified.',
            'mapping_config.amount_strategy.in' => 'Invalid amount strategy. Must be: single, separate, or type_column.',
        ];
    }
}
