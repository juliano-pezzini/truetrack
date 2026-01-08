import { describe, test, expect } from '@jest/globals';

describe('Example Test Suite', () => {
    test('should pass basic assertion', () => {
        expect(true).toBe(true);
    });

    test('should perform basic math', () => {
        expect(2 + 2).toBe(4);
    });

    test('should work with arrays', () => {
        const arr = [1, 2, 3];
        expect(arr).toHaveLength(3);
        expect(arr).toContain(2);
    });
});
