import { useState, useEffect } from 'react';
import { usePage, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import AutoRuleForm from './AutoRuleForm';
import AutoRuleTable from './AutoRuleTable';
import TestCoverageModal from './TestCoverageModal';

export default function AutoCategoryRules() {
    const { auth } = usePage().props;
    const [rules, setRules] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [editingRule, setEditingRule] = useState(null);
    const [showTestModal, setShowTestModal] = useState(false);
    const [filter, setFilter] = useState({ active: true });
    const [page, setPage] = useState(1);
    const [error, setError] = useState(null);

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    };

    useEffect(() => {
        fetchRules();
    }, [filter, page]);

    const fetchRules = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({
                'filter[active]': filter.active ? '1' : '0',
                page,
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
            setError('Failed to load rules. Please try again.');
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
                body: JSON.stringify(formData),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || `Failed to create rule (${response.status})`;
                setError(errorMessage);
                throw new Error(errorMessage);
            }

            setShowForm(false);
            fetchRules();
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
            setShowForm(false);
            fetchRules();
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

            fetchRules();
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

            fetchRules();
        } catch (error) {
            console.error('Error:', error);
        }
    };

    return (
        <div className="py-12">
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 bg-white border-b border-gray-200">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-bold text-gray-900">
                                Auto-Category Rules
                            </h2>
                            <div className="flex gap-2">
                                <PrimaryButton
                                    onClick={() => setShowTestModal(true)}
                                >
                                    Test Coverage
                                </PrimaryButton>
                                <PrimaryButton
                                    onClick={() => {
                                        setEditingRule(null);
                                        setShowForm(true);
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

                        {/* Filters */}
                        <div className="mb-6 flex gap-4">
                            <label className="inline-flex items-center">
                                <input
                                    type="checkbox"
                                    checked={filter.active}
                                    onChange={(e) => {
                                        setFilter({ ...filter, active: e.target.checked });
                                        setPage(1);
                                    }}
                                    className="rounded border-gray-300 text-indigo-600"
                                />
                                <span className="ml-2 text-gray-700">Active Rules Only</span>
                            </label>
                        </div>

                        {/* Rules Table */}
                        <AutoRuleTable
                            rules={rules}
                            loading={loading}
                            onEdit={(rule) => {
                                setEditingRule(rule);
                                setShowForm(true);
                            }}
                            onDelete={handleDeleteRule}
                            onArchive={handleArchiveRule}
                        />
                    </div>
                </div>
            </div>

            {/* Form Modal */}
            <Modal
                show={showForm}
                onClose={() => {
                    setShowForm(false);
                    setEditingRule(null);
                }}
            >
                <div className="p-6">
                    <AutoRuleForm
                        rule={editingRule}
                        onSubmit={(formData) => {
                            if (editingRule) {
                                handleUpdateRule(editingRule.id, formData);
                            } else {
                                handleCreateRule(formData);
                            }
                        }}
                        onCancel={() => {
                            setShowForm(false);
                            setEditingRule(null);
                        }}
                    />
                </div>
            </Modal>

            {/* Test Coverage Modal */}
            <TestCoverageModal
                show={showTestModal}
                onClose={() => setShowTestModal(false)}
            />
        </div>
    );
}
