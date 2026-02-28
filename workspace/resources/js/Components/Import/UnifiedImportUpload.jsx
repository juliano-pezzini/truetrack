import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import FileDropZone from './FileDropZone';
import OfxImportOptions from './OfxImportOptions';
import XlsxSimplifiedWizard from './XlsxSimplifiedWizard';

export default function UnifiedImportUpload({ accounts, onSuccess }) {
    const [selectedFile, setSelectedFile] = useState(null);
    const [fileType, setFileType] = useState(null); // 'ofx' or 'xlsx'
    const [showOptions, setShowOptions] = useState(false);

    const { data, setData, post, processing, reset } = useForm({
        file: null,
        account_id: '',
        force_reimport: false,
    });

    const detectFileType = (file) => {
        const extension = file.name.split('.').pop().toLowerCase();
        if (['ofx', 'qfx'].includes(extension)) {
            return 'ofx';
        } else if (['xlsx', 'xls', 'csv'].includes(extension)) {
            return 'xlsx';
        }
        return null;
    };

    const handleFileSelect = (file) => {
        const type = detectFileType(file);
        
        if (!type) {
            alert('Unsupported file type. Please select an OFX, QFX, XLSX, XLS, or CSV file.');
            return;
        }

        setSelectedFile(file);
        setFileType(type);
        setData('file', file);
        setShowOptions(true);
    };

    const handleCancel = () => {
        setSelectedFile(null);
        setFileType(null);
        setShowOptions(false);
        reset();
    };

    // OFX Import handlers
    const handleOfxSubmit = () => {
        post(route('api.ofx-imports.store'), {
            forceFormData: true,
            onSuccess: () => {
                handleCancel();
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                console.error('OFX import failed:', errors);
            },
        });
    };

    // XLSX Import handlers
    const handleXlsxComplete = (importData) => {
        // Import started successfully
        handleCancel();
        if (onSuccess) onSuccess();
    };

    return (
        <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            {/* Loading Overlay */}
            {processing && (
                <div className="absolute inset-0 z-50 flex items-center justify-center rounded-lg bg-white bg-opacity-90">
                    <div className="text-center">
                        <div className="mx-auto h-16 w-16 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
                        <p className="mt-4 text-sm font-medium text-gray-700">Processing...</p>
                    </div>
                </div>
            )}

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h2 className="text-xl font-semibold text-gray-900">
                        Import Bank Statement
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Upload your OFX/QFX or XLSX/CSV file to automatically import transactions
                    </p>
                </div>

                {/* File Drop Zone */}
                {!showOptions && (
                    <FileDropZone
                        onFileSelect={handleFileSelect}
                        selectedFile={selectedFile}
                        disabled={processing}
                    />
                )}

                {/* OFX Options */}
                {showOptions && fileType === 'ofx' && (
                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-6">
                        <div className="mb-4 flex items-center gap-2">
                            <svg
                                className="h-5 w-5 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span className="font-medium text-blue-900">
                                OFX/QFX File Detected
                            </span>
                        </div>
                        <OfxImportOptions
                            accounts={accounts}
                            selectedAccount={data.account_id}
                            onAccountChange={(value) => setData('account_id', value)}
                            forceReimport={data.force_reimport}
                            onForceReimportChange={(value) => setData('force_reimport', value)}
                            onSubmit={handleOfxSubmit}
                            onCancel={handleCancel}
                            processing={processing}
                        />
                    </div>
                )}

                {/* XLSX Wizard */}
                {showOptions && fileType === 'xlsx' && (
                    <div className="rounded-lg border border-purple-200 bg-purple-50 p-6">
                        <div className="mb-4 flex items-center gap-2">
                            <svg
                                className="h-5 w-5 text-purple-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                            <span className="font-medium text-purple-900">
                                XLSX/CSV File Detected
                            </span>
                        </div>
                        <XlsxSimplifiedWizard
                            file={selectedFile}
                            accounts={accounts}
                            selectedAccount={data.account_id}
                            onAccountChange={(value) => setData('account_id', value)}
                            onComplete={handleXlsxComplete}
                            onCancel={handleCancel}
                        />
                    </div>
                )}

                {/* Info Box */}
                {!showOptions && (
                    <div className="rounded-md bg-gray-50 p-4">
                        <h4 className="mb-2 text-sm font-medium text-gray-900">
                            Supported Formats:
                        </h4>
                        <ul className="space-y-1 text-sm text-gray-600">
                            <li className="flex items-start">
                                <svg
                                    className="mr-2 mt-0.5 h-4 w-4 text-green-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                                <span>
                                    <strong>OFX/QFX:</strong> Standard bank statement format - automatic import
                                </span>
                            </li>
                            <li className="flex items-start">
                                <svg
                                    className="mr-2 mt-0.5 h-4 w-4 text-green-500"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                                <span>
                                    <strong>XLSX/XLS/CSV:</strong> Spreadsheet format - requires column mapping
                                </span>
                            </li>
                        </ul>
                        <div className="mt-3 border-t border-gray-200 pt-3">
                            <a
                                href="/api/v1/xlsx-imports/template"
                                className="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                ⬇ Download XLSX Template
                            </a>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
