const API_BASE = 'http://localhost/officina/api/';
const APP_URL = 'http://localhost/officina';

if (typeof window !== 'undefined') {
    window.API_BASE = API_BASE;
    window.APP_URL = APP_URL;
}