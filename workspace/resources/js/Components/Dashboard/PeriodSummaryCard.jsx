export default function PeriodSummaryCard({ period, summary }) {
    const formatCurrency = (amount) => {
        const numValue = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(numValue);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const totalIncome = parseFloat(summary?.revenue) || 0;
    const totalExpenses = parseFloat(summary?.expenses) || 0;
    const profitLoss = totalIncome - totalExpenses;
    const isProfitable = profitLoss >= 0;

    return (
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Period Summary</h3>
                    <span className="text-sm text-gray-500 dark:text-gray-400">
                        {formatDate(period.start_date)} - {formatDate(period.end_date)}
                    </span>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {/* Total Income */}
                    <div className="rounded-lg bg-green-50 p-4 dark:bg-green-900/20 dark:ring-1 dark:ring-green-500/25">
                        <div className="text-sm font-medium text-green-600 dark:text-green-300">Total Income</div>
                        <div className="mt-2 text-2xl font-bold text-green-900 dark:text-green-200">
                            {formatCurrency(totalIncome)}
                        </div>
                    </div>

                    {/* Total Expenses */}
                    <div className="rounded-lg bg-red-50 p-4 dark:bg-red-900/20 dark:ring-1 dark:ring-red-500/25">
                        <div className="text-sm font-medium text-red-600 dark:text-red-300">Total Expenses</div>
                        <div className="mt-2 text-2xl font-bold text-red-900 dark:text-red-200">
                            {formatCurrency(totalExpenses)}
                        </div>
                    </div>

                    {/* Net Profit/Loss */}
                    <div className={`rounded-lg p-4 ${isProfitable ? 'bg-blue-50 dark:bg-blue-900/20 dark:ring-1 dark:ring-blue-500/25' : 'bg-orange-50 dark:bg-orange-900/20 dark:ring-1 dark:ring-orange-500/25'}`}>
                        <div className={`text-sm font-medium ${isProfitable ? 'text-blue-600 dark:text-blue-300' : 'text-orange-600 dark:text-orange-300'}`}>
                            {isProfitable ? 'Net Profit' : 'Net Loss'}
                        </div>
                        <div className={`mt-2 text-2xl font-bold ${isProfitable ? 'text-blue-900 dark:text-blue-200' : 'text-orange-900 dark:text-orange-200'}`}>
                            {formatCurrency(Math.abs(profitLoss))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
