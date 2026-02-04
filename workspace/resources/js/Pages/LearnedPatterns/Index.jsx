import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import LearnedPatternTable from './LearnedPatternTable';
import ConvertPatternModal from './ConvertPatternModal';

export default function LearnedPatterns() {
    const { auth } = usePage().props;
    const [patterns, setPatterns] = useState([]);
    const [loading, setLoading] = useState(false);
    const [stats, setStats] = useState(null);
    const [showConvertModal, setShowConvertModal] = useState(false);
    const [selectedPattern, setSelectedPattern] = useState(null);
    const [filter, setFilter] = useState({ active: true, minConfidence: 0 });
    const [page, setPage] = useState(1);

    useEffect(() => {
        fetchPatterns();
        fetchStatistics();
    }, [filter, page]);

    const fetchPatterns = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                'filter[active]': filter.active ? '1' : '0',
                'filter[min_confidence]': filter.minConfidence,
                page,
            });

            const response = await fetch(`/api/v1/learned-patterns?${params}`, {
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            const data = await response.json();
            setPatterns(data.data);
        } catch (error) {
            console.error('Failed to fetch patterns:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchStatistics = async () => {
        try {
            const response = await fetch('/api/v1/learned-patterns/statistics', {
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            const data = await response.json();
            setStats(data.data);
        } catch (error) {
            console.error('Failed to fetch statistics:', error);
        }
    };

    const handleToggle = async (patternId) => {
        try {
            const response = await fetch(`/api/v1/learned-patterns/${patternId}/toggle`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            if (response.ok) {
                fetchPatterns();
            }
        } catch (error) {
            console.error('Error toggling pattern:', error);
        }
    };

    const handleDelete = async (patternId) => {
        if (!confirm('Delete this pattern?')) return;

        try {
            const response = await fetch(`/api/v1/learned-patterns/${patternId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            if (response.ok) {
                fetchPatterns();
            }
        } catch (error) {
            console.error('Error deleting pattern:', error);
        }
    };

    const handleClearAll = async () => {
        if (!confirm('Clear ALL learned patterns? This cannot be undone.')) return;

        try {
            const response = await fetch('/api/v1/learned-patterns/clear-all', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });

            if (response.ok) {
                fetchPatterns();
                fetchStatistics();
            }
        } catch (error) {
            console.error('Error clearing patterns:', error);
        }
    };

    return (
        <div className="py-12">
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 bg-white border-b border-gray-200">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-2xl font-bold text-gray-900">
                                Learned Category Patterns
                            </h2>
                            <PrimaryButton onClick={handleClearAll}>
                                Clear All Learning
                            </PrimaryButton>
                        </div>

                        {/* Statistics Cards */}
                        {stats && (
                            <div className="grid grid-cols-4 gap-4 mb-6">
                                <div className="bg-blue-50 p-4 rounded">
                                    <p className="text-sm text-gray-600">Total Patterns</p>
                                    <p className="text-2xl font-bold text-blue-900">
                                        {stats.total_patterns}
                                    </p>
                                </div>
                                <div className="bg-green-50 p-4 rounded">
                                    <p className="text-sm text-gray-600">Active</p>
                                    <p className="text-2xl font-bold text-green-900">
                                        {stats.active_patterns}
                                    </p>
                                </div>
                                <div className="bg-purple-50 p-4 rounded">
                                    <p className="text-sm text-gray-600">Avg Confidence</p>
                                    <p className="text-2xl font-bold text-purple-900">
                                        {stats.average_confidence}%
                                    </p>
                                </div>
                                <div className="bg-yellow-50 p-4 rounded">
                                    <p className="text-sm text-gray-600">Corrections</p>
                                    <p className="text-2xl font-bold text-yellow-900">
                                        {stats.total_corrections}
                                    </p>
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
                                <span className="ml-2 text-gray-700">Active Only</span>
                            </label>
                        </div>

                        {/* Patterns Table */}
                        <LearnedPatternTable
                            patterns={patterns}
                            loading={loading}
                            onToggle={handleToggle}
                            onDelete={handleDelete}
                            onConvert={(pattern) => {
                                setSelectedPattern(pattern);
                                setShowConvertModal(true);
                            }}
                        />
                    </div>
                </div>
            </div>

            {/* Convert Pattern Modal */}
            <ConvertPatternModal
                show={showConvertModal}
                pattern={selectedPattern}
                onClose={() => {
                    setShowConvertModal(false);
                    setSelectedPattern(null);
                }}
                onSuccess={() => {
                    setShowConvertModal(false);
                    setSelectedPattern(null);
                    fetchPatterns();
                }}
            />
        </div>
    );
}
