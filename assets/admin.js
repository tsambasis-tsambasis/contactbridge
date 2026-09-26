/** Administrator-only editor. Previews never save options or send messages. */
(function () {
	'use strict';
	var page = document.querySelector('.tscb-admin');
	var form = document.getElementById('tscb-settings-form');
	var config = window.tscbAdmin;
	if (!page || !form || !config) { return; }
	var frame = document.getElementById('tscb-preview-frame');
	var canvas = page.querySelector('.tscb-preview-canvas');
	var viewport = page.querySelector('.tscb-preview-viewport');
	var previewState = document.getElementById('tscb-preview-state');
	var previewStatus = document.getElementById('tscb-preview-status');
	var retry = document.getElementById('tscb-preview-retry');
	var saveStatus = document.getElementById('tscb-save-status');
	var presetStatus = document.getElementById('tscb-preset-status');
	var allowedKeys = Array.isArray(config.keys) ? config.keys : [];
	var presets = config.presets || {};
	var appearanceKeys = ['color_surface', 'color_field', 'color_text', 'color_muted', 'color_border'];
	var device = 'desktop';
	var dirty = false;
	var controller = null;
	var timer = null;
	var sequence = 0;
	var fieldsReady = false;
	var fieldsInput = null;
	var fieldStates = [];
	var removedFields = [];
	var fieldStrings = config.fieldStrings || {};
	var fieldTypes = config.fieldTypes || {};
	var coreFieldIds = ['name', 'email', 'subject', 'message'];
	var fieldList = document.getElementById('tscb-fields-list');
	var fieldStatus = document.getElementById('tscb-field-status');
	var fieldTemplate = document.getElementById('tscb-field-template');
	Object.keys(presets).forEach(function (slug) {
		Object.keys(presets[slug].settings).forEach(function (key) {
			if (appearanceKeys.indexOf(key) === -1) { appearanceKeys.push(key); }
		});
	});
	function control(key) { return form.elements.namedItem('tscb_settings[' + key + ']'); }
	function markDirty() {
		dirty = true;
		saveStatus.textContent = config.unsaved;
		saveStatus.classList.add('is-unsaved');
	}
	function updateMode() {
		var mode = control('editor_mode').value === 'advanced' ? 'advanced' : 'simple';
		page.dataset.editorMode = mode;
		document.getElementById('tscb-advanced-options').hidden = mode !== 'advanced';
	}
	function updatePreset() {
		var value = control('preset').value;
		page.querySelectorAll('[data-tscb-preset]').forEach(function (button) {
			button.setAttribute('aria-pressed', String(button.dataset.tscbPreset === value));
		});
		presetStatus.textContent = presets[value] ? presets[value].label : config.custom;
	}
	function updateCustomColors() {
		page.querySelector('.tscb-custom-colors').classList.toggle('is-inactive', !control('custom_colors').checked);
	}
	function fitPreview() {
		var width = device === 'mobile' ? 375 : 900;
		var available = Math.max(0, canvas.clientWidth - 24);
		var scale = Math.min(1, available / width);
		frame.style.width = width + 'px';
		frame.style.height = '850px';
		frame.style.transform = 'scale(' + scale + ')';
		viewport.style.width = Math.round(width * scale) + 'px';
		viewport.style.height = Math.round(850 * scale) + 'px';
	}
	/** Build a new body from an appearance allowlist, never from the whole form. */
	function previewBody() {
		var body = new URLSearchParams();
		body.set('action', 'tscb_preview');
		body.set('nonce', config.nonce);
		body.set('state', previewState.value);
		body.set('tscb_settings[design_present]', '1');
		allowedKeys.forEach(function (key) {
			var field = control(key);
			if (!field) { return; }
			var value = field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value;
			if (typeof value === 'string') { body.set('tscb_settings[' + key + ']', value); }
		});
		form.querySelectorAll('input[name="tscb_settings[channels][]"]:checked').forEach(function (field) {
			if (['email', 'telegram', 'whatsapp'].indexOf(field.value) !== -1) {
				body.append('tscb_settings[channels][]', field.value);
			}
		});
		['privacy_whatsapp', 'privacy_telegram'].forEach(function (key) {
			var privacy = control(key);
			if (privacy && privacy.type === 'checkbox') {
				body.set('tscb_settings[' + key + ']', privacy.checked ? '1' : '0');
			}
		});
		if (fieldsReady) {
			syncFields();
			body.set('tscb_settings[fields_present]', '1');
			body.set('tscb_settings[fields_json]', fieldsInput.value);
		}
		return body;
	}
	function renderPreview(requestSequence) {
		controller = new AbortController();
		fetch(config.url, {
			method: 'POST', credentials: 'same-origin', cache: 'no-store',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: previewBody().toString(), signal: controller.signal
		}).then(function (response) {
			if (!response.ok) { throw new Error('preview-http'); }
			return response.json();
		}).then(function (result) {
			if (requestSequence !== sequence) { return; }
			if (!result.success || !result.data || typeof result.data.html !== 'string') {
				throw new Error('preview-response');
			}
			frame.srcdoc = result.data.html;
			previewStatus.textContent = dirty ? config.updated : config.ready;
			previewStatus.classList.remove('is-error');
			page.querySelector('.tscb-preview-panel').setAttribute('aria-busy', 'false');
		}).catch(function (error) {
			if (error.name === 'AbortError' || requestSequence !== sequence) { return; }
			previewStatus.textContent = config.error;
			previewStatus.classList.add('is-error');
			retry.hidden = false;
			page.querySelector('.tscb-preview-panel').setAttribute('aria-busy', 'false');
		});
	}
	function queuePreview(delay) {
		window.clearTimeout(timer);
		sequence += 1;
		var requestSequence = sequence;
		if (controller) { controller.abort(); }
		previewStatus.textContent = config.updating;
		previewStatus.classList.remove('is-error');
		retry.hidden = true;
		page.querySelector('.tscb-preview-panel').setAttribute('aria-busy', 'true');
		timer = window.setTimeout(function () { renderPreview(requestSequence); }, delay === undefined ? 200 : delay);
	}
	page.querySelectorAll('[data-tscb-preset]').forEach(function (button) {
		button.addEventListener('click', function () {
			var slug = button.dataset.tscbPreset;
			if (!presets[slug]) { return; }
			Object.keys(presets[slug].settings).forEach(function (key) {
				var field = control(key);
				if (!field || allowedKeys.indexOf(key) === -1) { return; }
				if (field.type === 'checkbox') { field.checked = Boolean(presets[slug].settings[key]); }
				else { field.value = presets[slug].settings[key]; }
			});
			control('preset').value = slug;
			updatePreset(); updateCustomColors(); markDirty(); queuePreview();
		});
	});
	/** Field schemas are separate from appearance presets and transport settings. */
	function fieldTitle(state) {
		return state.field.label || (state.automaticLabel ? state.initialLabel : '') || fieldTypes[state.field.type];
	}
	function syncFields() {
		if (!fieldsInput) { return; }
		fieldsInput.value = JSON.stringify(fieldStates.map(function (state) {
			var field = state.field;
			return {
				id: field.id,
				type: field.type,
				label: state.automaticLabel && field.label === state.initialLabel ? '' : field.label,
				placeholder: field.placeholder,
				required: Boolean(field.required),
				width: field.width,
				options: field.type === 'select' ? field.options.slice() : []
			};
		}));
	}
	function invalidFields() {
		if (!fieldsReady) { return null; }
		if (!fieldStates.length) {
			return { message: fieldStrings.empty, control: document.getElementById('tscb-add-field-type') };
		}
		for (var index = 0; index < fieldStates.length; index += 1) {
			var field = fieldStates[index].field;
			if (field.type === 'select' && (field.options.length < 1 || field.options.length > 20)) {
				return { message: fieldStrings.choices, control: fieldList.children[index].querySelector('[data-field-key="options"]') };
			}
		}
		return null;
	}
	function updateFieldMetadata() {
		document.getElementById('tscb-field-count').textContent = fieldStrings.count.replace('%1$d', fieldStates.length).replace('%2$d', '20');
		document.getElementById('tscb-add-field').disabled = fieldStates.length >= 20;
		document.getElementById('tscb-add-field-type').disabled = fieldStates.length >= 20;
		var undo = document.getElementById('tscb-undo-field');
		undo.hidden = removedFields.length === 0;
		undo.disabled = fieldStates.length >= 20;
		Array.from(fieldList.children).forEach(function (row, index) {
			var state = fieldStates[index];
			var title = fieldTitle(state);
			row.querySelector('.tscb-field-number').textContent = String(index + 1);
			row.querySelector('.tscb-field-title').textContent = title;
			row.setAttribute('aria-label', fieldStrings.row.replace('%1$d', index + 1).replace('%2$s', title));
			['up', 'down', 'remove'].forEach(function (action) {
				var button = row.querySelector('[data-field-action="' + action + '"]');
				button.setAttribute('aria-label', fieldStrings[action].replace('%s', title));
				button.title = fieldStrings[action].replace('%s', title);
			});
			row.querySelector('[data-field-action="up"]').disabled = index === 0;
			row.querySelector('[data-field-action="down"]').disabled = index === fieldStates.length - 1;
			row.querySelector('.tscb-builder-options').hidden = state.field.type !== 'select';
			row.querySelector('[data-field-key="options"]').setAttribute('aria-invalid', String(state.field.type === 'select' && (state.field.options.length < 1 || state.field.options.length > 20)));
		});
	}
	function renderFields() {
		var fragment = document.createDocumentFragment();
		fieldStates.forEach(function (state) {
			var row = fieldTemplate.content.firstElementChild.cloneNode(true);
			row.dataset.fieldId = state.field.id;
			row.querySelectorAll('[data-field-key]').forEach(function (input) {
				var key = input.dataset.fieldKey;
				if (input.type === 'checkbox') { input.checked = Boolean(state.field[key]); }
				else { input.value = key === 'options' ? state.field.options.join('\n') : state.field[key]; }
				input.id = coreFieldIds.indexOf(state.field.id) !== -1 && (key === 'label' || key === 'placeholder') ? 'tscb-' + key + '_' + state.field.id : 'tscb-field-' + state.field.id + '-' + key;
			});
			fragment.appendChild(row);
		});
		fieldList.replaceChildren(fragment);
		updateFieldMetadata();
	}
	function changedFields(message) {
		syncFields();
		updateFieldMetadata();
		var invalid = invalidFields();
		fieldStatus.textContent = invalid ? invalid.message : (message || '');
		fieldStatus.classList.toggle('is-error', Boolean(invalid));
		markDirty();
		queuePreview();
	}
	function focusField(id, action) {
		var row = Array.from(fieldList.children).find(function (item) { return item.dataset.fieldId === id; });
		if (!row) { return; }
		var button = action ? row.querySelector('[data-field-action="' + action + '"]') : null;
		(button && !button.disabled ? button : row.querySelector('[data-field-key="label"]')).focus();
	}
	function initFieldBuilder() {
		if (!fieldList || !fieldTemplate || !Array.isArray(config.fields)) { return; }
		var rawById = Object.create(null);
		(config.rawFields || []).forEach(function (field) { rawById[field.id] = field; });
		fieldStates = config.fields.map(function (field) {
			var raw = rawById[field.id];
			return {
				field: { id: field.id, type: field.type, label: field.label, placeholder: field.placeholder, required: Boolean(field.required), width: field.width, options: field.options.slice() },
				initialLabel: field.label,
				automaticLabel: !raw || raw.label === ''
			};
		});
		renderFields();
		// Only successfully initialized JavaScript opts into replacing saved fields.
		fieldsInput = document.createElement('input');
		fieldsInput.type = 'hidden';
		fieldsInput.name = 'tscb_settings[fields_json]';
		fieldsInput.id = 'tscb-fields-json';
		var present = document.createElement('input');
		present.type = 'hidden';
		present.name = 'tscb_settings[fields_present]';
		present.value = '1';
		form.appendChild(fieldsInput);
		form.appendChild(present);
		fieldsReady = true;
		syncFields();

		function editField(event) {
			var input = event.target;
			if (!input.hasAttribute('data-field-key')) { return; }
			var id = input.closest('.tscb-builder-field').dataset.fieldId;
			var state = fieldStates.find(function (item) { return item.field.id === id; });
			var key = input.dataset.fieldKey;
			if (key === 'required') { state.field.required = input.checked; }
			else if (key === 'options') { state.field.options = input.value.split(/\r?\n/).map(function (option) { return option.trim(); }).filter(Boolean); }
			else { state.field[key] = input.value; }
			if (key === 'type') {
				if (state.automaticLabel && (state.field.label === state.initialLabel || state.field.label === '') && coreFieldIds.indexOf(id) === -1) {
					var blankLabel = state.field.label === '';
					state.initialLabel = fieldTypes[state.field.type];
					state.field.label = blankLabel ? '' : state.initialLabel;
					input.closest('.tscb-builder-field').querySelector('[data-field-key="label"]').value = state.field.label;
				}
				if (state.field.type === 'select' && state.field.options.length === 0) {
					state.field.options = [fieldStrings.optionOne, fieldStrings.optionTwo];
					input.closest('.tscb-builder-field').querySelector('[data-field-key="options"]').value = state.field.options.join('\n');
				}
			}
			changedFields();
		}
		fieldList.addEventListener('input', editField);
		fieldList.addEventListener('change', editField);
		fieldList.addEventListener('click', function (event) {
			var button = event.target.closest('[data-field-action]');
			if (!button) { return; }
			var id = button.closest('.tscb-builder-field').dataset.fieldId;
			var index = fieldStates.findIndex(function (state) { return state.field.id === id; });
			var action = button.dataset.fieldAction;
			if (action === 'remove') {
				removedFields.push({ state: fieldStates.splice(index, 1)[0], index: index });
				renderFields(); changedFields(fieldStrings.removed);
				document.getElementById('tscb-undo-field').focus();
				return;
			}
			var nextIndex = index + (action === 'up' ? -1 : 1);
			if (nextIndex < 0 || nextIndex >= fieldStates.length) { return; }
			var moved = fieldStates.splice(index, 1)[0];
			fieldStates.splice(nextIndex, 0, moved);
			renderFields(); changedFields(fieldStrings.moved); focusField(id, action);
		});
		document.getElementById('tscb-add-field').addEventListener('click', function () {
			if (fieldStates.length >= 20) { fieldStatus.textContent = fieldStrings.limit; return; }
			var type = document.getElementById('tscb-add-field-type').value;
			var bytes = new Uint8Array(8);
			window.crypto.getRandomValues(bytes);
			var id = 'f_' + Array.from(bytes).map(function (value) { return value.toString(16).padStart(2, '0'); }).join('');
			var label = fieldTypes[type];
			fieldStates.push({ field: { id: id, type: type, label: label, placeholder: '', required: false, width: type === 'textarea' || type === 'checkbox' ? 'full' : 'half', options: type === 'select' ? [fieldStrings.optionOne, fieldStrings.optionTwo] : [] }, initialLabel: label, automaticLabel: true });
			renderFields(); changedFields(fieldStrings.added); focusField(id);
		});
		document.getElementById('tscb-undo-field').addEventListener('click', function () {
			if (!removedFields.length || fieldStates.length >= 20) { return; }
			var removed = removedFields.pop();
			fieldStates.splice(Math.min(removed.index, fieldStates.length), 0, removed.state);
			renderFields(); changedFields(fieldStrings.restored); focusField(removed.state.field.id);
		});
	}

	function onEdit(event) {
		var target = event.target;
		if (!target.name || !target.name.startsWith('tscb_settings[')) { return; }
		markDirty();
		if (target.name === 'tscb_settings[editor_mode]') { updateMode(); return; }
		if (target.hasAttribute('data-tscb-design')) {
			var key = target.name.slice(14, -1);
			if (appearanceKeys.indexOf(key) !== -1) { control('preset').value = 'custom'; updatePreset(); }
			updateCustomColors(); queuePreview();
		} else if (target.name === 'tscb_settings[channels][]' || target.name === 'tscb_settings[privacy_whatsapp]' || target.name === 'tscb_settings[privacy_telegram]') { queuePreview(); }
	}
	form.addEventListener('input', onEdit);
	form.addEventListener('change', onEdit);
	form.addEventListener('submit', function (event) {
		var invalid = invalidFields();
		if (invalid) {
			event.preventDefault();
			markDirty();
			control('editor_mode').value = 'advanced';
			updateMode();
			document.getElementById('tscb-field-builder-group').open = true;
			fieldStatus.textContent = invalid.message;
			fieldStatus.classList.add('is-error');
			invalid.control.focus();
			invalid.control.scrollIntoView({ block: 'center' });
			return;
		}
		if (fieldsReady) { syncFields(); }
		dirty = false;
	});
	page.querySelectorAll('.tscb-channel-choice input').forEach(function (checkbox) {
		function updateCard() { checkbox.closest('.tscb-channel-choice').classList.toggle('is-enabled', checkbox.checked); }
		checkbox.addEventListener('change', updateCard); updateCard();
	});
	page.querySelectorAll('[data-tscb-device]').forEach(function (button) {
		button.addEventListener('click', function () {
			device = button.dataset.tscbDevice;
			page.querySelectorAll('[data-tscb-device]').forEach(function (item) {
				item.setAttribute('aria-pressed', String(item === button));
			});
			fitPreview();
		});
	});
	previewState.addEventListener('change', function () { queuePreview(0); });
	retry.addEventListener('click', function () { queuePreview(0); });
	document.getElementById('tscb-insert-privacy-link').addEventListener('click', function () {
		var field = control('privacy_label');
		var marker = '{privacy_link}';
		var existing = field.value.indexOf(marker);
		if (existing !== -1) { field.focus(); field.setSelectionRange(existing, existing + marker.length); return; }
		var start = typeof field.selectionStart === 'number' ? field.selectionStart : field.value.length;
		var end = typeof field.selectionEnd === 'number' ? field.selectionEnd : start;
		field.value = field.value.slice(0, start) + marker + field.value.slice(end);
		field.focus(); field.setSelectionRange(start + marker.length, start + marker.length);
		field.dispatchEvent(new Event('input', { bubbles: true }));
	});
	page.querySelectorAll('.tscb-admin-nav a').forEach(function (link) {
		link.addEventListener('click', function () {
			var target = document.querySelector(link.getAttribute('href'));
			if (target && target.tagName === 'DETAILS') { target.open = true; }
		});
	});
	if ('ResizeObserver' in window) { new ResizeObserver(fitPreview).observe(canvas); }
	else { window.addEventListener('resize', fitPreview); }
	initFieldBuilder();
	updateMode(); updatePreset(); updateCustomColors(); fitPreview();
}());

/** Expand optional SMTP settings without disabling or resetting their inputs. */
(function () {
	'use strict';
	var method = document.getElementById('tscb-email_method');
	var details = document.getElementById('tscb-smtp-details');
	if (!method || !details) { return; }
	method.addEventListener('change', function () {
		details.open = method.value === 'smtp';
	});
}());
