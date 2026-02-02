import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TestCoverageModal from '../../../resources/js/Pages/AutoCategoryRules/TestCoverageModal';


jest.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            auth: {
                token: 'test-token',
            },
        },
    }),
}));

describe('TestCoverageModal Component', () => {
    const mockOnClose = jest.fn();
    const mockCoverageResult = {
        total_uncategorized: 150,
        would_be_categorized: 120,
        coverage_percentage: 80,
        by_category: [
            { category_id: 1, category_name: 'Groceries', count: 50, source: 'rule' },
            { category_id: 2, category_name: 'Utilities', count: 45, source: 'learned' },
            { category_id: 3, category_name: 'Entertainment', count: 25, source: 'rule' },
        ],
        uncovered_reasons: {
            missing_description: 20,
            no_matching_pattern: 10,
        },
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders modal with form fields', () => {
        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        expect(screen.getByLabelText(/from date/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/to date/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /test coverage/i })).toBeInTheDocument();
    });

    it('does not render when show is false', () => {
        const { container } = render(
            <TestCoverageModal show={false} onClose={mockOnClose} />
        );

        expect(container.querySelector('[role="dialog"]')).not.toBeInTheDocument();
    });

    it('submits form with valid dates', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        await user.type(fromDateInput, '2026-01-01');
        await user.type(toDateInput, '2026-01-31');

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        await user.click(testButton);

        await waitFor(() => {
            expect(global.fetch).toHaveBeenCalled();
        });
    });

    it('displays coverage results', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        await user.type(fromDateInput, '2026-01-01');
        await user.type(toDateInput, '2026-01-31');

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        await user.click(testButton);

        await waitFor(() => {
            expect(screen.getByText(/150/)).toBeInTheDocument();
            expect(screen.getByText(/80/)).toBeInTheDocument();
        });
    });

    it('displays coverage percentage correctly', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        await user.type(fromDateInput, '2026-01-01');
        await user.type(toDateInput, '2026-01-31');

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        await user.click(testButton);

        await waitFor(() => {
            expect(screen.getByText('80%')).toBeInTheDocument();
        });
    });

    it('displays category breakdown', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        await user.type(fromDateInput, '2026-01-01');
        await user.type(toDateInput, '2026-01-31');

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        await user.click(testButton);

        await waitFor(() => {
            expect(screen.getByText(/groceries/i)).toBeInTheDocument();
            expect(screen.getByText(/utilities/i)).toBeInTheDocument();
        });
    });

    it('handles API errors gracefully', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ message: 'Server error' }),
                ok: false,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        await user.type(fromDateInput, '2026-01-01');
        await user.type(toDateInput, '2026-01-31');

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        await user.click(testButton);

        await waitFor(() => {
            expect(screen.getByText(/failed to test coverage/i)).toBeInTheDocument();
        });
    });

    it('shows loading state during request', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(
            () =>
                new Promise(resolve =>
                    setTimeout(
                        () =>
                            resolve({
                                json: () => Promise.resolve({ data: mockCoverageResult }),
                                ok: true,
                            }),
                        100
                    )
                )
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        await user.type(fromDateInput, '2026-01-01');
        await user.type(toDateInput, '2026-01-31');

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        await user.click(testButton);

        expect(testButton).toBeDisabled();
    });

    it('closes modal on cancel button click', async () => {
        const user = userEvent.setup();
        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const closeButton = screen.getByRole('button', { name: /cancel/i });
        await user.click(closeButton);

        expect(mockOnClose).toHaveBeenCalled();
    });
});


