import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Instanciar o Echo/Pusher pode lançar de forma síncrona (ex: chave ausente
// por falta de configuração no .env do ambiente). Como este módulo é
// importado no início do app.js, uma exceção aqui travaria a execução do
// resto do arquivo (jQuery, SweetAlert2, modais, toggle de sidebar etc.) em
// TODA página do sistema — não só no chat. Por isso, isolamos com try/catch:
// se o Reverb não estiver configurado, o chat fica sem tempo real, mas o
// resto do sistema continua funcionando normalmente.
try {
	const key = import.meta.env.VITE_REVERB_APP_KEY;

	if (!key) {
		throw new Error('VITE_REVERB_APP_KEY não configurada — chat em tempo real desativado.');
	}

	window.Echo = new Echo({
		broadcaster: 'reverb',
		key,
		wsHost: import.meta.env.VITE_REVERB_HOST,
		wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
		wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
		forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
		enabledTransports: ['ws', 'wss'],
	});
} catch (e) {
	console.warn('[chat] Reverb/Echo não pôde ser inicializado:', e);
}

// resources/js/app.js é carregado como <script type="module">, que só executa
// depois que o HTML termina de ser parseado. Scripts inline no fim do body
// (como os das páginas do chat) podem rodar antes disso, então eles não podem
// assumir que window.Echo já existe — precisam esperar este evento. Ele é
// disparado mesmo quando a inicialização falha (window.Echo fica undefined).
window.dispatchEvent(new Event('laravel-echo:ready'));
