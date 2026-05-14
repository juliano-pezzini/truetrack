export function buildPaginationItems(currentPage, lastPage) {
    if (!lastPage || lastPage <= 1) {
        return [];
    }

    if (lastPage <= 7) {
        return Array.from({ length: lastPage }, (_, idx) => ({
            type: 'page',
            value: idx + 1,
        }));
    }

    const items = [];
    const addPage = (page) => {
        items.push({ type: 'page', value: page });
    };

    addPage(1);

    const start = Math.max(2, currentPage - 1);
    const end = Math.min(lastPage - 1, currentPage + 1);

    if (start > 2) {
        items.push({ type: 'ellipsis', key: 'left-ellipsis' });
    }

    for (let page = start; page <= end; page += 1) {
        addPage(page);
    }

    if (end < lastPage - 1) {
        items.push({ type: 'ellipsis', key: 'right-ellipsis' });
    }

    addPage(lastPage);

    return items;
}
