import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import XlsxSimplifiedWizard from '@/Components/Import/XlsxSimplifiedWizard';

// Mock axios
jest.mock('axios');

// Mock route helper
global.route = (name) => {
    const routes = {
        'api.xlsx-imports.detect-columns': '/api/v1/xlsx-imports/detect-columns',
        'api.xlsx-imports.preview': '/api/v1/xlsx-imports/preview',
        'api.xlsx-imports.store': '/api/v1/xlsx-imports',
    };
    return routes[name] || '';
};

// Mock child components
jest.mock('@/Components/XlsxImport/XlsxColumnMapper', () => ({
    __esModule: true,
    default: ({ onMappingConfirmed, onBack }) => (
        <div data-testid="column-mapper">
            <button onClick={() => onMappingConfirmed({ date: 'A', amount: 'B' })}>
                Confirm Mapping
            </button>
            <button onClick={onBack}>Back</button>
        </div>
    ),
}));

jest.mock('@/Components/XlsxImport/XlsxPreviewTable', () => ({
    __esModule: true,
    default: ({ onConfirm, onBack, isProcessing }) => (
        <div data-testid="preview-table">
            <button onClick={onBack} disabled={isProcessing}>
                Back to Mapping
            </button>
            <button onClick={onConfirm} disabled={isProcessing}>
                {isProcessing ? 'Processing...' : "Looks Good! Continue"}
            </button>
        </div>
    ),
}));

describe('XlsxSimplifiedWizard', () => {
    const mockFile = new File(['content'], 'statement.xlsx', {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });

    const mockAccounts = [
        { id: 1, name: 'Checking', type: 'bank' },
        { id: 2, name: 'Savings', type: 'bank' },
    ];

    const mockOnComplete = jest.fn();
    const mockOnCancel = jest.fn();
    const mockOnAccountChange = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
        global.alert = jest.fn();
        // Mock document.querySelector for CSRF token
        document.querySelector = jest.fn((selector) => {
            if (selector === 'meta[name="csrf-token"]') {
                return { content: 'test-csrf-token' };
            }
            return null;
        });
    });

    describe('Column Detection', () => {
        test('shows error if column detection fails', async () => {
            axios.post.mockRejectedValueOnce(
                new Error('Failed to detect columns')
            );

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={0}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            await waitFor(() => {
                expect(
                    screen.getByText(/Failed to detect columns/i)
                ).toBeInTheDocument();
            });
        });
    });

    describe('Happy Path: Successful Import', () => {
        test('completes full import flow successfully', async () => {
            const user = userEvent.setup();

            // Mock responses
            axios.post
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            headers: ['Date', 'Amount'],
                            suggested_mapping: {
                                date: 'Date',
                                amount: 'Amount',
                            },
                        },
                    },
                })
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            preview_transactions: [
                                {
                                    date: '2026-03-01',
                                    amount: 100.00,
                                    description: 'Transfer',
                                },
                            ],
                            validation_summary: {
                                total: 1,
                                valid: 1,
                                invalid: 0,
                            },
                        },
                    },
                })
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            id: 123,
                            status: 'processing',
                        },
                    },
                });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={1}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            // Wait for column detection
            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Confirm mapping
            const confirmMappingButton = screen.getByText('Confirm Mapping');
            await user.click(confirmMappingButton);

            // Wait for preview
            await waitFor(() => {
                expect(screen.getByTestId('preview-table')).toBeInTheDocument();
            });

            // Confirm import
            const confirmImportButton = screen.getByText("Looks Good! Continue");
            await user.click(confirmImportButton);

            // Verify completion callback
            await waitFor(() => {
                expect(mockOnComplete).toHaveBeenCalledWith(
                    expect.objectContaining({
                        id: 123,
                        status: 'processing',
                    })
                );
            });
        });
    });

    describe('Error Handling: 409 Duplicate', () => {
        test('handles 409 duplicate file error', async () => {
            const user = userEvent.setup();

            // Mock successful column detection and preview
            axios.post
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            headers: ['Date', 'Amount'],
                            suggested_mapping: {
                                date: 'Date',
                                amount: 'Amount',
                            },
                        },
                    },
                })
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            preview_transactions: [
                                {
                                    date: '2026-03-01',
                                    amount: 100.00,
                                },
                            ],
                            validation_summary: {
                                total: 1,
                                valid: 1,
                                invalid: 0,
                            },
                        },
                    },
                })
                .mockRejectedValueOnce({
                    response: {
                        status: 409,
                        data: {
                            requires_confirmation: true,
                            message: 'This file has already been imported',
                        },
                    },
                });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={1}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            // Wait for detection
            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Confirm mapping
            const confirmMappingButton = screen.getByText('Confirm Mapping');
            await user.click(confirmMappingButton);

            // Wait for preview and attempt import
            await waitFor(() => {
                expect(screen.getByTestId('preview-table')).toBeInTheDocument();
            });

            const confirmImportButton = screen.getByText("Looks Good! Continue");
            await user.click(confirmImportButton);

            // Verify error message
            await waitFor(() => {
                expect(
                    screen.getByText(/This file has already been imported/i)
                ).toBeInTheDocument();
            });

            // Verify callback was NOT called
            expect(mockOnComplete).not.toHaveBeenCalled();
        });
    });

    describe('Error Handling: 422 Validation Error', () => {
        test('handles 422 validation errors', async () => {
            const user = userEvent.setup();

            axios.post
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            headers: ['Date', 'Amount'],
                            suggested_mapping: {
                                date: 'Date',
                                amount: 'Amount',
                            },
                        },
                    },
                })
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            preview_transactions: [
                                {
                                    date: '2026-03-01',
                                    amount: 100.00,
                                },
                            ],
                            validation_summary: {
                                total: 1,
                                valid: 1,
                                invalid: 0,
                            },
                        },
                    },
                })
                .mockRejectedValueOnce({
                    response: {
                        status: 422,
                        data: {
                            errors: {
                                account_id: ['The account_id must be a valid account'],
                                'mapping_config.date': ['Invalid date format'],
                            },
                        },
                    },
                });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={1}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            // Wait for detection
            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Confirm mapping
            const confirmMappingButton = screen.getByText('Confirm Mapping');
            await user.click(confirmMappingButton);

            // Wait for preview
            await waitFor(() => {
                expect(screen.getByTestId('preview-table')).toBeInTheDocument();
            });

            const confirmImportButton = screen.getByText("Looks Good! Continue");
            await user.click(confirmImportButton);

            // Verify error message includes validation errors
            await waitFor(() => {
                const errorText = screen.getByText(/Validation failed/i).textContent;
                expect(errorText).toContain('account_id');
                expect(errorText).toContain('mapping_config.date');
            });

            expect(mockOnComplete).not.toHaveBeenCalled();
        });
    });

    describe('Error Handling: 403 Access Denied', () => {
        test('handles 403 access denied error', async () => {
            const user = userEvent.setup();

            axios.post
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            headers: ['Date', 'Amount'],
                            suggested_mapping: {
                                date: 'Date',
                                amount: 'Amount',
                            },
                        },
                    },
                })
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            preview_transactions: [
                                {
                                    date: '2026-03-01',
                                    amount: 100.00,
                                },
                            ],
                            validation_summary: {
                                total: 1,
                                valid: 1,
                                invalid: 0,
                            },
                        },
                    },
                })
                .mockRejectedValueOnce({
                    response: {
                        status: 403,
                        data: {
                            message: 'Unauthorized: You do not have permission to import to this account',
                        },
                    },
                });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={1}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            // Wait for detection
            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Confirm mapping
            const confirmMappingButton = screen.getByText('Confirm Mapping');
            await user.click(confirmMappingButton);

            // Wait for preview
            await waitFor(() => {
                expect(screen.getByTestId('preview-table')).toBeInTheDocument();
            });

            const confirmImportButton = screen.getByText("Looks Good! Continue");
            await user.click(confirmImportButton);

            // Verify error message
            await waitFor(() => {
                expect(
                    screen.getByText(/Access denied/i)
                ).toBeInTheDocument();
            });

            expect(mockOnComplete).not.toHaveBeenCalled();
        });
    });

    describe('Step Navigation', () => {
        test('shows step indicator', async () => {
            axios.post.mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['Date', 'Amount'],
                        suggested_mapping: {
                            date: 'Date',
                            amount: 'Amount',
                        },
                    },
                },
            });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={0}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            expect(screen.getByText(/Step 1 of 2/i)).toBeInTheDocument();
        });

        test('can go back from preview to mapping', async () => {
            const user = userEvent.setup();

            axios.post
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            headers: ['Date', 'Amount'],
                            suggested_mapping: {
                                date: 'Date',
                                amount: 'Amount',
                            },
                        },
                    },
                })
                .mockResolvedValueOnce({
                    data: {
                        data: {
                            preview_transactions: [
                                {
                                    date: '2026-03-01',
                                    amount: 100.00,
                                },
                            ],
                            validation_summary: {
                                total: 1,
                                valid: 1,
                                invalid: 0,
                            },
                        },
                    },
                });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount={1}
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            // Wait for detection
            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Confirm mapping to go to step 2
            const confirmMappingButton = screen.getByText('Confirm Mapping');
            await user.click(confirmMappingButton);

            await waitFor(() => {
                expect(screen.getByText(/Step 2 of 2/i)).toBeInTheDocument();
            });

            // Click back button
            const backButton = screen.getByText('Back to Mapping');
            await user.click(backButton);

            // Should be back at step 1
            await waitFor(() => {
                expect(screen.getByText(/Step 1 of 2/i)).toBeInTheDocument();
            });
        });
    });

    describe('Account Selection', () => {
        test('disables mapping until account is selected', async () => {
            axios.post.mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['Date', 'Amount'],
                        suggested_mapping: {
                            date: 'Date',
                            amount: 'Amount',
                        },
                    },
                },
            });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount=""
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Should show message about selecting account
            expect(
                screen.getByText(/Please select an account before continuing/i)
            ).toBeInTheDocument();
        });
    });

    describe('Account Selection', () => {
        test('disables mapping until account is selected', async () => {
            axios.post.mockResolvedValueOnce({
                data: {
                    data: {
                        headers: ['Date', 'Amount'],
                        suggested_mapping: {
                            date: 'Date',
                            amount: 'Amount',
                        },
                    },
                },
            });

            render(
                <XlsxSimplifiedWizard
                    file={mockFile}
                    accounts={mockAccounts}
                    selectedAccount=""
                    onAccountChange={mockOnAccountChange}
                    onComplete={mockOnComplete}
                    onCancel={mockOnCancel}
                />
            );

            await waitFor(() => {
                expect(screen.queryByText(/Analyzing your file/i)).not.toBeInTheDocument();
            });

            // Should show message about selecting account
            expect(
                screen.getByText(/Please select an account before continuing/i)
            ).toBeInTheDocument();
        });
    });
});
