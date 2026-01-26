import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function OfxImportUpload({ accounts, onSuccess }) {
    const [selectedFile, setSelectedFile] = useState(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
        account_id: '',
        force_reimport: false,
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        setSelectedFile(file);
        setData('file', file);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        post(route('api.ofx-imports.store'), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                setSelectedFile(null);
                if (onSuccess) onSuccess();
            },
        });
    };

    const handleCancel = () => {
        reset();
        setSelectedFile(null);
    };

    return (
        <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 className="mb-4 text-lg font-semibold text-gray-900">
                Upload OFX Statement
            </h3>

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="account_id" value="Account" />
                    <select
                        id="account_id"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        value={data.account_id}
                        onChange={(e) => setData('account_id', e.target.value)}
                        required
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
                    <InputLabel htmlFor="file" value="OFX File" />
                    <input
                        type="file"
                        id="file"
                        accept=".ofx,.qfx"
                        onChange={handleFileChange}
                        className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        required
                    />
                    {selectedFile && (
                        <p className="mt-2 text-sm text-gray-600">
                            Selected: {selectedFile.name} (
                            {(selectedFile.size / 1024).toFixed(2)} KB)
                        </p>
                    )}
                    <InputError message={errors.file} className="mt-2" />
                </div>

                <div className="flex items-center">
                    <input
                        type="checkbox"
                        id="force_reimport"
                        checked={data.force_reimport}
                        onChange={(e) =>
                            setData('force_reimport', e.target.checked)
                        }
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label
                        htmlFor="force_reimport"
                        className="ml-2 block text-sm text-gray-700"
                    >
                        Force reimport (even if file was previously imported)
                    </label>
                </div>

                <div className="flex items-center justify-end space-x-3">
                    {selectedFile && (
                        <SecondaryButton
                            type="button"
                            onClick={handleCancel}
                            disabled={processing}
                        >
                            Cancel
                        </SecondaryButton>
                    )}
                    <PrimaryButton disabled={processing || !data.file || !data.account_id}>
                        {processing ? 'Uploading...' : 'Upload & Process'}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    );
}
