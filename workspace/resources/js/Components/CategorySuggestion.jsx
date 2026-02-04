import { useMemo } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function CategorySuggestion({
    suggestion,
    description,
    onAccept,
    onReject,
    loading = false
}) {
    // Highlight matched text in description
    const highlightedDescription = useMemo(() => {
        if (!suggestion?.matched_keywords || !description) {
            return description;
        }

        let highlighted = description;
        const keywords = Array.isArray(suggestion.matched_keywords)
            ? suggestion.matched_keywords
            : [suggestion.matched_keywords];

        // Sort keywords by length (longest first) to avoid partial replacements
        const sortedKeywords = [...keywords].sort((a, b) => b.length - a.length);

        sortedKeywords.forEach(keyword => {
            const regex = new RegExp(`(${keyword})`, 'gi');
            highlighted = highlighted.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
        });

        return highlighted;
    }, [description, suggestion]);

    if (!suggestion || !suggestion.suggested_category_id) {
        return null;
    }

    const confidenceBadgeColor = suggestion.confidence_score >= 90
        ? 'bg-green-100 text-green-800'
        : suggestion.confidence_score >= 75
            ? 'bg-blue-100 text-blue-800'
            : 'bg-yellow-100 text-yellow-800';

    const sourceLabel = {
        'rule_exact': 'Exact Rule Match',
        'rule_fuzzy': 'Fuzzy Rule Match',
        'learned_keyword': 'Learned Pattern',
        'manual_suggestion': 'Manual Suggestion',
    }[suggestion.source] || 'Auto-Suggested';

    return (
        <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <p className="text-sm font-medium text-blue-900">
                            Suggested Category
                        </p>
                        <span className={`px-2 py-0.5 rounded text-xs font-medium ${confidenceBadgeColor}`}>
                            {suggestion.confidence_score}% confident
                        </span>
                        <span className="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
                            {sourceLabel}
                        </span>
                    </div>

                    <p className="text-base font-semibold text-blue-900 mb-1">
                        {suggestion.category?.name || 'Unknown Category'}
                    </p>

                    {suggestion.matched_keywords && suggestion.matched_keywords.length > 0 && (
                        <p className="text-xs text-blue-600 mb-2">
                            Matched: "{suggestion.matched_keywords.join('", "')}"
                        </p>
                    )}

                    {description && (
                        <div className="mt-2 text-sm text-blue-700">
                            <strong>Description:</strong>{' '}
                            <span dangerouslySetInnerHTML={{ __html: highlightedDescription }} />
                        </div>
                    )}

                    {suggestion.should_auto_apply && (
                        <div className="mt-2 flex items-center gap-1 text-xs text-blue-600">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                            <span>High confidence - will be auto-applied on save</span>
                        </div>
                    )}
                </div>

                <div className="flex gap-2 ml-4">
                    <PrimaryButton
                        onClick={onAccept}
                        disabled={loading}
                        className="px-3 py-1 text-sm"
                    >
                        Accept
                    </PrimaryButton>
                    <SecondaryButton
                        onClick={onReject}
                        disabled={loading}
                        className="px-3 py-1 text-sm"
                    >
                        Reject
                    </SecondaryButton>
                </div>
            </div>

            {suggestion.alternatives && suggestion.alternatives.length > 0 && (
                <details className="mt-3 pt-3 border-t border-blue-200">
                    <summary className="text-xs text-blue-600 cursor-pointer hover:underline font-medium">
                        Other suggestions ({suggestion.alternatives.length})
                    </summary>
                    <ul className="mt-2 space-y-1">
                        {suggestion.alternatives.map((alt, idx) => (
                            <li
                                key={idx}
                                className="text-xs text-blue-700 flex items-center justify-between p-2 bg-white rounded hover:bg-blue-50 cursor-pointer"
                                onClick={() => onAccept(alt)}
                            >
                                <div>
                                    <span className="font-medium">{alt.category?.name}</span>
                                    <span className="ml-2 text-blue-500">({alt.confidence_score}%)</span>
                                    {alt.source && (
                                        <span className="ml-2 text-gray-500 text-xs">
                                            via {alt.source.replace('_', ' ')}
                                        </span>
                                    )}
                                </div>
                                <button className="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    Use this
                                </button>
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </div>
    );
}
