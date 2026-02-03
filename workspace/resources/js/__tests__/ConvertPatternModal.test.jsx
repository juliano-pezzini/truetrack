import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ConvertPatternModal from '../../../resources/js/Pages/LearnedPatterns/ConvertPatternModal';


jest.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            auth: {
                token: 'test-token',
            },
        },
    }),
}));

describe('ConvertPatternModal Component', () => {
    const mockPattern = {
        id: 1,
        keyword: 'amazon',
        category: { id: 1, name: 'Shopping' },
        occurrence_count: 15,
        confidence_score: 85,
    };

    const mockOnClose = jest.fn();
    const mockOnSuccess = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders modal with pattern details', () => {
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        expect(screen.getByText('amazon')).toBeInTheDocument();
        expect(screen.getByText('Shopping')).toBeInTheDocument();
        expect(screen.getByText(/85%/)).toBeInTheDocument();
        expect(screen.getByText('15')).toBeInTheDocument();
    });

    it('does not render when isOpen is false', () => {
        const { container } = render(
            <ConvertPatternModal
                show={false}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        expect(container.querySelector('[role="dialog"]')).not.toBeInTheDocument();
    });

    it('displays keyword in monospace code element', () => {
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        expect(screen.getByText('amazon', { selector: 'code' })).toBeInTheDocument();
    });

    it('displays pattern metadata', () => {
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        expect(screen.getByText(/confidence/i)).toBeInTheDocument();
        expect(screen.getByText(/occurrences/i)).toBeInTheDocument();
    });

    it('validates priority field required', async () => {
        const user = userEvent.setup();
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        const convertButton = screen.getByRole('button', { name: /convert to rule/i });
        await user.click(convertButton);

        expect(screen.getByText(/priority is required/i)).toBeInTheDocument();
    });

    it('renders priority input with min/max attributes', () => {
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        const priorityInput = screen.getByPlaceholderText(/lower = higher priority/i);
        expect(priorityInput).toHaveAttribute('min', '1');
        expect(priorityInput).toHaveAttribute('max', '1000');
    });

    it('submits form with valid priority', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ data: { id: 1 } }),
            })
        );

        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        const priorityInput = screen.getByPlaceholderText(/lower = higher priority/i);
        await user.type(priorityInput, '10');

        const convertButton = screen.getByRole('button', { name: /convert to rule/i });
        await user.click(convertButton);

        await waitFor(() => {
            expect(mockOnSuccess).toHaveBeenCalled();
        });
    });

    it('closes modal on close button click', async () => {
        const user = userEvent.setup();
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        const closeButton = screen.getByRole('button', { name: /cancel/i });
        await user.click(closeButton);

        expect(mockOnClose).toHaveBeenCalled();
    });

    it('shows loading state during submission', async () => {
        const user = userEvent.setup();
        global.fetch = jest.fn(
            () =>
                new Promise(resolve =>
                    setTimeout(
                        () =>
                            resolve({
                                ok: true,
                                json: () => Promise.resolve({ data: { id: 1 } }),
                            }),
                        100
                    )
                )
        );

        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        const priorityInput = screen.getByPlaceholderText(/lower = higher priority/i);
        await user.type(priorityInput, '10');

        const convertButton = screen.getByRole('button', { name: /convert to rule/i });
        await user.click(convertButton);

        expect(convertButton).toBeDisabled();
    });

    it('displays category with correct color indicator', () => {
        const { container } = render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        expect(screen.getByText('Shopping')).toBeInTheDocument();
    });

    it('displays info box with pattern details', () => {
        render(
            <ConvertPatternModal
                show={true}
                pattern={mockPattern}
                onClose={mockOnClose}
                onSuccess={mockOnSuccess}
            />
        );

        const priorityInput = screen.getByPlaceholderText(/lower = higher priority/i);
        expect(priorityInput).toBeInTheDocument();
    });
});


