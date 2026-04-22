import '../css/app.css';
import './bootstrap';
import ThemeProvider from '@/Components/ThemeProvider';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Get CSRF token from meta tag
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        const initialPreference =
            props.initialPage.props.auth?.user?.theme_preference ?? 'system';

        root.render(
            <ThemeProvider initialPreference={initialPreference}>
                <App {...props} />
            </ThemeProvider>,
        );
    },
    progress: {
        color: '#4F46E5',
        showSpinner: true,
        delay: 250,
    },
});
