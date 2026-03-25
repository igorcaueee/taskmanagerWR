import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
	const btn = document.getElementById('nav-toggle');
	const sidebar = document.getElementById('main-sidebar');
	if (!btn || !sidebar) {
		return;
	}

	const setAria = (expanded) => btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');

	// Helper to determine screen size
	const isMobile = () => window.matchMedia('(max-width: 767px)').matches;

	// Initialize aria state
	if (isMobile()) {
		// mobile: sidebar is hidden unless it has .open
		sidebar.classList.remove('open');
		setAria(false);
	} else {
		// desktop: expanded unless body has collapsed flag
		// default: collapsed so it only opens when user clicks
		document.body.classList.add('sidebar-collapsed');
		setAria(false);
	}

	btn.addEventListener('click', () => {
		if (isMobile()) {
			// toggle overlay sidebar on mobile
			sidebar.classList.toggle('open');
			setAria(sidebar.classList.contains('open'));
		} else {
			// toggle collapsed state for desktop
			document.body.classList.toggle('sidebar-collapsed');
			setAria(!document.body.classList.contains('sidebar-collapsed'));
		}
	});
	// Optional: close mobile sidebar when clicking outside
	document.addEventListener('click', (e) => {
		if (!isMobile()) return;
		if (!sidebar.classList.contains('open')) return;
		const target = e.target;
		if (target === btn || btn.contains(target) || sidebar.contains(target)) return;
		sidebar.classList.remove('open');
		setAria(false);
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
			// rotate chevron
			if (chevron) {
				chevron.classList.toggle('rotate-90');
			}
			// update aria
			const expanded = !target.classList.contains('hidden');
			toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
		});
	});
});
