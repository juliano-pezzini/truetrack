import { render, screen } from '@testing-library/react';
import Index from '@/Pages/Tags/Index';

// Mock Inertia
jest.mock('@inertiajs/react', () => ({
    ...jest.requireActual('@inertiajs/react'),
    router: {
        get: jest.fn(),
        delete: jest.fn(),
    },
    Head: ({ children }) => <>{children}</>,
    Link: ({ children, href }) => <a href={href}>{children}</a>,
}));

// Mock AuthenticatedLayout
jest.mock('@/Layouts/AuthenticatedLayout', () => ({
    __esModule: true,
    default: ({ children, header }) => (
        <div data-testid="authenticated-layout">
            <div data-testid="header">{header}</div>
            {children}
        </div>
    ),
}));

describe('Tags Index', () => {
    const mockAuth = {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
        },
    };

    const mockTags = {
        data: [
            {
                id: 1,
                name: 'Essential',
                color: '#EF4444',
                created_at: '2026-01-10T00:00:00.000000Z',
                updated_at: '2026-01-10T00:00:00.000000Z',
            },
            {
                id: 2,
                name: 'Entertainment',
                color: '#8B5CF6',
                created_at: '2026-01-10T00:00:00.000000Z',
                updated_at: '2026-01-10T00:00:00.000000Z',
            },
        ],
        links: [
            { url: null, label: '&laquo; Previous', active: false },
            { url: 'http://localhost/tags?page=1', label: '1', active: true },
            { url: 'http://localhost/tags?page=2', label: '2', active: false },
            { url: 'http://localhost/tags?page=2', label: 'Next &raquo;', active: false },
        ],
        meta: {
            current_page: 1,
            from: 1,
            to: 2,
            total: 2,
            per_page: 15,
        },
    };

    beforeEach(() => {
        jest.clearAllMocks();
        // Mock window.confirm
        global.confirm = jest.fn(() => true);
    });

    test('renders tags index page', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        expect(screen.getByText('Tags')).toBeInTheDocument();
    });

    test('displays create tag button', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        const createButton = screen.getByText('Create Tag');
        expect(createButton).toBeInTheDocument();
    });

    test('displays filter section', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        expect(screen.getByPlaceholderText(/Enter tag name/i)).toBeInTheDocument();
        expect(screen.getByText('Apply Filters')).toBeInTheDocument();
        expect(screen.getByText('Clear')).toBeInTheDocument();
    });

    test('displays tag list with data', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        expect(screen.getByText('Essential')).toBeInTheDocument();
        expect(screen.getByText('Entertainment')).toBeInTheDocument();
        expect(screen.getByText('#EF4444')).toBeInTheDocument();
        expect(screen.getByText('#8B5CF6')).toBeInTheDocument();
    });

    test('displays edit and delete buttons for each tag', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        const editButtons = screen.getAllByText('Edit');
        const deleteButtons = screen.getAllByText('Delete');

        expect(editButtons).toHaveLength(2);
        expect(deleteButtons).toHaveLength(2);
    });

    test('displays empty state when no tags', () => {
        const emptyTags = {
            data: [],
            links: [],
            meta: { total: 0 },
        };

        render(<Index auth={mockAuth} tags={emptyTags} filters={{}} />);

        expect(screen.getByText(/No tags found/i)).toBeInTheDocument();
        expect(screen.getByText(/Create your first tag to get started/i)).toBeInTheDocument();
    });

    test('displays pagination info', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        expect(screen.getByText(/Showing/i)).toBeInTheDocument();
        expect(screen.getByText(/results/i)).toBeInTheDocument();

        // Check that pagination shows complete text
        const paginationElements = screen.getAllByText((content, element) => {
            return element?.tagName === 'P' && element?.textContent?.includes('Showing') && element?.textContent?.includes('results');
        });
        expect(paginationElements.length).toBeGreaterThan(0);
        expect(paginationElements[0]).toHaveTextContent('Showing 1 to 2 of 2 results');
    });

    test('displays color indicators', () => {
        render(<Index auth={mockAuth} tags={mockTags} filters={{}} />);

        const table = screen.getByRole('table');
        const colorDivs = table.querySelectorAll('[style*="background-color"]');

        // Should have color indicators for each tag
        expect(colorDivs.length).toBeGreaterThanOrEqual(2);
    });
});
