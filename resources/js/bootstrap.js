import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * In production builds we sometimes make axios requests outside Blade forms
 * (admin dashboard widgets, reports, notifications, etc.). When those requests
 * do not include the CSRF header Laravel immediately responds with a 419.
 * Laravel's default bootstrap file normally copies the token from the meta tag,
 * but that code was removed at some point. Restoring it ensures every axios
 * request automatically carries the `X-CSRF-TOKEN` header.
 */
const token = document.head?.querySelector('meta[name="csrf-token"]');

if (token?.content) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else if (import.meta.env.DEV) {
    console.warn(
        'CSRF token meta tag not found. Axios requests may be rejected with HTTP 419.'
    );
}

// Ensure cookies such as `XSRF-TOKEN` and `laravel_session` stick to requests
// when calling same-origin endpoints (Render serves HTTPS through a proxy).
window.axios.defaults.withCredentials = true;
