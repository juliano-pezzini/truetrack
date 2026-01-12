export default function InvestmentSummary({ returns }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const formatPercentage = (value) => {
        return `${value >= 0 ? '+' : ''}${value.toFixed(2)}%`;
    };

    if (!returns || returns.length === 0) {
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
