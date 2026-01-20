import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import OfxImportUpload from '@/Components/OfxImport/OfxImportUpload';
import OfxImportList from '@/Components/OfxImport/OfxImportList';

export default function Index({ auth, accounts, imports }) {
    const handleUploadSuccess = () => {
        // Refresh the page to show the new import
        window.location.reload();
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    OFX Import
                </h2>
            }
        >
            <Head title="OFX Import" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Upload Section */}
                    <OfxImportUpload
                        accounts={accounts}
                        onSuccess={handleUploadSuccess}
                    />

                    {/* Active Imports Alert */}
                    {imports.some(
                        (imp) =>
                            imp.status === 'processing' ||
                            imp.status === 'pending'
                    ) && (
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
                        <h3 className="mb-4 text-lg font-semibold text-gray-900">
                            Import History
                        </h3>
                        <OfxImportList initialImports={imports} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
