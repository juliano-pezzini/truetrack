<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', Rule::enum(CategoryType::class)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn([$category->id]), // Prevent self-reference
                Rule::exists('categories', 'id')->where(function ($query) use ($category) {
                    $query->where('user_id', $this->user()->id)
                        ->where('type', $this->input('type', $category->type->value));
                }),
            ],
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
            'parent_id.exists' => 'The parent category must exist and have the same type.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }
}
