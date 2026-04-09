import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import UnifiedImportUpload from '@/Components/Import/UnifiedImportUpload';
import axios from 'axios';

// Mock axios
jest.mock('axios');

global.route = (name) => {
    const routes = {
        'api.ofx-imports.store': '/api/v1/ofx-imports',
        'api.xlsx-imports.detect-columns': '/api/v1/xlsx-imports/detect-columns',
        'api.xlsx-imports.preview': '/api/v1/xlsx-imports/preview',
        'api.xlsx-imports.store': '/api/v1/xlsx-imports',
    };

    return routes[name] || '';
};

// Mock child components
jest.mock('@/Components/Import/OfxImportOptions', () => ({
    __esModule: true,
    default: ({ onSubmit, onCancel }) => (
        <div data-testid="ofx-options">
            <button onClick={onSubmit}>Submit OFX</button>
            <button onClick={onCancel}>Cancel</button>
        </div>
    ),
}));

jest.mock('@/Components/Import/XlsxSimplifiedWizard', () => ({
    __esModule: true,
    default: ({ onComplete, onCancel }) => (
        <div data-testid="xlsx-wizard">
            <button onClick={() => onComplete({ id: 123 })}>Complete XLSX</button>
            <button onClick={onCancel}>Cancel</button>
        </div>
    ),
}));

describe('UnifiedImportUpload', () => {
    const mockAccounts = [
        { id: 1, name: 'Checking Account', type: 'bank' },
        { id: 2, name: 'Savings Account', type: 'bank' },
    ];

    const mockOnSuccess = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
        axios.post.mockResolvedValue({ data: { data: {} } });
        // Mock window.alert
        global.alert = jest.fn();
    });

    test('renders file upload area initially', () => {
        render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

        expect(screen.getByText(/drop your file here or click to browse/i)).toBeInTheDocument();
        expect(screen.getByText(/supports:/i)).toBeInTheDocument();
    });

    describe('File Type Detection', () => {
        test('detects OFX file type correctly', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.ofx', { type: 'application/x-ofx' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('ofx-options')).toBeInTheDocument();
            });
        });

        test('detects QFX file type correctly', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.qfx', { type: 'application/x-qfx' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('ofx-options')).toBeInTheDocument();
            });
        });

        test('detects XLSX file type correctly', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.xlsx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('xlsx-wizard')).toBeInTheDocument();
            });
        });

        test('detects XLS file type correctly', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.xls', { type: 'application/vnd.ms-excel' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('xlsx-wizard')).toBeInTheDocument();
            });
        });

        test('detects CSV file type correctly', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.csv', { type: 'text/csv' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('xlsx-wizard')).toBeInTheDocument();
            });
        });

        test('rejects unsupported file types', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'document.pdf', { type: 'application/pdf' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(global.alert).toHaveBeenCalled();
                expect(screen.queryByTestId('ofx-options')).not.toBeInTheDocument();
                expect(screen.queryByTestId('xlsx-wizard')).not.toBeInTheDocument();
            });
        });

        test('rejects unsupported file extensions', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'document.txt', { type: 'text/plain' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(global.alert).toHaveBeenCalled();
            });
        });
    });

    describe('File Selection Flow', () => {
        test('shows OFX options after selecting OFX file', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.ofx', { type: 'application/x-ofx' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('ofx-options')).toBeInTheDocument();
                expect(screen.queryByText(/drop your file here or click to browse/i)).not.toBeInTheDocument();
            });
        });

        test('shows XLSX wizard after selecting XLSX file', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.xlsx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('xlsx-wizard')).toBeInTheDocument();
                expect(screen.queryByText(/drop your file here or click to browse/i)).not.toBeInTheDocument();
            });
        });

        test('can cancel and return to file upload', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.ofx', { type: 'application/x-ofx' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('ofx-options')).toBeInTheDocument();
            });

            const cancelButton = screen.getByText('Cancel');
            fireEvent.click(cancelButton);

            await waitFor(() => {
                expect(screen.getByText(/drop your file here or click to browse/i)).toBeInTheDocument();
                expect(screen.queryByTestId('ofx-options')).not.toBeInTheDocument();
            });
        });
    });

    describe('Import Submission', () => {
        test('triggers submission callback for OFX import', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.ofx', { type: 'application/x-ofx' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('ofx-options')).toBeInTheDocument();
            });

            const submitButton = screen.getByText('Submit OFX');
            fireEvent.click(submitButton);

            await waitFor(() => {
                expect(axios.post).toHaveBeenCalledWith(
                    '/api/v1/ofx-imports',
                    expect.any(FormData)
                );
                expect(mockOnSuccess).toHaveBeenCalled();
            });
        });

        test('triggers completion callback for XLSX import', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'statement.xlsx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('xlsx-wizard')).toBeInTheDocument();
            });

            const completeButton = screen.getByText('Complete XLSX');
            fireEvent.click(completeButton);

            await waitFor(() => {
                expect(mockOnSuccess).toHaveBeenCalled();
            });
        });
    });

    describe('Case Insensitivity', () => {
        test('handles uppercase file extensions', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'STATEMENT.OFX', { type: 'application/x-ofx' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('ofx-options')).toBeInTheDocument();
            });
        });

        test('handles mixed case file extensions', async () => {
            render(<UnifiedImportUpload accounts={mockAccounts} onSuccess={mockOnSuccess} />);

            const file = new File(['content'], 'Statement.XlSx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const input = screen.getByTestId('file-input');

            fireEvent.change(input, { target: { files: [file] } });

            await waitFor(() => {
                expect(screen.getByTestId('xlsx-wizard')).toBeInTheDocument();
            });
        });
    });
});
