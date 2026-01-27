import { useState } from 'react';
import { useForm } from '@inertiajs/react';
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
    const [previewData, setPreviewData] = useState(null);
    const [validationSummary, setValidationSummary] = useState(null);

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

        setData('xlsx_file', file);

        // Auto-detect columns
        const formData = new FormData();
        formData.append('xlsx_file', file);

        try {
            const response = await axios.post('/api/v1/xlsx-imports/detect-columns', formData);
            setDetectedHeaders(response.data.data.headers);
            setSuggestedMapping(response.data.data.suggested_mapping);
            setStep(2);
        } catch (error) {
            console.error('Column detection failed:', error);
        }
    };

    const handleMappingConfirmed = async (mappingConfig) => {
        setData('mapping_config', mappingConfig);

        // Get preview
        const formData = new FormData();
        formData.append('xlsx_file', data.xlsx_file);
        formData.append('mapping_config', JSON.stringify(mappingConfig));

        try {
            const response = await axios.post('/api/v1/xlsx-imports/preview', formData);
            setPreviewData(response.data.data.preview_transactions);
            setValidationSummary(response.data.data.validation_summary);
            setStep(3);
        } catch (error) {
            console.error('Preview failed:', error);
        }
    };

    const handlePreviewConfirm = () => {
        setStep(4);
    };

    const handleFinalSubmit = (e) => {
        e.preventDefault();

        post(route('api.v1.xlsx-imports.store'), {
            forceFormData: true,
            onSuccess: (response) => {
                if (onImportStarted) {
                    onImportStarted(response.data.import_id);
                }
                reset();
                setStep(1);
                setDetectedHeaders([]);
                setSuggestedMapping(null);
                setPreviewData(null);
            },
        });
    };

    const handleBack = () => {
        setStep(step - 1);
    };

    const handleDownloadTemplate = () => {
        window.open('/api/v1/xlsx-imports/template', '_blank');
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-semibold">Import from XLSX/CSV</h2>
                    <p className="text-sm text-gray-600 mt-1">
                        Active imports: {activeImportsCount} of {maxImports}
                    </p>
                </div>
                <SecondaryButton onClick={handleDownloadTemplate}>
                    Download Template
                </SecondaryButton>
            </div>

            {/* Step 1: File Upload & Account Selection */}
            {step === 1 && (
                <div className="bg-white rounded-lg shadow p-6 space-y-4">
                    <div>
                        <InputLabel htmlFor="account_id" value="Account *" />
                        <select
                            id="account_id"
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
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
                            className="mt-1 block w-full"
                            accept=".xlsx,.xls,.csv"
                            onChange={handleFileSelect}
                            disabled={!data.account_id || concurrencyLimitReached}
                        />
                        <InputError message={errors.xlsx_file} className="mt-2" />
                        {concurrencyLimitReached && (
                            <p className="mt-2 text-sm text-red-600">
                                You have reached the maximum number of concurrent imports. Please wait for existing imports to complete.
                            </p>
                        )}
                    </div>
                </div>
            )}

            {/* Step 2: Column Mapping */}
            {step === 2 && (
                <div className="bg-white rounded-lg shadow p-6">
                    <XlsxColumnMapper
                        headers={detectedHeaders}
                        suggestedMapping={suggestedMapping}
                        accountId={data.account_id}
                        onMappingConfirmed={handleMappingConfirmed}
                        onBack={handleBack}
                    />
                </div>
            )}

            {/* Step 3: Preview */}
            {step === 3 && (
                <div className="bg-white rounded-lg shadow p-6">
                    <XlsxPreviewTable
                        previewData={previewData}
                        validationSummary={validationSummary}
                        onConfirm={handlePreviewConfirm}
                        onBack={handleBack}
                    />
                </div>
            )}

            {/* Step 4: Reconciliation Options & Confirm */}
            {step === 4 && (
                <div className="bg-white rounded-lg shadow p-6 space-y-4">
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
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
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
