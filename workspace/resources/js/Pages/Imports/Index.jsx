import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import OfxImportUpload from '@/Components/OfxImport/OfxImportUpload';
import XlsxImportUpload from '@/Components/XlsxImport/XlsxImportUpload';
import { useState, useEffect } from 'react';

export default function Index({ auth, accounts, imports }) {
    const [fileType, setFileType] = useState(null);
    const [activeTab, setActiveTab] = useState('all');

    const handleUploadSuccess = () => {
        // Refresh the data while preserving the SPA experience
        router.reload({ preserveScroll: true });
    };

    // Filter imports based on active tab
    const filteredImports = imports.filter((imp) => {
        if (activeTab === 'all') return true;
        if (activeTab === 'ofx') return imp.type === 'ofx';
        if (activeTab === 'xlsx') return imp.type === 'xlsx';
        return true;
    });

    // Count imports by type
    const ofxCount = imports.filter((imp) => imp.type === 'ofx').length;
    const xlsxCount = imports.filter((imp) => imp.type === 'xlsx').length;

    // Check for active imports
    const hasActiveImports = imports.some(
        (imp) => imp.status === 'processing' || imp.status === 'pending'
    );

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
                    {/* OFX/QFX Upload Section */}
                    <OfxImportUpload
                        accounts={accounts}
                        onSuccess={handleUploadSuccess}
                    />

                    {/* XLSX/CSV Upload Section */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <XlsxImportUpload
                            accounts={accounts}
                            activeImportsCount={imports.filter(
                                (imp) =>
                                    imp.status === 'processing' ||
                                    imp.status === 'pending'
                            ).length}
                            maxImports={5}
                            onImportStarted={handleUploadSuccess}
                        />
                    </div>

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

                    {/* Import History with Tabs */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Import History
                            </h3>
                        </div>

                        {/* Tabs */}
                        <div className="mb-4 border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    onClick={() => setActiveTab('all')}
                                    className={`${
                                        activeTab === 'all'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                    } whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
                                >
                                    All Imports ({imports.length})
                                </button>
                                <button
                                    onClick={() => setActiveTab('ofx')}
                                    className={`${
                                        activeTab === 'ofx'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                    } whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
                                >
                                    OFX Imports ({ofxCount})
                                </button>
                                <button
                                    onClick={() => setActiveTab('xlsx')}
                                    className={`${
                                        activeTab === 'xlsx'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                    } whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
                                >
                                    XLSX/CSV Imports ({xlsxCount})
                                </button>
                            </nav>
                        </div>

                        {/* Import List */}
                        <div className="space-y-4">
                            {filteredImports.length === 0 ? (
                                <p className="py-8 text-center text-sm text-gray-500">
                                    No imports found.
                                </p>
                            ) : (
                                filteredImports.map((importData) => (
                                    <div
                                        key={`${importData.type}-${importData.id}`}
                                        className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                                    >
                                        <div className="space-y-2">
                                            {/* Header with type badge */}
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <h4 className="font-semibold text-gray-900">
                                                            {importData.filename}
                                                        </h4>
                                                        <span
                                                            className={`rounded px-2 py-0.5 text-xs font-medium ${
                                                                importData.type === 'ofx'
                                                                    ? 'bg-blue-100 text-blue-800'
                                                                    : 'bg-green-100 text-green-800'
                                                            }`}
                                                        >
                                                            {importData.type === 'ofx' ? 'OFX' : 'XLSX/CSV'}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-gray-600">
                                                        {importData.account?.name || 'Unknown Account'}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {new Date(importData.created_at).toLocaleString()}
                                                    </p>
                                                </div>
                                                {/* Status badge */}
                                                <span
                                                    className={`rounded px-3 py-1 text-xs font-semibold ${
                                                        importData.status === 'completed'
                                                            ? 'bg-green-100 text-green-800'
                                                            : importData.status === 'processing'
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : importData.status === 'failed'
                                                            ? 'bg-red-100 text-red-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                    }`}
                                                >
                                                    {importData.status}
                                                </span>
                                            </div>

                                            {/* Progress/Stats */}
                                            {importData.status === 'processing' && (
                                                <div className="mt-2">
                                                    <div className="mb-1 flex justify-between text-xs text-gray-600">
                                                        <span>Processing...</span>
                                                        <span>{importData.progress}%</span>
                                                    </div>
                                                    <div className="h-2 overflow-hidden rounded-full bg-gray-200">
                                                        <div
                                                            className="h-full bg-blue-600 transition-all"
                                                            style={{ width: `${importData.progress}%` }}
                                                        ></div>
                                                    </div>
                                                </div>
                                            )}

                                            {importData.status === 'completed' && (
                                                <div className="text-sm text-gray-600">
                                                    <span>Processed: {importData.processed_count || 0}</span>
                                                    {importData.type === 'ofx' && importData.matched_count !== undefined && (
                                                        <span className="ml-4">
                                                            Matched: {importData.matched_count}
                                                        </span>
                                                    )}
                                                    {importData.type === 'xlsx' && (
                                                        <>
                                                            {importData.skipped_count > 0 && (
                                                                <span className="ml-4 text-yellow-600">
                                                                    Skipped: {importData.skipped_count}
                                                                </span>
                                                            )}
                                                            {importData.duplicate_count > 0 && (
                                                                <span className="ml-4 text-orange-600">
                                                                    Duplicates: {importData.duplicate_count}
                                                                </span>
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                            )}

                                            {importData.status === 'failed' && importData.error_message && (
                                                <div className="rounded bg-red-50 p-2 text-sm text-red-700">
                                                    {importData.error_message}
                                                </div>
                                            )}

                                            {/* Actions */}
                                            {importData.reconciliation_id && (
                                                <div className="mt-2">
                                                    <a
                                                        href={route('reconciliations.show', importData.reconciliation_id)}
                                                        className="text-sm text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        View Reconciliation →
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
