/*
 * gdi-shell.js
 * Behaviour for the mobile drawer and the colour-theme picker (markup: includes/gdi-shell.php,
 * styling: 04-shell.css). The theme itself is applied before first paint by the inline head
 * script in gdi-shell.php; this file only handles choosing and saving.
 *
 * Browser storage: localStorage key "gdi-theme", values brand | parchment | slate | system.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'gdi-theme';
	var CHOICES = ['brand', 'parchment', 'slate', 'system'];
	var root = document.documentElement;
	var darkQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

	/* ---------------- Theme picker ---------------- */

	// The current choice is also held in memory, so it survives when localStorage is blocked
	// (otherwise "System" would be forgotten and OS colour-scheme changes would stop being followed).
	var currentChoice = null;

	function readChoice() {
		if (currentChoice !== null && CHOICES.indexOf(currentChoice) !== -1) {
			return currentChoice;
		}
		try {
			var saved = window.localStorage.getItem(STORAGE_KEY);
			if (CHOICES.indexOf(saved) !== -1) {
				return saved;
			}
		} catch (e) {}
		return 'brand';
	}

	function resolve(choice) {
		if (choice === 'system') {
			return darkQuery && darkQuery.matches ? 'slate' : 'brand';
		}
		return choice;
	}

	function apply(choice) {
		root.setAttribute('data-gdi-choice', choice);
		root.setAttribute('data-gdi-theme', resolve(choice));
	}

	function syncPickers(choice) {
		document.querySelectorAll('.gdi-theme-picker input[type="radio"]').forEach(function (input) {
			input.checked = input.value === choice;
		});
	}

	function onPick(choice) {
		currentChoice = choice;
		try {
			window.localStorage.setItem(STORAGE_KEY, choice);
		} catch (e) {}
		apply(choice);
		syncPickers(choice);
	}

	function initPickers() {
		// Footer fallback: if no picker was placed in the footer layout, copy the drawer's one there.
		var footer = document.querySelector('.et-l--footer');
		var source = document.querySelector('#gdi-mm-panel .gdi-theme-picker');
		if (!source) {
			// No drawer (no primary menu assigned): the picker comes from the PHP-rendered template.
			var tpl = document.getElementById('gdi-picker-template');
			source = tpl && tpl.content ? tpl.content.querySelector('.gdi-theme-picker') : null;
		}
		if (footer && source && !footer.querySelector('.gdi-theme-picker')) {
			var wrap = document.createElement('div');
			wrap.className = 'gdi-theme-picker-wrap';
			var copy = source.cloneNode(true);
			copy.querySelectorAll('input[type="radio"]').forEach(function (input) {
				input.name = 'gdi-theme-footer';
			});
			wrap.appendChild(copy);
			footer.appendChild(wrap);
		}

		var choice = readChoice();
		document.querySelectorAll('.gdi-theme-picker').forEach(function (picker) {
			picker.hidden = false;
			picker.addEventListener('change', function (e) {
				if (e.target && e.target.type === 'radio' && CHOICES.indexOf(e.target.value) !== -1) {
					onPick(e.target.value);
				}
			});
		});
		syncPickers(choice);

		// "System" follows the device while the page is open.
		if (darkQuery) {
			var onSystemChange = function () {
				if (readChoice() === 'system') {
					apply('system');
				}
			};
			if (darkQuery.addEventListener) {
				darkQuery.addEventListener('change', onSystemChange);
			} else if (darkQuery.addListener) {
				darkQuery.addListener(onSystemChange);
			}
		}

		// Keep other open tabs in step.
		window.addEventListener('storage', function (e) {
			if (e.key === STORAGE_KEY) {
				currentChoice = null; // another tab changed it: storage is the source of truth now
				var next = readChoice();
				currentChoice = next;
				apply(next);
				syncPickers(next);
			}
		});
	}

	/* ---------------- Mobile drawer ---------------- */

	function initDrawer() {
		var openBtn = document.getElementById('gdi-mm-open');
		var panel = document.getElementById('gdi-mm-panel');
		var overlay = document.getElementById('gdi-mm-overlay');
		var closeBtn = document.getElementById('gdi-mm-close');
		if (!openBtn || !panel || !overlay || !closeBtn) {
			return;
		}

		// The button sits inside the header so it scrolls away with it (the header is not sticky
		// below 981px). If the header wrapper is missing it stays viewport-fixed rather than vanishing.
		var header = document.querySelector('.et-l--header');
		if (header) {
			header.appendChild(openBtn);
		}
		openBtn.hidden = false;

		var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';
		var menu = document.getElementById('gdi-mm-menu');

		if (menu) {
			var submenuIndex = 0;
			menu.querySelectorAll('li.menu-item-has-children').forEach(function (item) {
				var parentLink = null;
				var submenu = null;
				Array.prototype.forEach.call(item.children, function (child) {
					if (!parentLink && child.tagName === 'A') {
						parentLink = child;
					} else if (!submenu && child.tagName === 'UL' && child.classList.contains('sub-menu')) {
						submenu = child;
					}
				});
				if (!parentLink || !submenu) {
					return;
				}

				submenuIndex += 1;
				var submenuId = 'gdi-mm-submenu-' + submenuIndex;
				while (document.getElementById(submenuId)) {
					submenuIndex += 1;
					submenuId = 'gdi-mm-submenu-' + submenuIndex;
				}
				submenu.id = submenuId;

				var expanded = [
					'current-menu-item',
					'current-menu-parent',
					'current-menu-ancestor',
					'current-page-ancestor',
					'current_page_ancestor'
				].some(function (className) {
					return item.classList.contains(className);
				});

				var toggle = document.createElement('button');
				toggle.type = 'button';
				toggle.className = 'gdi-mm-submenu-toggle';
				toggle.setAttribute('aria-controls', submenuId);
				toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				toggle.setAttribute('aria-label', parentLink.textContent.trim() + ' submenu');
				submenu.hidden = !expanded;
				item.insertBefore(toggle, submenu);
			});

			menu.addEventListener('click', function (e) {
				var toggle = e.target.closest ? e.target.closest('.gdi-mm-submenu-toggle') : null;
				if (!toggle || !menu.contains(toggle)) {
					return;
				}
				var submenu = document.getElementById(toggle.getAttribute('aria-controls'));
				if (!submenu) {
					return;
				}
				var expanded = toggle.getAttribute('aria-expanded') === 'true';
				toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				submenu.hidden = expanded;
			});
		}

		function isOpen() {
			return document.body.classList.contains('gdi-mm-open');
		}

		function openMenu() {
			document.body.classList.add('gdi-mm-open');
			openBtn.setAttribute('aria-expanded', 'true');
			openBtn.setAttribute('aria-label', 'Close menu');
			panel.inert = false;
			panel.removeAttribute('inert');
			var first = panel.querySelector(FOCUSABLE);
			if (first) {
				first.focus();
			}
		}

		function closeMenu(returnFocus) {
			document.body.classList.remove('gdi-mm-open');
			openBtn.setAttribute('aria-expanded', 'false');
			openBtn.setAttribute('aria-label', 'Open menu');
			panel.inert = true;
			panel.setAttribute('inert', '');
			if (returnFocus) {
				openBtn.focus();
			}
		}

		openBtn.addEventListener('click', function () {
			if (isOpen()) {
				closeMenu(true);
			} else {
				openMenu();
			}
		});
		closeBtn.addEventListener('click', function () { closeMenu(true); });
		overlay.addEventListener('click', function () { closeMenu(true); });

		panel.addEventListener('click', function (e) {
			if (e.target.closest && e.target.closest('.gdi-mm-submenu-toggle')) {
				return;
			}
			var link = e.target.closest ? e.target.closest('a') : null;
			if (link) {
				closeMenu(false);
			}
		});

		document.addEventListener('keydown', function (e) {
			if (!isOpen()) {
				return;
			}
			if (e.key === 'Escape') {
				closeMenu(true);
				return;
			}
			if (e.key === 'Tab') {
				// Focus trap: keep Tab inside the open panel (and its close button).
				var items = Array.prototype.slice.call(panel.querySelectorAll(FOCUSABLE)).filter(function (el) {
					return !el.closest('[hidden]') && (el.offsetParent !== null || el === document.activeElement);
				});
				if (!items.length) {
					return;
				}
				var first = items[0];
				var last = items[items.length - 1];
				if (e.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && (document.activeElement === last || !panel.contains(document.activeElement))) {
					e.preventDefault();
					first.focus();
				}
			}
		});

		// Leaving the tablet/phone range closes the drawer (the desktop menu takes over).
		var wide = window.matchMedia ? window.matchMedia('(min-width: 981px)') : null;
		if (wide) {
			var onWide = function () {
				if (wide.matches && isOpen()) {
					closeMenu(false);
				}
			};
			if (wide.addEventListener) {
				wide.addEventListener('change', onWide);
			} else if (wide.addListener) {
				wide.addListener(onWide);
			}
		}
	}

	function init() {
		initDrawer();
		initPickers();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
