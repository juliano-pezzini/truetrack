import axios from 'axios';

export async function ensureCsrfCookie() {
    await axios.get('/sanctum/csrf-cookie');
}
