export default function InvestmentSummary({ returns }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount || 0);
    };

    const formatPercentage = (value) => {
        const numValue = parseFloat(value) || 0;
        return `${numValue >= 0 ? '+' : ''}${numValue.toFixed(2)}%`;
    };

    // Handle both array format (per-account) and object format (aggregate)
    if (!returns) {
        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div className="p-6">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Investment Returns</h3>
                    <p className="text-gray-500 dark:text-gray-400">No investment data available.</p>
                </div>
            </div>
        );
    }

    // If returns is an object (aggregate data), display it as a summary card
    if (!Array.isArray(returns)) {
        const returnAmount = parseFloat(returns.return_amount) || 0;
        const returnPercentage = parseFloat(returns.return_percentage) || 0;
        const isPositive = returnAmount >= 0;

        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div className="p-6">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Investment Returns</h3>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/40">
                            <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Initial Value</div>
                            <div className="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {formatCurrency(returns.initial_value)}
                            </div>
                        </div>

                        <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/40">
                            <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Current Value</div>
                            <div className="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {formatCurrency(returns.current_value)}
                            </div>
                        </div>

                        <div className={`rounded-lg p-4 ${isPositive ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'}`}>
                            <div className={`text-sm font-medium ${isPositive ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300'}`}>
                                Return Amount
                            </div>
                            <div className={`mt-2 text-2xl font-bold ${isPositive ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}>
                                {formatCurrency(returnAmount)}
                            </div>
                        </div>

                        <div className={`rounded-lg p-4 ${isPositive ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'}`}>
                            <div className={`text-sm font-medium ${isPositive ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300'}`}>
                                Return Percentage
                            </div>
                            <div className={`mt-2 text-2xl font-bold ${isPositive ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}>
                                {formatPercentage(returnPercentage)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // If returns is an array (per-account data)
    if (returns.length === 0) {
        return (
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div className="p-6">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Investment Returns</h3>
                    <p className="text-gray-500 dark:text-gray-400">No investment accounts found.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div className="p-6">
                <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Investment Returns</h3>

                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                    Account
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                    Initial
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                    Current
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                    Return
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                    %
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            {returns.map((account, index) => {
                                const returnAmount = parseFloat(account.total_return);
                                const returnPercentage = parseFloat(account.return_percentage);
                                const isPositive = returnAmount >= 0;

                                return (
                                    <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {account.account_name}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">
                                            {formatCurrency(account.initial_balance)}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">
                                            {formatCurrency(account.current_balance)}
                                        </td>
                                        <td className={`px-4 py-3 text-sm font-medium text-right ${
                                            isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                        }`}>
                                            {formatCurrency(returnAmount)}
                                        </td>
                                        <td className={`px-4 py-3 text-sm font-medium text-right ${
                                            isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                        }`}>
                                            {formatPercentage(returnPercentage)}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
