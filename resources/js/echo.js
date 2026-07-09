import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
	broadcaster: 'reverb',
	key: import.meta.env.VITE_REVERB_APP_KEY,
	wsHost: import.meta.env.VITE_REVERB_HOST,
	wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
	wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
	forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
	enabledTransports: ['ws', 'wss'],
});

// resources/js/app.js é carregado como <script type="module">, que só executa
// depois que o HTML termina de ser parseado. Scripts inline no fim do body
// (como os das páginas do chat) podem rodar antes disso, então eles não podem
// assumir que window.Echo já existe — precisam esperar este evento.
window.dispatchEvent(new Event('laravel-echo:ready'));
