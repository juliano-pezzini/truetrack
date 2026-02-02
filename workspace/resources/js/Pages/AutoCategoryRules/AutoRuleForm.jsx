import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

export default function AutoRuleForm({ rule, onSubmit, onCancel }) {
    const { auth } = usePage().props;
    const [formData, setFormData] = useState({
        pattern: '',
        category_id: '',
        priority: '',
    });
    const [categories, setCategories] = useState([]);
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [overlappingWarnings, setOverlappingWarnings] = useState([]);

    useEffect(() => {
        if (rule) {
            setFormData({
                pattern: rule.pattern,
                category_id: rule.category.id,
                priority: rule.priority,
            });
        }
        fetchCategories();
    }, [rule]);

    const fetchCategories = async () => {
        try {
            const response = await fetch('/api/v1/categories', {
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });
            const data = await response.json();
            setCategories(data.data || []);
        } catch (error) {
            console.error('Failed to fetch categories:', error);
        }
    };

    const checkOverlaps = async () => {
        try {
            const response = await fetch('/api/v1/auto-category-rules', {
                headers: {
                    'Authorization': `Bearer ${auth.token}`,
                },
            });
            const data = await response.json();
            const rules = data.data || [];

            const overlaps = rules.filter(
                r =>
                    formData.pattern &&
                    r.pattern !== formData.pattern &&
                    (formData.pattern.includes(r.pattern) || r.pattern.includes(formData.pattern))
            );

            setOverlappingWarnings(overlaps);
        } catch (error) {
            console.error('Error checking overlaps:', error);
        }
    };

    useEffect(() => {
        const timer = setTimeout(() => {
            if (formData.pattern) {
                checkOverlaps();
            }
        }, 500);

        return () => clearTimeout(timer);
    }, [formData.pattern]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value,
        }));
        setErrors(prev => ({
            ...prev,
            [name]: '',
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        try {
            // Validate on client side
            const newErrors = {};
            if (!formData.pattern.trim()) {
                newErrors.pattern = 'Pattern is required';
            }
            if (!formData.category_id) {
                newErrors.category_id = 'Category is required';
            }
            if (!formData.priority) {
                newErrors.priority = 'Priority is required';
            }

            if (Object.keys(newErrors).length > 0) {
                setErrors(newErrors);
                setLoading(false);
                return;
            }

            onSubmit(formData);
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Pattern *
                </label>
                <TextInput
                    name="pattern"
                    value={formData.pattern}
                    onChange={handleChange}
                    placeholder="e.g., amazon, groceries, salary"
                    className="w-full"
                />
                {errors.pattern && <InputError message={errors.pattern} />}
            </div>

            {overlappingWarnings.length > 0 && (
                <div className="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <p className="text-sm font-medium text-yellow-800 mb-2">
                        ⚠️ Potential Pattern Overlaps:
                    </p>
                    <ul className="text-sm text-yellow-700 space-y-1">
                        {overlappingWarnings.map(w => (
                            <li key={w.id}>
                                Pattern "{w.pattern}" (Priority: {w.priority})
                            </li>
                        ))}
                    </ul>
                    <p className="text-xs text-yellow-600 mt-2">
                        Note: Rules are matched in priority order. Higher priority rules match first.
                    </p>
                </div>
            )}

            <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Category *
                </label>
                <select
                    name="category_id"
                    value={formData.category_id}
                    onChange={handleChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                >
                    <option value="">Select a category</option>
                    {categories.map(cat => (
                        <option key={cat.id} value={cat.id}>
                            {cat.name}
                        </option>
                    ))}
                </select>
                {errors.category_id && <InputError message={errors.category_id} />}
            </div>

            <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Priority *
                </label>
                <TextInput
                    name="priority"
                    type="number"
                    value={formData.priority}
                    onChange={handleChange}
                    placeholder="e.g., 10 (lower numbers = higher priority)"
                    min="1"
                    max="1000"
                    className="w-full"
                />
                {errors.priority && <InputError message={errors.priority} />}
                <p className="text-xs text-gray-500 mt-1">
                    Lower numbers execute first. Use gaps of 10 (10, 20, 30...) for reordering.
                </p>
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t">
                <SecondaryButton onClick={onCancel} disabled={loading}>
                    Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" disabled={loading}>
                    {loading ? 'Saving...' : rule ? 'Update Rule' : 'Create Rule'}
                </PrimaryButton>
            </div>
        </form>
    );
}
