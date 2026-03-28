/**
 * jazz_program.js
 *
 * Controls the Jazz Program overlay component.
 *
 * Open triggers  — any element with [data-jazz-open] on the page.
 *                  The nav link uses this so it opens the overlay when the
 *                  component is present, or falls back to navigating to /jazz.
 * Close triggers — [data-jazz-close] inside the overlay, or pressing Escape.
 * Auto-open      — only when the URL hash is #jazz-open (e.g. after a planner
 *                  POST redirect).
 * History        — opening pushes a /jazz state so the browser Back button
 *                  cleanly dismisses the overlay.
 * Day tabs       — all day panels are pre-rendered; JS swaps visibility so no
 *                  round-trip is needed when the user switches days.
 */
(function () {
	'use strict';

	var overlay = document.getElementById('jazz-program-overlay');
	if (!overlay) return; // component not on this page — let nav links navigate normally

	/* ── Open / close ─────────────────────────────────────── */

	function openOverlay(pushState) {
		overlay.removeAttribute('hidden');
		document.body.classList.add('jazz-overlay-open');
		overlay.focus();
		if (pushState) {
			history.pushState({ jazzOpen: true }, '', '/jazz');
		}
	}

	function closeOverlay(useHistory) {
		overlay.setAttribute('hidden', '');
		document.body.classList.remove('jazz-overlay-open');
		if (useHistory && history.state && history.state.jazzOpen) {
			history.back();
		}
	}

	function openFromHashIfNeeded() {
		if (window.location.hash !== '#jazz-open') {
			return;
		}

		// Clear the hash so repeated CTA clicks still trigger a hash change.
		history.replaceState(null, '', window.location.pathname + window.location.search);
		openOverlay(false);
	}

	/* ── Auto-open on load ────────────────────────────────── */

	// Redirected back here after a planner POST (return_to includes #jazz-open).
	openFromHashIfNeeded();

	/* ── Event: open trigger ──────────────────────────────── */

	document.addEventListener('click', function (e) {
		var trigger = e.target.closest('[data-jazz-open]');
		if (trigger) {
			e.preventDefault();
			openOverlay(true);
			return;
		}

		// Support CMS CTA links that are configured as href="#jazz-open".
		var hashTrigger = e.target.closest('a[href="#jazz-open"]');
		if (!hashTrigger) return;

		e.preventDefault();
		openOverlay(true);
	});

	window.addEventListener('hashchange', function () {
		openFromHashIfNeeded();
	});

	/* ── Event: close trigger ─────────────────────────────── */

	overlay.addEventListener('click', function (e) {
		if (e.target.closest('[data-jazz-close]')) {
			closeOverlay(true);
		}
	});

	/* ── Event: Escape key ────────────────────────────────── */

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !overlay.hasAttribute('hidden')) {
			closeOverlay(true);
		}
	});

	/* ── Event: browser back / forward ───────────────────── */

	window.addEventListener('popstate', function (e) {
		// Forward into a /jazz state → re-open
		if (e.state && e.state.jazzOpen) {
			openOverlay(false);
			return;
		}
		// Back away from /jazz state → close without re-pushing history
		if (!overlay.hasAttribute('hidden')) {
			closeOverlay(false);
		}
	});

	/* ── Day tab switching (client-side) ──────────────────── */

	overlay.addEventListener('click', function (e) {
		var tab = e.target.closest('[data-jazz-tab]');
		if (!tab) return;

		var key = tab.dataset.jazzTab;

		// Update tab active states
		overlay.querySelectorAll('[data-jazz-tab]').forEach(function (t) {
			var active = t.dataset.jazzTab === key;
			t.classList.toggle('active', active);
			t.setAttribute('aria-selected', active ? 'true' : 'false');
		});

		// Show the matching day panel, hide the rest
		overlay.querySelectorAll('[data-jazz-panel]').forEach(function (p) {
			if (p.dataset.jazzPanel === key) {
				p.removeAttribute('hidden');
			} else {
				p.setAttribute('hidden', '');
			}
		});
	});

}());
