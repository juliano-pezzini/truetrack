import { router } from '@inertiajs/react';
import ImportProgress from './ImportProgress';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function ImportHistoryCard({ importData, type: legacyType, onDelete }) {
    const {
        id,
        type,
        filename,
        account,
        created_at,
        status,
        reconciliation,
        reconciliation_id,
        error_report_path,
        has_error_report,
        has_errors,
    } = importData;
    const importType = type ?? legacyType;
    const reconciliationId = reconciliation?.id ?? reconciliation_id;

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
        if (reconciliationId) {
            router.visit(route('reconciliations.show', reconciliationId));
        }
    };

    const handleCancel = () => {
        // XLSX imports don't support cancellation yet
        if (importType === 'xlsx') {
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
        window.open(route('api.xlsx-imports.download', id), '_blank', 'noopener,noreferrer');
    };

    const handleDownloadErrorReport = () => {
        window.open(route('api.xlsx-imports.error-report', id), '_blank', 'noopener,noreferrer');
    };

    const canCancel = importType === 'ofx' && (status === 'pending' || status === 'processing');
    const canViewReconciliation = status === 'completed' && Boolean(reconciliationId);
    const hasErrorReport =
        importType === 'xlsx' &&
        status === 'completed' &&
        Boolean(has_errors || has_error_report || error_report_path);

    // Type badge styling
    const getTypeBadge = () => {
        const badges = {
            ofx: 'bg-blue-100 text-blue-800',
            xlsx: 'bg-purple-100 text-purple-800',
        };
        return badges[importType] || 'bg-gray-100 text-gray-800';
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
                                {importType?.toUpperCase() ?? 'IMPORT'}
                            </span>
                        </div>
                        <p className="mt-1 text-sm text-gray-600">
                            {account?.name || 'Unknown Account'}
                        </p>
                    </div>
                </div>

                {/* Progress */}
                <ImportProgress importData={importData} type={importType} />

                {/* Action Buttons */}
                {(canViewReconciliation || canCancel || hasErrorReport || (importType === 'xlsx' && status === 'completed')) && (
                    <div className="flex flex-wrap gap-2">
                        {canViewReconciliation && (
                            <SecondaryButton
                                onClick={handleViewReconciliation}
                                className="px-3 py-1 text-xs"
                            >
                                View Reconciliation
                            </SecondaryButton>
                        )}
                        
                        {importType === 'xlsx' && status === 'completed' && (
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
                    {status === 'completed' && reconciliationId && (
                        <span className="rounded bg-green-100 px-2 py-1 text-green-700">
                            Reconciliation #{reconciliationId}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}
