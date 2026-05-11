import './bootstrap';
import Swal from 'sweetalert2';
window.Swal = Swal;

import $ from 'jquery';

window.$ = $;
window.jQuery = $;

window.openModal = function (url) {
	$.get(url, function (html) {
		const container = document.getElementById('modalContent');
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

window.closeModal = function () {
	document.getElementById('globalModal').classList.add('hidden');
	document.getElementById('modalContent').innerHTML = '';
};

document.addEventListener('DOMContentLoaded', () => {
	// Close global modal when clicking the backdrop
	document.getElementById('globalModal')?.addEventListener('click', function (e) {
		if (e.target === this) {
			window.closeModal();
		}
	});

	// Open modal on any element with data-modal-url
	document.addEventListener('click', function (e) {
		const btn = e.target.closest('[data-modal-url]');
		if (!btn) {
			return;
		}
		window.openModal(btn.dataset.modalUrl);
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
