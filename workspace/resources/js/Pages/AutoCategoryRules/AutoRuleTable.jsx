export default function AutoRuleTable({ rules, loading, onEdit, onDelete, onArchive }) {
    if (loading) {
        return <div className="text-center py-8">Loading rules...</div>;
    }

    if (!rules || rules.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No auto-category rules yet. Create one to get started!
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Priority
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Pattern
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
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
                    {rules.map(rule => (
                        <tr key={rule.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {rule.priority}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <code className="bg-gray-100 px-2 py-1 rounded">{rule.pattern}</code>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {rule.category.name}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                <span
                                    className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                        rule.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    }`}
                                >
                                    {rule.is_active ? 'Active' : 'Archived'}
                                </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button
                                    onClick={() => onEdit(rule)}
                                    className="text-indigo-600 hover:text-indigo-900"
                                >
                                    Edit
                                </button>
                                {rule.is_active && (
                                    <button
                                        onClick={() => onArchive(rule.id)}
                                        className="text-yellow-600 hover:text-yellow-900"
                                    >
                                        Archive
                                    </button>
                                )}
                                <button
                                    onClick={() => onDelete(rule.id)}
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
