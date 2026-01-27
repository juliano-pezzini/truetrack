<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateXlsxColumnMappingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'mapping_config' => 'nullable|array',
            'mapping_config.date_column' => 'required_with:mapping_config|string',
            'mapping_config.description_column' => 'required_with:mapping_config|string',
            'mapping_config.amount_strategy' => 'required_with:mapping_config|in:single,separate,type_column',
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
}
