import { useState, useEffect } from 'react';
import axios from 'axios';
import OfxImportCard from './OfxImportCard';

export default function OfxImportList({ initialImports = [], accountId = null }) {
    const [imports, setImports] = useState(initialImports);
    const [loading, setLoading] = useState(false);
    const [filter, setFilter] = useState('all');

    const fetchImports = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (accountId) {
                params.append('filter[account_id]', accountId);
            }
            if (filter !== 'all') {
                params.append('filter[status]', filter);
            }

            const response = await axios.get(
                `/api/v1/ofx-imports?${params.toString()}`
            );
            setImports(response.data.data);
        } catch (error) {
            console.error('Failed to fetch imports:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchImports();
    }, [filter, accountId]);

    // Auto-refresh every 5 seconds if there are active imports
    useEffect(() => {
        const hasActiveImports = imports.some(
            (imp) => imp.status === 'processing' || imp.status === 'pending'
        );

        if (!hasActiveImports) return;

        const interval = setInterval(() => {
            fetchImports();
        }, 5000);

        return () => clearInterval(interval);
    }, [imports]);

    const handleDelete = (id) => {
        setImports(imports.filter((imp) => imp.id !== id));
    };

    const filteredImports = imports;

    return (
        <div className="space-y-4">
            {/* Filter Tabs */}
            <div className="flex space-x-2 border-b border-gray-200">
                {[
                    { key: 'all', label: 'All' },
                    { key: 'pending', label: 'Pending' },
                    { key: 'processing', label: 'Processing' },
                    { key: 'completed', label: 'Completed' },
                    { key: 'failed', label: 'Failed' },
                ].map((tab) => (
                    <button
                        key={tab.key}
                        onClick={() => setFilter(tab.key)}
                        className={`border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
                            filter === tab.key
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* Loading State */}
            {loading && (
                <div className="flex items-center justify-center py-8">
                    <div className="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600" />
                </div>
            )}

            {/* Import List */}
            {!loading && filteredImports.length === 0 && (
                <div className="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                    <p className="text-gray-600">
                        No imports found. Upload an OFX file to get started.
                    </p>
                </div>
            )}

            {!loading && filteredImports.length > 0 && (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {filteredImports.map((importData) => (
                        <OfxImportCard
                            key={importData.id}
                            import={importData}
                            onDelete={handleDelete}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
