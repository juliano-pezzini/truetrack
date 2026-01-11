import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';

export default function Index({ auth, categories, filters, categoryTypes }) {
    const [filterType, setFilterType] = useState(filters?.type || '');
    const [filterActive, setFilterActive] = useState(filters?.is_active ?? '');
    const [filterParentOnly, setFilterParentOnly] = useState(filters?.parent_only || false);

    const typeOptions = [
        { value: '', label: 'All Types' },
        ...categoryTypes.map(type => ({ value: type.value, label: type.label })),
    ];

    const activeOptions = [
        { value: '', label: 'All Categories' },
        { value: '1', label: 'Active Only' },
        { value: '0', label: 'Inactive Only' },
    ];

    const applyFilters = () => {
        const params = {};

        if (filterType) {
            params.type = filterType;
        }

        if (filterActive !== '') {
            params.is_active = filterActive;
        }

        if (filterParentOnly) {
            params.parent_only = '1';
        }

        router.get(route('categories.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setFilterType('');
        setFilterActive('');
        setFilterParentOnly(false);
        router.get(route('categories.index'));
    };

    const deleteCategory = (categoryId) => {
        if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            router.delete(route('categories.destroy', categoryId));
        }
    };

    const getCategoryTypeClass = (type) => {
        return type === 'revenue' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Categories
                    </h2>
                    <Link
                        href={route('categories.create')}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        Create Category
                    </Link>
                </div>
            }
        >
            <Head title="Categories" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Filters</h3>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label htmlFor="filterType" className="block text-sm font-medium text-gray-700 mb-2">
                                        Category Type
                                    </label>
                                    <select
                                        id="filterType"
                                        value={filterType}
                                        onChange={(e) => setFilterType(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        {typeOptions.map(option => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="filterActive" className="block text-sm font-medium text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        id="filterActive"
                                        value={filterActive}
                                        onChange={(e) => setFilterActive(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        {activeOptions.map(option => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="flex items-end">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={filterParentOnly}
                                            onChange={(e) => setFilterParentOnly(e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            Parent Categories Only
                                        </span>
                                    </label>
                                </div>

                                <div className="flex items-end gap-2">
                                    <button
                                        onClick={applyFilters}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                                    >
                                        Apply
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Categories Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {categories.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <p className="text-gray-500 mb-4">No categories found.</p>
                                    <Link
                                        href={route('categories.create')}
                                        className="text-indigo-600 hover:text-indigo-800"
                                    >
                                        Create your first category
                                    </Link>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Type
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Parent
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Description
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {categories.data.map((category) => (
                                                <tr key={category.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            {!category.is_parent && (
                                                                <span className="mr-2 text-gray-400">└─</span>
                                                            )}
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {category.name}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getCategoryTypeClass(category.type)}`}>
                                                            {category.type_label}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {category.parent?.name || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                                        {category.description || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${category.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                                            {category.is_active ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('categories.edit', category.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 mr-4"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteCategory(category.id)}
                                                            className="text-red-600 hover:text-red-900"
                                                            disabled={category.has_children}
                                                            title={category.has_children ? 'Cannot delete category with subcategories' : 'Delete category'}
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {categories.data.length > 0 && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing <span className="font-medium">{categories.from}</span> to{' '}
                                        <span className="font-medium">{categories.to}</span> of{' '}
                                        <span className="font-medium">{categories.total}</span> results
                                    </div>
                                    <div className="flex gap-2">
                                        {categories.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-2 rounded-md text-sm ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : link.url
                                                        ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
