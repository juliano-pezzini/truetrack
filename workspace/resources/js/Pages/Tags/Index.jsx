import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useEffect, useRef, useState } from 'react';
import normalizeInertiaUrl from '@/Utils/normalizeInertiaUrl';

export default function Index({ auth, tags, filters }) {
    const [filterName, setFilterName] = useState(filters?.filter?.name || '');
    const [selectedTagIds, setSelectedTagIds] = useState([]);
    const selectAllRef = useRef(null);

    const visibleTagIds = tags?.data?.map((tag) => tag.id) || [];
    const selectedTagIdSet = new Set(selectedTagIds);
    const allVisibleSelected = visibleTagIds.length > 0
        && visibleTagIds.every((tagId) => selectedTagIdSet.has(tagId));
    const someVisibleSelected = visibleTagIds.some((tagId) => selectedTagIdSet.has(tagId));

    useEffect(() => {
        if (selectAllRef.current) {
            selectAllRef.current.indeterminate = someVisibleSelected && !allVisibleSelected;
        }
    }, [allVisibleSelected, someVisibleSelected]);

    useEffect(() => {
        setSelectedTagIds([]);
    }, [tags?.data]);

    const previousLinkUrl = tags.links?.length ? normalizeInertiaUrl(tags.links[0].url) : null;
    const nextLinkUrl = tags.links?.length
        ? normalizeInertiaUrl(tags.links[tags.links.length - 1].url)
        : null;

    const applyFilters = () => {
        const params = {};

        if (filterName) {
            params['filter[name]'] = filterName;
        }

        router.get(route('tags.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setFilterName('');
        router.get(route('tags.index'));
    };

    const deleteTag = (tagId) => {
        if (confirm('Are you sure you want to delete this tag?')) {
            router.delete(route('tags.destroy', tagId));
        }
    };

    const toggleTagSelection = (tagId) => {
        setSelectedTagIds((currentSelection) => (
            currentSelection.includes(tagId)
                ? currentSelection.filter((selectedTagId) => selectedTagId !== tagId)
                : [...currentSelection, tagId]
        ));
    };

    const toggleAllVisibleTags = () => {
        setSelectedTagIds((currentSelection) => {
            const currentSelectionSet = new Set(currentSelection);
            const allCurrentlyVisibleSelected = visibleTagIds.length > 0
                && visibleTagIds.every((tagId) => currentSelectionSet.has(tagId));

            if (allCurrentlyVisibleSelected) {
                return currentSelection.filter(
                    (tagId) => !visibleTagIds.includes(tagId)
                );
            }

            return Array.from(new Set([...currentSelection, ...visibleTagIds]));
        });
    };

    const deleteSelectedTags = () => {
        if (selectedTagIds.length === 0) {
            return;
        }

        const tagCount = selectedTagIds.length;
        const tagLabel = tagCount === 1 ? 'tag' : 'tags';

        if (confirm(`Are you sure you want to delete ${tagCount} selected ${tagLabel}?`)) {
            router.delete(route('tags.bulk-destroy'), {
                data: {
                    tag_ids: selectedTagIds,
                },
                preserveScroll: true,
                onSuccess: () => setSelectedTagIds([]),
            });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
                        Tags
                    </h2>
                    <Link
                        href={route('tags.create')}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        Create Tag
                    </Link>
                </div>
            }
        >
            <Head title="Tags" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
                        <div className="p-6">
                            <div className="flex flex-wrap gap-4 items-end">
                                <div className="flex-1 min-w-[200px]">
                                    <label className="block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300">
                                        Search by Name
                                    </label>
                                    <input
                                        type="text"
                                        value={filterName}
                                        onChange={(e) => setFilterName(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && applyFilters()}
                                        placeholder="Enter tag name..."
                                        className="w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                </div>

                                <div className="flex gap-2">
                                    <button
                                        onClick={applyFilters}
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                    >
                                        Apply Filters
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tags List */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                        {tags.data.length > 0 ? (
                            <div>
                                <div className="p-6 pb-0">
                                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                Tag List
                                            </h3>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {selectedTagIds.length > 0
                                                    ? `${selectedTagIds.length} selected`
                                                    : 'Select rows to delete them in bulk.'}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={deleteSelectedTags}
                                            disabled={selectedTagIds.length === 0}
                                            className="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300 dark:bg-red-500 dark:hover:bg-red-400 dark:disabled:bg-red-900/50"
                                        >
                                            Delete Selected ({selectedTagIds.length})
                                        </button>
                                    </div>
                                </div>

                                <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900/40">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                <input
                                                    ref={selectAllRef}
                                                    type="checkbox"
                                                    checked={allVisibleSelected}
                                                    onChange={toggleAllVisibleTags}
                                                    aria-label="Select all visible tags"
                                                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                                                />
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                Tag
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                Color
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                Created
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                        {tags.data.map((tag) => {
                                            const isSelected = selectedTagIdSet.has(tag.id);

                                            return (
                                            <tr key={tag.id} className={`hover:bg-gray-50 dark:hover:bg-gray-700/40 ${isSelected ? 'bg-indigo-50/60 dark:bg-indigo-900/20' : ''}`}>
                                                <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => toggleTagSelection(tag.id)}
                                                        aria-label={`Select tag ${tag.id}`}
                                                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                                                    />
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div
                                                            className="w-4 h-4 rounded-full mr-3"
                                                            style={{ backgroundColor: tag.color }}
                                                        ></div>
                                                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {tag.name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <code className="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-200">
                                                        {tag.color}
                                                    </code>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {new Date(tag.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <Link
                                                        href={route('tags.edit', tag.id)}
                                                        className="mr-4 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <button
                                                        onClick={() => deleteTag(tag.id)}
                                                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        ) : (
                            <div className="p-6 text-center text-gray-500 dark:text-gray-400">
                                No tags found. Create your first tag to get started!
                            </div>
                        )}

                        {/* Pagination */}
                        {tags.links && tags.links.length > 3 && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 dark:bg-gray-800 dark:border-gray-700">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {previousLinkUrl && (
                                            <Link
                                                href={previousLinkUrl}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {nextLinkUrl && (
                                            <Link
                                                href={nextLinkUrl}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                                <p className="text-sm text-gray-700 dark:text-gray-300">
                                                Showing{' '}
                                                <span className="font-medium">{tags.meta?.from || 0}</span>{' '}
                                                to <span className="font-medium">{tags.meta?.to || 0}</span>{' '}
                                                of{' '}
                                                <span className="font-medium">{tags.meta?.total || 0}</span>{' '}
                                                results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {tags.links.map((link, index) => {
                                                    const href = normalizeInertiaUrl(link.url);

                                                    return (
                                                        <Link
                                                            key={index}
                                                            href={href || '#'}
                                                            preserveState
                                                            preserveScroll
                                                            className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                                link.active
                                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 dark:bg-indigo-900/30 dark:border-indigo-400 dark:text-indigo-300'
                                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                                                            } ${!href ? 'cursor-not-allowed opacity-50' : ''}`}
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                        />
                                                    );
                                                })}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
