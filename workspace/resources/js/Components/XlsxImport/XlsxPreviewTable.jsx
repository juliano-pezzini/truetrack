import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

// Maximum number of preview rows to display
const MAX_PREVIEW_ROWS = 5;

export default function XlsxPreviewTable({ previewData, validationSummary, onConfirm, onBack, isProcessing = false }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getTypeColor = (type) => {
        return type === 'credit' ? 'text-green-600' : 'text-red-600';
    };

    const previewRowCount = Array.isArray(previewData) ? previewData.length : 0;
    const displayRowCount = Math.min(MAX_PREVIEW_ROWS, previewRowCount);
    const rowLabel = displayRowCount === 1 ? 'row' : 'rows';

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Preview Transactions</h3>
                <p className="text-sm text-gray-600 mt-1 dark:text-gray-400">
                    {displayRowCount > 0
                        ? `Review the first ${displayRowCount} ${rowLabel} to ensure the mapping is correct`
                        : 'No rows are available to preview yet.'}
                </p>
            </div>

            {/* Validation Summary */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 dark:bg-blue-900/20 dark:border-blue-800">
                <div className="flex items-center justify-between">
                    <div>
                        <span className="font-medium">Valid Rows:</span>
                            <span className="ml-2 text-green-600 font-semibold dark:text-green-300">
                            {validationSummary.valid_rows}
                        </span>
                    </div>
                    {validationSummary.rows_with_warnings > 0 && (
                        <div>
                            <span className="font-medium">Rows with Warnings:</span>
                            <span className="ml-2 text-yellow-600 font-semibold dark:text-yellow-300">
                                {validationSummary.rows_with_warnings}
                            </span>
                        </div>
                    )}
                </div>
            </div>

            {/* Preview Table */}
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Date
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Description
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Amount
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Type
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Category
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Tags
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        {previewData.map((transaction, index) => (
                            <tr key={index} className={transaction.warnings?.length > 0 ? 'bg-yellow-50 dark:bg-yellow-900/10' : ''}>
                                <td className="px-4 py-3 text-sm">
                                    {formatDate(transaction.transaction_date)}
                                </td>
                                <td className="px-4 py-3 text-sm">
                                    <div>
                                        {transaction.description}
                                        {transaction.warnings?.length > 0 && (
                                            <div className="mt-1 space-y-1">
                                                {transaction.warnings.map((warning, wIndex) => (
                                                    <div key={wIndex} className="text-xs text-yellow-700 flex items-center dark:text-yellow-300">
                                                        <span className="mr-1">⚠️</span>
                                                        {warning}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </td>
                                <td className={`px-4 py-3 text-sm font-medium ${getTypeColor(transaction.type)} dark:text-green-300 dark:!text-red-300`}>
                                    {formatCurrency(transaction.amount)}
                                </td>
                                <td className="px-4 py-3 text-sm">
                                    <span className={`px-2 py-1 rounded text-xs font-medium ${
                                        transaction.type === 'credit' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                    }`}>
                                        {transaction.type}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {transaction.category_name || '—'}
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {transaction.tags?.length > 0 ? (
                                        <div className="flex flex-wrap gap-1">
                                            {transaction.tags.map((tag, tIndex) => (
                                                <span key={tIndex} className="px-2 py-1 bg-gray-200 rounded text-xs dark:bg-gray-700 dark:text-gray-200">
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        '—'
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-between pt-4">
                <SecondaryButton onClick={onBack} disabled={isProcessing}>
                    Back to Mapping
                </SecondaryButton>
                <PrimaryButton onClick={onConfirm} disabled={isProcessing}>
                    {isProcessing ? 'Processing...' : 'Looks Good! Continue'}
                </PrimaryButton>
            </div>
        </div>
    );
}
