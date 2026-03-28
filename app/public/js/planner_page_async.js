(function () {
	'use strict';

	if (window.__hfPlannerPageAsyncBound === true) {
		return;
	}
	window.__hfPlannerPageAsyncBound = true;

	var activeClass = 'is-updating';
	var quantityTimers = new WeakMap();

	function postForm(form) {
		return fetch(form.action, {
			method: 'POST',
			body: new FormData(form),
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		}).then(function (response) {
			var contentType = response.headers.get('Content-Type') || '';
			if (contentType.toLowerCase().indexOf('application/json') === -1) {
				throw new Error('Unexpected response format');
			}
			return response.json().then(function (payload) {
				if (!response.ok || !payload || payload.success === false) {
					throw new Error(payload && payload.message ? payload.message : 'Request failed');
				}
				return payload;
			});
		});
	}

	function updateSummary(planner) {
		if (!planner || typeof planner !== 'object') {
			return;
		}

		var totalQuantity = Number(planner.total_quantity);
		if (!Number.isFinite(totalQuantity) || totalQuantity < 0) {
			totalQuantity = 0;
		}

		var totalPrice = String(planner.total_price || '0.00');

		document.querySelectorAll('[data-planner-count]').forEach(function (node) {
			node.textContent = String(totalQuantity);
		});

		document.querySelectorAll('[data-planner-total]').forEach(function (node) {
			node.textContent = totalPrice;
		});
	}

	function getEventCard(eventId) {
		if (!eventId) {
			return null;
		}
		return document.querySelector('[data-planner-event-id="' + String(eventId) + '"]');
	}

	function removeConflictEntries(eventId) {
		if (!eventId) {
			return;
		}

		document.querySelectorAll('[data-conflict-left-event-id], [data-conflict-right-event-id]').forEach(function (node) {
			if (!(node instanceof HTMLElement)) {
				return;
			}

			var left = Number(node.getAttribute('data-conflict-left-event-id') || '0');
			var right = Number(node.getAttribute('data-conflict-right-event-id') || '0');
			if (left === eventId || right === eventId) {
				node.classList.remove('planner-conflict-paired-events');
				node.removeAttribute('data-conflict-left-event-id');
				node.removeAttribute('data-conflict-right-event-id');

				var title = node.querySelector('.planner-conflict-paired-title');
				if (title) {
					title.remove();
				}

				if (node.querySelectorAll('[data-planner-event-id]').length === 0) {
					node.remove();
				}
			}
		});
	}

	function updateEventFromPayload(eventId, payload) {
		if (!eventId || !payload || !payload.item) {
			return;
		}

		var card = getEventCard(eventId);
		if (!card) {
			return;
		}

		var qtyInput = card.querySelector('[data-planner-qty-input]');
		if (qtyInput && payload.item.quantity) {
			qtyInput.value = String(payload.item.quantity);
		}

		var lineTotal = card.querySelector('[data-planner-line-total]');
		if (lineTotal && typeof payload.item.line_total !== 'undefined') {
			lineTotal.textContent = String(payload.item.line_total);
		}
	}

	function hidePlannerAndShowEmptyState() {
		var layout = document.querySelector('.planner-layout');
		if (layout) {
			layout.remove();
		}

		var conflictWrap = document.querySelector('.planner-conflicts');
		if (conflictWrap) {
			conflictWrap.remove();
		}

		var page = document.querySelector('.planner-page');
		if (!page) {
			return;
		}

		var empty = document.createElement('div');
		empty.className = 'card shadow-sm border-0';
		empty.innerHTML = '' +
			'<div class="card-body text-center py-5">' +
			'<h2 class="h4 mb-3">Your planner is empty</h2>' +
			'<p class="text-muted mb-4">Add tickets to get started.</p>' +
			'<a class="btn cta-btn" href="/jazz">Browse events</a>' +
			'</div>';
		page.appendChild(empty);
	}

	function showInlineStatus(form, message, isError) {
		if (!(form instanceof HTMLFormElement)) {
			return;
		}

		var existing = form.querySelector('.planner-inline-status');
		if (existing) {
			existing.remove();
		}

		var node = document.createElement('p');
		node.className = 'planner-inline-status ' + (isError ? 'is-error' : 'is-success');
		node.textContent = message;
		form.appendChild(node);

		window.setTimeout(function () {
			node.remove();
		}, 2600);
	}

	function submitPlannerForm(form) {
		if (!(form instanceof HTMLFormElement)) {
			return;
		}

		if (form.dataset.submitting === 'true') {
			return;
		}

		var action = form.getAttribute('data-planner-async');
		if (!action) {
			return;
		}

		form.dataset.submitting = 'true';

		var submitButton = form.querySelector('button[type="submit"]');
		if (submitButton) {
			submitButton.disabled = true;
		}

		var eventId = Number(form.getAttribute('data-planner-event-id') || '0');
		var eventCard = getEventCard(eventId);
		if (eventCard) {
			eventCard.classList.add(activeClass);
		}

		postForm(form)
			.then(function (response) {
				updateSummary(response.planner || null);

				if (action === 'quantity') {
					updateEventFromPayload(eventId, response.payload || null);
					var qtyInput = form.querySelector('[data-planner-qty-input]');
					if (qtyInput) {
						qtyInput.dataset.lastCommittedValue = String(qtyInput.value);
					}
					return;
				}

				if (action === 'remove') {
					if (eventCard) {
						eventCard.remove();
					}
					removeConflictEntries(eventId);
					if (response.planner && response.planner.is_empty === true) {
						hidePlannerAndShowEmptyState();
					}
					return;
				}

				if (action === 'clear') {
					hidePlannerAndShowEmptyState();
				}
			})
			.catch(function (error) {
				showInlineStatus(form, error.message || 'Could not update planner', true);
			})
			.finally(function () {
				form.dataset.submitting = 'false';
				if (eventCard) {
					eventCard.classList.remove(activeClass);
				}
				if (submitButton) {
					submitButton.disabled = false;
				}
			});
	}

	function scheduleQuantitySubmit(input, delayMs) {
		if (!(input instanceof HTMLInputElement)) {
			return;
		}

		var form = input.closest('form[data-planner-async="quantity"]');
		if (!(form instanceof HTMLFormElement)) {
			return;
		}

		if (!input.checkValidity()) {
			return;
		}

		var currentValue = String(input.value);
		var lastValue = String(input.dataset.lastCommittedValue || '');
		if (currentValue === lastValue) {
			return;
		}

		var existingTimer = quantityTimers.get(input);
		if (existingTimer) {
			window.clearTimeout(existingTimer);
		}

		var timer = window.setTimeout(function () {
			submitPlannerForm(form);
		}, delayMs);
		quantityTimers.set(input, timer);
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!(form instanceof HTMLFormElement)) {
			return;
		}

		var action = form.getAttribute('data-planner-async');
		if (!action) {
			return;
		}

		event.preventDefault();
		submitPlannerForm(form);
	}, true);

	document.querySelectorAll('form[data-planner-async="quantity"] [data-planner-qty-input]').forEach(function (input) {
		if (!(input instanceof HTMLInputElement)) {
			return;
		}

		input.dataset.lastCommittedValue = String(input.value);
	});

	document.addEventListener('input', function (event) {
		var input = event.target;
		if (!(input instanceof HTMLInputElement) || !input.matches('[data-planner-qty-input]')) {
			return;
		}

		scheduleQuantitySubmit(input, 520);
	});

	document.addEventListener('change', function (event) {
		var input = event.target;
		if (!(input instanceof HTMLInputElement) || !input.matches('[data-planner-qty-input]')) {
			return;
		}

		scheduleQuantitySubmit(input, 0);
	});

	document.addEventListener('blur', function (event) {
		var input = event.target;
		if (!(input instanceof HTMLInputElement) || !input.matches('[data-planner-qty-input]')) {
			return;
		}

		scheduleQuantitySubmit(input, 0);
	}, true);
}());
