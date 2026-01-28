<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreXlsxColumnMappingRequest extends FormRequest
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
            'account_id' => 'nullable|exists:accounts,id',
            'name' => 'required|string|max:255',
            'mapping_config' => 'required|array',
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
            'is_default' => 'nullable|boolean',
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
            'name.required' => 'Mapping name is required.',
            'mapping_config.required' => 'Column mapping configuration is required.',
        ];
    }
}
