import { render, screen } from '@testing-library/react';
import ImportProgress from '@/Components/Import/ImportProgress';

describe('ImportProgress', () => {
    describe('Progress Calculation', () => {
        test('uses progress field when available', () => {
            const importData = {
                status: 'processing',
                progress: 75,
                processed_count: 50,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).toHaveStyle('width: 75%');
        });

        test('uses progress_percentage field when progress is not available', () => {
            const importData = {
                status: 'processing',
                progress_percentage: 60,
                processed_count: 30,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).toHaveStyle('width: 60%');
        });

        test('calculates from counts when progress fields are unavailable', () => {
            const importData = {
                status: 'processing',
                processed_count: 25,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).toHaveStyle('width: 25%');
        });

        test('returns 0 when total_count is 0', () => {
            const importData = {
                status: 'pending',
                processed_count: 0,
                total_count: 0,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).toHaveStyle('width: 0%');
        });

        test('returns 0 when total_count is undefined', () => {
            const importData = {
                status: 'pending',
                processed_count: 10,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).toHaveStyle('width: 0%');
        });

        test('handles NaN gracefully', () => {
            const importData = {
                status: 'processing',
                processed_count: 50,
                // total_count is missing, should not cause NaN
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            // Should be 0%, not NaN%
            expect(progressBar).toHaveStyle('width: 0%');
        });
    });

    describe('Status Display', () => {
        test('displays completed status with green badge', () => {
            const importData = {
                status: 'completed',
                processed_count: 100,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const badge = screen.getByText('Completed');
            expect(badge).toHaveClass('bg-green-100', 'text-green-800');
        });

        test('displays failed status with red badge', () => {
            const importData = {
                status: 'failed',
                error_message: 'Something went wrong',
            };

            render(<ImportProgress importData={importData} />);

            const badge = screen.getByText('Failed');
            expect(badge).toHaveClass('bg-red-100', 'text-red-800');
        });

        test('displays processing status with blue badge', () => {
            const importData = {
                status: 'processing',
                processed_count: 50,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const badge = screen.getByText('Processing');
            expect(badge).toHaveClass('bg-blue-100', 'text-blue-800');
        });

        test('displays pending status with yellow badge', () => {
            const importData = {
                status: 'pending',
                processed_count: 0,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const badge = screen.getByText('Pending');
            expect(badge).toHaveClass('bg-yellow-100', 'text-yellow-800');
        });
    });

    describe('Progress Bar Animation', () => {
        test('shows animated progress bar for processing status', () => {
            const importData = {
                status: 'processing',
                processed_count: 50,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).toHaveClass('animate-pulse');
        });

        test('does not animate when status is completed', () => {
            const importData = {
                status: 'completed',
                processed_count: 100,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).not.toHaveClass('animate-pulse');
        });

        test('does not animate when progress is 100%', () => {
            const importData = {
                status: 'processing',
                progress: 100,
            };

            render(<ImportProgress importData={importData} />);

            const progressBar = screen.getByRole('progressbar', { hidden: true });
            expect(progressBar).not.toHaveClass('animate-pulse');
        });
    });

    describe('Count Display', () => {
        test('shows count for active imports when total_count is available', () => {
            const importData = {
                status: 'processing',
                processed_count: 50,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.getByText('50 / 100')).toBeInTheDocument();
        });

        test('does not show count when total_count is undefined', () => {
            const importData = {
                status: 'processing',
                processed_count: 50,
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.queryByText(/\d+ \/ \d+/)).not.toBeInTheDocument();
        });

        test('does not show count for completed status', () => {
            const importData = {
                status: 'completed',
                processed_count: 100,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.queryByText('100 / 100')).not.toBeInTheDocument();
        });
    });

    describe('XLSX Stats Display', () => {
        test('shows detailed stats for XLSX imports when completed', () => {
            const importData = {
                status: 'completed',
                processed_count: 95,
                total_count: 100,
                skipped_count: 3,
                duplicate_count: 2,
            };

            render(<ImportProgress importData={importData} type="xlsx" />);

            expect(screen.getByText('100')).toBeInTheDocument(); // Total
            expect(screen.getByText('95')).toBeInTheDocument(); // Processed
            expect(screen.getByText('3')).toBeInTheDocument(); // Skipped
            expect(screen.getByText('2')).toBeInTheDocument(); // Duplicates
        });

        test('does not show stats for OFX imports', () => {
            const importData = {
                status: 'completed',
                processed_count: 95,
                total_count: 100,
                skipped_count: 3,
                duplicate_count: 2,
            };

            render(<ImportProgress importData={importData} type="ofx" />);

            expect(screen.queryByText('Skipped')).not.toBeInTheDocument();
            expect(screen.queryByText('Duplicates')).not.toBeInTheDocument();
        });

        test('conditionally renders total when available', () => {
            const importData = {
                status: 'completed',
                processed_count: 95,
                skipped_count: 3,
                // total_count is missing
            };

            render(<ImportProgress importData={importData} type="xlsx" />);

            expect(screen.queryByText('Total')).not.toBeInTheDocument();
        });
    });

    describe('Error Messages', () => {
        test('displays error message for failed imports', () => {
            const importData = {
                status: 'failed',
                error_message: 'Invalid file format',
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.getByText(/Error: Invalid file format/i)).toBeInTheDocument();
        });

        test('does not show error message when status is not failed', () => {
            const importData = {
                status: 'completed',
                error_message: 'Should not be shown',
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.queryByText(/Should not be shown/i)).not.toBeInTheDocument();
        });
    });

    describe('Success Messages', () => {
        test('displays success message for completed imports', () => {
            const importData = {
                status: 'completed',
                processed_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.getByText(/Import completed successfully/i)).toBeInTheDocument();
        });
    });

    describe('Status Messages', () => {
        test('displays processing message', () => {
            const importData = {
                status: 'processing',
                processed_count: 50,
                total_count: 100,
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.getByText(/Processing transactions/i)).toBeInTheDocument();
        });

        test('displays pending message', () => {
            const importData = {
                status: 'pending',
            };

            render(<ImportProgress importData={importData} />);

            expect(screen.getByText(/Import queued/i)).toBeInTheDocument();
        });
    });
});
