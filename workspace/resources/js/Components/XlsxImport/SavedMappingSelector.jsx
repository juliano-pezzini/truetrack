import { useState, useEffect } from 'react';
import axios from 'axios';

export default function SavedMappingSelector({ accountId, onMappingSelected }) {
    const [mappings, setMappings] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedMappingId, setSelectedMappingId] = useState('');

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
            if (mapping) {
                onMappingSelected(mapping.mapping_config);
            }
        }
    };

    if (loading) {
        return <div className="text-sm text-gray-500">Loading saved mappings...</div>;
    }

    if (mappings.length === 0) {
        return <div className="text-sm text-gray-500">No saved mappings available</div>;
    }

    return (
        <div className="mt-1">
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
        </div>
    );
}
