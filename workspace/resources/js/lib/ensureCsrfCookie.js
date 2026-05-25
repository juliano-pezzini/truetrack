import axios from 'axios';

let csrfPromise = null;

export async function ensureCsrfCookie() {
    // Memoize the CSRF cookie request to avoid redundant network calls
    // The XSRF-TOKEN cookie remains valid for the entire session
    if (csrfPromise) {
        return csrfPromise;
    }

    csrfPromise = axios.get('/sanctum/csrf-cookie').catch(() => {
        // Reset on error so retry is attempted
        csrfPromise = null;
        throw new Error('Failed to fetch CSRF cookie');
    });

    return csrfPromise;
}
