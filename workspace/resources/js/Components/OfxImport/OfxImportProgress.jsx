export default function OfxImportProgress({ import: importData }) {
    const { processed_count, total_count, status } = importData;

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
        <div className="space-y-2">
            <div className="flex items-center justify-between text-sm">
                <span className="font-medium text-gray-700">
                    {getStatusText()}
                </span>
                {isActive && (
                    <span className="text-gray-600">
                        {processed_count} / {total_count}
                    </span>
                )}
            </div>

            <div className="relative h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    className={`h-full transition-all duration-300 ${getStatusColor()} ${
                        isActive && progress < 100 ? 'animate-pulse' : ''
                    }`}
                    style={{ width: `${progress}%` }}
                />
            </div>

            {status === 'processing' && (
                <p className="text-xs text-gray-500">
                    Processing transactions...
                </p>
            )}

            {status === 'failed' && importData.error_message && (
                <p className="text-xs text-red-600">
                    Error: {importData.error_message}
                </p>
            )}
        </div>
    );
}
