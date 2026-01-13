import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';

export default function Show({ auth, reconciliation, suggestedTransactions }) {
    const [selectedTransaction, setSelectedTransaction] = useState(null);

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

    const calculateDiscrepancy = () => {
        const reconciledTotal = reconciliation.transactions.reduce((sum, transaction) => {
            return sum + (transaction.type === 'credit' ? parseFloat(transaction.amount) : -parseFloat(transaction.amount));
        }, 0);

        return parseFloat(reconciliation.statement_balance) - reconciledTotal;
    };

    const addTransaction = (transactionId) => {
        router.post(
            route('reconciliations.add-transaction', reconciliation.id),
            { transaction_id: transactionId },
            { preserveState: true, preserveScroll: true }
        );
    };

    const removeTransaction = (transactionId) => {
        if (confirm('Remove this transaction from reconciliation?')) {
            router.delete(
                route('reconciliations.remove-transaction', [reconciliation.id, transactionId]),
                { preserveState: true, preserveScroll: true }
            );
        }
    };

    const completeReconciliation = () => {
        const discrepancy = calculateDiscrepancy();
        if (Math.abs(discrepancy) > 0.01) {
            if (!confirm(`There is a discrepancy of ${formatCurrency(Math.abs(discrepancy))}. Complete anyway?`)) {
                return;
            }
        }

        router.post(
            route('reconciliations.complete', reconciliation.id),
            {},
            { preserveState: false }
        );
    };

    const getStatusBadge = (status) => {
        const classes = status === 'completed'
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';

        return (
            <span className={`px-3 py-1 text-sm font-semibold rounded-full ${classes}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    const getDiscrepancyClass = (amount) => {
        if (Math.abs(amount) < 0.01) return 'text-green-600';
        return 'text-red-600';
    };

    const discrepancy = calculateDiscrepancy();
    const isPending = reconciliation.status === 'pending';

    // Filter out already matched transactions from suggestions
    const availableTransactions = suggestedTransactions.filter(
        (st) => !reconciliation.transactions.some((rt) => rt.id === st.id)
    );

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Reconciliation Details
                    </h2>
                    <div className="flex gap-2">
                        <Link
                            href={route('reconciliations.index')}
                            className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Back to List
                        </Link>
                        {isPending && (
                            <button
                                onClick={completeReconciliation}
                                className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                            >
                                Complete Reconciliation
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Reconciliation - ${reconciliation.account?.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Summary Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500">Account</h3>
                                    <p className="mt-1 text-lg font-semibold text-gray-900">
                                        {reconciliation.account?.name}
                                    </p>
                                    <p className="text-sm text-gray-500">{reconciliation.account?.type}</p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500">Statement Date</h3>
                                    <p className="mt-1 text-lg font-semibold text-gray-900">
                                        {formatDate(reconciliation.statement_date)}
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500">Statement Balance</h3>
                                    <p className="mt-1 text-lg font-semibold text-gray-900">
                                        {formatCurrency(reconciliation.statement_balance)}
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500">Status</h3>
                                    <p className="mt-1">
                                        {getStatusBadge(reconciliation.status)}
                                    </p>
                                </div>
                            </div>

                            {/* Discrepancy Alert */}
                            <div className={`mt-6 p-4 rounded-md ${Math.abs(discrepancy) < 0.01 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}`}>
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        {Math.abs(discrepancy) < 0.01 ? (
                                            <svg className="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        ) : (
                                            <svg className="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                    </div>
                                    <div className="ml-3">
                                        <h3 className={`text-sm font-medium ${Math.abs(discrepancy) < 0.01 ? 'text-green-800' : 'text-red-800'}`}>
                                            {Math.abs(discrepancy) < 0.01 ? 'Balanced' : 'Discrepancy Found'}
                                        </h3>
                                        <div className={`mt-1 text-sm ${Math.abs(discrepancy) < 0.01 ? 'text-green-700' : 'text-red-700'}`}>
                                            <p>
                                                {Math.abs(discrepancy) < 0.01
                                                    ? 'Reconciliation is balanced. All transactions match the statement.'
                                                    : `There is a difference of ${formatCurrency(Math.abs(discrepancy))} that needs to be resolved.`}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Two Column Layout */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Matched Transactions */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold mb-4">
                                    Matched Transactions ({reconciliation.transactions.length})
                                </h3>

                                {reconciliation.transactions.length === 0 ? (
                                    <p className="text-gray-500 text-center py-8">
                                        No transactions matched yet. Select transactions from the suggestions.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {reconciliation.transactions.map((transaction) => (
                                            <div
                                                key={transaction.id}
                                                className="flex justify-between items-center p-3 bg-gray-50 rounded-md hover:bg-gray-100"
                                            >
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {transaction.description}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {formatDate(transaction.transaction_date)} • {transaction.type}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className={`text-sm font-semibold ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
                                                        {transaction.type === 'credit' ? '+' : '-'}
                                                        {formatCurrency(transaction.amount)}
                                                    </span>
                                                    {isPending && (
                                                        <button
                                                            onClick={() => removeTransaction(transaction.id)}
                                                            className="text-red-600 hover:text-red-800"
                                                            title="Remove"
                                                        >
                                                            <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Suggested Transactions */}
                        {isPending && (
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-semibold mb-4">
                                        Suggested Transactions ({availableTransactions.length})
                                    </h3>

                                    {availableTransactions.length === 0 ? (
                                        <p className="text-gray-500 text-center py-8">
                                            No available transactions to match.
                                        </p>
                                    ) : (
                                        <div className="space-y-2">
                                            {availableTransactions.map((transaction) => (
                                                <div
                                                    key={transaction.id}
                                                    className="flex justify-between items-center p-3 bg-gray-50 rounded-md hover:bg-gray-100"
                                                >
                                                    <div className="flex-1">
                                                        <p className="text-sm font-medium text-gray-900">
                                                            {transaction.description}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            {formatDate(transaction.transaction_date)} • {transaction.type}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-3">
                                                        <span className={`text-sm font-semibold ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
                                                            {transaction.type === 'credit' ? '+' : '-'}
                                                            {formatCurrency(transaction.amount)}
                                                        </span>
                                                        <button
                                                            onClick={() => addTransaction(transaction.id)}
                                                            className="text-indigo-600 hover:text-indigo-800"
                                                            title="Add to reconciliation"
                                                        >
                                                            <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fillRule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clipRule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
