import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LearnedPatternTable from '../../../resources/js/Pages/LearnedPatterns/LearnedPatternTable';

describe('LearnedPatternTable Component', () => {
    const mockPatterns = [
        {
            id: 1,
            keyword: 'amazon',
            category: { id: 1, name: 'Shopping' },
            occurrence_count: 15,
            confidence_score: 85,
            is_active: true,
        },
        {
            id: 2,
            keyword: 'walmart',
            category: { id: 1, name: 'Shopping' },
            occurrence_count: 8,
            confidence_score: 60,
            is_active: true,
        },
        {
            id: 3,
            keyword: 'grocery',
            category: { id: 2, name: 'Food' },
            occurrence_count: 3,
            confidence_score: 35,
            is_active: false,
        },
    ];

    const mockOnToggle = jest.fn();
    const mockOnDelete = jest.fn();
    const mockOnConvert = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders table with patterns', () => {
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        expect(screen.getByText('amazon')).toBeInTheDocument();
        expect(screen.getByText('walmart')).toBeInTheDocument();
        expect(screen.getByText('grocery')).toBeInTheDocument();
    });

    it('displays correct occurrence counts', () => {
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        expect(screen.getByText('15')).toBeInTheDocument();
        expect(screen.getByText('8')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('displays confidence scores with correct colors', () => {
        const { container } = render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const highConfidenceBadge = screen.getByText('85%');
        expect(highConfidenceBadge.classList.contains('bg-green-100')).toBeTruthy();

        const mediumConfidenceBadge = screen.getByText('60%');
        expect(mediumConfidenceBadge.classList.contains('bg-yellow-100')).toBeTruthy();

        const lowConfidenceBadge = screen.getByText('35%');
        expect(lowConfidenceBadge.classList.contains('bg-red-100')).toBeTruthy();
    });

    it('displays category names correctly', () => {
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const categoryElements = screen.getAllByText('Shopping');
        expect(categoryElements.length).toBeGreaterThan(0);

        expect(screen.getByText('Food')).toBeInTheDocument();
    });

    it('displays active/inactive status correctly', () => {
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const activeBadges = screen.getAllByText('Active');
        expect(activeBadges.length).toBeGreaterThan(0);

        expect(screen.getByText('Disabled')).toBeInTheDocument();
    });

    it('calls onToggle when toggle button clicked', async () => {
        const user = userEvent.setup();
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const toggleButtons = screen.getAllByRole('button', { name: /enable|disable/i });
        await user.click(toggleButtons[0]);

        expect(mockOnToggle).toHaveBeenCalledWith(mockPatterns[0].id);
    });

    it('calls onDelete when delete button clicked', async () => {
        const user = userEvent.setup();
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const deleteButtons = screen.getAllByRole('button', { name: /delete/i });
        await user.click(deleteButtons[0]);

        expect(mockOnDelete).toHaveBeenCalledWith(mockPatterns[0].id);
    });

    it('calls onConvert when convert button clicked', async () => {
        const user = userEvent.setup();
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const convertButtons = screen.getAllByRole('button', { name: /convert/i });
        await user.click(convertButtons[0]);

        expect(mockOnConvert).toHaveBeenCalledWith(mockPatterns[0]);
    });

    it('displays loading state', () => {
        render(
            <LearnedPatternTable
                patterns={[]}
                loading={true}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        expect(screen.getByText(/loading patterns/i)).toBeInTheDocument();
    });

    it('displays empty state when no patterns', () => {
        render(
            <LearnedPatternTable
                patterns={[]}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        expect(screen.getByText(/no learned patterns yet/i)).toBeInTheDocument();
    });

    it('renders keywords in monospace font', () => {
        const { container } = render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        const keywords = container.querySelectorAll('code');
        expect(keywords.length).toBeGreaterThan(0);
    });

    it('displays correct column headers', () => {
        render(
            <LearnedPatternTable
                patterns={mockPatterns}
                loading={false}
                onToggle={mockOnToggle}
                onDelete={mockOnDelete}
                onConvert={mockOnConvert}
            />
        );

        expect(screen.getByText('Keyword')).toBeInTheDocument();
        expect(screen.getByText('Category')).toBeInTheDocument();
        expect(screen.getByText('Occurrences')).toBeInTheDocument();
        expect(screen.getByText('Confidence')).toBeInTheDocument();
        expect(screen.getByText('Status')).toBeInTheDocument();
        expect(screen.getByText('Actions')).toBeInTheDocument();
    });
});



