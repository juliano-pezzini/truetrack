import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

    it('renders form with empty fields for create', () => {
        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        expect(screen.getByPlaceholderText(/amazon, groceries, salary/i)).toHaveValue('');
        expect(screen.getByRole('combobox')).toHaveValue('');
        expect(screen.getByPlaceholderText(/lower numbers = higher priority/i)).toHaveValue(null);
    });

    it('renders form with values for edit', () => {
        render(
            <AutoRuleForm
                rule={mockRule}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        expect(screen.getByPlaceholderText(/amazon, groceries, salary/i)).toHaveValue('amazon');

        const categorySelect = screen.getByRole('combobox');
        const priorityInput = screen.getByPlaceholderText(/lower numbers = higher priority/i);

        return waitFor(() => {
            expect(categorySelect).toHaveValue('1');
            expect(priorityInput).toHaveValue(10);
        });
    });

    it('validates pattern field', async () => {
        const user = userEvent.setup();
        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        await user.click(submitButton);

        expect(screen.getByText(/pattern is required/i)).toBeInTheDocument();
    });

    it('validates category selection', async () => {
        const user = userEvent.setup();
        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const patternInput = screen.getByPlaceholderText(/amazon, groceries, salary/i);
        await user.type(patternInput, 'amazon');

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        await user.click(submitButton);

        expect(screen.getByText(/category is required/i)).toBeInTheDocument();
    });

    it('detects overlapping patterns', async () => {
        const user = userEvent.setup();

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

        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const patternInput = screen.getByPlaceholderText(/amazon, groceries, salary/i);
        await user.type(patternInput, 'amazon');

        await waitFor(() => {
            expect(screen.getByText(/potential pattern overlaps/i)).toBeInTheDocument();
        });
    });

    it('submits form with valid data', async () => {
        const user = userEvent.setup();
        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        await user.type(screen.getByPlaceholderText(/amazon, groceries, salary/i), 'amazon');
        await user.selectOptions(screen.getByRole('combobox'), '1');
        await user.type(screen.getByPlaceholderText(/lower numbers = higher priority/i), '10');

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        await user.click(submitButton);

        expect(mockOnSubmit).toHaveBeenCalledWith({
            pattern: 'amazon',
            category_id: '1',
            priority: '10',
        });
    });

    it('calls onCancel when cancel button clicked', async () => {
        const user = userEvent.setup();
        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const cancelButton = screen.getByRole('button', { name: /cancel/i });
        await user.click(cancelButton);

        expect(mockOnCancel).toHaveBeenCalled();
    });

    it('shows loading state during submission', async () => {
        const user = userEvent.setup();
        mockOnSubmit.mockImplementation(
            () => new Promise(resolve => setTimeout(resolve, 100))
        );

        render(
            <AutoRuleForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        await user.type(screen.getByPlaceholderText(/amazon, groceries, salary/i), 'amazon');
        await user.selectOptions(screen.getByRole('combobox'), '1');
        await user.type(screen.getByPlaceholderText(/lower numbers = higher priority/i), '10');

        const submitButton = screen.getByRole('button', { name: /create rule/i });
        await user.click(submitButton);

        await waitFor(() => {
            expect(submitButton).toBeDisabled();
        });
    });
});


