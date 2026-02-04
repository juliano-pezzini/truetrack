import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function TestCoverageModal({ show, onClose }) {
    const { auth } = usePage().props;
    const [fromDate, setFromDate] = useState('');
    const [toDate, setToDate] = useState('');
    const [loading, setLoading] = useState(false);
    const [coverage, setCoverage] = useState(null);
    const [error, setError] = useState('');

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    };

    const handleTest = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        setCoverage(null);

        try {
            const response = await fetch('/api/v1/auto-category-rules/test-coverage', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    from_date: fromDate,
                    to_date: toDate,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                setCoverage(data.data);
            } else {
                setError(data.message || 'Failed to test coverage');
            }
        } catch (err) {
            setError('Error testing coverage: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        setCoverage(null);
        setFromDate('');
        setToDate('');
        setError('');
        onClose();
    };

    return (
        <Modal show={show} onClose={handleClose}>
            <div className="p-6 max-w-2xl">
                <h2 className="text-2xl font-bold mb-4">Test Rule Coverage</h2>

                {!coverage ? (
                    <form onSubmit={handleTest}>
                        <p className="text-gray-600 mb-4">
                            Test how many uncategorized transactions in a date range would be
                            auto-categorized by your current rules.
                        </p>

                        <div className="mb-4">
                            <label
                                className="block text-sm font-medium text-gray-700 mb-2"
                                htmlFor="coverage-from-date"
                            >
                                From Date
                            </label>
                            <TextInput
                                id="coverage-from-date"
                                type="date"
                                value={fromDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                required
                            />
                        </div>

                        <div className="mb-4">
                            <label
                                className="block text-sm font-medium text-gray-700 mb-2"
                                htmlFor="coverage-to-date"
                            >
                                To Date
                            </label>
                            <TextInput
                                id="coverage-to-date"
                                type="date"
                                value={toDate}
                                onChange={(e) => setToDate(e.target.value)}
                                required
                            />
                        </div>

                        {error && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded text-red-700">
                                {error}
                            </div>
                        )}

                        <div className="flex justify-end gap-3">
                            <button
                                type="button"
                                onClick={handleClose}
                                className="px-4 py-2 text-gray-700 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                            <PrimaryButton type="submit" disabled={loading}>
                                {loading ? 'Testing...' : 'Test Coverage'}
                            </PrimaryButton>
                        </div>
                    </form>
                ) : (
                    <div className="space-y-6">
                        <div className="grid grid-cols-3 gap-4">
                            <div className="bg-blue-50 p-4 rounded">
                                <p className="text-sm text-gray-600">Total Uncategorized</p>
                                <p className="text-3xl font-bold text-blue-900">
                                    {coverage.total_uncategorized}
                                </p>
                            </div>
                            <div className="bg-green-50 p-4 rounded">
                                <p className="text-sm text-gray-600">Would Be Categorized</p>
                                <p className="text-3xl font-bold text-green-900">
                                    {coverage.would_be_categorized}
                                </p>
                            </div>
                            <div className="bg-purple-50 p-4 rounded">
                                <p className="text-sm text-gray-600">Coverage</p>
                                <p className="text-3xl font-bold text-purple-900">
                                    {coverage.coverage_percentage}%
                                </p>
                            </div>
                        </div>

                        {coverage.by_category.length > 0 && (
                            <div>
                                <h3 className="font-semibold mb-3">By Category</h3>
                                <div className="space-y-2">
                                    {coverage.by_category.map(cat => (
                                        <div
                                            key={cat.category_id}
                                            className="flex justify-between items-center p-3 bg-gray-50 rounded"
                                        >
                                            <div>
                                                <p className="font-medium">{cat.category_name}</p>
                                                <p className="text-sm text-gray-600">
                                                    {cat.count} transaction{cat.count !== 1 ? 's' : ''}
                                                </p>
                                            </div>
                                            <span className="text-sm font-medium">
                                                {cat.source}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {Object.keys(coverage.uncovered_reasons).length > 0 && (
                            <div>
                                <h3 className="font-semibold mb-3">Why Some Couldn't Be Categorized</h3>
                                <ul className="space-y-1 text-sm text-gray-700">
                                    {Object.entries(coverage.uncovered_reasons).map(([reason, count]) => (
                                        <li key={reason}>
                                            {reason}: {count}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <PrimaryButton onClick={() => setCoverage(null)}>
                                Back
                            </PrimaryButton>
                            <button
                                onClick={handleClose}
                                className="px-4 py-2 text-gray-700 hover:text-gray-900"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </Modal>
    );
}
