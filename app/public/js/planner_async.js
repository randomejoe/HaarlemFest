(function () {
	'use strict';

	if (window.__hfPlannerAsyncBound === true) {
		return;
	}
	window.__hfPlannerAsyncBound = true;

	var supportedForms = ['jazz-add-form', 'ah-add-form', 'ars-add-form'];
	var defaultDelayMs = 650;
	var defaultAddedHoldMs = 3200;
	var defaultAddingLabel = 'Adding...';
	var defaultAddedLabel = 'Added';

	function isPlannerForm(form) {
		if (!(form instanceof HTMLFormElement)) {
			return false;
		}

		for (var i = 0; i < supportedForms.length; i += 1) {
			if (form.classList.contains(supportedForms[i])) {
				return true;
			}
		}

		return false;
	}

	function toPositiveNumber(raw, fallback) {
		var value = Number(raw);
		return Number.isFinite(value) && value > 0 ? value : fallback;
	}

	function setButtonLabel(button, nextLabel) {
		var label = button.querySelector('.ah-btn-label, .ars-button-label');
		if (label) {
			label.textContent = nextLabel;
			return;
		}

		button.textContent = nextLabel;
	}

	function updatePlannerSummary(planner) {
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

		document.querySelectorAll('[data-planner-item-label]').forEach(function (node) {
			node.textContent = totalQuantity === 1 ? 'item' : 'items';
		});

		document.querySelectorAll('[data-planner-total]').forEach(function (node) {
			node.textContent = totalPrice;
		});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!isPlannerForm(form)) {
			return;
		}

		event.preventDefault();

		var button = form.querySelector('button[type="submit"]');
		if (!button || button.disabled || button.dataset.submitting === 'true') {
			return;
		}

		var originalLabel = button.dataset.originalLabel;
		if (!originalLabel) {
			var labelNode = button.querySelector('.ah-btn-label, .ars-button-label');
			originalLabel = labelNode ? labelNode.textContent : button.textContent;
			button.dataset.originalLabel = originalLabel || '';
		}

		var delayMs = toPositiveNumber(button.getAttribute('data-submit-delay-ms'), defaultDelayMs);
		var addedHoldMs = toPositiveNumber(button.getAttribute('data-added-hold-ms'), defaultAddedHoldMs);
		var addingLabel = button.getAttribute('data-adding-label') || defaultAddingLabel;
		var addedLabel = button.getAttribute('data-added-label') || defaultAddedLabel;

		button.dataset.submitting = 'true';
		button.classList.remove('is-added');
		button.classList.add('is-submitting');
		button.disabled = true;
		setButtonLabel(button, addingLabel);

		window.setTimeout(function () {
			fetch(form.action, {
				method: 'POST',
				body: new FormData(form),
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Request failed');
					}

					var contentType = response.headers.get('Content-Type') || '';
					if (contentType.toLowerCase().indexOf('application/json') === -1) {
						return null;
					}

					return response.json();
				})
				.then(function (payload) {
					if (payload && payload.success === false) {
						throw new Error(payload.message || 'Request failed');
					}

					if (payload && payload.planner) {
						updatePlannerSummary(payload.planner);
					}

					button.dataset.submitting = 'false';
					button.classList.remove('is-submitting');
					button.classList.add('is-added');
					button.disabled = false;
					setButtonLabel(button, addedLabel);

					window.setTimeout(function () {
						if (button.dataset.submitting === 'false') {
							button.classList.remove('is-added');
							setButtonLabel(button, originalLabel || 'Add to My Programme');
						}
					}, addedHoldMs);
				})
				.catch(function () {
					button.dataset.submitting = 'false';
					button.classList.remove('is-submitting');
					button.classList.remove('is-added');
					button.disabled = false;
					setButtonLabel(button, originalLabel || 'Add to My Programme');
				});
		}, delayMs);
	}, true);
}());
