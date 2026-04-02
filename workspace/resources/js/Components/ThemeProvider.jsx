import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';

const ThemeContext = createContext(null);

export const THEME_STORAGE_KEY = 'truetrack:theme_preference';
const ALLOWED_PREFERENCES = ['light', 'dark', 'system'];

function sanitizeThemePreference(preference) {
    if (ALLOWED_PREFERENCES.includes(preference)) {
        return preference;
    }

    return 'system';
}

function getSystemTheme() {
    if (typeof window === 'undefined' || !window.matchMedia) {
        return 'light';
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
}

function applyTheme(preference) {
    if (typeof document === 'undefined') {
        return 'light';
    }

    const effectiveTheme =
        preference === 'system' ? getSystemTheme() : preference;

    document.documentElement.classList.toggle('dark', effectiveTheme === 'dark');
    document.documentElement.dataset.themePreference = preference;
    document.documentElement.dataset.themeEffective = effectiveTheme;

    return effectiveTheme;
}

export default function ThemeProvider({ initialPreference = 'system', children }) {
    const [themePreference, setThemePreference] = useState(() => {
        const fallbackPreference = sanitizeThemePreference(initialPreference);

        if (typeof window === 'undefined') {
            return fallbackPreference;
        }

        try {
            const storedPreference = window.localStorage.getItem(THEME_STORAGE_KEY);

            if (storedPreference) {
                return sanitizeThemePreference(storedPreference);
            }
        } catch {
            // Ignore blocked storage and continue with server preference.
        }

        return fallbackPreference;
    });

    const [effectiveTheme, setEffectiveTheme] = useState(() =>
        themePreference === 'system' ? getSystemTheme() : themePreference,
    );

    const updateThemePreference = useCallback((preference) => {
        setThemePreference(sanitizeThemePreference(preference));
    }, []);

    useEffect(() => {
        const effective = applyTheme(themePreference);
        setEffectiveTheme(effective);

        try {
            window.localStorage.setItem(THEME_STORAGE_KEY, themePreference);
        } catch {
            // Ignore blocked storage to avoid breaking render.
        }
    }, [themePreference]);

    useEffect(() => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return;
        }

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        const handleChange = () => {
            if (themePreference === 'system') {
                const effective = applyTheme('system');
                setEffectiveTheme(effective);
            }
        };

        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handleChange);
            return () => mediaQuery.removeEventListener('change', handleChange);
        }

        mediaQuery.addListener(handleChange);
        return () => mediaQuery.removeListener(handleChange);
    }, [themePreference]);

    const value = useMemo(
        () => ({
            themePreference,
            effectiveTheme,
            setThemePreference: updateThemePreference,
        }),
        [themePreference, effectiveTheme, updateThemePreference],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme must be used inside ThemeProvider.');
    }

    return context;
}