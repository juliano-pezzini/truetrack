import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import AutoRuleForm from '../../../resources/js/Pages/AutoCategoryRules/AutoRuleForm';


jest.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            auth: {
                token: 'test-token',
            },
        },
    }),
}));

describe('AutoRuleForm Component', () => {
    const mockCategories = [
        { id: 1, name: 'Groceries', type: 'expense' },
        { id: 2, name: 'Entertainment', type: 'expense' },
        { id: 3, name: 'Salary', type: 'revenue' },
    ];

    const mockRule = {
        id: 1,
        pattern: 'amazon',
        category_id: 1,
        priority: 10,
        is_active: true,
    };

    const mockOnSubmit = jest.fn();
    const mockOnCancel = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
        global.fetch = jest.fn((url) => {
            if (url.includes('/api/v1/categories')) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({ data: mockCategories }),
                });
            }

            if (url.includes('/api/v1/auto-category-rules')) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({ data: [] }),
                });
            }

            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({}),
            });
        });
    });

    const renderForm = async (props = {}) => {
        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
                {...props}
            />
        );

        await waitFor(() => {
            expect(screen.getByText('Groceries')).toBeInTheDocument();
        });
    };

    it('renders form with empty fields for create', async () => {
        await renderForm();

        expect(screen.getByPlaceholderText(/amazon, groceries, salary/i)).toHaveValue('');
        expect(screen.getByRole('combobox')).toHaveValue('');
        expect(screen.getByPlaceholderText(/lower numbers = higher priority/i)).toHaveValue(null);
    });

    it('renders form with values for edit', async () => {
        await renderForm({ rule: mockRule });

        expect(screen.getByPlaceholderText(/amazon, groceries, salary/i)).toHaveValue('amazon');

        const categorySelect = screen.getByRole('combobox');
        const priorityInput = screen.getByPlaceholderText(/lower numbers = higher priority/i);

        return waitFor(() => {
            expect(categorySelect).toHaveValue('1');
            expect(priorityInput).toHaveValue(10);
        });
    });

    it('validates pattern field', async () => {
        await renderForm();

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        fireEvent.click(submitButton);

        expect(screen.getByText(/pattern is required/i)).toBeInTheDocument();
    });

    it('validates category selection', async () => {
        await renderForm();

        const patternInput = screen.getByPlaceholderText(/amazon, groceries, salary/i);
        fireEvent.change(patternInput, { target: { value: 'amazon' } });

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        fireEvent.click(submitButton);

        expect(screen.getByText(/category is required/i)).toBeInTheDocument();
    });

    it('detects overlapping patterns', async () => {
        global.fetch = jest.fn((url) => {
            if (url.includes('/api/v1/categories')) {
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({ data: mockCategories }),
                });
            }

            if (url.includes('/api/v1/auto-category-rules')) {
                return Promise.resolve({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            data: [
                                { id: 2, pattern: 'amazon store', priority: 20 },
                            ],
                        }),
                });
            }

            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({}),
            });
        });

        await renderForm();

        const patternInput = screen.getByPlaceholderText(/amazon, groceries, salary/i);
        fireEvent.change(patternInput, { target: { value: 'amazon' } });

        await waitFor(() => {
            expect(screen.getByText(/potential pattern overlaps/i)).toBeInTheDocument();
        });
    });

    it('submits form with valid data', async () => {
        mockOnSubmit.mockResolvedValueOnce(undefined);

        await renderForm();

        fireEvent.change(screen.getByPlaceholderText(/amazon, groceries, salary/i), {
            target: { value: 'amazon' },
        });
        fireEvent.change(screen.getByRole('combobox'), {
            target: { value: '1' },
        });
        await waitFor(() => {
            expect(screen.getByRole('combobox')).toHaveValue('1');
        });
        fireEvent.change(screen.getByPlaceholderText(/lower numbers = higher priority/i), {
            target: { value: '10' },
        });

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith({
                pattern: 'amazon',
                category_id: '1',
                priority: '10',
            });
        });

        await waitFor(() => {
            expect(submitButton).not.toBeDisabled();
        });
    });

    it('calls onCancel when cancel button clicked', async () => {
        await renderForm();

        const cancelButton = screen.getByRole('button', { name: /cancel/i });
        fireEvent.click(cancelButton);

        expect(mockOnCancel).toHaveBeenCalled();
    });

});


