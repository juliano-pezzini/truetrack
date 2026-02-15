import axios from 'axios';

// Configure axios defaults
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Get CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    // Set CSRF token for axios
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    
    // Make CSRF token available globally for Inertia
    window.csrfToken = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}
