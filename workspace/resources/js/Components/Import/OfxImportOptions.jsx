import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function OfxImportOptions({ 
    accounts, 
    selectedAccount, 
    onAccountChange, 
    forceReimport, 
    onForceReimportChange,
    onSubmit,
    onCancel,
    processing 
}) {
    return (
        <div className="space-y-4">
            <h3 className="text-lg font-semibold text-gray-900">
                Import OFX Statement
            </h3>

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

            {/* Force Reimport Checkbox */}
            <div className="flex items-center">
                <input
                    type="checkbox"
                    id="force_reimport"
                    checked={forceReimport}
                    onChange={(e) => onForceReimportChange(e.target.checked)}
                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label
                    htmlFor="force_reimport"
                    className="ml-2 block text-sm text-gray-700"
                >
                    Force reimport (ignore duplicate check)
                </label>
            </div>

            {/* Info Box */}
            <div className="rounded-md bg-blue-50 p-3">
                <p className="text-xs text-blue-800">
                    <strong>Note:</strong> The system will automatically create a reconciliation 
                    and attempt to match existing transactions in your account.
                </p>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-end space-x-3">
                <SecondaryButton onClick={onCancel} disabled={processing}>
                    Cancel
                </SecondaryButton>
                <PrimaryButton 
                    onClick={onSubmit} 
                    disabled={processing || !selectedAccount}
                >
                    {processing ? 'Uploading...' : 'Import'}
                </PrimaryButton>
            </div>
        </div>
    );
}
