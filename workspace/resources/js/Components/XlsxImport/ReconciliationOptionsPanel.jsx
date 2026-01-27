import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function ReconciliationOptionsPanel({
    enabled,
    onEnabledChange,
    statementDate,
    onStatementDateChange,
    statementBalance,
    onStatementBalanceChange,
    errors,
}) {
    return (
        <div className="space-y-4">
            <div className="flex items-start">
                <input
                    id="create_reconciliation"
                    type="checkbox"
                    checked={enabled}
                    onChange={(e) => onEnabledChange(e.target.checked)}
                    className="rounded border-gray-300"
                />
                <div className="ml-3">
                    <label htmlFor="create_reconciliation" className="font-medium text-gray-700">
                        Create reconciliation from this import
                    </label>
                    <p className="text-sm text-gray-500 mt-1">
                        Transactions will be automatically matched to existing entries using fuzzy matching
                    </p>
                </div>
            </div>

            {enabled && (
                <div className="ml-6 space-y-4 pl-4 border-l-2 border-gray-200">
                    <div>
                        <InputLabel htmlFor="statement_date" value="Statement Date *" />
                        <input
                            id="statement_date"
                            type="date"
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            value={statementDate}
                            onChange={(e) => onStatementDateChange(e.target.value)}
                        />
                        <InputError message={errors.statement_date} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="statement_balance" value="Statement Closing Balance *" />
                        <div className="mt-1 relative rounded-md shadow-sm">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span className="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input
                                id="statement_balance"
                                type="number"
                                step="0.01"
                                className="pl-7 block w-full border-gray-300 rounded-md shadow-sm"
                                value={statementBalance}
                                onChange={(e) => onStatementBalanceChange(e.target.value)}
                                placeholder="0.00"
                            />
                        </div>
                        <InputError message={errors.statement_balance} className="mt-2" />
                    </div>

                    <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-blue-700">
                                    The system will automatically match imported transactions with existing entries based on date,
                                    amount, and description similarity. Exact matches (100% confidence) will be automatically linked.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
