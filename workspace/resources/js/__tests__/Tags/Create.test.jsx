import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Create from '@/Pages/Tags/Create';

// Mock Inertia
jest.mock('@inertiajs/react', () => ({
    ...jest.requireActual('@inertiajs/react'),
    router: {
        post: jest.fn(),
    },
    Head: ({ children }) => <>{children}</>,
    Link: ({ children, href }) => <a href={href}>{children}</a>,
    useForm: () => ({
        data: {
            name: '',
            color: '#3B82F6',
        },
        setData: jest.fn(),
        post: jest.fn(),
        processing: false,
        errors: {},
    }),
}));

// Mock AuthenticatedLayout
jest.mock('@/Layouts/AuthenticatedLayout', () => ({
    __esModule: true,
    default: ({ children }) => <div data-testid="authenticated-layout">{children}</div>,
}));

describe('TagForm Create', () => {
    const mockAuth = {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
        },
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders create tag form', () => {
        render(<Create auth={mockAuth} />);

        expect(screen.getByText('Create Tag')).toBeInTheDocument();
        expect(screen.getByLabelText(/Tag Name/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Color/i)).toBeInTheDocument();
    });

    test('displays color presets', () => {
        render(<Create auth={mockAuth} />);

        const colorButtons = screen.getAllByRole('button', { name: /Red|Orange|Amber|Green|Teal|Blue|Violet|Pink/i });
        
        // Should have 8 color presets
        expect(colorButtons.length).toBeGreaterThanOrEqual(8);
    });

    test('displays color picker input', () => {
        render(<Create auth={mockAuth} />);

        const colorInputs = screen.getAllByDisplayValue('#3B82F6');
        
        // Should have both color picker and text input
        expect(colorInputs.length).toBeGreaterThanOrEqual(1);
    });

    test('shows preview section', () => {
        render(<Create auth={mockAuth} />);

        expect(screen.getByText(/Preview:/i)).toBeInTheDocument();
    });

    test('shows helper text for color field', () => {
        render(<Create auth={mockAuth} />);

        expect(
            screen.getByText(/Choose a preset color or enter a custom hex color code/i)
        ).toBeInTheDocument();
    });

    test('displays create button', () => {
        render(<Create auth={mockAuth} />);

        const createButton = screen.getByRole('button', { name: /Create Tag/i });
        expect(createButton).toBeInTheDocument();
    });

    test('displays cancel link', () => {
        render(<Create auth={mockAuth} />);

        const cancelLink = screen.getByText(/Cancel/i);
        expect(cancelLink).toBeInTheDocument();
    });
});
