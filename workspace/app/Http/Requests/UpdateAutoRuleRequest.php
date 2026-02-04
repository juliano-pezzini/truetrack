<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAutoRuleRequest extends FormRequest
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
        $ruleId = $this->route('id');

        return [
            'pattern' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-_\.\'&]+$/',
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('user_id', auth()->id())
                    ->where('deleted_at', null),
            ],
            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
                // Allow current rule's priority, but ensure no conflicts with other rules
                Rule::unique('auto_category_rules', 'priority')
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at')
                    ->ignore($ruleId),
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
            'pattern.required' => 'Pattern is required',
            'pattern.min' => 'Pattern must be at least 2 characters',
            'pattern.max' => 'Pattern must not exceed 100 characters',
            'pattern.regex' => 'Pattern contains invalid characters. Use letters, numbers, spaces, and -_.\'&',
            'category_id.required' => 'Category is required',
            'category_id.exists' => 'Selected category does not exist or is not accessible',
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
        // Normalize pattern to lowercase
        if ($this->has('pattern')) {
            $this->merge([
                'pattern' => strtolower(trim($this->input('pattern'))),
            ]);
        }

        // Ensure category_id is an integer
        if ($this->has('category_id')) {
            $this->merge([
                'category_id' => (int) $this->input('category_id'),
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
