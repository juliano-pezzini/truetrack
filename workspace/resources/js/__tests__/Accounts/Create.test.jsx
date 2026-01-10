import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { router } from '@inertiajs/react';
import Create from '@/Pages/Accounts/Create';

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
            type: 'bank',
            description: '',
            balance: '0.00',
            is_active: true,
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

describe('AccountForm Create', () => {
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

    test('renders create account form', () => {
        render(<Create auth={mockAuth} />);

        expect(screen.getByText('Create Account')).toBeInTheDocument();
        expect(screen.getByLabelText(/Account Name/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Account Type/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Description/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Initial Balance/i)).toBeInTheDocument();
        expect(screen.getByText(/Account is active/i)).toBeInTheDocument();
    });

    test('displays all account type options', () => {
        render(<Create auth={mockAuth} />);

        const typeSelect = screen.getByLabelText(/Account Type/i);
        
        expect(typeSelect).toContainHTML('Bank Account');
        expect(typeSelect).toContainHTML('Credit Card');
        expect(typeSelect).toContainHTML('Wallet');
        expect(typeSelect).toContainHTML('Transitional');
    });

    test('shows helper text for balance field', () => {
        render(<Create auth={mockAuth} />);

        expect(
            screen.getByText(/For credit cards, use negative values to indicate debt/i)
        ).toBeInTheDocument();
    });

    test('displays create button', () => {
        render(<Create auth={mockAuth} />);

        expect(screen.getByRole('button', { name: /Create Account/i })).toBeInTheDocument();
    });

    test('displays cancel link', () => {
        render(<Create auth={mockAuth} />);

        const cancelLink = screen.getByText('Cancel');
        expect(cancelLink).toHaveAttribute('href', '/accounts');
    });
});
