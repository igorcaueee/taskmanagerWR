import './bootstrap';
import './echo';
import Swal from 'sweetalert2';
window.Swal = Swal;

import $ from 'jquery';

window.$ = $;
window.jQuery = $;

window._modalBackUrl = null;
window._modalBackWidth = null;
window._modalHasChanges = false;

window.openModal = function (url, widthClass) {
	$.get(url, function (html) {
		const container = document.getElementById('modalContent');
		const modalContainer = document.getElementById('globalModalContainer');
		if (widthClass) {
			modalContainer.dataset.defaultWidth = modalContainer.className.match(/max-w-\S+/)?.[0] ?? 'max-w-2xl';
			modalContainer.classList.remove('max-w-2xl', 'max-w-3xl', 'max-w-4xl', 'max-w-5xl', 'max-w-6xl');
			modalContainer.classList.add(widthClass);
		}
		window._modalHasChanges = false;
		container.innerHTML = html;
		container.querySelectorAll('script').forEach(function (oldScript) {
			const newScript = document.createElement('script');
			[...oldScript.attributes].forEach(function (attr) {
				newScript.setAttribute(attr.name, attr.value);
			});
			newScript.textContent = oldScript.textContent;
			oldScript.parentNode.replaceChild(newScript, oldScript);
		});
		document.getElementById('globalModal').classList.remove('hidden');
	});
};

window.closeModal = async function () {
	if (window._modalHasChanges) {
		const result = await Swal.fire({
			title: 'Alterações não salvas',
			text: 'Você tem alterações não salvas. Deseja descartá-las?',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Sim, descartar',
			cancelButtonText: 'Continuar editando',
			confirmButtonColor: '#ef4444',
		});
		if (!result.isConfirmed) { return; }
		window._modalHasChanges = false;
	}

	if (window._modalBackUrl) {
		const backUrl = window._modalBackUrl;
		const backWidth = window._modalBackWidth;
		window._modalBackUrl = null;
		window._modalBackWidth = null;
		const modalContainer = document.getElementById('globalModalContainer');
		modalContainer.classList.remove('max-w-2xl', 'max-w-3xl', 'max-w-4xl', 'max-w-5xl', 'max-w-6xl');
		modalContainer.classList.add(backWidth || 'max-w-2xl');
		delete modalContainer.dataset.defaultWidth;
		window.openModal(backUrl);
		return;
	}

	const modalContainer = document.getElementById('globalModalContainer');
	const defaultWidth = modalContainer.dataset.defaultWidth;
	if (defaultWidth) {
		modalContainer.classList.remove('max-w-2xl', 'max-w-3xl', 'max-w-4xl', 'max-w-5xl', 'max-w-6xl');
		modalContainer.classList.add(defaultWidth);
		delete modalContainer.dataset.defaultWidth;
	}
	document.getElementById('globalModal').classList.add('hidden');
	document.getElementById('modalContent').innerHTML = '';
};

document.addEventListener('DOMContentLoaded', () => {
	// Close global modal when clicking the backdrop
	// Track mousedown origin to avoid closing when the user starts a click inside
	// the modal content and releases the mouse button outside (e.g. while selecting text).
	let mousedownOnBackdrop = false;
	const globalModal = document.getElementById('globalModal');

	globalModal?.addEventListener('mousedown', function (e) {
		mousedownOnBackdrop = e.target === this;
	});

	globalModal?.addEventListener('click', function (e) {
		if (e.target === this && mousedownOnBackdrop) {
			window.closeModal();
		}
		mousedownOnBackdrop = false;
	});

	// Open modal on any element with data-modal-url
	document.addEventListener('click', async function (e) {
		const btn = e.target.closest('[data-modal-url]');
		if (!btn) {
			return;
		}

		if (window._modalHasChanges) {
			const result = await Swal.fire({
				title: 'Alterações não salvas',
				text: 'Você tem alterações não salvas. Deseja descartá-las?',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: 'Sim, descartar',
				cancelButtonText: 'Continuar editando',
				confirmButtonColor: '#ef4444',
			});
			if (!result.isConfirmed) { return; }
			window._modalHasChanges = false;
		}

		if (btn.dataset.modalBackUrl) {
			window._modalBackWidth = document.getElementById('globalModalContainer').className.match(/max-w-\S+/)?.[0] ?? 'max-w-2xl';
			window._modalBackUrl = btn.dataset.modalBackUrl;
		} else {
			window._modalBackUrl = null;
			window._modalBackWidth = null;
		}

		window.openModal(btn.dataset.modalUrl, btn.dataset.modalWidth || null);
	});

	// Phone mask (delegated so it works on AJAX-loaded inputs)
	document.addEventListener('input', function (e) {
		if (!e.target.classList.contains('telefone-mask')) {
			return;
		}
		let value = e.target.value.replace(/\D/g, '').slice(0, 11);
		let formatted = '';
		if (value.length > 0) {
			formatted = '(' + value.slice(0, 2);
		}
		if (value.length >= 2) {
			formatted += ') ' + value.slice(2, value.length > 10 ? 7 : 6);
		}
		if (value.length > 10) {
			formatted += '-' + value.slice(7, 11);
		} else if (value.length > 6) {
			formatted += '-' + value.slice(6, 10);
		}
		e.target.value = formatted;
	});
	// Submenu toggles inside sidebar
	const submenuToggles = document.querySelectorAll('.submenu-toggle');
	submenuToggles.forEach((toggle) => {
		const targetId = toggle.getAttribute('data-target');
		const target = targetId ? document.getElementById(targetId) : null;
		const chevron = toggle.querySelector('svg');
		toggle.addEventListener('click', (ev) => {
			if (!target) return;
			target.classList.toggle('hidden');
			if (chevron) {
				chevron.classList.toggle('rotate-90');
			}
			const expanded = !target.classList.contains('hidden');
			toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
		});
	});

	const btn = document.getElementById('nav-toggle');
	const sidebar = document.getElementById('main-sidebar');
	const overlay = document.getElementById('sidebar-overlay');

	if (!btn || !sidebar) {
		return;
	}

	const setAria = (expanded) => btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
	const isMobile = () => window.matchMedia('(max-width: 767px)').matches;

	function openSidebar() {
		sidebar.classList.add('open');
		if (overlay) overlay.classList.remove('hidden');
		setAria(true);
	}

	function closeSidebar() {
		sidebar.classList.remove('open');
		if (overlay) overlay.classList.add('hidden');
		setAria(false);
	}

	if (isMobile()) {
		closeSidebar();
	}

	btn.addEventListener('click', () => {
		if (isMobile()) {
			sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
		} else {
			document.body.classList.toggle('sidebar-collapsed');
			setAria(!document.body.classList.contains('sidebar-collapsed'));
		}
	});

	if (overlay) {
		overlay.addEventListener('click', closeSidebar);
	}
});

// ─── Detecção de sessão expirada ────────────────────────────────────────────
(function () {
	let sessionAlertShown = false;

	function showSessionExpired() {
		if (sessionAlertShown) return;
		sessionAlertShown = true;

		Swal.fire({
			icon: 'warning',
			title: 'Sessão expirada',
			text: 'Sua sessão foi encerrada por inatividade. Clique abaixo para fazer login novamente.',
			confirmButtonText: 'Fazer login',
			confirmButtonColor: '#0084AA',
			allowOutsideClick: false,
			allowEscapeKey: false,
		}).then(() => {
			window.location.href = '/login';
		});
	}

	// Intercepta fetch nativo
	const originalFetch = window.fetch;
	window.fetch = async function (...args) {
		const response = await originalFetch(...args);
		if (response.status === 419) {
			showSessionExpired();
		}
		return response;
	};

	// Intercepta jQuery AJAX ($.ajax, $.get, $.post, etc.)
	$(document).ajaxError(function (_event, xhr) {
		if (xhr.status === 419) {
			showSessionExpired();
		}
	});
})();
