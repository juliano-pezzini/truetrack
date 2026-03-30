import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import OfxImportUpload from '@/Components/OfxImport/OfxImportUpload';

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
                reset: () => setFormData(initialData),
            };
        },
    };
});

describe('OfxImportUpload', () => {
    const accounts = [{ id: 1, name: 'Main Account', type: 'bank' }];

    beforeEach(() => {
        jest.clearAllMocks();
        global.route = jest.fn(() => '/api/v1/ofx-imports');
        jest.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        console.error.mockRestore();
    });

    it('submits via axios and calls onSuccess', async () => {
        const user = userEvent.setup();
        const onSuccess = jest.fn();
        axios.post.mockResolvedValue({ data: { data: { id: 55 } } });

        render(<OfxImportUpload accounts={accounts} onSuccess={onSuccess} />);

        await user.selectOptions(screen.getByLabelText('Account'), '1');

        const fileInput = screen.getByLabelText('OFX File');
        const file = new File(['OFXDATA'], 'statement.ofx', { type: 'application/x-ofx' });
        await user.upload(fileInput, file);

        const submitButton = screen.getByRole('button', { name: 'Upload & Process' });
        fireEvent.submit(submitButton.closest('form'));

        await waitFor(() => {
            expect(axios.post).toHaveBeenCalledWith(
                '/api/v1/ofx-imports',
                expect.any(FormData),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'Content-Type': 'multipart/form-data',
                    }),
                })
            );

            expect(onSuccess).toHaveBeenCalled();
        });
    });

    it('shows validation errors when API returns 422', async () => {
        const user = userEvent.setup();
        axios.post.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        account_id: ['The account field is required.'],
                    },
                },
            },
        });

        render(<OfxImportUpload accounts={accounts} onSuccess={jest.fn()} />);

        await user.selectOptions(screen.getByLabelText('Account'), '1');
        const fileInput = screen.getByLabelText('OFX File');
        const file = new File(['OFXDATA'], 'statement.ofx', { type: 'application/x-ofx' });
        await user.upload(fileInput, file);

        const submitButton = screen.getByRole('button', { name: 'Upload & Process' });
        fireEvent.submit(submitButton.closest('form'));

        await waitFor(() => {
            expect(screen.getByText('Validation failed. Please review your inputs.')).toBeInTheDocument();
            expect(screen.getByText('The account field is required.')).toBeInTheDocument();
        });
    });

    it('shows generic error on non-validation failure', async () => {
        const user = userEvent.setup();
        axios.post.mockRejectedValue({
            response: {
                status: 403,
                data: { message: 'Forbidden' },
            },
        });

        render(<OfxImportUpload accounts={accounts} onSuccess={jest.fn()} />);

        await user.selectOptions(screen.getByLabelText('Account'), '1');
        const fileInput = screen.getByLabelText('OFX File');
        const file = new File(['OFXDATA'], 'statement.ofx', { type: 'application/x-ofx' });
        await user.upload(fileInput, file);

        const submitButton = screen.getByRole('button', { name: 'Upload & Process' });
        fireEvent.submit(submitButton.closest('form'));

        await waitFor(() => {
            expect(screen.getByText('OFX import failed. Please try again.')).toBeInTheDocument();
        });
    });
});
