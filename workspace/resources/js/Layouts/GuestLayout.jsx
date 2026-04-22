import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-950">
            <div className="mb-2">
                <Link href="/">
                    <ApplicationLogo
                        className="justify-center"
                        iconClassName="h-24 w-24 sm:h-28 sm:w-28"
                    />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-900 dark:shadow-none dark:ring-1 dark:ring-gray-800">
                {children}
            </div>
        </div>
    );
}
