// Lazily creates a Laravel Echo (Reverb) client when websockets are configured; otherwise returns null
// and callers fall back to polling.
let instance = null;

export async function getEcho() {
    if (typeof window === 'undefined' || !import.meta.env.VITE_REVERB_APP_KEY) return null;
    if (instance) return instance;
    const [{ default: Echo }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')]);
    window.Pusher = Pusher;
    instance = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
    return instance;
}
