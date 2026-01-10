import { render, screen } from '@testing-library/react';
import Index from '@/Pages/Accounts/Index';

// Mock Inertia
jest.mock('@inertiajs/react', () => ({
    ...jest.requireActual('@inertiajs/react'),
    router: {
        get: jest.fn(),
        delete: jest.fn(),
        visit: jest.fn(),
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

describe('AccountList Index', () => {
    const mockAuth = {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
        },
    };

    const mockAccounts = {
        data: [
            {
                id: 1,
                name: 'Main Checking',
                type: 'bank',
                type_label: 'Bank Account',
                description: 'Primary account',
                balance: 5000.00,
                is_active: true,
            },
            {
                id: 2,
                name: 'Visa Credit Card',
                type: 'credit_card',
                type_label: 'Credit Card',
                description: null,
                balance: -1250.50,
                is_active: true,
            },
        ],
        links: [],
        from: 1,
        to: 2,
        total: 2,
    };

    test('renders accounts list page', () => {
        render(<Index auth={mockAuth} accounts={mockAccounts} filters={{}} />);

        expect(screen.getByText('Accounts')).toBeInTheDocument();
        expect(screen.getByText('Create Account')).toBeInTheDocument();
    });

    test('displays account data in table', () => {
        render(<Index auth={mockAuth} accounts={mockAccounts} filters={{}} />);

        expect(screen.getByText('Main Checking')).toBeInTheDocument();
        expect(screen.getByText('Primary account')).toBeInTheDocument();
        
        // "Bank Account" appears in both the filter dropdown and the table badge
        const bankAccountElements = screen.getAllByText('Bank Account');
        expect(bankAccountElements.length).toBeGreaterThanOrEqual(1);
        
        expect(screen.getByText('Visa Credit Card')).toBeInTheDocument();
        
        // "Credit Card" also appears in both the filter dropdown and the table badge
        const creditCardElements = screen.getAllByText('Credit Card');
        expect(creditCardElements.length).toBeGreaterThanOrEqual(1);
    });

    test('formats currency correctly', () => {
        render(<Index auth={mockAuth} accounts={mockAccounts} filters={{}} />);

        expect(screen.getByText('$5,000.00')).toBeInTheDocument();
        expect(screen.getByText('-$1,250.50')).toBeInTheDocument();
    });

    test('displays active status badges', () => {
        render(<Index auth={mockAuth} accounts={mockAccounts} filters={{}} />);

        const activeBadges = screen.getAllByText('Active');
        expect(activeBadges).toHaveLength(2);
    });

    test('shows empty state when no accounts', () => {
        const emptyAccounts = {
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0,
        };

        render(<Index auth={mockAuth} accounts={emptyAccounts} filters={{}} />);

        expect(
            screen.getByText(/No accounts found. Create your first account to get started./i)
        ).toBeInTheDocument();
    });

    test('renders filter section', () => {
        render(<Index auth={mockAuth} accounts={mockAccounts} filters={{}} />);

        expect(screen.getByText('Filters')).toBeInTheDocument();
        expect(screen.getByLabelText(/Account Type/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Status/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Apply/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Clear/i })).toBeInTheDocument();
    });

    test('displays edit and delete buttons for each account', () => {
        render(<Index auth={mockAuth} accounts={mockAccounts} filters={{}} />);

        const editLinks = screen.getAllByText('Edit');
        const deleteButtons = screen.getAllByText('Delete');

        expect(editLinks).toHaveLength(2);
        expect(deleteButtons).toHaveLength(2);
    });

    test('shows pagination info when available', () => {
        const accountsWithPagination = {
            ...mockAccounts,
            links: [
                { label: 'Previous', url: null, active: false },
                { label: '1', url: '/accounts?page=1', active: true },
                { label: '2', url: '/accounts?page=2', active: false },
                { label: 'Next', url: '/accounts?page=2', active: false },
            ],
        };

        render(<Index auth={mockAuth} accounts={accountsWithPagination} filters={{}} />);

        expect(screen.getByText(/Showing 1 to 2 of 2 results/i)).toBeInTheDocument();
    });
});
