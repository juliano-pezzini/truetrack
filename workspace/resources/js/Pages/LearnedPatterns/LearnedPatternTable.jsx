export default function LearnedPatternTable({ patterns, loading, onToggle, onDelete, onConvert }) {
    if (loading) {
        return <div className="text-center py-8">Loading patterns...</div>;
    }

    if (!patterns || patterns.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No learned patterns yet. Make category corrections to build your learning data.
            </div>
        );
    }

    const getConfidenceColor = (confidence) => {
        if (confidence >= 75) return 'bg-green-100 text-green-800';
        if (confidence >= 50) return 'bg-yellow-100 text-yellow-800';
        return 'bg-red-100 text-red-800';
    };

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Keyword
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Occurrences
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Confidence
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
                    {patterns.map(pattern => (
                        <tr key={pattern.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <code className="bg-gray-100 px-2 py-1 rounded">{pattern.keyword}</code>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {pattern.category.name}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {pattern.occurrence_count}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                <span
                                    className={`inline-flex px-3 py-1 text-xs font-semibold rounded-full ${getConfidenceColor(
                                        pattern.confidence_score
                                    )}`}
                                >
                                    {pattern.confidence_score}%
                                </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                <span
                                    className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                        pattern.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    }`}
                                >
                                    {pattern.is_active ? 'Active' : 'Disabled'}
                                </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button
                                    onClick={() => onConvert(pattern)}
                                    className="text-blue-600 hover:text-blue-900"
                                    title="Convert to explicit rule"
                                >
                                    Convert
                                </button>
                                <button
                                    onClick={() => onToggle(pattern.id)}
                                    className={`${
                                        pattern.is_active
                                            ? 'text-yellow-600 hover:text-yellow-900'
                                            : 'text-green-600 hover:text-green-900'
                                    }`}
                                    title={pattern.is_active ? 'Disable' : 'Enable'}
                                >
                                    {pattern.is_active ? 'Disable' : 'Enable'}
                                </button>
                                <button
                                    onClick={() => onDelete(pattern.id)}
                                    className="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
