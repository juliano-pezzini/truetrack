import { useState } from 'react';
import { router } from '@inertiajs/react';
import SecondaryButton from '@/Components/SecondaryButton';

export default function XlsxImportHistory({ imports, accounts }) {
    const [filters, setFilters] = useState({
        account_id: '',
        status: '',
        date_from: '',
        date_to: '',
    });

    const [expandedRows, setExpandedRows] = useState(new Set());

    const handleFilterChange = (key, value) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);

        // Apply filters via Inertia
        const params = {};
        Object.keys(newFilters).forEach((k) => {
            if (newFilters[k]) {
                params[`filter[${k}]`] = newFilters[k];
            }
        });

        router.get(route('xlsx-imports.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const toggleRowExpansion = (importId) => {
        setExpandedRows((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(importId)) {
                newSet.delete(importId);
            } else {
                newSet.add(importId);
            }
            return newSet;
        });
    };

    const getStatusBadge = (status) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800',
            processing: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };

        return (
            <span className={`px-2 py-1 rounded text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    const handleDownloadFile = (importId) => {
        window.open(`/api/v1/xlsx-imports/${importId}/download`, '_blank');
    };

    const handleDownloadErrorReport = (importId) => {
        window.open(`/api/v1/xlsx-imports/${importId}/error-report`, '_blank');
    };

    return (
        <div className="space-y-6">
            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-4">
                <h3 className="font-semibold mb-4">Filters</h3>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Account</label>
                        <select
                            className="block w-full border-gray-300 rounded-md shadow-sm"
                            value={filters.account_id}
                            onChange={(e) => handleFilterChange('account_id', e.target.value)}
                        >
                            <option value="">All Accounts</option>
                            {accounts.map((account) => (
                                <option key={account.id} value={account.id}>
                                    {account.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            className="block w-full border-gray-300 rounded-md shadow-sm"
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

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            type="date"
                            className="block w-full border-gray-300 rounded-md shadow-sm"
                            value={filters.date_from}
                            onChange={(e) => handleFilterChange('date_from', e.target.value)}
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            type="date"
                            className="block w-full border-gray-300 rounded-md shadow-sm"
                            value={filters.date_to}
                            onChange={(e) => handleFilterChange('date_to', e.target.value)}
                        />
                    </div>
                </div>
            </div>

            {/* Imports Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Filename
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Account
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Progress
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {imports.data.map((importRecord) => (
                            <>
                                <tr key={importRecord.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        {new Date(importRecord.created_at).toLocaleDateString()}
                                    </td>
                                    <td className="px-6 py-4 text-sm">
                                        <button
                                            onClick={() => toggleRowExpansion(importRecord.id)}
                                            className="text-blue-600 hover:text-blue-800 hover:underline"
                                        >
                                            {importRecord.filename}
                                        </button>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        {importRecord.account?.name || '—'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        {getStatusBadge(importRecord.status)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        {importRecord.processed_count} / {importRecord.total_count}
                                        {importRecord.skipped_count > 0 && (
                                            <span className="ml-2 text-yellow-600">
                                                ({importRecord.skipped_count} skipped)
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <SecondaryButton onClick={() => handleDownloadFile(importRecord.id)}>
                                            Download
                                        </SecondaryButton>
                                        {importRecord.has_errors && (
                                            <SecondaryButton onClick={() => handleDownloadErrorReport(importRecord.id)}>
                                                Errors
                                            </SecondaryButton>
                                        )}
                                    </td>
                                </tr>

                                {/* Expanded Row Details */}
                                {expandedRows.has(importRecord.id) && (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-4 bg-gray-50">
                                            <div className="grid grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <span className="font-medium">Processed:</span> {importRecord.processed_count}
                                                </div>
                                                <div>
                                                    <span className="font-medium">Skipped:</span> {importRecord.skipped_count}
                                                </div>
                                                <div>
                                                    <span className="font-medium">Duplicates:</span> {importRecord.duplicate_count}
                                                </div>
                                                {importRecord.reconciliation_id && (
                                                    <div className="col-span-3">
                                                        <span className="font-medium">Reconciliation:</span>
                                                        <a
                                                            href={`/reconciliations/${importRecord.reconciliation_id}`}
                                                            className="ml-2 text-blue-600 hover:underline"
                                                        >
                                                            View Reconciliation
                                                        </a>
                                                    </div>
                                                )}
                                                {importRecord.error_message && (
                                                    <div className="col-span-3 text-red-600">
                                                        <span className="font-medium">Error:</span> {importRecord.error_message}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </>
                        ))}
                    </tbody>
                </table>

                {/* Pagination */}
                {imports.meta && (
                    <div className="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div className="flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Showing {imports.meta.from} to {imports.meta.to} of {imports.meta.total} results
                            </div>
                            <div className="flex space-x-2">
                                {imports.links.prev && (
                                    <SecondaryButton onClick={() => router.visit(imports.links.prev)}>
                                        Previous
                                    </SecondaryButton>
                                )}
                                {imports.links.next && (
                                    <SecondaryButton onClick={() => router.visit(imports.links.next)}>
                                        Next
                                    </SecondaryButton>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
