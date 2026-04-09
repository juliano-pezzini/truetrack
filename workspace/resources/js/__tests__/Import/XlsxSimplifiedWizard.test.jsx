import { useState } from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import XlsxSimplifiedWizard from '@/Components/Import/XlsxSimplifiedWizard';
import axios from 'axios';

jest.mock('axios');

jest.mock('@/Components/XlsxImport/XlsxColumnMapper', () => ({
    __esModule: true,
    default: ({ accountId, onMappingConfirmed, onBack }) => (
        <div data-testid="column-mapper">
            <div>Selected account: {accountId || 'none'}</div>
            <button
                type="button"
                onClick={() => onMappingConfirmed({ transaction_date: 'date', amount: 'amount' })}
                disabled={!accountId}
            >
                Confirm mapping
            </button>
            <button type="button" onClick={onBack}>
                Back
            </button>
        </div>
    ),
}));

jest.mock('@/Components/XlsxImport/XlsxPreviewTable', () => ({
    __esModule: true,
    default: ({ previewData, validationSummary, onConfirm, onBack, isProcessing }) => (
        <div data-testid="preview-table">
            <div data-testid="preview-state">{isProcessing ? 'processing' : 'idle'}</div>
            <div data-testid="preview-count">{previewData.length}</div>
            <div data-testid="validation-count">{validationSummary.valid_rows}</div>
            <button type="button" onClick={onConfirm}>
                Confirm import
            </button>
            <button type="button" onClick={onBack}>
                Back to mapping
            </button>
        </div>
    ),
}));

global.route = (name) => {
    const routes = {
        'api.xlsx-imports.detect-columns': '/api/v1/xlsx-imports/detect-columns',
        'api.xlsx-imports.preview': '/api/v1/xlsx-imports/preview',
        'api.xlsx-imports.store': '/api/v1/xlsx-imports',
    };

    return routes[name] || '';
};

function Harness({ file, accounts, onComplete }) {
    const [selectedAccount, setSelectedAccount] = useState('');

    return (
        <XlsxSimplifiedWizard
            file={file}
            accounts={accounts}
            selectedAccount={selectedAccount}
            onAccountChange={setSelectedAccount}
            onComplete={onComplete}
            onCancel={jest.fn()}
        />
    );
}

function createDeferred() {
    let resolve;
    let reject;

    const promise = new Promise((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return { promise, resolve, reject };
}

describe('XlsxSimplifiedWizard', () => {
    const accounts = [
        { id: 1, name: 'Checking Account', type: 'bank' },
    ];
    const file = new File(['date,amount\n2026-01-01,10.00'], 'statement.xlsx', {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });

    beforeEach(() => {
        jest.clearAllMocks();
        global.alert = jest.fn();
    });

    test('runs the happy path from detect columns to import confirmation', async () => {
        const user = require('@testing-library/user-event').default.setup();
        const onComplete = jest.fn();
        const storeDeferred = createDeferred();

        axios.post
            .mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['date', 'amount'],
                        suggested_mapping: { transaction_date: 'date', amount: 'amount' },
                    },
                },
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        preview_transactions: [
                            {
                                transaction_date: '2026-01-01',
                                description: 'Coffee',
                                amount: 10,
                                type: 'debit',
                                category_name: 'Food',
                                tags: ['morning'],
                            },
                        ],
                        validation_summary: { valid_rows: 1, rows_with_warnings: 0 },
                    },
                },
            })
            .mockImplementationOnce(() => storeDeferred.promise);

        render(<Harness file={file} accounts={accounts} onComplete={onComplete} />);

        await waitFor(() => {
            expect(screen.getByTestId('column-mapper')).toBeInTheDocument();
        });

        await user.selectOptions(screen.getByRole('combobox'), '1');
        await user.click(screen.getByRole('button', { name: /confirm mapping/i }));

        await waitFor(() => {
            expect(screen.getByTestId('preview-table')).toBeInTheDocument();
            expect(screen.getByTestId('preview-count')).toHaveTextContent('1');
        });

        const confirmButton = screen.getByRole('button', { name: /confirm import/i });
        await user.click(confirmButton);

        await waitFor(() => {
            expect(screen.getByTestId('preview-state')).toHaveTextContent('processing');
        });

        storeDeferred.resolve({
            data: {
                data: {
                    id: 99,
                    status: 'queued',
                },
            },
        });

        await waitFor(() => {
            expect(onComplete).toHaveBeenCalledWith({
                id: 99,
                status: 'queued',
            });
        });
    });

    test('shows validation error when preview generation fails with 422', async () => {
        axios.post
            .mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['date', 'amount'],
                        suggested_mapping: { transaction_date: 'date', amount: 'amount' },
                    },
                },
            })
            .mockRejectedValueOnce({
                response: {
                    status: 422,
                    data: {
                        errors: {
                            transaction_date: ['is required'],
                        },
                    },
                },
            });

        render(<Harness file={file} accounts={accounts} onComplete={jest.fn()} />);

        await waitFor(() => {
            expect(screen.getByTestId('column-mapper')).toBeInTheDocument();
        });

        fireEvent.change(screen.getByRole('combobox'), { target: { value: '1' } });
        fireEvent.click(screen.getByRole('button', { name: /confirm mapping/i }));

        await waitFor(() => {
            expect(screen.getByText(/validation failed: transaction_date: is required/i)).toBeInTheDocument();
        });
    });

    test('shows duplicate file message when store rejects with 409', async () => {
        const user = require('@testing-library/user-event').default.setup();

        axios.post
            .mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['date', 'amount'],
                        suggested_mapping: { transaction_date: 'date', amount: 'amount' },
                    },
                },
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        preview_transactions: [
                            {
                                transaction_date: '2026-01-01',
                                description: 'Coffee',
                                amount: 10,
                                type: 'debit',
                                category_name: 'Food',
                                tags: [],
                            },
                        ],
                        validation_summary: { valid_rows: 1, rows_with_warnings: 0 },
                    },
                },
            })
            .mockRejectedValueOnce({
                response: {
                    status: 409,
                    data: {},
                },
            });

        render(<Harness file={file} accounts={accounts} onComplete={jest.fn()} />);

        await waitFor(() => {
            expect(screen.getByTestId('column-mapper')).toBeInTheDocument();
        });

        await user.selectOptions(screen.getByRole('combobox'), '1');
        await user.click(screen.getByRole('button', { name: /confirm mapping/i }));

        await waitFor(() => {
            expect(screen.getByTestId('preview-table')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /confirm import/i }));

        await waitFor(() => {
            expect(screen.getByText(/this file has already been imported/i)).toBeInTheDocument();
        });
    });

    test('shows access denied message when store rejects with 403', async () => {
        const user = require('@testing-library/user-event').default.setup();

        axios.post
            .mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['date', 'amount'],
                        suggested_mapping: { transaction_date: 'date', amount: 'amount' },
                    },
                },
            })
            .mockResolvedValueOnce({
                data: {
                    data: {
                        preview_transactions: [
                            {
                                transaction_date: '2026-01-01',
                                description: 'Coffee',
                                amount: 10,
                                type: 'debit',
                                category_name: 'Food',
                                tags: [],
                            },
                        ],
                        validation_summary: { valid_rows: 1, rows_with_warnings: 0 },
                    },
                },
            })
            .mockRejectedValueOnce({
                response: {
                    status: 403,
                    data: {},
                },
            });

        render(<Harness file={file} accounts={accounts} onComplete={jest.fn()} />);

        await waitFor(() => {
            expect(screen.getByTestId('column-mapper')).toBeInTheDocument();
        });

        await user.selectOptions(screen.getByRole('combobox'), '1');
        await user.click(screen.getByRole('button', { name: /confirm mapping/i }));

        await waitFor(() => {
            expect(screen.getByTestId('preview-table')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /confirm import/i }));

        await waitFor(() => {
            expect(screen.getByText(/access denied/i)).toBeInTheDocument();
        });
    });
});
