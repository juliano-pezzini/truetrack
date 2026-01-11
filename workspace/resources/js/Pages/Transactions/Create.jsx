import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useState } from 'react';

export default function Create({ auth, accounts, categories, tags }) {
    const { data, setData, post, processing, errors } = useForm({
        account_id: '',
        category_id: '',
        amount: '',
        description: '',
        transaction_date: new Date().toISOString().split('T')[0],
        settled_date: '',
        type: 'debit',
        tag_ids: [],
    });

    const [selectedTags, setSelectedTags] = useState([]);

    const transactionTypes = [
        { value: 'debit', label: 'Debit (Income/Deposit)' },
        { value: 'credit', label: 'Credit (Expense/Charge)' },
    ];

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('transactions.store'));
    };

    const toggleTag = (tagId) => {
        const newSelectedTags = selectedTags.includes(tagId)
            ? selectedTags.filter(id => id !== tagId)
            : [...selectedTags, tagId];
        
        setSelectedTags(newSelectedTags);
        setData('tag_ids', newSelectedTags);
    };

    const getTagStyle = (color) => ({
        backgroundColor: color + '20',
        borderColor: color,
        color: color,
    });

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Create Transaction
                </h2>
            }
        >
            <Head title="Create Transaction" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit}>
                                {/* Account */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="account_id" value="Account *" />
                                    <select
                                        id="account_id"
                                        value={data.account_id}
                                        onChange={(e) => setData('account_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">Select an account</option>
                                        {accounts?.data?.map((account) => (
                                            <option key={account.id} value={account.id}>
                                                {account.name} ({account.type})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.account_id} className="mt-2" />
                                </div>

                                {/* Category */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="category_id" value="Category" />
                                    <select
                                        id="category_id"
                                        value={data.category_id}
                                        onChange={(e) => setData('category_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Select a category (optional)</option>
                                        {categories?.data?.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name} ({category.type})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.category_id} className="mt-2" />
                                </div>

                                {/* Transaction Type */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="type" value="Transaction Type *" />
                                    <select
                                        id="type"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        {transactionTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.type} className="mt-2" />
                                    <p className="mt-1 text-sm text-gray-500">
                                        Debit: increases balance for bank/wallet, decreases for credit card<br />
                                        Credit: decreases balance for bank/wallet, increases for credit card
                                    </p>
                                </div>

                                {/* Amount */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="amount" value="Amount *" />
                                    <TextInput
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className="mt-1 block w-full"
                                        required
                                        placeholder="0.00"
                                    />
                                    <InputError message={errors.amount} className="mt-2" />
                                </div>

                                {/* Transaction Date */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="transaction_date" value="Transaction Date *" />
                                    <TextInput
                                        id="transaction_date"
                                        type="date"
                                        value={data.transaction_date}
                                        onChange={(e) => setData('transaction_date', e.target.value)}
                                        className="mt-1 block w-full"
                                        required
                                    />
                                    <InputError message={errors.transaction_date} className="mt-2" />
                                </div>

                                {/* Settled Date */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="settled_date" value="Settled Date" />
                                    <TextInput
                                        id="settled_date"
                                        type="date"
                                        value={data.settled_date}
                                        onChange={(e) => setData('settled_date', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.settled_date} className="mt-2" />
                                    <p className="mt-1 text-sm text-gray-500">
                                        Leave empty for pending transactions
                                    </p>
                                </div>

                                {/* Description */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="description" value="Description" />
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="3"
                                        placeholder="Optional notes about this transaction"
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>

                                {/* Tags */}
                                <div className="mb-6">
                                    <InputLabel value="Tags (optional)" />
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {tags?.data?.map((tag) => (
                                            <button
                                                key={tag.id}
                                                type="button"
                                                onClick={() => toggleTag(tag.id)}
                                                style={selectedTags.includes(tag.id) ? getTagStyle(tag.color) : {}}
                                                className={`px-3 py-1 text-sm rounded-full border-2 transition-colors ${
                                                    selectedTags.includes(tag.id)
                                                        ? 'font-semibold'
                                                        : 'border-gray-300 text-gray-700 hover:border-gray-400'
                                                }`}
                                            >
                                                {tag.name}
                                            </button>
                                        ))}
                                    </div>
                                    <InputError message={errors.tag_ids} className="mt-2" />
                                </div>

                                {/* Form Actions */}
                                <div className="flex items-center justify-end gap-4">
                                    <Link
                                        href={route('transactions.index')}
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        Cancel
                                    </Link>
                                    <PrimaryButton disabled={processing}>
                                        Create Transaction
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
