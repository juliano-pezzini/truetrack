import { useState, useEffect } from 'react';
import axios from 'axios';
import ConfirmModal from '../ConfirmModal';

export default function SavedMappingSelector({ accountId, onMappingSelected }) {
    const [mappings, setMappings] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedMappingId, setSelectedMappingId] = useState('');
    const [isDeleting, setIsDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState(null);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [mappingToDelete, setMappingToDelete] = useState(null);

    useEffect(() => {
        fetchMappings();
    }, [accountId]);

    const fetchMappings = async () => {
        setLoading(true);
        try {
            const params = {};
            if (accountId) {
                params['filter[account_id]'] = accountId;
            }

            const response = await axios.get('/api/v1/xlsx-column-mappings', { params });
            setMappings(response.data.data);
        } catch (error) {
            console.error('Failed to fetch saved mappings:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleMappingSelect = (e) => {
        const mappingId = e.target.value;
        setSelectedMappingId(mappingId);

        if (mappingId) {
            const mapping = mappings.find((m) => m.id === parseInt(mappingId));
            if (mapping && mapping.mapping_config) {
                // Filter out null/undefined values from the saved mapping
                const cleanedConfig = Object.entries(mapping.mapping_config).reduce((acc, [key, value]) => {
                    if (value !== null && value !== undefined) {
                        acc[key] = value;
                    }
                    return acc;
                }, {});
                onMappingSelected(cleanedConfig);
            }
        }
    };

    const handleDeleteMapping = async () => {
        if (!selectedMappingId) return;

        const mapping = mappings.find((m) => m.id === parseInt(selectedMappingId));
        setMappingToDelete(mapping);
        setShowDeleteConfirm(true);
    };

    const handleConfirmDelete = async () => {
        setIsDeleting(true);
        setDeleteError(null);

        try {
            await axios.delete(`/api/v1/xlsx-column-mappings/${selectedMappingId}`);
            setSelectedMappingId('');
            setShowDeleteConfirm(false);
            setMappingToDelete(null);
            await fetchMappings();
        } catch (error) {
            console.error('Failed to delete saved mapping:', error);
            setDeleteError('Failed to remove saved mapping. Please try again.');
        } finally {
            setIsDeleting(false);
        }
    };

    const handleCancelDelete = () => {
        setShowDeleteConfirm(false);
        setMappingToDelete(null);
    };

    if (loading) {
        return <div className="text-sm text-gray-500">Loading saved mappings...</div>;
    }

    if (mappings.length === 0) {
        return <div className="text-sm text-gray-500">No saved mappings available</div>;
    }

    return (
        <>
            <div className="mt-1 space-y-2">
                <div className="flex gap-2">
                    <select
                        className="block w-full border-gray-300 rounded-md shadow-sm"
                        value={selectedMappingId}
                        onChange={handleMappingSelect}
                    >
                        <option value="">-- Select a saved mapping --</option>
                        {mappings.map((mapping) => (
                            <option key={mapping.id} value={mapping.id}>
                                {mapping.name}
                                {mapping.is_default && ' (Default)'}
                                {mapping.account_id && ` - ${mapping.account?.name || 'Account-specific'}`}
                            </option>
                        ))}
                    </select>
                    <button
                        type="button"
                        onClick={handleDeleteMapping}
                        disabled={!selectedMappingId || isDeleting}
                        className="px-3 py-2 text-sm rounded-md border border-red-300 text-red-700 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {isDeleting ? 'Removing...' : 'Remove'}
                    </button>
                </div>
                {deleteError && (
                    <p className="text-sm text-red-600">{deleteError}</p>
                )}
            </div>
            <ConfirmModal
                show={showDeleteConfirm}
                title="Remove Saved Mapping"
                message={`Remove "${mappingToDelete?.name || 'this mapping'}"? This cannot be undone.`}
                confirmText="Remove"
                cancelText="Cancel"
                onConfirm={handleConfirmDelete}
                onCancel={handleCancelDelete}
                isLoading={isDeleting}
                isDangerous={true}
                maxWidth="sm"
            />
        </>
    );
}
