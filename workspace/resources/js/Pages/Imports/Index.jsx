import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import UnifiedImportUpload from '@/Components/Import/UnifiedImportUpload';
import ImportHistoryCard from '@/Components/Import/ImportHistoryCard';
import { useState, useEffect } from 'react';

export default function Index({ auth, accounts, imports }) {
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

    // Auto-refresh if there are active imports
    useEffect(() => {
        if (!hasActiveImports) return;

        const interval = setInterval(() => {
            router.reload({ preserveScroll: true, only: ['imports'] });
        }, 5000); // Refresh every 5 seconds

        return () => clearInterval(interval);
    }, [hasActiveImports]);

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
                                    <ImportHistoryCard
                                        key={`${importData.type}-${importData.id}`}
                                        importData={importData}
                                        type={importData.type}
                                        onDelete={handleUploadSuccess}
                                    />
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
