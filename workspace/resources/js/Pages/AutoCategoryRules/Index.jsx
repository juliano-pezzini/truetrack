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

    useEffect(() => {
        fetchRules();
    }, [filter, page]);

    const fetchRules = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                'filter[active]': filter.active ? '1' : '0',
                page,
            });

            const response = await fetch(`/api/v1/auto-category-rules?${params}`, {
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            const data = await response.json();
            setRules(data.data);
        } catch (error) {
            console.error('Failed to fetch rules:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCreateRule = async (formData) => {
        try {
            const response = await fetch('/api/v1/auto-category-rules', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData),
            });

            if (response.ok) {
                setShowForm(false);
                fetchRules();
            } else {
                console.error('Failed to create rule');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const handleUpdateRule = async (ruleId, formData) => {
        try {
            const response = await fetch(`/api/v1/auto-category-rules/${ruleId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData),
            });

            if (response.ok) {
                setEditingRule(null);
                setShowForm(false);
                fetchRules();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const handleDeleteRule = async (ruleId) => {
        if (!confirm('Are you sure you want to delete this rule?')) return;

        try {
            const response = await fetch(`/api/v1/auto-category-rules/${ruleId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            if (response.ok) {
                fetchRules();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const handleArchiveRule = async (ruleId) => {
        try {
            const response = await fetch(`/api/v1/auto-category-rules/${ruleId}/archive`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            if (response.ok) {
                fetchRules();
            }
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
