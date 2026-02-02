import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function ConvertPatternModal({ show, pattern, onClose, onSuccess }) {
    const { auth } = usePage().props;
    const [priority, setPriority] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (pattern) {
            // Auto-suggest next priority
            setPriority('');
        }
    }, [pattern]);

    const handleConvert = async (e) => {
        e.preventDefault();
        if (!priority) {
            setError('Priority is required');
            return;
        }

        setLoading(true);
        setError('');

        try {
            const response = await fetch(`/api/v1/learned-patterns/${pattern.id}/convert`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pattern_id: pattern.id,
                    priority: parseInt(priority),
                }),
            });

            const data = await response.json();

            if (response.ok) {
                onSuccess();
            } else {
                setError(data.message || 'Failed to convert pattern');
            }
        } catch (err) {
            setError('Error: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal show={show} onClose={onClose}>
            <div className="p-6 max-w-lg">
                <h2 className="text-2xl font-bold mb-4">Convert Pattern to Rule</h2>

                {pattern && (
                    <div className="space-y-4">
                        <div className="bg-blue-50 p-4 rounded border border-blue-200">
                            <p className="text-sm text-gray-600 mb-1">Pattern</p>
                            <code className="text-lg font-bold">{pattern.keyword}</code>
                            <p className="text-sm text-gray-600 mt-2">
                                Category: <strong>{pattern.category.name}</strong>
                            </p>
                            <p className="text-sm text-gray-600 mt-1">
                                Confidence: <strong>{pattern.confidence_score}%</strong>
                            </p>
                            <p className="text-sm text-gray-600 mt-1">
                                Occurrences: <strong>{pattern.occurrence_count}</strong>
                            </p>
                        </div>

                        <p className="text-gray-700">
                            Converting this pattern to an explicit rule will apply it to all matching
                            transactions going forward with higher priority than learned patterns.
                        </p>

                        <form onSubmit={handleConvert}>
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Priority (Required) *
                                </label>
                                <TextInput
                                    type="number"
                                    value={priority}
                                    onChange={(e) => {
                                        setPriority(e.target.value);
                                        setError('');
                                    }}
                                    placeholder="e.g., 10 (lower = higher priority)"
                                    min="1"
                                    max="1000"
                                    required
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    Rules are evaluated by priority (lowest first). Use gaps of 10 for easier
                                    reordering.
                                </p>
                            </div>

                            {error && (
                                <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded text-red-700">
                                    {error}
                                </div>
                            )}

                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="px-4 py-2 text-gray-700 hover:text-gray-900"
                                    disabled={loading}
                                >
                                    Cancel
                                </button>
                                <PrimaryButton type="submit" disabled={loading}>
                                    {loading ? 'Converting...' : 'Convert to Rule'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </Modal>
    );
}
