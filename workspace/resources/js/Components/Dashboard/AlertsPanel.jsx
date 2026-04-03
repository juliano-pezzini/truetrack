export default function AlertsPanel({ alerts }) {
    const formatCurrency = (amount) => {
        const numValue = parseFloat(amount) || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(numValue);
    };

    const hasAccountAlerts = alerts.accounts_at_risk && alerts.accounts_at_risk.length > 0;
    const hasCreditCardAlerts = alerts.credit_card_alerts && alerts.credit_card_alerts.length > 0;

    if (!hasAccountAlerts && !hasCreditCardAlerts) {
        return null;
    }

    return (
        <div className="overflow-hidden border border-orange-200 bg-orange-50 shadow-sm sm:rounded-lg dark:border-orange-500/35 dark:bg-orange-950/20">
            <div className="p-6">
                <div className="flex items-center mb-4">
                    <svg
                        className="mr-2 h-6 w-6 text-orange-600 dark:text-orange-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <h3 className="text-lg font-semibold text-orange-900 dark:text-orange-200">
                        Financial Alerts ({alerts.total_count})
                    </h3>
                </div>

                <div className="space-y-4">
                    {/* Accounts at Risk */}
                    {hasAccountAlerts && (
                        <div>
                            <h4 className="mb-2 text-sm font-medium text-orange-800 dark:text-orange-300">
                                Accounts at Risk
                            </h4>
                            <div className="space-y-2">
                                {alerts.accounts_at_risk.map((account, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border border-orange-200 bg-white p-3 dark:border-orange-500/25 dark:bg-gray-800"
                                    >
                                        <div className="flex justify-between items-center">
                                            <span className="font-medium text-gray-900 dark:text-gray-100">
                                                {account.account_name}
                                            </span>
                                            <span className="font-semibold text-red-600 dark:text-red-400">
                                                {formatCurrency(account.balance || 0)}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            Account balance is negative or critically low
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Credit Card Alerts */}
                    {hasCreditCardAlerts && (
                        <div>
                            <h4 className="mb-2 text-sm font-medium text-orange-800 dark:text-orange-300">
                                Credit Card Payment Alerts
                            </h4>
                            <div className="space-y-2">
                                {alerts.credit_card_alerts.map((alert, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border border-orange-200 bg-white p-3 dark:border-orange-500/25 dark:bg-gray-800"
                                    >
                                        <div className="flex justify-between items-center">
                                            <span className="font-medium text-gray-900 dark:text-gray-100">
                                                {alert.account_name}
                                            </span>
                                            <span className="font-semibold text-red-600 dark:text-red-400">
                                                {formatCurrency(alert.amount_owed || 0)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between items-center mt-2 text-sm">
                                            <span className="text-gray-600 dark:text-gray-300">
                                                Available funds:
                                            </span>
                                            <span className={alert.can_pay_full ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}>
                                                {formatCurrency(alert.available_funds || 0)}
                                            </span>
                                        </div>
                                        {!alert.can_pay_full && (
                                            <p className="mt-1 text-sm font-medium text-red-600 dark:text-red-400">
                                                ⚠️ Insufficient funds to cover credit card balance
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
