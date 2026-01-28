import { useState, useEffect } from 'react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function XlsxImportProgress({ importId, onComplete }) {
    const [importData, setImportData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [polling, setPolling] = useState(true);

    useEffect(() => {
        fetchImportStatus();
        const interval = setInterval(() => {
            if (polling) {
                fetchImportStatus();
            }
        }, 2000); // Poll every 2 seconds

        return () => clearInterval(interval);
    }, [importId, polling]);

    const fetchImportStatus = async () => {
        try {
            const response = await axios.get(`/api/v1/xlsx-imports/${importId}`);
            setImportData(response.data.data);
            setLoading(false);

            // Stop polling if completed or failed
            if (response.data.data.status === 'completed' || response.data.data.status === 'failed') {
                setPolling(false);
                if (response.data.data.status === 'completed' && onComplete) {
                    onComplete(response.data.data);
                }
            }
        } catch (error) {
            console.error('Failed to fetch import status:', error);
            setLoading(false);
            setPolling(false);
        }
    };

    const handleDownloadFile = () => {
        window.open(`/api/v1/xlsx-imports/${importId}/download`, '_blank');
    };

    const handleDownloadErrorReport = () => {
        window.open(`/api/v1/xlsx-imports/${importId}/error-report`, '_blank');
    };

    if (loading) {
        return <div className="text-center py-8">Loading import status...</div>;
    }

    if (!importData) {
        return <div className="text-center py-8 text-red-600">Import not found</div>;
    }

    const getStatusColor = (status) => {
        switch (status) {
            case 'completed':
                return 'text-green-600 bg-green-100';
            case 'processing':
                return 'text-blue-600 bg-blue-100';
            case 'failed':
                return 'text-red-600 bg-red-100';
            case 'pending':
                return 'text-yellow-600 bg-yellow-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    return (
        <div className="bg-white rounded-lg shadow-md p-6 space-y-4">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-semibold">{importData.filename}</h3>
                    <p className="text-sm text-gray-600">{new Date(importData.created_at).toLocaleString()}</p>
                </div>
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(importData.status)}`}>
                    {importData.status.charAt(0).toUpperCase() + importData.status.slice(1)}
                </span>
            </div>

            {/* Progress Bar */}
            {(importData.status === 'processing' || importData.status === 'completed') && (
                <div>
                    <div className="flex justify-between text-sm mb-1">
                        <span>Progress</span>
                        <span className="font-medium">{importData.progress_percentage.toFixed(1)}%</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                        <div
                            className="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                            style={{ width: `${importData.progress_percentage}%` }}
                        ></div>
                    </div>
                </div>
            )}

            {/* Statistics */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{importData.total_count}</div>
                    <div className="text-sm text-gray-600">Total Rows</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-bold text-green-600">{importData.processed_count}</div>
                    <div className="text-sm text-gray-600">Processed</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-bold text-yellow-600">{importData.skipped_count}</div>
                    <div className="text-sm text-gray-600">Skipped</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-bold text-orange-600">{importData.duplicate_count}</div>
                    <div className="text-sm text-gray-600">Duplicates</div>
                </div>
            </div>

            {/* Error Message */}
            {importData.error_message && (
                <div className="bg-red-50 border border-red-200 rounded-md p-4">
                    <p className="text-sm text-red-800">
                        <span className="font-medium">Error:</span> {importData.error_message}
                    </p>
                </div>
            )}

            {/* Success Message */}
            {importData.status === 'completed' && (
                <div className="bg-green-50 border border-green-200 rounded-md p-4">
                    <p className="text-sm text-green-800">
                        <span className="font-medium">Import completed successfully!</span>
                        {importData.reconciliation_id && (
                            <span className="ml-2">
                                <a
                                    href={`/reconciliations/${importData.reconciliation_id}`}
                                    className="underline hover:text-green-900"
                                >
                                    View Reconciliation
                                </a>
                            </span>
                        )}
                    </p>
                </div>
            )}

            {/* Action Buttons */}
            <div className="flex flex-wrap gap-2 pt-4">
                <SecondaryButton onClick={handleDownloadFile}>
                    Download Original File
                </SecondaryButton>

                {importData.has_errors && (
                    <SecondaryButton onClick={handleDownloadErrorReport}>
                        Download Error Report
                    </SecondaryButton>
                )}

                {importData.status === 'completed' && onComplete && (
                    <PrimaryButton onClick={() => window.location.reload()}>
                        Start New Import
                    </PrimaryButton>
                )}
            </div>
        </div>
    );
}
