import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import axios from 'axios';

// Function to get fresh CSRF token
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

// Function to refresh CSRF token from server
const refreshCsrfToken = async () => {
    try {
        const response = await fetch('/csrf-token', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.csrf_token) {
                // Update the meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.csrf_token);
                }
                return data.csrf_token;
            }
        }
    } catch (error) {
        console.warn('Failed to refresh CSRF token:', error);
    }
    return null;
};

// Configure axios globally
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set initial CSRF token
let currentToken = getCsrfToken();
axios.defaults.headers.common['X-CSRF-TOKEN'] = currentToken;

// Add request interceptor to ensure fresh token on each request
axios.interceptors.request.use(
    config => {
        // Get fresh token for each request
        const freshToken = getCsrfToken();
        if (freshToken) {
            config.headers['X-CSRF-TOKEN'] = freshToken;
            currentToken = freshToken;
        }
        return config;
    },
    error => Promise.reject(error)
);

// Add response interceptor to handle CSRF token refresh
axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch, try to refresh token
            const freshToken = await refreshCsrfToken();
            if (freshToken) {
                // Token was refreshed, retry the request
                currentToken = freshToken;
                axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
                
                // Retry the original request
                const originalRequest = error.config;
                if (originalRequest && !originalRequest._retry) {
                    originalRequest._retry = true;
                    originalRequest.headers['X-CSRF-TOKEN'] = freshToken;
                    return axios(originalRequest);
                }
            } else {
                // No fresh token available, reload page
                console.warn('Unable to refresh CSRF token, reloading page...');
                window.location.reload();
            }
        }
        return Promise.reject(error);
    }
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
    // Add CSRF token to all Inertia requests
    before: (page, visit) => {
        // Add fresh CSRF token to headers for all requests
        const freshToken = getCsrfToken();
        if (freshToken) {
            visit.headers = {
                ...visit.headers,
                'X-CSRF-TOKEN': freshToken,
            };
        }
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Set up periodic CSRF token refresh (every 10 minutes)
setInterval(async () => {
    try {
        const freshToken = await refreshCsrfToken();
        if (freshToken) {
            currentToken = freshToken;
            axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
            console.log('CSRF token refreshed automatically');
        }
    } catch (error) {
        console.warn('Failed to refresh CSRF token automatically:', error);
    }
}, 10 * 60 * 1000); // 10 minutes
