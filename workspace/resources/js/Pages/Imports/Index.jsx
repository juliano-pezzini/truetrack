import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import UnifiedImportUpload from '@/Components/Import/UnifiedImportUpload';
import ImportHistoryCard from '@/Components/Import/ImportHistoryCard';
import { buildPaginationItems } from './pagination';
import { useEffect } from 'react';

export default function Index({ auth, accounts, imports, filters }) {
    const { data: importList, meta } = imports;
    const paginationItems = buildPaginationItems(meta.current_page, meta.last_page);

    const hasActiveImports = importList.some(
        (imp) => imp.status === 'processing' || imp.status === 'pending'
    );

    // Auto-refresh if there are active imports
    useEffect(() => {
        if (!hasActiveImports) return;

        const interval = setInterval(() => {
            router.reload({ preserveScroll: true, only: ['imports'] });
        }, 5000); // Refresh every 5 seconds

        return () => clearInterval(interval);
    }, [hasActiveImports]);

    const buildParams = (overrides = {}) => {
        const merged = { ...filters, ...overrides };
        const params = {};
        if (merged.type) params['filter[type]'] = merged.type;
        if (merged.account_id) params['filter[account_id]'] = merged.account_id;
        if (merged.status) params['filter[status]'] = merged.status;
        return params;
    };

    const handleFilterChange = (key, value) => {
        router.get(route('imports.index'), buildParams({ [key]: value }), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleUploadSuccess = () => {
        router.reload({ preserveScroll: true });
    };

    const goToPage = (page) => {
        router.get(route('imports.index'), { ...buildParams(), page }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Statement Import
                </h2>
            }
        >
            <Head title="Import Statements" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Unified Upload Section */}
                    <UnifiedImportUpload
                        accounts={accounts}
                        onSuccess={handleUploadSuccess}
                    />

                    {/* Active Imports Alert */}
                    {hasActiveImports && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg
                                        className="h-5 w-5 text-blue-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm text-blue-700">
                                        Imports are being processed in the
                                        background. This page will auto-refresh
                                        every 5 seconds.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Import History */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Import History
                            </h3>
                            {meta.total > 0 && (
                                <span className="text-sm text-gray-500">
                                    {meta.from}–{meta.to} of {meta.total}
                                </span>
                            )}
                        </div>

                        {/* Filters */}
                        <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                    Type
                                </label>
                                <select
                                    className="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={filters.type}
                                    onChange={(e) => handleFilterChange('type', e.target.value)}
                                >
                                    <option value="">All Types</option>
                                    <option value="ofx">OFX / QFX</option>
                                    <option value="xlsx">XLSX / CSV</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                    Account
                                </label>
                                <select
                                    className="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={filters.account_id}
                                    onChange={(e) => handleFilterChange('account_id', e.target.value)}
                                >
                                    <option value="">All Accounts</option>
                                    {accounts.map((acc) => (
                                        <option key={acc.id} value={acc.id}>
                                            {acc.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                    Status
                                </label>
                                <select
                                    className="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={filters.status}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>

                        {/* Import List */}
                        <div className="space-y-4">
                            {importList.length === 0 ? (
                                <p className="py-8 text-center text-sm text-gray-500">
                                    No imports found.
                                </p>
                            ) : (
                                importList.map((importData) => (
                                    <ImportHistoryCard
                                        key={`${importData.type}-${importData.id}`}
                                        importData={importData}
                                        onDelete={handleUploadSuccess}
                                    />
                                ))
                            )}
                        </div>

                        {/* Pagination */}
                        {meta.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between border-t pt-4">
                                <button
                                    onClick={() => goToPage(meta.current_page - 1)}
                                    disabled={meta.current_page <= 1}
                                    className="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Previous
                                </button>

                                <div className="flex gap-1">
                                    {paginationItems.map((item) =>
                                        item.type === 'ellipsis' ? (
                                            <span
                                                key={item.key}
                                                className="min-w-[2rem] px-2 py-1.5 text-center text-sm font-medium text-gray-400"
                                            >
                                                ...
                                            </span>
                                        ) : (
                                            <button
                                                key={item.value}
                                                onClick={() => goToPage(item.value)}
                                                className={`min-w-[2rem] rounded-md px-2 py-1.5 text-sm font-medium ${
                                                    item.value === meta.current_page
                                                        ? 'bg-indigo-600 text-white'
                                                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                                }`}
                                            >
                                                {item.value}
                                            </button>
                                        )
                                    )}
                                </div>

                                <button
                                    onClick={() => goToPage(meta.current_page + 1)}
                                    disabled={meta.current_page >= meta.last_page}
                                    className="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Next
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
