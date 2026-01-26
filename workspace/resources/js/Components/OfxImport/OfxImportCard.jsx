import { router } from '@inertiajs/react';
import OfxImportProgress from './OfxImportProgress';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function OfxImportCard({ import: importData, onDelete }) {
    const { id, filename, account, created_at, status, reconciliation } =
        importData;

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
        if (window.confirm('Are you sure you want to cancel this import?')) {
            router.delete(route('api.ofx-imports.destroy', id), {
                onSuccess: () => {
                    if (onDelete) onDelete(id);
                },
            });
        }
    };

    const canCancel = status === 'pending' || status === 'processing';
    const canViewReconciliation = status === 'completed' && reconciliation;

    return (
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
            <div className="space-y-3">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <h4 className="font-semibold text-gray-900">
                            {filename}
                        </h4>
                        <p className="text-sm text-gray-600">
                            {account?.name || 'Unknown Account'}
                        </p>
                    </div>
                    <div className="flex space-x-2">
                        {canViewReconciliation && (
                            <SecondaryButton
                                onClick={handleViewReconciliation}
                                className="py-1 px-3 text-xs"
                            >
                                View
                            </SecondaryButton>
                        )}
                        {canCancel && (
                            <DangerButton
                                onClick={handleCancel}
                                className="py-1 px-3 text-xs"
                            >
                                Cancel
                            </DangerButton>
                        )}
                    </div>
                </div>

                {/* Progress */}
                <OfxImportProgress import={importData} />

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
