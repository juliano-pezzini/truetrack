import { fireEvent, render, screen } from '@testing-library/react';
import Index from '@/Pages/Transactions/Index';

jest.mock('@inertiajs/react', () => ({
    ...jest.requireActual('@inertiajs/react'),
    router: {
        get: jest.fn(),
        delete: jest.fn(),
    },
    Head: ({ children }) => <>{children}</>,
    Link: ({ children, href }) => <a href={href}>{children}</a>,
}));

jest.mock('@/Layouts/AuthenticatedLayout', () => ({
    __esModule: true,
    default: ({ children, header }) => (
        <div data-testid="authenticated-layout">
            <div data-testid="header">{header}</div>
            {children}
        </div>
    ),
}));

describe('Transactions Index', () => {
    const mockAuth = {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
        },
    };

    const mockTransactions = {
        data: [
            {
                id: 1,
                account_id: 1,
                category_id: 1,
                amount: '120.50',
                description: 'Groceries',
                transaction_date: '2026-01-10',
                settled_date: '2026-01-11',
                type: 'debit',
                account: { name: 'Checking' },
                category: { name: 'Food' },
            },
            {
                id: 2,
                account_id: 1,
                category_id: 2,
                amount: '250.00',
                description: 'Salary',
                transaction_date: '2026-01-15',
                settled_date: null,
                type: 'credit',
                account: { name: 'Checking' },
                category: { name: 'Income' },
            },
        ],
        links: [],
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
        global.confirm = jest.fn(() => true);
    });

    test('renders bulk delete controls', () => {
        render(
            <Index
                auth={mockAuth}
                transactions={mockTransactions}
                accounts={{ data: [] }}
                categories={{ data: [] }}
                tags={{ data: [] }}
                filters={{}}
            />
        );

        expect(screen.getByText('Transaction List')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Delete Selected \(0\)/i })).toBeDisabled();
        expect(screen.getByLabelText('Select all transactions')).toBeInTheDocument();
    });

    test('enables bulk delete after selecting a row', () => {
        render(
            <Index
                auth={mockAuth}
                transactions={mockTransactions}
                accounts={{ data: [] }}
                categories={{ data: [] }}
                tags={{ data: [] }}
                filters={{}}
            />
        );

        fireEvent.click(screen.getByLabelText('Select transaction 1'));

        expect(screen.getByRole('button', { name: /Delete Selected \(1\)/i })).toBeEnabled();
    });

    test('select all toggles every visible row', () => {
        render(
            <Index
                auth={mockAuth}
                transactions={mockTransactions}
                accounts={{ data: [] }}
                categories={{ data: [] }}
                tags={{ data: [] }}
                filters={{}}
            />
        );

        fireEvent.click(screen.getByLabelText('Select all transactions'));

        expect(screen.getByLabelText('Select transaction 1')).toBeChecked();
        expect(screen.getByLabelText('Select transaction 2')).toBeChecked();
        expect(screen.getByRole('button', { name: /Delete Selected \(2\)/i })).toBeEnabled();
    });

    test('bulk delete sends selected transaction ids', () => {
        const { router } = jest.requireMock('@inertiajs/react');

        render(
            <Index
                auth={mockAuth}
                transactions={mockTransactions}
                accounts={{ data: [] }}
                categories={{ data: [] }}
                tags={{ data: [] }}
                filters={{}}
            />
        );

        fireEvent.click(screen.getByLabelText('Select transaction 1'));
        fireEvent.click(screen.getByRole('button', { name: /Delete Selected \(1\)/i }));

        expect(global.confirm).toHaveBeenCalledWith(
            'Are you sure you want to delete 1 selected transaction? This will adjust the account balances.'
        );
        expect(router.delete).toHaveBeenCalledWith(
            '/transactions/bulk-delete',
            { transaction_ids: [1] },
            expect.objectContaining({ preserveScroll: true, onSuccess: expect.any(Function) })
        );
    });

    test('does not render row delete buttons anymore', () => {
        render(
            <Index
                auth={mockAuth}
                transactions={mockTransactions}
                accounts={{ data: [] }}
                categories={{ data: [] }}
                tags={{ data: [] }}
                filters={{}}
            />
        );

        expect(screen.getAllByText('Edit')).toHaveLength(2);
        expect(screen.queryByText('Delete')).not.toBeInTheDocument();
    });
});