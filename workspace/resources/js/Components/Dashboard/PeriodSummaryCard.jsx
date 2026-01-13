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
        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold">Period Summary</h3>
                    <span className="text-sm text-gray-500">
                        {formatDate(period.start_date)} - {formatDate(period.end_date)}
                    </span>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {/* Total Income */}
                    <div className="rounded-lg bg-green-50 p-4">
                        <div className="text-sm font-medium text-green-600">Total Income</div>
                        <div className="mt-2 text-2xl font-bold text-green-900">
                            {formatCurrency(totalIncome)}
                        </div>
                    </div>

                    {/* Total Expenses */}
                    <div className="rounded-lg bg-red-50 p-4">
                        <div className="text-sm font-medium text-red-600">Total Expenses</div>
                        <div className="mt-2 text-2xl font-bold text-red-900">
                            {formatCurrency(totalExpenses)}
                        </div>
                    </div>

                    {/* Net Profit/Loss */}
                    <div className={`rounded-lg p-4 ${isProfitable ? 'bg-blue-50' : 'bg-orange-50'}`}>
                        <div className={`text-sm font-medium ${isProfitable ? 'text-blue-600' : 'text-orange-600'}`}>
                            {isProfitable ? 'Net Profit' : 'Net Loss'}
                        </div>
                        <div className={`mt-2 text-2xl font-bold ${isProfitable ? 'text-blue-900' : 'text-orange-900'}`}>
                            {formatCurrency(Math.abs(profitLoss))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
