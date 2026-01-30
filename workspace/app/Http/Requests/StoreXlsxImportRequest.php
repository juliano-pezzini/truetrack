<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Account;
use App\Models\XlsxImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreXlsxImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the account
        $account = Account::find($this->input('account_id'));

        return $account && $account->user_id === $this->user()->id;
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
            'force' => [
                'sometimes',
                'boolean',
            ],
            'account_id' => [
                'required',
                'exists:accounts,id',
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
            'column_mapping_id' => 'nullable|exists:xlsx_column_mappings,id',
            'save_mapping' => 'nullable|boolean',
            'mapping_name' => 'nullable|string|max:255',
            'set_as_default' => 'nullable|boolean',
            'create_reconciliation' => 'nullable|boolean',
            'statement_date' => 'nullable|date|required_if:create_reconciliation,true',
            'statement_balance' => 'nullable|numeric|required_if:create_reconciliation,true',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check concurrency limit
            $maxConcurrent = DB::table('settings')
                ->where('key', 'max_concurrent_imports_per_user')
                ->value('value') ?? 5;

            $activeImports = XlsxImport::where('user_id', $this->user()->id)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            if ($activeImports >= $maxConcurrent) {
                $validator->errors()->add(
                    'concurrency',
                    "You have reached the maximum number of concurrent imports ({$maxConcurrent}). Please wait for existing imports to complete."
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_id.required' => 'Account is required.',
            'account_id.exists' => 'Invalid account selected.',
            'statement_date.required_if' => 'Statement date is required when creating a reconciliation.',
            'statement_balance.required_if' => 'Statement balance is required when creating a reconciliation.',
        ];
    }
}
