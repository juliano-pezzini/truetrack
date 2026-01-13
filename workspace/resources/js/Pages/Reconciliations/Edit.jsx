import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Edit({ auth, reconciliation, accounts }) {
    const { data, setData, put, processing, errors } = useForm({
        statement_date: reconciliation.statement_date,
        statement_balance: reconciliation.statement_balance,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('reconciliations.update', reconciliation.id));
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Reconciliation
                </h2>
            }
        >
            <Head title="Edit Reconciliation" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Account Info (Read Only) */}
                            <div className="mb-6 p-4 bg-gray-50 rounded-md">
                                <h3 className="text-sm font-medium text-gray-700 mb-2">Account Information</h3>
                                <p className="text-lg font-semibold text-gray-900">
                                    {reconciliation.account?.name}
                                </p>
                                <p className="text-sm text-gray-500">
                                    {reconciliation.account?.type} • Created {formatDate(reconciliation.created_at)}
                                </p>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Statement Date */}
                                <div>
                                    <label htmlFor="statement_date" className="block text-sm font-medium text-gray-700">
                                        Statement Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="statement_date"
                                        value={data.statement_date}
                                        onChange={(e) => setData('statement_date', e.target.value)}
                                        className={`mt-1 block w-full rounded-md shadow-sm ${
                                            errors.statement_date
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                        }`}
                                        required
                                    />
                                    {errors.statement_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.statement_date}</p>
                                    )}
                                </div>

                                {/* Statement Balance */}
                                <div>
                                    <label htmlFor="statement_balance" className="block text-sm font-medium text-gray-700">
                                        Statement Balance <span className="text-red-500">*</span>
                                    </label>
                                    <div className="mt-1 relative rounded-md shadow-sm">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span className="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input
                                            type="number"
                                            id="statement_balance"
                                            step="0.01"
                                            value={data.statement_balance}
                                            onChange={(e) => setData('statement_balance', e.target.value)}
                                            className={`block w-full rounded-md pl-7 pr-12 ${
                                                errors.statement_balance
                                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                            }`}
                                            placeholder="0.00"
                                            required
                                        />
                                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span className="text-gray-500 sm:text-sm">USD</span>
                                        </div>
                                    </div>
                                    {errors.statement_balance && (
                                        <p className="mt-1 text-sm text-red-600">{errors.statement_balance}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        The ending balance shown on your bank or credit card statement.
                                    </p>
                                </div>

                                {/* Transaction Info */}
                                <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <svg className="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3 flex-1">
                                            <p className="text-sm text-blue-700">
                                                This reconciliation has <span className="font-semibold">{reconciliation.transactions?.length || 0} matched transactions</span>.
                                                To add or remove transactions, please go to the detail view.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex items-center justify-end gap-4">
                                    <Link
                                        href={route('reconciliations.show', reconciliation.id)}
                                        className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Updating...' : 'Update Reconciliation'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
