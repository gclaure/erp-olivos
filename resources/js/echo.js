import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'xzuby3q7gyfuxvrnwesw';
const isHttps = window.location.protocol === 'https:';
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : (isHttps ? 443 : 8080);
const forceTLS = import.meta.env.VITE_REVERB_SCHEME ? import.meta.env.VITE_REVERB_SCHEME === 'https' : isHttps;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: forceTLS,
    enabledTransports: ['ws', 'wss'],
});

