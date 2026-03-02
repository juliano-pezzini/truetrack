import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { router } from '@inertiajs/react';
import ImportHistoryCard from '@/Components/Import/ImportHistoryCard';

// Mock Inertia router
jest.mock('@inertiajs/react', () => ({
    router: {
        visit: jest.fn(),
        delete: jest.fn(),
    },
}));

// Mock route helper
global.route = (name, params) => {
    const routes = {
        'reconciliations.show': (id) => `/reconciliations/${id}`,
        'api.ofx-imports.destroy': (id) => `/api/v1/ofx-imports/${id}`,
        'api.xlsx-imports.download': (id) => `/api/v1/xlsx-imports/${id}/download`,
        'api.xlsx-imports.error-report': (id) => `/api/v1/xlsx-imports/${id}/error-report`,
    };

    if (typeof routes[name] === 'function') {
        return routes[name](params);
    }

    return routes[name] || '';
};

// Mock ImportProgress component
jest.mock('@/Components/Import/ImportProgress', () => ({
    __esModule: true,
    default: ({ importData, type }) => (
        <div data-testid="import-progress">
            Progress: {importData.status}
        </div>
    ),
}));

describe('ImportHistoryCard', () => {
    const mockOnDelete = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
        global.confirm = jest.fn(() => true);
        global.alert = jest.fn();
        global.open = jest.fn();
    });

    describe('Display Information', () => {
        test('renders import card with basic information', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking Account' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('statement.ofx')).toBeInTheDocument();
            expect(screen.getByText('Checking Account')).toBeInTheDocument();
            expect(screen.getByText('OFX')).toBeInTheDocument();
        });

        test('renders XLSX badge for XLSX imports', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking Account' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.getByText('XLSX')).toBeInTheDocument();
        });

        test('displays unknown account when account is missing', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Unknown Account')).toBeInTheDocument();
        });
    });

    describe('Cancel Button Visibility', () => {
        test('shows cancel button for OFX imports in pending state', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'pending',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Cancel')).toBeInTheDocument();
        });

        test('shows cancel button for OFX imports in processing state', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'processing',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Cancel')).toBeInTheDocument();
        });

        test('does NOT show cancel button for XLSX imports in pending state', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'pending',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('Cancel')).not.toBeInTheDocument();
        });

        test('does NOT show cancel button for XLSX imports in processing state', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'processing',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('Cancel')).not.toBeInTheDocument();
        });

        test('does not show cancel button for completed imports', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('Cancel')).not.toBeInTheDocument();
        });

        test('does not show cancel button for failed imports', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'failed',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('Cancel')).not.toBeInTheDocument();
        });
    });

    describe('Cancel Import Action', () => {
        test('cancels OFX import with confirmation', async () => {
            const user = userEvent.setup();
            const importData = {
                id: 123,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'pending',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            const cancelButton = screen.getByText('Cancel');
            await user.click(cancelButton);

            await waitFor(() => {
                expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to cancel this import?');
                expect(router.delete).toHaveBeenCalledWith(
                    '/api/v1/ofx-imports/123',
                    expect.objectContaining({
                        onSuccess: expect.any(Function),
                    })
                );
            });
        });

        test('does not cancel if user declines confirmation', async () => {
            global.confirm = jest.fn(() => false);
            const user = userEvent.setup();
            const importData = {
                id: 123,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'pending',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            const cancelButton = screen.getByText('Cancel');
            await user.click(cancelButton);

            await waitFor(() => {
                expect(global.confirm).toHaveBeenCalled();
                expect(router.delete).not.toHaveBeenCalled();
            });
        });
    });

    describe('View Reconciliation Action', () => {
        test('shows view reconciliation button for completed imports', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                reconciliation: { id: 456 },
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('View Reconciliation')).toBeInTheDocument();
        });

        test('does not show view reconciliation when no reconciliation exists', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('View Reconciliation')).not.toBeInTheDocument();
        });

        test('shows view reconciliation button when only reconciliation_id exists', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                reconciliation_id: 789,
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('View Reconciliation')).toBeInTheDocument();
            expect(screen.getByText('Reconciliation #789')).toBeInTheDocument();
        });

        test('navigates to reconciliation on click', async () => {
            const user = userEvent.setup();
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                reconciliation: { id: 456 },
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            const viewButton = screen.getByText('View Reconciliation');
            await user.click(viewButton);

            await waitFor(() => {
                expect(router.visit).toHaveBeenCalledWith('/reconciliations/456');
            });
        });
    });

    describe('XLSX Download Actions', () => {
        test('shows download file button for completed XLSX imports', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Download File')).toBeInTheDocument();
        });

        test('downloads file when download button clicked', async () => {
            const user = userEvent.setup();
            const importData = {
                id: 123,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            const downloadButton = screen.getByText('Download File');
            await user.click(downloadButton);

            await waitFor(() => {
                expect(global.open).toHaveBeenCalledWith('/api/v1/xlsx-imports/123/download', '_blank');
            });
        });

        test('shows error report button when error report exists', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                error_report_path: '/path/to/report.xlsx',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Error Report')).toBeInTheDocument();
        });

        test('does not show error report button when no error report', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('Error Report')).not.toBeInTheDocument();
        });

        test('shows error report button when has_errors is true', () => {
            const importData = {
                id: 1,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                has_errors: true,
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Error Report')).toBeInTheDocument();
        });

        test('downloads error report when clicked', async () => {
            const user = userEvent.setup();
            const importData = {
                id: 123,
                filename: 'statement.xlsx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                error_report_path: '/path/to/report.xlsx',
            };

            render(<ImportHistoryCard importData={importData} type="xlsx" onDelete={mockOnDelete} />);

            const errorReportButton = screen.getByText('Error Report');
            await user.click(errorReportButton);

            await waitFor(() => {
                expect(global.open).toHaveBeenCalledWith('/api/v1/xlsx-imports/123/error-report', '_blank');
            });
        });
    });

    describe('Reconciliation Badge', () => {
        test('displays reconciliation badge in footer for completed imports', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'completed',
                reconciliation: { id: 999 },
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText('Reconciliation #999')).toBeInTheDocument();
        });

        test('does not display reconciliation badge when not completed', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T10:00:00Z',
                status: 'processing',
                reconciliation: { id: 999 },
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.queryByText('Reconciliation #999')).not.toBeInTheDocument();
        });
    });

    describe('Date Formatting', () => {
        test('formats and displays upload date', () => {
            const importData = {
                id: 1,
                filename: 'statement.ofx',
                account: { name: 'Checking' },
                created_at: '2026-03-01T14:30:00Z',
                status: 'completed',
            };

            render(<ImportHistoryCard importData={importData} type="ofx" onDelete={mockOnDelete} />);

            expect(screen.getByText(/Uploaded/i)).toBeInTheDocument();
        });
    });
});
