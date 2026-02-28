import { router } from '@inertiajs/react';
import ImportProgress from './ImportProgress';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function ImportHistoryCard({ importData, type, onDelete }) {
    const { id, filename, account, created_at, status, reconciliation, error_report_path } = importData;

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleViewReconciliation = () => {
        if (reconciliation) {
            router.visit(route('reconciliations.show', reconciliation.id));
        }
    };

    const handleCancel = () => {
        // XLSX imports don't support cancellation yet
        if (type === 'xlsx') {
            alert('XLSX import cancellation is not supported yet.');
            return;
        }
        
        const endpoint = route('api.ofx-imports.destroy', id);
            
        if (window.confirm('Are you sure you want to cancel this import?')) {
            router.delete(endpoint, {
                onSuccess: () => {
                    if (onDelete) onDelete(id);
                },
            });
        }
    };

    const handleDownloadFile = () => {
        window.open(`/api/v1/xlsx-imports/${id}/download`, '_blank');
    };

    const handleDownloadErrorReport = () => {
        window.open(`/api/v1/xlsx-imports/${id}/error-report`, '_blank');
    };

    const canCancel = status === 'pending' || status === 'processing';
    const canViewReconciliation = status === 'completed' && reconciliation;
    const hasErrorReport = type === 'xlsx' && status === 'completed' && error_report_path;

    // Type badge styling
    const getTypeBadge = () => {
        const badges = {
            ofx: 'bg-blue-100 text-blue-800',
            xlsx: 'bg-purple-100 text-purple-800',
        };
        return badges[type] || 'bg-gray-100 text-gray-800';
    };

    return (
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
            <div className="space-y-3">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <div className="flex items-center gap-2">
                            <h4 className="font-semibold text-gray-900">
                                {filename}
                            </h4>
                            <span className={`inline-flex rounded px-2 py-0.5 text-xs font-medium ${getTypeBadge()}`}>
                                {type.toUpperCase()}
                            </span>
                        </div>
                        <p className="mt-1 text-sm text-gray-600">
                            {account?.name || 'Unknown Account'}
                        </p>
                    </div>
                </div>

                {/* Progress */}
                <ImportProgress importData={importData} type={type} />

                {/* Action Buttons */}
                {(canViewReconciliation || canCancel || hasErrorReport || (type === 'xlsx' && status === 'completed')) && (
                    <div className="flex flex-wrap gap-2">
                        {canViewReconciliation && (
                            <SecondaryButton
                                onClick={handleViewReconciliation}
                                className="px-3 py-1 text-xs"
                            >
                                View Reconciliation
                            </SecondaryButton>
                        )}
                        
                        {type === 'xlsx' && status === 'completed' && (
                            <SecondaryButton
                                onClick={handleDownloadFile}
                                className="px-3 py-1 text-xs"
                            >
                                Download File
                            </SecondaryButton>
                        )}

                        {hasErrorReport && (
                            <SecondaryButton
                                onClick={handleDownloadErrorReport}
                                className="px-3 py-1 text-xs"
                            >
                                Error Report
                            </SecondaryButton>
                        )}

                        {canCancel && (
                            <DangerButton
                                onClick={handleCancel}
                                className="px-3 py-1 text-xs"
                            >
                                Cancel
                            </DangerButton>
                        )}
                    </div>
                )}

                {/* Footer */}
                <div className="flex items-center justify-between border-t pt-2 text-xs text-gray-500">
                    <span>Uploaded {formatDate(created_at)}</span>
                    {status === 'completed' && reconciliation && (
                        <span className="rounded bg-green-100 px-2 py-1 text-green-700">
                            Reconciliation #{reconciliation.id}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}
