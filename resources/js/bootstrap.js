/**
 * Laravel's axios shim stays available for ad-hoc requests, but the primary
 * transport is window.LoanPro.request (see app.js).
 */
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
