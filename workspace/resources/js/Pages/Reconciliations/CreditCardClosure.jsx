import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function CreditCardClosure({ auth, accounts }) {
    const { data, setData, post, processing, errors } = useForm({
        credit_card_account_id: '',
        bank_account_id: '',
        payment_amount: '',
        payment_date: new Date().toISOString().split('T')[0],
        statement_balance: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('credit-card-closure'));
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const calculateNewBalance = () => {
        if (!data.bank_account_id || !data.payment_amount) return null;

        const selectedAccount = accounts.find(
            (acc) => acc.id.toString() === data.bank_account_id && acc.type === 'bank'
        );

        if (!selectedAccount) return null;

        return parseFloat(selectedAccount.balance) - parseFloat(data.payment_amount);
    };

    const getCreditCardAccount = () => {
        if (!data.credit_card_account_id) return null;
        return accounts.find((acc) => acc.id.toString() === data.credit_card_account_id);
    };

    const newBankBalance = calculateNewBalance();
    const creditCardAccount = getCreditCardAccount();
    const bankAccounts = accounts.filter((acc) => acc.type === 'bank');
    const creditCardAccounts = accounts.filter((acc) => acc.type === 'credit_card');

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Credit Card Monthly Closure
                </h2>
            }
        >
            <Head title="Credit Card Closure" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Info Alert */}
                            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-blue-800">About Credit Card Closure</h3>
                                        <div className="mt-2 text-sm text-blue-700">
                                            <p>
                                                This process will create a payment transaction from your bank account to pay off
                                                the credit card balance. Two transactions will be created:
                                            </p>
                                            <ul className="list-disc list-inside mt-2 space-y-1">
                                                <li>A debit (withdrawal) from the selected bank account</li>
                                                <li>A credit (payment) to the credit card account</li>
                                            </ul>
                                            <p className="mt-2">
                                                Both transactions will be automatically linked to a new reconciliation and
                                                the reconciliation will be marked as completed.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Credit Card Account Selection */}
                                <div>
                                    <label htmlFor="credit_card_account_id" className="block text-sm font-medium text-gray-700">
                                        Credit Card Account <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="credit_card_account_id"
                                        value={data.credit_card_account_id}
                                        onChange={(e) => setData('credit_card_account_id', e.target.value)}
                                        className={`mt-1 block w-full rounded-md shadow-sm ${
                                            errors.credit_card_account_id
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                        }`}
                                        required
                                    >
                                        <option value="">Select a credit card account...</option>
                                        {creditCardAccounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.credit_card_account_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.credit_card_account_id}</p>
                                    )}
                                </div>

                                {/* Bank Account Selection */}
                                <div>
                                    <label htmlFor="bank_account_id" className="block text-sm font-medium text-gray-700">
                                        Pay From Bank Account <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="bank_account_id"
                                        value={data.bank_account_id}
                                        onChange={(e) => setData('bank_account_id', e.target.value)}
                                        className={`mt-1 block w-full rounded-md shadow-sm ${
                                            errors.bank_account_id
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                        }`}
                                        required
                                    >
                                        <option value="">Select a bank account...</option>
                                        {bankAccounts.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name} - {formatCurrency(account.balance)}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.bank_account_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.bank_account_id}</p>
                                    )}
                                </div>

                                {/* Statement Balance */}
                                <div>
                                    <label htmlFor="statement_balance" className="block text-sm font-medium text-gray-700">
                                        Statement Balance <span className="text-red-500">*</span>
                                    </label>
                                    <div className="mt-1 relative rounded-md shadow-sm">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span className="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input
                                            type="number"
                                            id="statement_balance"
                                            step="0.01"
                                            value={data.statement_balance}
                                            onChange={(e) => setData('statement_balance', e.target.value)}
                                            className={`block w-full rounded-md pl-7 pr-12 ${
                                                errors.statement_balance
                                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                            }`}
                                            placeholder="-0.00"
                                            required
                                        />
                                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span className="text-gray-500 sm:text-sm">USD</span>
                                        </div>
                                    </div>
                                    {errors.statement_balance && (
                                        <p className="mt-1 text-sm text-red-600">{errors.statement_balance}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Enter the balance from your credit card statement (usually negative).
                                    </p>
                                </div>

                                {/* Payment Date */}
                                <div>
                                    <label htmlFor="payment_date" className="block text-sm font-medium text-gray-700">
                                        Payment Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="payment_date"
                                        value={data.payment_date}
                                        onChange={(e) => setData('payment_date', e.target.value)}
                                        className={`mt-1 block w-full rounded-md shadow-sm ${
                                            errors.payment_date
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                        }`}
                                        required
                                    />
                                    {errors.payment_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.payment_date}</p>
                                    )}
                                </div>

                                {/* Payment Amount */}
                                <div>
                                    <label htmlFor="payment_amount" className="block text-sm font-medium text-gray-700">
                                        Payment Amount <span className="text-red-500">*</span>
                                    </label>
                                    <div className="mt-1 relative rounded-md shadow-sm">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span className="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input
                                            type="number"
                                            id="payment_amount"
                                            step="0.01"
                                            value={data.payment_amount}
                                            onChange={(e) => setData('payment_amount', e.target.value)}
                                            className={`block w-full rounded-md pl-7 pr-12 ${
                                                errors.payment_amount
                                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                                            }`}
                                            placeholder="0.00"
                                            required
                                        />
                                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span className="text-gray-500 sm:text-sm">USD</span>
                                        </div>
                                    </div>
                                    {errors.payment_amount && (
                                        <p className="mt-1 text-sm text-red-600">{errors.payment_amount}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Amount to pay. Can be partial or full balance.
                                    </p>
                                </div>

                                {/* Balance Preview */}
                                {newBankBalance !== null && data.statement_balance && (
                                    <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                        <h4 className="text-sm font-medium text-yellow-800 mb-2">Balance Preview</h4>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <p className="text-yellow-700">Bank Account New Balance:</p>
                                                <p className={`font-semibold ${newBankBalance < 0 ? 'text-red-600' : 'text-green-600'}`}>
                                                    {formatCurrency(newBankBalance)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-yellow-700">Credit Card Remaining Balance:</p>
                                                <p className="font-semibold text-gray-900">
                                                    {formatCurrency(parseFloat(data.statement_balance) + parseFloat(data.payment_amount))}
                                                </p>
                                            </div>
                                        </div>
                                        {newBankBalance < 0 && (
                                            <p className="mt-2 text-xs text-red-600 font-medium">
                                                ⚠️ Warning: This payment will overdraw your bank account!
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* Action Buttons */}
                                <div className="flex items-center justify-end gap-4">
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
                                        {processing ? 'Processing...' : 'Process Payment & Complete'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
