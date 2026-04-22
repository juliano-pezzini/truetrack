import { buildPaginationItems } from '@/Pages/Imports/pagination';

describe('Imports pagination helper', () => {
    test('returns empty list when there is no pagination', () => {
        expect(buildPaginationItems(1, 0)).toEqual([]);
        expect(buildPaginationItems(1, 1)).toEqual([]);
    });

    test('returns all pages when total pages is small', () => {
        expect(buildPaginationItems(3, 5)).toEqual([
            { type: 'page', value: 1 },
            { type: 'page', value: 2 },
            { type: 'page', value: 3 },
            { type: 'page', value: 4 },
            { type: 'page', value: 5 },
        ]);
    });

    test('returns truncated items with both ellipses for middle page', () => {
        expect(buildPaginationItems(6, 12)).toEqual([
            { type: 'page', value: 1 },
            { type: 'ellipsis', key: 'left-ellipsis' },
            { type: 'page', value: 5 },
            { type: 'page', value: 6 },
            { type: 'page', value: 7 },
            { type: 'ellipsis', key: 'right-ellipsis' },
            { type: 'page', value: 12 },
        ]);
    });

    test('returns truncated items near start without left ellipsis', () => {
        expect(buildPaginationItems(2, 12)).toEqual([
            { type: 'page', value: 1 },
            { type: 'page', value: 2 },
            { type: 'page', value: 3 },
            { type: 'ellipsis', key: 'right-ellipsis' },
            { type: 'page', value: 12 },
        ]);
    });

    test('returns truncated items near end without right ellipsis', () => {
        expect(buildPaginationItems(11, 12)).toEqual([
            { type: 'page', value: 1 },
            { type: 'ellipsis', key: 'left-ellipsis' },
            { type: 'page', value: 10 },
            { type: 'page', value: 11 },
            { type: 'page', value: 12 },
        ]);
    });
});
