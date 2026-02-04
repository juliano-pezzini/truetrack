import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { useState } from 'react';
import AutoRuleForm from '@/Pages/AutoCategoryRules/AutoRuleForm';
import AutoRuleTable from '@/Pages/AutoCategoryRules/AutoRuleTable';
import TestCoverageModal from '@/Pages/AutoCategoryRules/TestCoverageModal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Index({ auth, categories, filters, categoryTypes }) {
    const [filterType, setFilterType] = useState(filters?.type || '');
    const [filterActive, setFilterActive] = useState(filters?.is_active ?? '');
    const [filterParentOnly, setFilterParentOnly] = useState(filters?.parent_only || false);
    const [showAutoRulesModal, setShowAutoRulesModal] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [rules, setRules] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showRuleForm, setShowRuleForm] = useState(false);
    const [editingRule, setEditingRule] = useState(null);
    const [error, setError] = useState(null);
    const [showTestCoverageModal, setShowTestCoverageModal] = useState(false);

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    };

    const typeOptions = [
        { value: '', label: 'All Types' },
        ...categoryTypes.map(type => ({ value: type.value, label: type.label })),
    ];

    const activeOptions = [
        { value: '', label: 'All Categories' },
        { value: '1', label: 'Active Only' },
        { value: '0', label: 'Inactive Only' },
    ];

    const applyFilters = () => {
        const params = {};

        if (filterType) {
            params.type = filterType;
        }

        if (filterActive !== '') {
            params.is_active = filterActive;
        }

        if (filterParentOnly) {
            params.parent_only = '1';
        }

        router.get(route('categories.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setFilterType('');
        setFilterActive('');
        setFilterParentOnly(false);
        router.get(route('categories.index'));
    };

    const deleteCategory = (categoryId) => {
        if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            router.delete(route('categories.destroy', categoryId));
        }
    };

    const getCategoryTypeClass = (type) => {
        return type === 'revenue' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100';
    };

    const openAutoRulesModal = async (category) => {
        setSelectedCategory(category);
        setShowAutoRulesModal(true);
        setShowRuleForm(false);
        setEditingRule(null);
        await fetchRulesForCategory(category.id);
    };

    const closeAutoRulesModal = () => {
        setShowAutoRulesModal(false);
        setSelectedCategory(null);
        setRules([]);
        setShowRuleForm(false);
        setEditingRule(null);
        setError(null);
    };

    const fetchRulesForCategory = async (categoryId) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({
                'filter[category_id]': categoryId,
                'filter[active]': '1',
            });

            const response = await fetch(`/api/v1/auto-category-rules?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch rules: ${response.status}`);
            }

            const data = await response.json();
            setRules(data.data);
        } catch (error) {
            console.error('Failed to fetch rules:', error);
            setError('Failed to load auto-category rules. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateRule = async (formData) => {
        setError(null);
        try {
            const response = await fetch('/api/v1/auto-category-rules', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    ...formData,
                    category_id: selectedCategory.id,
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || `Failed to create rule (${response.status})`;
                setError(errorMessage);
                throw new Error(errorMessage);
            }

            setShowRuleForm(false);
            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error creating rule:', error);
            if (!error.message.includes('Failed to create rule')) {
                setError('An error occurred while creating the rule. Please try again.');
            }
        }
    };

    const handleUpdateRule = async (ruleId, formData) => {
        setError(null);
        try {
            const response = await fetch(`/api/v1/auto-category-rules/${ruleId}`, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(formData),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || `Failed to update rule (${response.status})`;
                setError(errorMessage);
                throw new Error(errorMessage);
            }

            setEditingRule(null);
            setShowRuleForm(false);
            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error updating rule:', error);
            if (!error.message.includes('Failed to update rule')) {
                setError('An error occurred while updating the rule. Please try again.');
            }
        }
    };

    const handleDeleteRule = async (ruleId) => {
        if (!confirm('Are you sure you want to delete this rule?')) return;

        setError(null);
        try {
            const response = await fetch(`/api/v1/auto-category-rules/${ruleId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || `Failed to delete rule (${response.status})`;
                setError(errorMessage);
                throw new Error(errorMessage);
            }

            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error deleting rule:', error);
            if (!error.message.includes('Failed to delete rule')) {
                setError('An error occurred while deleting the rule. Please try again.');
            }
        }
    };

    const handleArchiveRule = async (ruleId) => {
        setError(null);
        try {
            const response = await fetch(`/api/v1/auto-category-rules/${ruleId}/archive`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || `Failed to archive rule (${response.status})`;
                setError(errorMessage);
                throw new Error(errorMessage);
            }

            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error archiving rule:', error);
            if (!error.message.includes('Failed to archive rule')) {
                setError('An error occurred while archiving the rule. Please try again.');
            }
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Categories
                    </h2>
                    <Link
                        href={route('categories.create')}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        Create Category
                    </Link>
                </div>
            }
        >
            <Head title="Categories" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Filters</h3>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label htmlFor="filterType" className="block text-sm font-medium text-gray-700 mb-2">
                                        Category Type
                                    </label>
                                    <select
                                        id="filterType"
                                        value={filterType}
                                        onChange={(e) => setFilterType(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        {typeOptions.map(option => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="filterActive" className="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        id="filterActive"
                                        value={filterActive}
                                        onChange={(e) => setFilterActive(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        {activeOptions.map(option => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex items-end">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={filterParentOnly}
                                            onChange={(e) => setFilterParentOnly(e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            Parent Categories Only
                                        </span>
                                    </label>
                                </div>

                                <div className="flex items-end gap-2">
                                    <button
                                        onClick={applyFilters}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                                    >
                                        Apply
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Categories Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {categories.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <p className="text-gray-500 mb-4">No categories found.</p>
                                    <Link
                                        href={route('categories.create')}
                                        className="text-indigo-600 hover:text-indigo-800"
                                    >
                                        Create your first category
                                    </Link>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Type
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Parent
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Description
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {categories.data.map((category) => (
                                                <tr key={category.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            {!category.is_parent && (
                                                                <span className="mr-2 text-gray-400">└─</span>
                                                            )}
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {category.name}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getCategoryTypeClass(category.type)}`}>
                                                            {category.type_label}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {category.parent?.name || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                                        {category.description || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${category.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                                            {category.is_active ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <button
                                                            onClick={() => openAutoRulesModal(category)}
                                                            className="text-purple-600 hover:text-purple-900 mr-4"
                                                            title="Manage auto-categorization rules"
                                                        >
                                                            Auto Rules
                                                        </button>
                                                        <Link
                                                            href={route('categories.edit', category.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 mr-4"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteCategory(category.id)}
                                                            className="text-red-600 hover:text-red-900"
                                                            disabled={category.has_children}
                                                            title={category.has_children ? 'Cannot delete category with subcategories' : 'Delete category'}
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {categories.data.length > 0 && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing <span className="font-medium">{categories.from}</span> to{' '}
                                        <span className="font-medium">{categories.to}</span> of{' '}
                                        <span className="font-medium">{categories.total}</span> results
                                    </div>
                                    <div className="flex gap-2">
                                        {categories.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-2 rounded-md text-sm ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : link.url
                                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Auto-Category Rules Modal */}
            <Modal show={showAutoRulesModal} onClose={closeAutoRulesModal} maxWidth="4xl">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">
                                Auto-Category Rules for "{selectedCategory?.name}"
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Manage automatic categorization rules for this category
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <SecondaryButton
                                onClick={() => setShowTestCoverageModal(true)}
                            >
                                Test Coverage
                            </SecondaryButton>
                            <PrimaryButton
                                onClick={() => {
                                    setEditingRule(null);
                                    setShowRuleForm(true);
                                }}
                            >
                                Add Rule
                            </PrimaryButton>
                        </div>
                    </div>

                    {error && (
                        <div className="mb-4 rounded-md bg-red-50 p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-red-800">{error}</p>
                                </div>
                                <div className="ml-auto pl-3">
                                    <div className="-mx-1.5 -my-1.5">
                                        <button
                                            type="button"
                                            onClick={() => setError(null)}
                                            className="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50"
                                        >
                                            <span className="sr-only">Dismiss</span>
                                            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {showRuleForm ? (
                        <div className="bg-gray-50 p-6 rounded-lg">
                            <h3 className="text-lg font-semibold mb-4">
                                {editingRule ? 'Edit Rule' : 'Create New Rule'}
                            </h3>
                            <AutoRuleForm
                                rule={editingRule}
                                fixedCategory={selectedCategory}
                                onSubmit={(formData) => {
                                    if (editingRule) {
                                        handleUpdateRule(editingRule.id, formData);
                                    } else {
                                        handleCreateRule(formData);
                                    }
                                }}
                                onCancel={() => {
                                    setShowRuleForm(false);
                                    setEditingRule(null);
                                }}
                            />
                        </div>
                    ) : (
                        <div>
                            {loading ? (
                                <div className="text-center py-8">
                                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                                    <p className="mt-2 text-gray-600">Loading rules...</p>
                                </div>
                            ) : (
                                <AutoRuleTable
                                    rules={rules}
                                    onEdit={(rule) => {
                                        setEditingRule(rule);
                                        setShowRuleForm(true);
                                    }}
                                    onDelete={handleDeleteRule}
                                    onArchive={handleArchiveRule}
                                />
                            )}
                        </div>
                    )}

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeAutoRulesModal}>
                            Close
                        </SecondaryButton>
                    </div>
                </div>
            </Modal>

            {/* Test Coverage Modal */}
            <TestCoverageModal
                show={showTestCoverageModal}
                onClose={() => setShowTestCoverageModal(false)}
            />
        </AuthenticatedLayout>
    );
}
