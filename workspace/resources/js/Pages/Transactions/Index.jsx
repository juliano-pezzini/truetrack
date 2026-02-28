import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';

export default function Index({ auth, transactions, accounts, categories, tags, filters }) {
    const [filterAccount, setFilterAccount] = useState(filters?.filter?.account_id || '');
    const [filterCategory, setFilterCategory] = useState(filters?.filter?.category_id || '');
    const [filterType, setFilterType] = useState(filters?.filter?.type || '');
    const [filterSettled, setFilterSettled] = useState(filters?.filter?.is_settled ?? '');
    const [filterDateFrom, setFilterDateFrom] = useState(filters?.filter?.date_from || '');
    const [filterDateTo, setFilterDateTo] = useState(filters?.filter?.date_to || '');
    const [filterTag, setFilterTag] = useState(filters?.filter?.tag || '');

    const transactionTypes = [
        { value: '', label: 'All Types' },
        { value: 'debit', label: 'Debit' },
        { value: 'credit', label: 'Credit' },
    ];

    const settledOptions = [
        { value: '', label: 'All Transactions' },
        { value: '1', label: 'Settled Only' },
        { value: '0', label: 'Unsettled Only' },
    ];

    const applyFilters = () => {
        const params = {};

        if (filterAccount) params['filter[account_id]'] = filterAccount;
        if (filterCategory) params['filter[category_id]'] = filterCategory;
        if (filterType) params['filter[type]'] = filterType;
        if (filterSettled !== '') params['filter[settled]'] = filterSettled;
        if (filterDateFrom) params['filter[date_from]'] = filterDateFrom;
        if (filterDateTo) params['filter[date_to]'] = filterDateTo;
        if (filterTag) params['filter[tag]'] = filterTag;

        router.get(route('transactions.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setFilterAccount('');
        setFilterCategory('');
        setFilterType('');
        setFilterSettled('');
        setFilterDateFrom('');
        setFilterDateTo('');
        setFilterTag('');
        router.get(route('transactions.index'));
    };

    const deleteTransaction = (transactionId) => {
        if (confirm('Are you sure you want to delete this transaction? This will adjust the account balance.')) {
            router.delete(route('transactions.destroy', transactionId));
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getTypeClass = (type) => {
        return type === 'credit' ? 'text-green-600' : 'text-red-600';
    };

    const getTypeBadge = (type) => {
        const bgClass = type === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        return (
            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${bgClass}`}>
                {type.toUpperCase()}
            </span>
        );
    };

    const getSettledBadge = (settledDate) => {
        if (settledDate) {
            return (
                <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                    Settled
                </span>
            );
        }
        return (
            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                Pending
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Transactions
                    </h2>
                    <Link
                        href={route('transactions.create')}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        New Transaction
                    </Link>
                </div>
            }
        >
            <Head title="Transactions" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Filters</h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {/* Account Filter */}
                                <div>
                                    <label htmlFor="filterAccount" className="block text-sm font-medium text-gray-700 mb-2">
                                        Account
                                    </label>
                                    <select
                                        id="filterAccount"
                                        value={filterAccount}
                                        onChange={(e) => setFilterAccount(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">All Accounts</option>
                                        {accounts?.data?.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Category Filter */}
                                <div>
                                    <label htmlFor="filterCategory" className="block text-sm font-medium text-gray-700 mb-2">
                                        Category
                                    </label>
                                    <select
                                        id="filterCategory"
                                        value={filterCategory}
                                        onChange={(e) => setFilterCategory(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">All Categories</option>
                                        {categories?.data?.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Type Filter */}
                                <div>
                                    <label htmlFor="filterType" className="block text-sm font-medium text-gray-700 mb-2">
                                        Type
                                    </label>
                                    <select
                                        id="filterType"
                                        value={filterType}
                                        onChange={(e) => setFilterType(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        {transactionTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Settled Filter */}
                                <div>
                                    <label htmlFor="filterSettled" className="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        id="filterSettled"
                                        value={filterSettled}
                                        onChange={(e) => setFilterSettled(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        {settledOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Date From */}
                                <div>
                                    <label htmlFor="filterDateFrom" className="block text-sm font-medium text-gray-700 mb-2">
                                        From Date
                                    </label>
                                    <input
                                        type="date"
                                        id="filterDateFrom"
                                        value={filterDateFrom}
                                        onChange={(e) => setFilterDateFrom(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                {/* Date To */}
                                <div>
                                    <label htmlFor="filterDateTo" className="block text-sm font-medium text-gray-700 mb-2">
                                        To Date
                                    </label>
                                    <input
                                        type="date"
                                        id="filterDateTo"
                                        value={filterDateTo}
                                        onChange={(e) => setFilterDateTo(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                {/* Tag Filter */}
                                <div>
                                    <label htmlFor="filterTag" className="block text-sm font-medium text-gray-700 mb-2">
                                        Tag
                                    </label>
                                    <select
                                        id="filterTag"
                                        value={filterTag}
                                        onChange={(e) => setFilterTag(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">All Tags</option>
                                        {tags?.data?.map((tag) => (
                                            <option key={tag.id} value={tag.name}>
                                                {tag.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="mt-4 flex gap-2">
                                <button
                                    type="button"
                                    onClick={applyFilters}
                                    className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                                >
                                    Apply Filters
                                </button>
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                >
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Transactions List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {transactions?.data?.length === 0 ? (
                                <div className="text-center py-8">
                                    <p className="text-gray-500">No transactions found.</p>
                                    <Link
                                        href={route('transactions.create')}
                                        className="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                                    >
                                        Create your first transaction
                                    </Link>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Account
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Category
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Description
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Type
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Amount
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {transactions?.data?.map((transaction) => (
                                                <tr key={transaction.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {formatDate(transaction.transaction_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {transaction.account?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {transaction.category?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        {transaction.description || '—'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getTypeBadge(transaction.type)}
                                                    </td>
                                                    <td className={`px-6 py-4 whitespace-nowrap text-sm text-right font-medium ${getTypeClass(transaction.type)}`}>
                                                        {formatCurrency(transaction.amount)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getSettledBadge(transaction.settled_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('transactions.edit', transaction.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 mr-3"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteTransaction(transaction.id)}
                                                            className="text-red-600 hover:text-red-900"
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
                            {transactions?.links && transactions.links.length > 3 && (
                                <div className="mt-6 flex justify-center">
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        {transactions.links.map((link, index) =>
                                            link.url ? (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ) : (
                                                <span
                                                    key={index}
                                                    className="relative inline-flex items-center px-4 py-2 border text-sm font-medium bg-white border-gray-300 text-gray-500 cursor-not-allowed opacity-50"
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ),
                                        )}
                                    </nav>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
