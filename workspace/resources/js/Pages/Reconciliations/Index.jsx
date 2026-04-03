import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';
import normalizeInertiaUrl from '@/Utils/normalizeInertiaUrl';

export default function Index({ auth, reconciliations, accounts, filters }) {
    const [filterAccountId, setFilterAccountId] = useState(filters?.filter?.account_id || '');
    const [filterStatus, setFilterStatus] = useState(filters?.filter?.status || '');

    const statusOptions = [
        { value: '', label: 'All Statuses' },
        { value: 'pending', label: 'Pending' },
        { value: 'completed', label: 'Completed' },
    ];

    const applyFilters = () => {
        const params = {};

        if (filterAccountId) {
            params['filter[account_id]'] = filterAccountId;
        }

        if (filterStatus) {
            params['filter[status]'] = filterStatus;
        }

        router.get(route('reconciliations.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setFilterAccountId('');
        setFilterStatus('');
        router.get(route('reconciliations.index'));
    };

    const deleteReconciliation = (reconciliationId) => {
        if (confirm('Are you sure you want to delete this reconciliation?')) {
            router.delete(route('reconciliations.destroy', reconciliationId));
        }
    };

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

    const getStatusBadge = (status) => {
        const classes = status === 'completed'
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';

        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${classes}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
                        Reconciliations
                    </h2>
                    <div className="flex gap-2">
                        <Link
                            href={route('credit-card-closure.form')}
                            className="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
                        >
                            Credit Card Closure
                        </Link>
                        <Link
                            href={route('reconciliations.create')}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                        >
                            New Reconciliation
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Reconciliations" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Filters</h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="filterAccount" className="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                        Account
                                    </label>
                                    <select
                                        id="filterAccount"
                                        value={filterAccountId}
                                        onChange={(e) => setFilterAccountId(e.target.value)}
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        <option value="">All Accounts</option>
                                        {accounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name} ({account.type})
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="filterStatus" className="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                        Status
                                    </label>
                                    <select
                                        id="filterStatus"
                                        value={filterStatus}
                                        onChange={(e) => setFilterStatus(e.target.value)}
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        {statusOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <button
                                        type="button"
                                        onClick={applyFilters}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                    >
                                        Apply Filters
                                    </button>
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Reconciliations List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6">
                            {reconciliations.data.length === 0 ? (
                                <p className="text-gray-500 text-center py-8 dark:text-gray-400">
                                    No reconciliations found. Create your first reconciliation to get started.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900/40">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Account
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Statement Date
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Statement Balance
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Reconciled At
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                            {reconciliations.data.map((reconciliation) => (
                                                <tr key={reconciliation.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {reconciliation.account?.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500 dark:text-gray-400">
                                                            {reconciliation.account?.type}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {formatDate(reconciliation.statement_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {formatCurrency(reconciliation.statement_balance)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(reconciliation.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {reconciliation.reconciled_at
                                                            ? formatDate(reconciliation.reconciled_at)
                                                            : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('reconciliations.show', reconciliation.id)}
                                                            className="mr-3 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        >
                                                            View
                                                        </Link>
                                                        {reconciliation.status === 'pending' && (
                                                            <>
                                                                <Link
                                                                    href={route('reconciliations.edit', reconciliation.id)}
                                                                    className="mr-3 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                                >
                                                                    Edit
                                                                </Link>
                                                                <button
                                                                    onClick={() => deleteReconciliation(reconciliation.id)}
                                                                    className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                                >
                                                                    Delete
                                                                </button>
                                                            </>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {reconciliations.links && reconciliations.links.length > 3 && (
                                <div className="mt-6 flex justify-center gap-2">
                                    {reconciliations.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={normalizeInertiaUrl(link.url) || '#'}
                                            className={`px-3 py-2 rounded-md ${
                                                link.active
                                                    ? 'bg-indigo-600 text-white'
                                                    : normalizeInertiaUrl(link.url)
                                                    ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:border-gray-600'
                                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            preserveState
                                            preserveScroll
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
