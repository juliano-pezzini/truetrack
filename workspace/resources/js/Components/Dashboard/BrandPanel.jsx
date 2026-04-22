import ApplicationLogo from '@/Components/ApplicationLogo';

export default function BrandPanel() {
    return (
        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <div className="flex h-full min-h-[300px] items-center justify-center">
                <ApplicationLogo
                    iconClassName="h-64 w-64 md:h-72 md:w-72"
                />
            </div>
        </div>
    );
}
