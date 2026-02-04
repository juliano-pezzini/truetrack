<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertPatternRequest extends FormRequest
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
            'pattern_id' => [
                'required',
                'integer',
                Rule::exists('learned_category_patterns', 'id')
                    ->where('user_id', auth()->id()),
            ],
            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
                // Ensure priority is unique for this user
                Rule::unique('auto_category_rules', 'priority')
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
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
            'pattern_id.required' => 'Pattern is required',
            'pattern_id.exists' => 'Selected pattern does not exist or is not accessible',
            'priority.required' => 'Priority is required',
            'priority.unique' => 'This priority is already in use. Priorities must be unique.',
            'priority.min' => 'Priority must be at least 1',
            'priority.max' => 'Priority must not exceed 1000',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure pattern_id is an integer
        if ($this->has('pattern_id')) {
            $this->merge([
                'pattern_id' => (int) $this->input('pattern_id'),
            ]);
        }

        // Ensure priority is an integer
        if ($this->has('priority')) {
            $this->merge([
                'priority' => (int) $this->input('priority'),
            ]);
        }
    }
}
