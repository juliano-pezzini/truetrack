import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import XlsxColumnMapper from './XlsxColumnMapper';
import XlsxPreviewTable from './XlsxPreviewTable';
import ReconciliationOptionsPanel from './ReconciliationOptionsPanel';

export default function XlsxImportUpload({ accounts, activeImportsCount, maxImports, onImportStarted }) {
    const [step, setStep] = useState(1); // 1: Upload, 2: Map, 3: Preview, 4: Confirm
    const [detectedHeaders, setDetectedHeaders] = useState([]);
    const [suggestedMapping, setSuggestedMapping] = useState(null);
    const [detectedNumberFormat, setDetectedNumberFormat] = useState('us');
    const [formatConfidence, setFormatConfidence] = useState(0);
    const [previewData, setPreviewData] = useState(null);
    const [validationSummary, setValidationSummary] = useState(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [duplicateImportId, setDuplicateImportId] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        account_id: '',
        xlsx_file: null,
        mapping_config: {},
        save_mapping: true,
        mapping_name: '',
        create_reconciliation: false,
        statement_date: '',
        statement_balance: '',
    });

    const concurrencyLimitReached = activeImportsCount >= maxImports;

    const handleFileSelect = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setData('file', file);
        setSubmitError(null);
        setDuplicateImportId(null);
        setIsProcessing(true);

        // Auto-detect columns
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await axios.post('/api/v1/xlsx-imports/detect-columns', formData);
            setDetectedHeaders(response.data.data.headers);
            setSuggestedMapping(response.data.data.suggested_mapping);
            setDetectedNumberFormat(response.data.data.detected_number_format || 'us');
            setFormatConfidence(response.data.data.format_confidence || 0);
            setStep(2);
        } catch (error) {
            console.error('Column detection failed:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleMappingConfirmed = async (mappingConfig) => {
        setData('mapping_config', mappingConfig);
        setSubmitError(null);
        setDuplicateImportId(null);
        setIsProcessing(true);

        // Get preview - send as FormData with proper structure
        const formData = new FormData();
        formData.append('file', data.file);

        // Append mapping_config fields individually for proper Laravel validation
        // Only append non-null, non-empty values
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

        try {
            const response = await axios.post('/api/v1/xlsx-imports/preview', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            setPreviewData(response.data.data.preview_transactions);
            setValidationSummary(response.data.data.validation_summary);
            setStep(3);
        } catch (error) {
            console.error('Preview failed:', error);
            if (error.response?.data?.errors) {
                console.error('Validation errors:', error.response.data.errors);
            }
        } finally {
            setIsProcessing(false);
        }
    };

    const handlePreviewConfirm = () => {
        setStep(4);
    };

    const submitImport = async (force = false) => {
        setSubmitError(null);
        if (!force) {
            setDuplicateImportId(null);
        }
        setIsProcessing(true);

        // Build FormData with all import configuration
        const formData = new FormData();
        formData.append('file', data.file);
        formData.append('account_id', data.account_id);

        // Append mapping_config fields
        // Only append non-null, non-empty values
        Object.keys(data.mapping_config).forEach(key => {
            const value = data.mapping_config[key];
            if (value !== null && value !== undefined && value !== '') {
                if (typeof value === 'object') {
                    formData.append(`mapping_config[${key}]`, JSON.stringify(value));
                } else {
                    formData.append(`mapping_config[${key}]`, value);
                }
            }
        });

        // Optional fields
        if (data.save_mapping) {
            formData.append('save_mapping', '1');
            if (data.mapping_name) {
                formData.append('mapping_name', data.mapping_name);
            }
        }

        if (data.create_reconciliation) {
            formData.append('create_reconciliation', '1');
            if (data.statement_date) {
                formData.append('statement_date', data.statement_date);
            }
            if (data.statement_balance) {
                formData.append('statement_balance', data.statement_balance);
            }
        }

        if (force) {
            formData.append('force', '1');
        }

        try {
            const response = await axios.post('/api/v1/xlsx-imports', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            if (onImportStarted) {
                onImportStarted(response.data.data.id);
            }

            // Reset form
            reset();
            setStep(1);
            setDetectedHeaders([]);
            setSuggestedMapping(null);
            setPreviewData(null);
        } catch (error) {
            console.error('Import submission failed:', error);
            if (error.response?.status === 409 && error.response?.data?.requires_confirmation) {
                setSubmitError(error.response.data.message || 'This file has already been imported for this account.');
                setDuplicateImportId(error.response.data.duplicate_import_id ?? null);
            } else if (error.response?.data?.errors) {
                console.error('Validation errors:', error.response.data.errors);
                setSubmitError('Import validation failed. Please review your inputs and try again.');
            } else {
                setSubmitError('Import submission failed. Please try again.');
            }
        } finally {
            setIsProcessing(false);
        }
    };

    const handleFinalSubmit = async (e) => {
        e.preventDefault();
        await submitImport(false);
    };

    const handleForceReimport = async () => {
        await submitImport(true);
    };

    const handleBack = () => {
        setStep(step - 1);
    };

    const handleDownloadTemplate = () => {
        window.open('/api/v1/xlsx-imports/template', '_blank');
    };

    return (
        <div className="relative space-y-4">
            {/* Loading Overlay */}
            {(isProcessing || processing) && (
                <div className="absolute inset-0 z-50 flex items-center justify-center rounded-lg bg-white bg-opacity-90">
                    <div className="text-center">
                        <div className="mx-auto h-16 w-16 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
                        <p className="mt-4 text-sm font-medium text-gray-700">Processing...</p>
                    </div>
                </div>
            )}

            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-semibold text-gray-900">
                        Upload XLSX/CSV Statement
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">
                        Import transactions from spreadsheet files with flexible column mapping
                    </p>
                </div>
                <SecondaryButton onClick={handleDownloadTemplate}>
                    Download Template
                </SecondaryButton>
            </div>

            {/* Concurrency Warning */}
            {concurrencyLimitReached && (
                <div className="rounded-md bg-yellow-50 p-4">
                    <p className="text-sm text-yellow-700">
                        You have reached the maximum number of concurrent imports ({maxImports}).
                        Please wait for existing imports to complete.
                    </p>
                </div>
            )}

            {/* Submission Error */}
            {submitError && (
                <div className="rounded-md bg-red-50 p-4 border border-red-200">
                    <p className="text-sm text-red-700">{submitError}</p>
                    {duplicateImportId && (
                        <div className="mt-2 space-y-2">
                            <p className="text-xs text-red-600">
                                Existing import ID: {duplicateImportId}. Check import history for details.
                            </p>
                            <PrimaryButton onClick={handleForceReimport} disabled={isProcessing || processing}>
                                Force reimport
                            </PrimaryButton>
                        </div>
                    )}
                </div>
            )}

            {/* Step 1: File Upload & Account Selection */}
            {step === 1 && (
                <div className="space-y-4">
                    <div>
                        <InputLabel htmlFor="account_id" value="Account *" />
                        <select
                            id="account_id"
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            value={data.account_id}
                            onChange={(e) => setData('account_id', e.target.value)}
                        >
                            <option value="">Select an account</option>
                            {accounts.map((account) => (
                                <option key={account.id} value={account.id}>
                                    {account.name} ({account.type})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.account_id} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="xlsx_file" value="XLSX/CSV File *" />
                        <input
                            id="xlsx_file"
                            type="file"
                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            accept=".xlsx,.xls,.csv"
                            onChange={handleFileSelect}
                            disabled={!data.account_id || concurrencyLimitReached}
                        />
                        <p className="mt-2 text-sm text-gray-500">
                            Supported formats: .xlsx, .xls, .csv (max 10MB)
                        </p>
                        <InputError message={errors.xlsx_file} className="mt-2" />
                    </div>

                    {!data.account_id && (
                        <p className="text-sm text-gray-500 italic">
                            Please select an account first, then choose a file to begin the import process.
                        </p>
                    )}
                </div>
            )}

            {/* Step 2: Column Mapping */}
            {step === 2 && (
                <XlsxColumnMapper
                    headers={detectedHeaders}
                    suggestedMapping={suggestedMapping}
                    detectedNumberFormat={detectedNumberFormat}
                    formatConfidence={formatConfidence}
                    accountId={data.account_id}
                    onMappingConfirmed={handleMappingConfirmed}
                    onBack={handleBack}
                />
            )}

            {/* Step 3: Preview */}
            {step === 3 && (
                <XlsxPreviewTable
                    previewData={previewData}
                    validationSummary={validationSummary}
                    onConfirm={handlePreviewConfirm}
                    onBack={handleBack}
                />
            )}

            {/* Step 4: Reconciliation Options & Confirm */}
            {step === 4 && (
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold">Import Options</h3>

                    <div>
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={data.save_mapping}
                                onChange={(e) => setData('save_mapping', e.target.checked)}
                                className="rounded border-gray-300"
                            />
                            <span className="ml-2 text-sm">Save column mapping for future imports</span>
                        </label>

                        {data.save_mapping && (
                            <div className="mt-2 ml-6">
                                <InputLabel htmlFor="mapping_name" value="Mapping Name" />
                                <input
                                    id="mapping_name"
                                    type="text"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.mapping_name}
                                    onChange={(e) => setData('mapping_name', e.target.value)}
                                    placeholder="e.g., Chase Bank Format"
                                />
                                <InputError message={errors.mapping_name} className="mt-2" />
                            </div>
                        )}
                    </div>

                    <ReconciliationOptionsPanel
                        enabled={data.create_reconciliation}
                        onEnabledChange={(enabled) => setData('create_reconciliation', enabled)}
                        statementDate={data.statement_date}
                        onStatementDateChange={(date) => setData('statement_date', date)}
                        statementBalance={data.statement_balance}
                        onStatementBalanceChange={(balance) => setData('statement_balance', balance)}
                        errors={errors}
                    />

                    <div className="flex justify-between pt-4">
                        <SecondaryButton onClick={handleBack}>
                            Back
                        </SecondaryButton>
                        <PrimaryButton onClick={handleFinalSubmit} disabled={processing}>
                            {processing ? 'Importing...' : 'Start Import'}
                        </PrimaryButton>
                    </div>
                </div>
            )}
        </div>
    );
}
