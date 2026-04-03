import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { useTheme } from '@/Components/ThemeProvider';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const themeOptions = [
    {
        value: 'light',
        label: 'Light',
        description: 'Always use the light interface theme.',
    },
    {
        value: 'dark',
        label: 'Dark',
        description: 'Always use the dark interface theme.',
    },
    {
        value: 'system',
        label: 'System Default (Automatic)',
        description:
            'Follow your operating system or browser theme preference.',
    },
];

export default function UpdateThemePreferenceForm({ className = '' }) {
    const user = usePage().props.auth.user;
    const { setThemePreference } = useTheme();
    const {
        data,
        setData,
        patch,
        processing,
        errors,
        recentlySuccessful,
    } = useForm({
        name: user.name,
        email: user.email,
        theme_preference: user.theme_preference ?? 'system',
    });

    useEffect(() => {
        setData({
            name: user.name,
            email: user.email,
            theme_preference: user.theme_preference ?? 'system',
        });
    }, [setData, user.email, user.name, user.theme_preference]);

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'), {
            preserveScroll: true,
            onSuccess: () => setThemePreference(data.theme_preference),
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Appearance
                </h2>

                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Choose how the application should display the interface theme.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="space-y-3">
                    {themeOptions.map((option) => (
                        <label
                            key={option.value}
                            className="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-indigo-400 dark:border-gray-700 dark:hover:border-indigo-500"
                        >
                            <input
                                type="radio"
                                name="theme_preference"
                                value={option.value}
                                checked={data.theme_preference === option.value}
                                onChange={(e) =>
                                    setData('theme_preference', e.target.value)
                                }
                                className="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                            />
                            <span>
                                <span className="block text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {option.label}
                                </span>
                                <span className="block text-sm text-gray-600 dark:text-gray-400">
                                    {option.description}
                                </span>
                            </span>
                        </label>
                    ))}

                    <InputError
                        message={errors.theme_preference}
                        className="mt-2"
                    />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}