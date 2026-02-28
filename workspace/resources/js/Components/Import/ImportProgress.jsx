export default function ImportProgress({ importData, type = 'ofx' }) {
    const { processed_count, total_count, status, skipped_count, duplicate_count } = importData;

    const getProgressPercentage = () => {
        if (total_count === 0) return 0;
        return Math.round((processed_count / total_count) * 100);
    };

    const getStatusColor = () => {
        switch (status) {
            case 'completed':
                return 'bg-green-500';
            case 'failed':
                return 'bg-red-500';
            case 'processing':
                return 'bg-blue-500';
            case 'pending':
                return 'bg-yellow-500';
            default:
                return 'bg-gray-500';
        }
    };

    const getStatusBadgeColor = () => {
        switch (status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            case 'processing':
                return 'bg-blue-100 text-blue-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusText = () => {
        switch (status) {
            case 'completed':
                return 'Completed';
            case 'failed':
                return 'Failed';
            case 'processing':
                return 'Processing';
            case 'pending':
                return 'Pending';
            default:
                return 'Unknown';
        }
    };

    const progress = getProgressPercentage();
    const isActive = status === 'processing' || status === 'pending';

    return (
        <div className="space-y-3">
            {/* Status Badge and Progress */}
            <div className="flex items-center justify-between">
                <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getStatusBadgeColor()}`}>
                    {getStatusText()}
                </span>
                {isActive && (
                    <span className="text-sm font-medium text-gray-600">
                        {processed_count} / {total_count}
                    </span>
                )}
            </div>

            {/* Progress Bar */}
            <div className="relative h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    className={`h-full transition-all duration-300 ${getStatusColor()} ${
                        isActive && progress < 100 ? 'animate-pulse' : ''
                    }`}
                    style={{ width: `${progress}%` }}
                />
            </div>

            {/* Stats for XLSX imports */}
            {type === 'xlsx' && (status === 'completed' || status === 'processing') && (
                <div className="grid grid-cols-4 gap-2 text-center">
                    <div className="rounded-lg bg-blue-50 p-2">
                        <div className="text-xs text-gray-600">Total</div>
                        <div className="text-lg font-semibold text-blue-900">{total_count}</div>
                    </div>
                    <div className="rounded-lg bg-green-50 p-2">
                        <div className="text-xs text-gray-600">Processed</div>
                        <div className="text-lg font-semibold text-green-900">{processed_count}</div>
                    </div>
                    {skipped_count !== undefined && (
                        <div className="rounded-lg bg-yellow-50 p-2">
                            <div className="text-xs text-gray-600">Skipped</div>
                            <div className="text-lg font-semibold text-yellow-900">{skipped_count}</div>
                        </div>
                    )}
                    {duplicate_count !== undefined && (
                        <div className="rounded-lg bg-purple-50 p-2">
                            <div className="text-xs text-gray-600">Duplicates</div>
                            <div className="text-lg font-semibold text-purple-900">{duplicate_count}</div>
                        </div>
                    )}
                </div>
            )}

            {/* Status Messages */}
            {status === 'processing' && (
                <p className="text-xs text-gray-500">
                    Processing transactions... This may take a few moments.
                </p>
            )}

            {status === 'pending' && (
                <p className="text-xs text-gray-500">
                    Import queued. Processing will begin shortly.
                </p>
            )}

            {status === 'failed' && importData.error_message && (
                <div className="rounded-md bg-red-50 p-3">
                    <p className="text-xs font-medium text-red-800">
                        Error: {importData.error_message}
                    </p>
                </div>
            )}

            {status === 'completed' && (
                <div className="rounded-md bg-green-50 p-3">
                    <p className="text-xs font-medium text-green-800">
                        ✓ Import completed successfully!
                    </p>
                </div>
            )}
        </div>
    );
}
