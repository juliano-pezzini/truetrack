import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';
import normalizeInertiaUrl from '@/Utils/normalizeInertiaUrl';

export default function Index({ auth, accounts, filters }) {
    const [filterType, setFilterType] = useState(filters?.filter?.type || '');
    const [filterActive, setFilterActive] = useState(filters?.filter?.is_active ?? '');

    const accountTypes = [
        { value: '', label: 'All Types' },
        { value: 'bank', label: 'Bank Account' },
        { value: 'credit_card', label: 'Credit Card' },
        { value: 'wallet', label: 'Wallet' },
        { value: 'transitional', label: 'Transitional' },
    ];

    const activeOptions = [
        { value: '', label: 'All Accounts' },
        { value: '1', label: 'Active Only' },
        { value: '0', label: 'Inactive Only' },
    ];

    const applyFilters = () => {
        const params = {};

        if (filterType) {
            params['filter[type]'] = filterType;
        }

        if (filterActive !== '') {
            params['filter[is_active]'] = filterActive;
        }

        router.get(route('accounts.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setFilterType('');
        setFilterActive('');
        router.get(route('accounts.index'));
    };

    const deleteAccount = (accountId) => {
        if (confirm('Are you sure you want to delete this account?')) {
            router.delete(route('accounts.destroy', accountId));
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const getBalanceClass = (balance) => {
        if (balance > 0) return 'text-green-600';
        if (balance < 0) return 'text-red-600';
        return 'text-gray-600';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
                        Accounts
                    </h2>
                    <Link
                        href={route('accounts.create')}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        Create Account
                    </Link>
                </div>
            }
        >
            <Head title="Accounts" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Filters</h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="filterType" className="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                        Account Type
                                    </label>
                                    <select
                                        id="filterType"
                                        value={filterType}
                                        onChange={(e) => setFilterType(e.target.value)}
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        {accountTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="filterActive" className="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                        Status
                                    </label>
                                    <select
                                        id="filterActive"
                                        value={filterActive}
                                        onChange={(e) => setFilterActive(e.target.value)}
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        {activeOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex items-end gap-2">
                                    <button
                                        onClick={applyFilters}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                    >
                                        Apply
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Accounts List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6">
                            {accounts.data.length === 0 ? (
                                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                    No accounts found. Create your first account to get started.
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900/40">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Type
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Balance
                                                </th>
                                                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                            {accounts.data.map((account) => (
                                                <tr key={account.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {account.name}
                                                        </div>
                                                        {account.description && (
                                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                                {account.description}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                            {account.type_label}
                                                        </span>
                                                    </td>
                                                    <td className={`px-6 py-4 whitespace-nowrap text-right text-sm font-medium ${getBalanceClass(account.balance)}`}>
                                                        {formatCurrency(account.balance)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-center">
                                                        {account.is_active ? (
                                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                                Active
                                                            </span>
                                                        ) : (
                                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                                Inactive
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('accounts.edit', account.id)}
                                                            className="mr-4 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteAccount(account.id)}
                                                            className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {accounts.links && accounts.links.length > 3 && (
                                <div className="mt-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-700 dark:text-gray-300">
                                        Showing {accounts.from} to {accounts.to} of {accounts.total} results
                                    </div>
                                    <div className="flex gap-2">
                                        {accounts.links.map((link, index) => {
                                            const normalizedUrl = normalizeInertiaUrl(link.url);

                                            return (
                                                <button
                                                    key={index}
                                                    onClick={() => {
                                                        if (normalizedUrl) {
                                                            router.visit(normalizedUrl);
                                                        }
                                                    }}
                                                    disabled={!normalizedUrl}
                                                    className={`px-3 py-1 rounded-md ${
                                                        link.active
                                                            ? 'bg-indigo-600 text-white'
                                                            : normalizedUrl
                                                            ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:border-gray-600'
                                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
