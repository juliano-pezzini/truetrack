import axios from 'axios';

// Configure axios defaults
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Use cookie-based XSRF token handling instead of a fixed meta token header.
// This avoids stale X-CSRF-TOKEN values after session regeneration (e.g. login).
