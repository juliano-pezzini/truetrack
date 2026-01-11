import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect } from 'react';

export default function Edit({ auth, category, categoryTypes, parentCategories }) {
    const { data, setData, put, processing, errors } = useForm({
        name: category.name || '',
        type: category.type || 'expense',
        description: category.description || '',
        parent_id: category.parent_id || '',
        is_active: category.is_active ?? true,
    });

    // Filter parent categories by selected type (excluding self)
    const filteredParentCategories = parentCategories.filter(
        parent => parent.type === data.type && parent.id !== category.id
    );

    // Clear parent_id when type changes
    useEffect(() => {
        if (data.parent_id) {
            const selectedParent = parentCategories.find(p => p.id.toString() === data.parent_id.toString());
            if (selectedParent && selectedParent.type !== data.type) {
                setData('parent_id', '');
            }
        }
    }, [data.type]);

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('categories.update', category.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Category: {category.name}
                </h2>
            }
        >
            <Head title={`Edit ${category.name}`} />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit}>
                                {/* Category Name */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="name" value="Category Name" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full"
                                        autoFocus
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                {/* Category Type */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="type" value="Category Type" />
                                    <select
                                        id="type"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        {categoryTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.type} className="mt-2" />
                                    <p className="mt-1 text-sm text-gray-600">
                                        Revenue categories for income, Expense categories for spending.
                                    </p>
                                </div>

                                {/* Parent Category */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="parent_id" value="Parent Category (Optional)" />
                                    <select
                                        id="parent_id"
                                        value={data.parent_id}
                                        onChange={(e) => setData('parent_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">None - This is a parent category</option>
                                        {filteredParentCategories.map((parent) => (
                                            <option key={parent.id} value={parent.id}>
                                                {parent.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.parent_id} className="mt-2" />
                                    <p className="mt-1 text-sm text-gray-600">
                                        Create subcategories by selecting a parent. Must be the same type.
                                    </p>
                                </div>

                                {/* Description */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="description" value="Description (Optional)" />
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="3"
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>

                                {/* Is Active */}
                                <div className="mb-6">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-600">
                                            Category is active
                                        </span>
                                    </label>
                                    <InputError message={errors.is_active} className="mt-2" />
                                </div>

                                {/* Warning if has children */}
                                {category.has_children && (
                                    <div className="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                        <p className="text-sm text-yellow-800">
                                            <strong>Note:</strong> This category has subcategories. Changing the type will affect all subcategories.
                                        </p>
                                    </div>
                                )}

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end gap-4">
                                    <Link
                                        href={route('categories.index')}
                                        className="text-sm text-gray-600 hover:text-gray-900"
                                    >
                                        Cancel
                                    </Link>
                                    <PrimaryButton disabled={processing}>
                                        Update Category
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
