import { useState, useEffect } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import SavedMappingSelector from './SavedMappingSelector';

const TRANSACTION_FIELDS = [
    { key: 'date_column', label: 'Transaction Date', required: true },
    { key: 'description_column', label: 'Description', required: true },
    { key: 'amount_column', label: 'Amount', required: false },
    { key: 'debit_column', label: 'Debit', required: false },
    { key: 'credit_column', label: 'Credit', required: false },
    { key: 'type_column', label: 'Type', required: false },
    { key: 'category_column', label: 'Category', required: false },
    { key: 'settled_date_column', label: 'Settled Date', required: false },
    { key: 'tags_column', label: 'Tags', required: false },
];

const AMOUNT_STRATEGIES = [
    { value: 'single', label: 'Single Amount Column (negative = debit)', description: 'One column with positive/negative values' },
    { value: 'separate', label: 'Separate Debit/Credit Columns', description: 'Two columns: one for debits, one for credits' },
    { value: 'type_column', label: 'Amount + Type Column', description: 'Amount column with separate type indicator' },
];

export default function XlsxColumnMapper({ headers, suggestedMapping, detectedNumberFormat, formatConfidence, accountId, onMappingConfirmed, onBack }) {
    const [mappingConfig, setMappingConfig] = useState({
        date_column: suggestedMapping?.date_column || '',
        description_column: suggestedMapping?.description_column || '',
        amount_strategy: suggestedMapping?.amount_strategy || 'single',
        amount_column: suggestedMapping?.amount_column || '',
        debit_column: suggestedMapping?.debit_column || '',
        credit_column: suggestedMapping?.credit_column || '',
        type_column: suggestedMapping?.type_column || '',
        category_column: suggestedMapping?.category_column || '',
        settled_date_column: suggestedMapping?.settled_date_column || '',
        tags_column: suggestedMapping?.tags_column || '',
        number_format: detectedNumberFormat || 'us',
    });

    const [errors, setErrors] = useState({});

    useEffect(() => {
        validateMapping();
    }, [mappingConfig]);

    const validateMapping = () => {
        const newErrors = {};

        if (!mappingConfig.date_column) {
            newErrors.date_column = 'Transaction Date is required';
        }

        if (!mappingConfig.description_column) {
            newErrors.description_column = 'Description is required';
        }

        // Validate based on amount strategy
        if (mappingConfig.amount_strategy === 'single') {
            if (!mappingConfig.amount_column) {
                newErrors.amount_column = 'Amount column is required for single column strategy';
            }
        } else if (mappingConfig.amount_strategy === 'separate') {
            if (!mappingConfig.debit_column) {
                newErrors.debit_column = 'Debit column is required for separate columns strategy';
            }
            if (!mappingConfig.credit_column) {
                newErrors.credit_column = 'Credit column is required for separate columns strategy';
            }
        } else if (mappingConfig.amount_strategy === 'type_column') {
            if (!mappingConfig.amount_column) {
                newErrors.amount_column = 'Amount column is required for type column strategy';
            }
            if (!mappingConfig.type_column) {
                newErrors.type_column = 'Type column is required for type column strategy';
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleFieldChange = (fieldKey, value) => {
        setMappingConfig((prev) => ({
            ...prev,
            [fieldKey]: value,
        }));
    };

    const handleStrategyChange = (strategy) => {
        setMappingConfig((prev) => ({
            ...prev,
            amount_strategy: strategy,
        }));
    };

    const handleLoadMapping = (savedMapping) => {
        setMappingConfig(savedMapping);
    };

    const handleConfirm = () => {
        if (validateMapping()) {
            onMappingConfirmed(mappingConfig);
        }
    };

    const getFieldsToShow = () => {
        return TRANSACTION_FIELDS.filter((field) => {
            if (mappingConfig.amount_strategy === 'single') {
                return field.key !== 'debit_column' && field.key !== 'credit_column' && field.key !== 'type_column';
            } else if (mappingConfig.amount_strategy === 'separate') {
                return field.key !== 'amount_column' && field.key !== 'type_column';
            } else if (mappingConfig.amount_strategy === 'type_column') {
                return field.key !== 'debit_column' && field.key !== 'credit_column';
            }
            return true;
        });
    };

    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold">Map Your Columns</h3>
                <p className="text-sm text-gray-600 mt-1">
                    Match your spreadsheet columns to TrueTrack fields
                </p>
            </div>

            {/* Load Saved Mapping */}
            <div>
                <InputLabel value="Load Saved Mapping (Optional)" />
                <SavedMappingSelector accountId={accountId} onMappingSelected={handleLoadMapping} />
            </div>

            {/* Number Format Selection */}
            <div className="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                <h3 className="mb-2 text-sm font-semibold text-blue-900">Number Format</h3>
                <p className="mb-3 text-xs text-blue-700">
                    {formatConfidence > 0
                        ? `Auto-detected: ${mappingConfig.number_format === 'br' ? 'Brazilian' : 'US/UK'} format (${formatConfidence}% confidence)`
                        : 'Select the number format used in your file'}
                </p>
                <div className="space-y-2">
                    <label className="flex items-center space-x-3 rounded border border-blue-300 bg-white p-3 cursor-pointer hover:bg-blue-100">
                        <input
                            type="radio"
                            name="number_format"
                            value="us"
                            checked={mappingConfig.number_format === 'us'}
                            onChange={(e) => handleFieldChange('number_format', e.target.value)}
                            className="h-4 w-4 text-blue-600"
                        />
                        <div className="flex-1">
                            <div className="font-medium text-gray-900">US/UK Format</div>
                            <div className="text-xs text-gray-600">Example: 1,234.56 (comma as thousand separator, dot as decimal)</div>
                        </div>
                    </label>
                    <label className="flex items-center space-x-3 rounded border border-blue-300 bg-white p-3 cursor-pointer hover:bg-blue-100">
                        <input
                            type="radio"
                            name="number_format"
                            value="br"
                            checked={mappingConfig.number_format === 'br'}
                            onChange={(e) => handleFieldChange('number_format', e.target.value)}
                            className="h-4 w-4 text-blue-600"
                        />
                        <div className="flex-1">
                            <div className="font-medium text-gray-900">Brazilian/European Format</div>
                            <div className="text-xs text-gray-600">Example: 1.234,56 (dot as thousand separator, comma as decimal)</div>
                        </div>
                    </label>
                </div>
                <p className="mt-2 text-xs text-blue-600">
                    💡 Tip: Check the preview to verify amounts are parsed correctly
                </p>
            </div>

            {/* Amount Strategy Selection */}
            <div>
                <InputLabel value="Amount Detection Strategy *" />
                <div className="mt-2 space-y-2">
                    {AMOUNT_STRATEGIES.map((strategy) => (
                        <label key={strategy.value} className="flex items-start space-x-3 cursor-pointer">
                            <input
                                type="radio"
                                name="amount_strategy"
                                value={strategy.value}
                                checked={mappingConfig.amount_strategy === strategy.value}
                                onChange={(e) => handleStrategyChange(e.target.value)}
                                className="mt-1"
                            />
                            <div>
                                <div className="font-medium">{strategy.label}</div>
                                <div className="text-sm text-gray-500">{strategy.description}</div>
                            </div>
                        </label>
                    ))}
                </div>
            </div>

            {/* Column Mapping Fields */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {getFieldsToShow().map((field) => (
                    <div key={field.key}>
                        <InputLabel
                            htmlFor={field.key}
                            value={`${field.label} ${field.required ? '*' : ''}`}
                        />
                        <select
                            id={field.key}
                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm ${
                                errors[field.key] ? 'border-red-500' : ''
                            }`}
                            value={mappingConfig[field.key]}
                            onChange={(e) => handleFieldChange(field.key, e.target.value)}
                        >
                            <option value="">-- Not Used --</option>
                            {headers.map((header, index) => (
                                <option key={index} value={header}>
                                    {header}
                                </option>
                            ))}
                        </select>
                        {errors[field.key] && (
                            <p className="mt-1 text-sm text-red-600">{errors[field.key]}</p>
                        )}
                    </div>
                ))}
            </div>

            {/* Action Buttons */}
            <div className="flex justify-between pt-4">
                <SecondaryButton onClick={onBack}>Back</SecondaryButton>
                <PrimaryButton onClick={handleConfirm} disabled={Object.keys(errors).length > 0}>
                    Preview Import
                </PrimaryButton>
            </div>
        </div>
    );
}
