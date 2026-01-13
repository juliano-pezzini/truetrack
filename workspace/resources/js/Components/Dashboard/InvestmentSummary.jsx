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
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <h3 className="text-lg font-semibold mb-4">Investment Returns</h3>
                    <p className="text-gray-500">No investment data available.</p>
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
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <h3 className="text-lg font-semibold mb-4">Investment Returns</h3>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="bg-gray-50 rounded-lg p-4">
                            <div className="text-sm font-medium text-gray-600">Initial Value</div>
                            <div className="mt-2 text-2xl font-bold text-gray-900">
                                {formatCurrency(returns.initial_value)}
                            </div>
                        </div>

                        <div className="bg-gray-50 rounded-lg p-4">
                            <div className="text-sm font-medium text-gray-600">Current Value</div>
                            <div className="mt-2 text-2xl font-bold text-gray-900">
                                {formatCurrency(returns.current_value)}
                            </div>
                        </div>

                        <div className={`rounded-lg p-4 ${isPositive ? 'bg-green-50' : 'bg-red-50'}`}>
                            <div className={`text-sm font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                                Return Amount
                            </div>
                            <div className={`mt-2 text-2xl font-bold ${isPositive ? 'text-green-900' : 'text-red-900'}`}>
                                {formatCurrency(returnAmount)}
                            </div>
                        </div>

                        <div className={`rounded-lg p-4 ${isPositive ? 'bg-green-50' : 'bg-red-50'}`}>
                            <div className={`text-sm font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                                Return Percentage
                            </div>
                            <div className={`mt-2 text-2xl font-bold ${isPositive ? 'text-green-900' : 'text-red-900'}`}>
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
            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <h3 className="text-lg font-semibold mb-4">Investment Returns</h3>
                    <p className="text-gray-500">No investment accounts found.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6">
                <h3 className="text-lg font-semibold mb-4">Investment Returns</h3>

                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Account
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Initial
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Return
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    %
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {returns.map((account, index) => {
                                const returnAmount = parseFloat(account.total_return);
                                const returnPercentage = parseFloat(account.return_percentage);
                                const isPositive = returnAmount >= 0;

                                return (
                                    <tr key={index} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                            {account.account_name}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-900 text-right">
                                            {formatCurrency(account.initial_balance)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-900 text-right">
                                            {formatCurrency(account.current_balance)}
                                        </td>
                                        <td className={`px-4 py-3 text-sm font-medium text-right ${
                                            isPositive ? 'text-green-600' : 'text-red-600'
                                        }`}>
                                            {formatCurrency(returnAmount)}
                                        </td>
                                        <td className={`px-4 py-3 text-sm font-medium text-right ${
                                            isPositive ? 'text-green-600' : 'text-red-600'
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
