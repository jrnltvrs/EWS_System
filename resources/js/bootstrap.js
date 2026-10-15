import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

// Echo is set up in controller.blade.php (Pusher, matching BROADCAST_CONNECTION). echo.js targets Reverb,
// which isn't configured, and it threw on load, which stopped the rest of this bundle from running.
// import './echo';
