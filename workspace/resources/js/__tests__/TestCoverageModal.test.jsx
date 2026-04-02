import { fireEvent, render, screen, waitFor } from '@testing-library/react';
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
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        fireEvent.change(fromDateInput, { target: { value: '2026-01-01' } });
        fireEvent.change(toDateInput, { target: { value: '2026-01-31' } });

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        fireEvent.click(testButton);

        await waitFor(() => {
            expect(global.fetch).toHaveBeenCalled();
        });
    });

    it('displays coverage results', async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

    fireEvent.change(fromDateInput, { target: { value: '2026-01-01' } });
    fireEvent.change(toDateInput, { target: { value: '2026-01-31' } });

        const testButton = screen.getByRole('button', { name: /test coverage/i });
    fireEvent.click(testButton);

        await waitFor(() => {
            expect(screen.getByText(/150/)).toBeInTheDocument();
            expect(screen.getByText(/80/)).toBeInTheDocument();
        });
    });

    it('displays coverage percentage correctly', async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

    fireEvent.change(fromDateInput, { target: { value: '2026-01-01' } });
    fireEvent.change(toDateInput, { target: { value: '2026-01-31' } });

        const testButton = screen.getByRole('button', { name: /test coverage/i });
    fireEvent.click(testButton);

        await waitFor(() => {
            expect(screen.getByText('80%')).toBeInTheDocument();
        });
    });

    it('displays category breakdown', async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ data: mockCoverageResult }),
                ok: true,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

    fireEvent.change(fromDateInput, { target: { value: '2026-01-01' } });
    fireEvent.change(toDateInput, { target: { value: '2026-01-31' } });

        const testButton = screen.getByRole('button', { name: /test coverage/i });
    fireEvent.click(testButton);

        await waitFor(() => {
            expect(screen.getByText(/groceries/i)).toBeInTheDocument();
            expect(screen.getByText(/utilities/i)).toBeInTheDocument();
        });
    });

    it('handles API errors gracefully', async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ message: 'Server error' }),
                ok: false,
            })
        );

        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const fromDateInput = screen.getByLabelText(/from date/i);
        const toDateInput = screen.getByLabelText(/to date/i);

        fireEvent.change(fromDateInput, { target: { value: '2026-01-01' } });
        fireEvent.change(toDateInput, { target: { value: '2026-01-31' } });

        const testButton = screen.getByRole('button', { name: /test coverage/i });
        fireEvent.click(testButton);

        await waitFor(() => {
            expect(screen.getByText(/server error/i)).toBeInTheDocument();
        });
    });

    it('closes modal on cancel button click', async () => {
        render(<TestCoverageModal show={true} onClose={mockOnClose} />);

        const closeButton = screen.getByRole('button', { name: /cancel/i });
        fireEvent.click(closeButton);

        expect(mockOnClose).toHaveBeenCalled();
    });
});


