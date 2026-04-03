import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { useState } from 'react';
import axios from 'axios';
import AutoRuleForm from '@/Pages/AutoCategoryRules/AutoRuleForm';
import AutoRuleTable from '@/Pages/AutoCategoryRules/AutoRuleTable';
import TestCoverageModal from '@/Pages/AutoCategoryRules/TestCoverageModal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import normalizeInertiaUrl from '@/Utils/normalizeInertiaUrl';

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

            const { data } = await axios.get(`/api/v1/auto-category-rules?${params}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });
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
            await axios.post('/api/v1/auto-category-rules', {
                ...formData,
                category_id: selectedCategory.id,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            setShowRuleForm(false);
            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error creating rule:', error);
            const errorMessage = error.response?.data?.message || `Failed to create rule (${error.response?.status ?? 'unknown'})`;
            setError(errorMessage);
        }
    };

    const handleUpdateRule = async (ruleId, formData) => {
        setError(null);
        try {
            await axios.put(`/api/v1/auto-category-rules/${ruleId}`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            setEditingRule(null);
            setShowRuleForm(false);
            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error updating rule:', error);
            const errorMessage = error.response?.data?.message || `Failed to update rule (${error.response?.status ?? 'unknown'})`;
            setError(errorMessage);
        }
    };

    const handleDeleteRule = async (ruleId) => {
        if (!confirm('Are you sure you want to delete this rule?')) return;

        setError(null);
        try {
            await axios.delete(`/api/v1/auto-category-rules/${ruleId}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error deleting rule:', error);
            const errorMessage = error.response?.data?.message || `Failed to delete rule (${error.response?.status ?? 'unknown'})`;
            setError(errorMessage);
        }
    };

    const handleArchiveRule = async (ruleId) => {
        setError(null);
        try {
            await axios.post(`/api/v1/auto-category-rules/${ruleId}/archive`, {}, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            await fetchRulesForCategory(selectedCategory.id);
        } catch (error) {
            console.error('Error archiving rule:', error);
            const errorMessage = error.response?.data?.message || `Failed to archive rule (${error.response?.status ?? 'unknown'})`;
            setError(errorMessage);
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
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
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Filters</h3>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label htmlFor="filterType" className="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                        Category Type
                                    </label>
                                    <select
                                        id="filterType"
                                        value={filterType}
                                        onChange={(e) => setFilterType(e.target.value)}
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        {typeOptions.map(option => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="filterActive" className="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                                        Status
                                    </label>
                                    <select
                                        id="filterActive"
                                        value={filterActive}
                                        onChange={(e) => setFilterActive(e.target.value)}
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
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
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-900"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Parent Categories Only
                                        </span>
                                    </label>
                                </div>

                                <div className="flex items-end gap-2">
                                    <button
                                        onClick={applyFilters}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                    >
                                        Apply
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Categories Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6">
                            {categories.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <p className="text-gray-500 mb-4 dark:text-gray-400">No categories found.</p>
                                    <Link
                                        href={route('categories.create')}
                                        className="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        Create your first category
                                    </Link>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900/40">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Type
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Parent
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Description
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                            {categories.data.map((category) => (
                                                <tr key={category.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            {!category.is_parent && (
                                                                <span className="mr-2 text-gray-400 dark:text-gray-500">└─</span>
                                                            )}
                                                            <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {category.name}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getCategoryTypeClass(category.type)}`}>
                                                            {category.type_label}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {category.parent?.name || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 max-w-xs truncate dark:text-gray-400">
                                                        {category.description || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${category.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'}`}>
                                                            {category.is_active ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <button
                                                            onClick={() => openAutoRulesModal(category)}
                                                            className="mr-4 text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                            title="Manage auto-categorization rules"
                                                        >
                                                            Auto Rules
                                                        </button>
                                                        <Link
                                                            href={route('categories.edit', category.id)}
                                                            className="mr-4 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteCategory(category.id)}
                                                            className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
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
                                    <div className="text-sm text-gray-700 dark:text-gray-300">
                                        Showing <span className="font-medium">{categories.from}</span> to{' '}
                                        <span className="font-medium">{categories.to}</span> of{' '}
                                        <span className="font-medium">{categories.total}</span> results
                                    </div>
                                    <div className="flex gap-2">
                                        {categories.links.map((link, index) => {
                                            const href = normalizeInertiaUrl(link.url);

                                            return (
                                                <Link
                                                    key={index}
                                                    href={href || '#'}
                                                    className={`px-3 py-2 rounded-md text-sm ${
                                                        link.active
                                                            ? 'bg-indigo-600 text-white'
                                                            : href
                                                            ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:border-gray-600'
                                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            );
                                        })}
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
                            <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Auto-Category Rules for "{selectedCategory?.name}"
                            </h2>
                            <p className="text-sm text-gray-600 mt-1 dark:text-gray-400">
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
                        <div className="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-red-400 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-red-800 dark:text-red-200">{error}</p>
                                </div>
                                <div className="ml-auto pl-3">
                                    <div className="-mx-1.5 -my-1.5">
                                        <button
                                            type="button"
                                            onClick={() => setError(null)}
                                            className="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40 dark:focus:ring-offset-red-950"
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
                        <div className="bg-gray-50 p-6 rounded-lg dark:bg-gray-900/40">
                            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
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
                                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 dark:border-indigo-400"></div>
                                    <p className="mt-2 text-gray-600 dark:text-gray-400">Loading rules...</p>
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
