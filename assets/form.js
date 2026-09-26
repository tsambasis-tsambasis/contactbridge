/* ContactBridge. Native POST remains available without JavaScript. */
(function () {
	'use strict';

	function newSubmissionId() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}
		var bytes = new Uint8Array(16);
		if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
			window.crypto.getRandomValues(bytes);
		} else {
			for (var i = 0; i < bytes.length; i += 1) {
				bytes[i] = Math.floor(Math.random() * 256);
			}
		}
		bytes[6] = (bytes[6] & 15) | 64;
		bytes[8] = (bytes[8] & 63) | 128;
		return Array.from(bytes, function (byte, index) {
			var prefix = [4, 6, 8, 10].indexOf(index) !== -1 ? '-' : '';
			return prefix + byte.toString(16).padStart(2, '0');
		}).join('');
	}

	function initialize(form) {
		if (form.dataset.tscbReady) {
			return;
		}
		form.dataset.tscbReady = 'true';
		var button = form.querySelector('[type="submit"]');
		var label = form.querySelector('[data-tscb-button-label]');
		var status = form.querySelector('[data-tscb-status]');
		var submissionId = form.elements.namedItem('tscb_id');
		if (!button || !label || !status || !submissionId) {
			return;
		}
		var originalLabel = label.textContent;
		var pending = false;
		var fields = Object.create(null);
		form.querySelectorAll('[data-error-for]').forEach(function (error) {
			var name = error.dataset.errorFor;
			if (/^tscb_(?:name|email|subject|message|consent|f_[a-z0-9]{8,24})$/.test(name)) {
				var input = form.elements.namedItem(name);
				if (input && typeof input.setAttribute === 'function') {
					fields[name] = { input: input, error: error };
				}
			}
		});
		var fieldNames = Object.keys(fields);
		var counters = [];
		form.querySelectorAll('[data-tscb-counter]').forEach(function (counter) {
			var field = fields[counter.dataset.tscbCounter];
			if (field && field.input.tagName === 'TEXTAREA' && field.input.maxLength >= 0) {
				counters.push({ input: field.input, output: counter });
			}
		});
		// Cached HTML must not share one submission identifier between visitors.
		submissionId.value = newSubmissionId();

		function updateCounter() {
			counters.forEach(function (counter) {
				counter.output.textContent = counter.input.value.length + ' / ' + counter.input.maxLength;
				counter.output.hidden = false;
			});
		}

		function clearFieldError(name) {
			var field = fields[name];
			if (field) {
				field.input.removeAttribute('aria-invalid');
				field.error.textContent = '';
				field.error.hidden = true;
			}
		}

		function showStatus(text, state, focus) {
			status.textContent = text;
			status.dataset.state = state;
			status.hidden = false;
			if (focus) {
				status.focus();
			}
		}

		function markFields(responseFields) {
			var firstInvalid = null;
			if (!responseFields || typeof responseFields !== 'object') {
				return firstInvalid;
			}
			fieldNames.forEach(function (name) {
				var field = fields[name];
				if (Object.prototype.hasOwnProperty.call(responseFields, name) && typeof responseFields[name] === 'string' && responseFields[name]) {
					field.input.setAttribute('aria-invalid', 'true');
					field.error.textContent = responseFields[name];
					field.error.hidden = false;
					firstInvalid = firstInvalid || field.input;
				}
			});
			return firstInvalid;
		}

		async function request(data) {
			var controller = new AbortController();
			var timer = window.setTimeout(function () { controller.abort(); }, 45000);
			try {
				var response = await window.fetch(form.dataset.endpoint, {
					method: 'POST',
					body: data,
					credentials: 'same-origin',
					cache: 'no-store',
					headers: { 'Accept': 'application/json' },
					signal: controller.signal
				});
				var json = await response.json();
				if (!json || typeof json.success !== 'boolean') {
					throw new Error('Invalid response');
				}
				return json;
			} finally {
				window.clearTimeout(timer);
			}
		}

		counters.forEach(function (counter) {
			counter.input.addEventListener('input', updateCounter);
		});
		fieldNames.forEach(function (name) {
			fields[name].input.addEventListener('input', function () { clearFieldError(name); });
			fields[name].input.addEventListener('change', function () { clearFieldError(name); });
		});
		updateCounter();
		if (!status.hidden) {
			status.focus();
		}

		if (!window.fetch || !window.FormData || !window.AbortController) {
			return;
		}

		// The browser continues to provide native constraint validation.
		form.addEventListener('submit', async function (event) {
			event.preventDefault();
			if (pending) {
				return;
			}
			if (!form.reportValidity()) {
				return;
			}
			pending = true;
			button.disabled = true;
			label.textContent = form.dataset.pending;
			form.setAttribute('aria-busy', 'true');
			status.hidden = true;
			fieldNames.forEach(clearFieldError);

			try {
				// Full-page caches can contain an expired nonce. Obtain a fresh one first.
				var nonceData = new FormData();
				nonceData.append('action', 'tscb_nonce');
				var nonceResult = await request(nonceData);
				if (!nonceResult.success || !nonceResult.data || typeof nonceResult.data.nonce !== 'string') {
					throw new Error('Nonce refresh failed');
				}
				form.elements.namedItem('tscb_nonce').value = nonceResult.data.nonce;
				var result = await request(new FormData(form));
				var data = result.data && typeof result.data === 'object' ? result.data : {};
				if (result.success) {
					form.reset();
					form.elements.namedItem('tscb_id').value = newSubmissionId();
					updateCounter();
					showStatus(typeof data.message === 'string' ? data.message : form.dataset.success, 'success', true);
				} else {
					var firstInvalid = markFields(data.fields);
					showStatus(typeof data.message === 'string' ? data.message : form.dataset.validationError, 'error', !firstInvalid);
					if (firstInvalid) {
						firstInvalid.focus();
					}
				}
			} catch (error) {
				// Keep data and submission ID for a retry, including an ambiguous timeout.
				showStatus(form.dataset.networkError, 'error', true);
			} finally {
				pending = false;
				button.disabled = false;
				label.textContent = originalLabel;
				form.removeAttribute('aria-busy');
			}
		});
	}

	function init() {
		document.querySelectorAll('[data-tscb-form]').forEach(initialize);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
