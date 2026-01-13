import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create({ auth, accounts }) {
    const { data, setData, post, processing, errors } = useForm({
        account_id: '',
        statement_date: '',
        statement_balance: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('reconciliations.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Create Reconciliation
                </h2>
            }
        >
            <Head title="Create Reconciliation" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit}>
                                <div className="space-y-6">
                                    {/* Account Selection */}
                                    <div>
                                        <label htmlFor="account_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            Account <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="account_id"
                                            value={data.account_id}
                                            onChange={(e) => setData('account_id', e.target.value)}
                                            className={`w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.account_id ? 'border-red-500' : ''
                                            }`}
                                            required
                                        >
                                            <option value="">Select an account</option>
                                            {accounts.map((account) => (
                                                <option key={account.id} value={account.id}>
                                                    {account.name} ({account.type})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.account_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.account_id}</p>
                                        )}
                                    </div>

                                    {/* Statement Date */}
                                    <div>
                                        <label htmlFor="statement_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Statement Date <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            id="statement_date"
                                            value={data.statement_date}
                                            onChange={(e) => setData('statement_date', e.target.value)}
                                            className={`w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.statement_date ? 'border-red-500' : ''
                                            }`}
                                            required
                                        />
                                        {errors.statement_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.statement_date}</p>
                                        )}
                                    </div>

                                    {/* Statement Balance */}
                                    <div>
                                        <label htmlFor="statement_balance" className="block text-sm font-medium text-gray-700 mb-2">
                                            Statement Balance <span className="text-red-500">*</span>
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span className="text-gray-500">$</span>
                                            </div>
                                            <input
                                                type="number"
                                                step="0.01"
                                                id="statement_balance"
                                                value={data.statement_balance}
                                                onChange={(e) => setData('statement_balance', e.target.value)}
                                                className={`w-full pl-7 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                    errors.statement_balance ? 'border-red-500' : ''
                                                }`}
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                        {errors.statement_balance && (
                                            <p className="mt-1 text-sm text-red-600">{errors.statement_balance}</p>
                                        )}
                                        <p className="mt-1 text-sm text-gray-500">
                                            Enter the balance shown on your bank/credit card statement
                                        </p>
                                    </div>

                                    {/* Info Box */}
                                    <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                        <div className="flex">
                                            <div className="flex-shrink-0">
                                                <svg className="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div className="ml-3">
                                                <h3 className="text-sm font-medium text-blue-800">About Reconciliation</h3>
                                                <div className="mt-2 text-sm text-blue-700">
                                                    <p>
                                                        Reconciliation helps you verify that your recorded transactions match
                                                        your actual bank or credit card statement. After creating this
                                                        reconciliation, you'll be able to match transactions and identify
                                                        any discrepancies.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Buttons */}
                                    <div className="flex justify-end gap-4">
                                        <Link
                                            href={route('reconciliations.index')}
                                            className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                        >
                                            Cancel
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            {processing ? 'Creating...' : 'Create Reconciliation'}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
