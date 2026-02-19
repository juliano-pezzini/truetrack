import { useState, useEffect } from 'react';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import XlsxColumnMapper from '@/Components/XlsxImport/XlsxColumnMapper';
import XlsxPreviewTable from '@/Components/XlsxImport/XlsxPreviewTable';

export default function XlsxSimplifiedWizard({ 
    file,
    accounts, 
    selectedAccount,
    onAccountChange,
    onComplete,
    onCancel 
}) {
    const [step, setStep] = useState(1); // 1: Upload + Mapping, 2: Preview + Confirm
    const [detectedHeaders, setDetectedHeaders] = useState([]);
    const [suggestedMapping, setSuggestedMapping] = useState(null);
    const [mappingConfig, setMappingConfig] = useState({});
    const [previewData, setPreviewData] = useState(null);
    const [validationSummary, setValidationSummary] = useState(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [error, setError] = useState(null);
    const [saveMapping, setSaveMapping] = useState(true);
    const [mappingName, setMappingName] = useState('');
    const [forceReimport, setForceReimport] = useState(false);

    // Auto-detect columns when component mounts
    useEffect(() => {
        if (file) {
            detectColumns();
        }
    }, [file]);

    const detectColumns = async () => {
        setIsProcessing(true);
        setError(null);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await axios.post('/api/v1/xlsx-imports/detect-columns', formData);
            setDetectedHeaders(response.data.data.headers);
            setSuggestedMapping(response.data.data.suggested_mapping);
        } catch (err) {
            console.error('Column detection failed:', err);
            setError('Failed to detect columns. Please try again.');
        } finally {
            setIsProcessing(false);
        }
    };

    const handleMappingConfirmed = async (config) => {
        setMappingConfig(config);
        setError(null);
        setIsProcessing(true);

        // Get preview
        const formData = new FormData();
        formData.append('file', file);

        Object.keys(config).forEach(key => {
            const value = config[key];
            if (value !== null && value !== undefined && value !== '') {
                if (typeof value === 'object') {
                    formData.append(`mapping_config[${key}]`, JSON.stringify(value));
                } else {
                    formData.append(`mapping_config[${key}]`, value);
                }
            }
        });

        try {
            const response = await axios.post('/api/v1/xlsx-imports/preview', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            setPreviewData(response.data.data.preview_transactions);
            setValidationSummary(response.data.data.validation_summary);
            setStep(2);
        } catch (err) {
            console.error('Preview failed:', err);
            setError('Failed to generate preview. Please check your mapping configuration.');
        } finally {
            setIsProcessing(false);
        }
    };

    const handleConfirmImport = async () => {
        setError(null);
        setIsProcessing(true);

        // Build FormData with all import configuration
        const formData = new FormData();
        formData.append('file', file);
        formData.append('account_id', selectedAccount);

        // Append mapping_config fields
        Object.keys(mappingConfig).forEach(key => {
            const value = mappingConfig[key];
            if (value !== null && value !== undefined && value !== '') {
                if (typeof value === 'object') {
                    formData.append(`mapping_config[${key}]`, JSON.stringify(value));
                } else {
                    formData.append(`mapping_config[${key}]`, value);
                }
            }
        });

        // Optional fields
        if (saveMapping) {
            formData.append('save_mapping', '1');
            if (mappingName) {
                formData.append('mapping_name', mappingName);
            }
        }

        if (forceReimport) {
            formData.append('force', '1');
        }

        try {
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            
            const response = await axios.post('/api/v1/xlsx-imports', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            // Success! Return the import data
            onComplete(response.data.data);
        } catch (err) {
            console.error('Import submission failed:', err);
            
            if (err.response?.data?.error === 'DUPLICATE_IMPORT') {
                setError('This file has already been imported. Check "Force Reimport" to override.');
            } else if (err.response?.status === 403) {
                setError('Access denied. Please make sure you are logged in and have permission to import.');
            } else if (err.response?.status === 422) {
                // Validation errors
                const errors = err.response?.data?.errors || {};
                const errorMessages = Object.entries(errors)
                    .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                    .join(' | ');
                setError(`Validation failed: ${errorMessages}`);
            } else {
                setError(err.response?.data?.message || 'Failed to start import. Please try again.');
            }
        } finally {
            setIsProcessing(false);
        }
    };

    const handleBackToMapping = () => {
        setStep(1);
        setPreviewData(null);
        setValidationSummary(null);
    };

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold text-gray-900">
                    Import XLSX/CSV Statement
                </h3>
                <p className="mt-1 text-sm text-gray-600">
                    Step {step} of 2
                </p>
            </div>

            {error && (
                <div className="rounded-md bg-red-50 p-4">
                    <p className="text-sm text-red-800">{error}</p>
                </div>
            )}

            {isProcessing && step === 1 && (
                <div className="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                    <div className="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
                    <p className="mt-4 text-sm font-medium text-gray-700">
                        Analyzing your file...
                    </p>
                </div>
            )}

            {/* Step 1: Account + Mapping */}
            {step === 1 && !isProcessing && (
                <div className="space-y-6">
                    {/* Account Selection */}
                    <div>
                        <InputLabel htmlFor="account_id" value="Account" />
                        <select
                            id="account_id"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            value={selectedAccount}
                            onChange={(e) => onAccountChange(e.target.value)}
                            required
                        >
                            <option value="">Select an account</option>
                            {accounts.map((account) => (
                                <option key={account.id} value={account.id}>
                                    {account.name} ({account.type})
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Column Mapper */}
                    {detectedHeaders.length > 0 && (
                        <div className="rounded-lg border border-gray-200 bg-white p-6">
                            <XlsxColumnMapper
                                headers={detectedHeaders}
                                suggestedMapping={suggestedMapping}
                                accountId={selectedAccount}
                                onMappingConfirmed={handleMappingConfirmed}
                                onBack={onCancel}
                            />
                        </div>
                    )}
                </div>
            )}

            {/* Step 2: Preview + Confirm */}
            {step === 2 && (
                <div className="space-y-6">
                    {/* Options */}
                    <div className="rounded-lg border border-gray-200 bg-white p-6">
                        <h4 className="mb-4 text-base font-semibold text-gray-900">
                            Import Options
                        </h4>

                        <div className="space-y-4">
                            {/* Save Mapping */}
                            <div>
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="save_mapping"
                                        checked={saveMapping}
                                        onChange={(e) => setSaveMapping(e.target.checked)}
                                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <label htmlFor="save_mapping" className="ml-2 block text-sm text-gray-700">
                                        Save this column mapping for future imports
                                    </label>
                                </div>
                                {saveMapping && (
                                    <input
                                        type="text"
                                        placeholder="Mapping name (optional)"
                                        value={mappingName}
                                        onChange={(e) => setMappingName(e.target.value)}
                                        className="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                )}
                            </div>

                            {/* Force Reimport */}
                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    id="force_reimport"
                                    checked={forceReimport}
                                    onChange={(e) => setForceReimport(e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label htmlFor="force_reimport" className="ml-2 block text-sm text-gray-700">
                                    Force reimport (ignore duplicate check)
                                </label>
                            </div>

                            {/* Info Box */}
                            <div className="rounded-md bg-blue-50 p-3">
                                <p className="text-xs text-blue-800">
                                    <strong>Note:</strong> A reconciliation will be created automatically.
                                    The system will attempt to match existing transactions (100% confidence required for auto-match).
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Preview Table */}
                    <div className="rounded-lg border border-gray-200 bg-white p-6">
                        {previewData && (
                            <XlsxPreviewTable
                                previewData={previewData}
                                validationSummary={validationSummary}
                                onConfirm={handleConfirmImport}
                                onBack={handleBackToMapping}
                            />
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
