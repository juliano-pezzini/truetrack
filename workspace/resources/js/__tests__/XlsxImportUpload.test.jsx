import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import XlsxImportUpload from '@/Components/XlsxImport/XlsxImportUpload';

jest.mock('axios');

jest.mock('@inertiajs/react', () => {
    const React = require('react');

    return {
        useForm: (initialData) => {
            const [data, setFormData] = React.useState(initialData);

            return {
                data,
                setData: (key, value) => {
                    setFormData((prev) => ({
                        ...prev,
                        [key]: value,
                    }));
                },
                post: jest.fn(),
                processing: false,
                errors: {},
                reset: () => setFormData(initialData),
            };
        },
    };
});

jest.mock('@/Components/XlsxImport/XlsxColumnMapper', () => ({
    __esModule: true,
    default: ({ onMappingConfirmed }) => (
        <div>
            <p>Mock Column Mapper</p>
            <button type="button" onClick={() => onMappingConfirmed({
                transaction_date: 'Date',
                description: 'Description',
                amount: 'Amount',
                type: 'Type',
            })}
            >
                Confirm Mapping
            </button>
        </div>
    ),
}));

jest.mock('@/Components/XlsxImport/XlsxPreviewTable', () => ({
    __esModule: true,
    default: ({ onConfirm }) => (
        <div>
            <p>Mock Preview Table</p>
            <button type="button" onClick={onConfirm}>Continue From Preview</button>
        </div>
    ),
}));

jest.mock('@/Components/XlsxImport/ReconciliationOptionsPanel', () => ({
    __esModule: true,
    default: () => <div>Mock Reconciliation Options</div>,
}));

describe('XlsxImportUpload', () => {
    let consoleErrorSpy;

    const mockAccounts = [
        { id: 1, name: 'Checking', type: 'bank' },
    ];

    const setupAndMoveToStep4 = async () => {
        const user = userEvent.setup();
        const onImportStarted = jest.fn();

        render(
            <XlsxImportUpload
                accounts={mockAccounts}
                activeImportsCount={0}
                maxImports={3}
                onImportStarted={onImportStarted}
            />
        );

        await user.selectOptions(screen.getByLabelText('Account *'), '1');

        const fileInput = screen.getByLabelText('XLSX/CSV File *');
        const file = new File(['date,description,amount'], 'statement.csv', { type: 'text/csv' });

        await user.upload(fileInput, file);

        await screen.findByText('Mock Column Mapper');

        await user.click(screen.getByRole('button', { name: 'Confirm Mapping' }));

        await screen.findByText('Mock Preview Table');

        await user.click(screen.getByRole('button', { name: 'Continue From Preview' }));

        await waitFor(() => {
            expect(screen.getByText('Import Options')).toBeInTheDocument();
        });

        return { user, onImportStarted };
    };

    beforeEach(() => {
        jest.clearAllMocks();
        consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

        axios.post.mockImplementation((url) => {
            if (url === '/api/v1/xlsx-imports/detect-columns') {
                return Promise.resolve({
                    data: {
                        data: {
                            headers: ['Date', 'Description', 'Amount', 'Type'],
                            suggested_mapping: {
                                transaction_date: 'Date',
                                description: 'Description',
                                amount: 'Amount',
                                type: 'Type',
                            },
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports/preview') {
                return Promise.resolve({
                    data: {
                        data: {
                            preview_transactions: [
                                {
                                    transaction_date: '2026-01-01',
                                    description: 'Coffee',
                                    amount: 5.0,
                                    type: 'debit',
                                    warnings: [],
                                    category_name: null,
                                    tags: [],
                                },
                            ],
                            validation_summary: {
                                valid_rows: 1,
                                rows_with_warnings: 0,
                            },
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports') {
                return Promise.resolve({
                    data: {
                        data: {
                            id: 99,
                        },
                    },
                });
            }

            return Promise.reject(new Error(`Unexpected URL: ${url}`));
        });
    });

    afterEach(() => {
        consoleErrorSpy.mockRestore();
    });

    it('completes the main success path and starts import', async () => {
        const { user, onImportStarted } = await setupAndMoveToStep4();

        await user.click(screen.getByRole('button', { name: 'Start Import' }));

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/v1/xlsx-imports',
                expect.any(FormData),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'Content-Type': 'multipart/form-data',
                    }),
                })
            );

            expect(onImportStarted).toHaveBeenCalledWith(99);
        });
    });

    it('shows duplicate warning on 409 and allows force reimport', async () => {
        axios.post.mockImplementation((url) => {
            if (url === '/api/v1/xlsx-imports/detect-columns') {
                return Promise.resolve({
                    data: {
                        data: {
                            headers: ['Date', 'Description', 'Amount', 'Type'],
                            suggested_mapping: {},
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports/preview') {
                return Promise.resolve({
                    data: {
                        data: {
                            preview_transactions: [],
                            validation_summary: {
                                valid_rows: 0,
                                rows_with_warnings: 0,
                            },
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports') {
                return Promise.reject({
                    response: {
                        status: 409,
                        data: {
                            requires_confirmation: true,
                            message: 'Duplicate file detected.',
                            duplicate_import_id: 1234,
                        },
                    },
                });
            }

            return Promise.reject(new Error(`Unexpected URL: ${url}`));
        });

        const { user } = await setupAndMoveToStep4();

        await user.click(screen.getByRole('button', { name: 'Start Import' }));

        await waitFor(() => {
            expect(screen.getByText('Duplicate file detected.')).toBeInTheDocument();
            expect(screen.getByText(/Existing import ID: 1234/i)).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Force reimport' })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: 'Force reimport' }));

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledTimes(4);
        });
    });

    it('shows validation message on 422 response', async () => {
        axios.post.mockImplementation((url) => {
            if (url === '/api/v1/xlsx-imports/detect-columns') {
                return Promise.resolve({
                    data: {
                        data: {
                            headers: ['Date', 'Description', 'Amount', 'Type'],
                            suggested_mapping: {},
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports/preview') {
                return Promise.resolve({
                    data: {
                        data: {
                            preview_transactions: [],
                            validation_summary: {
                                valid_rows: 0,
                                rows_with_warnings: 0,
                            },
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports') {
                return Promise.reject({
                    response: {
                        status: 422,
                        data: {
                            errors: {
                                account_id: ['The account id field is required.'],
                            },
                        },
                    },
                });
            }

            return Promise.reject(new Error(`Unexpected URL: ${url}`));
        });

        const { user } = await setupAndMoveToStep4();

        await user.click(screen.getByRole('button', { name: 'Start Import' }));

        await waitFor(() => {
            expect(screen.getByText('Import validation failed. Please review your inputs and try again.')).toBeInTheDocument();
        });
    });

    it('shows generic message on 403 response', async () => {
        axios.post.mockImplementation((url) => {
            if (url === '/api/v1/xlsx-imports/detect-columns') {
                return Promise.resolve({
                    data: {
                        data: {
                            headers: ['Date', 'Description', 'Amount', 'Type'],
                            suggested_mapping: {},
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports/preview') {
                return Promise.resolve({
                    data: {
                        data: {
                            preview_transactions: [],
                            validation_summary: {
                                valid_rows: 0,
                                rows_with_warnings: 0,
                            },
                        },
                    },
                });
            }

            if (url === '/api/v1/xlsx-imports') {
                return Promise.reject({
                    response: {
                        status: 403,
                        data: {
                            message: 'Forbidden',
                        },
                    },
                });
            }

            return Promise.reject(new Error(`Unexpected URL: ${url}`));
        });

        const { user } = await setupAndMoveToStep4();

        await user.click(screen.getByRole('button', { name: 'Start Import' }));

        await waitFor(() => {
            expect(screen.getByText('Import submission failed. Please try again.')).toBeInTheDocument();
        });
    });
});
