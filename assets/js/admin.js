/*! Kashida Slider – visual editor (vanilla JS) */
(function () {
	'use strict';

	var D = window.KS_ADMIN, T = D.i18n, cfg = D.config;
	var app = document.getElementById('ks-app');
	if (!D || !app) { return; }

	var custom = cfg.mode === 'custom' || (cfg.mode === 'carousel' && cfg.source.type === 'custom');
	var tabKeys = Object.keys(D.tabs);
	var state = { cur: 0, tab: (cfg.mode === 'carousel' && cfg.source.type === 'custom') ? 'content' : tabKeys[0], device: 'desktop', dirty: false };
	var SIZES = { desktop: 1200, tablet: 768, mobile: 375 };
	var el = {}, previewTimer = null, previewAbort = null, dragFrom = null, fxBusy = false, fxGuard = null;

	function h(tag, attrs, kids) {
		var n = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			var v = attrs[k];
			if (v === null || v === undefined || v === false) { return; }
			if (k === 'text') { n.textContent = v; }
			else if (k.indexOf('on') === 0) { n.addEventListener(k.slice(2), v); }
			else { n.setAttribute(k, v === true ? '' : v); }
		});
		[].concat(kids || []).forEach(function (c) {
			if (c === null || c === undefined || c === false) { return; }
			n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
		});
		return n;
	}
	function clone(o) { return JSON.parse(JSON.stringify(o)); }
	function target() { return custom ? cfg.slides[state.cur] : cfg.design; }

	/* ---------- status / dirty ---------- */
	function setStatus(kind, text) {
		el.status.className = 'ks-status is-' + kind;
		el.status.textContent = text;
	}
	function changed(skipPreview) {
		state.dirty = true;
		setStatus('dirty', T.unsaved);
		if (!skipPreview) { schedulePreview(); }
	}
	window.addEventListener('beforeunload', function (e) {
		if (state.dirty) { e.preventDefault(); e.returnValue = T.leave; return T.leave; }
	});

	/* ---------- image picker ---------- */
	function pickImage(cb) {
		if (!window.wp || !wp.media) { return; }
		var frame = wp.media({ title: T.mediaTitle, button: { text: T.mediaButton }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			cb(a.url);
		});
		frame.open();
	}

	function pickImages(cb) {
		if (!window.wp || !wp.media) { window.alert(T.mediaUnavailable || 'The WordPress Media Library is unavailable.'); return; }
		var frame = wp.media({ title: T.mediaTitle, button: { text: T.mediaButton }, library: { type: 'image' }, multiple: true });
		frame.on('select', function () { cb(frame.state().get('selection').toJSON() || []); });
		frame.open();
	}

	/* ---------- generic controls ---------- */
	function control(def, obj, opts) {
		opts = opts || {};
		var key = def.key, id = 'ks-f-' + key, input, wrap;
		var set = function (v) {
			obj[key] = v;
			changed();
			if (def.type === 'select' || opts.rerender) { renderPanel(true); }
			if (key === 'h_d' || key === 'h_t' || key === 'h_m') { fit(); }
		};

		switch (def.type) {
			case 'textarea':
				input = h('textarea', { id: id, rows: 3, 'data-key': key, oninput: function (e) { set(e.target.value); } });
				input.value = obj[key] || '';
				break;
			case 'number':
				input = h('input', { id: id, type: 'number', min: def.min, max: def.max, step: def.step || 1, 'data-key': key, oninput: function (e) { set(e.target.value === '' ? def['default'] : parseInt(e.target.value, 10)); } });
				input.value = obj[key];
				break;
			case 'range':
				var out = h('output', { text: obj[key] });
				input = h('input', { id: id, type: 'range', min: def.min, max: def.max, 'data-key': key, oninput: function (e) { out.textContent = e.target.value; set(parseInt(e.target.value, 10)); } });
				input.value = obj[key];
				wrap = h('div', { class: 'ks-range' }, [input, out]);
				break;
			case 'color':
				var code = h('code', { text: obj[key], dir: 'ltr' });
				input = h('input', { id: id, type: 'color', 'data-key': key, oninput: function (e) { code.textContent = e.target.value; set(e.target.value); } });
				input.value = obj[key];
				wrap = h('div', { class: 'ks-color' }, [input, code]);
				break;
			case 'select':
				input = h('select', { id: id, 'data-key': key, onchange: function (e) { set(e.target.value); } },
					Object.keys(def.choices).map(function (v) { return h('option', { value: v, text: def.choices[v] }); }));
				input.value = String(obj[key]);
				break;
			case 'toggle':
				input = h('input', { id: id, type: 'checkbox', 'data-key': key, onchange: function (e) { set(e.target.checked ? 1 : 0); } });
				input.checked = !!obj[key];
				return h('div', { class: 'ks-field ks-field-toggle' }, [h('label', { for: id }, [input, ' ' + def.label])]);
			case 'image':
				return imageControl(def, obj, opts);
			default:
				input = h('input', { id: id, type: def.type === 'url' ? 'url' : 'text', 'data-key': key, dir: def.type === 'url' ? 'ltr' : null, oninput: function (e) { set(e.target.value); } });
				input.value = obj[key] || '';
		}
		return h('div', { class: 'ks-field' }, [h('label', { for: id, text: def.label }), wrap || input]);
	}

	function imageControl(def, obj) {
		var url = obj[def.key], box = h('div', { class: 'ks-field ks-field-image', 'data-key': def.key });
		var rerender = function () { renderPanel(true); renderStrip(); };
		var thumb = h('div', { class: 'ks-img-thumb' + (url ? '' : ' is-empty') });
		if (url) { thumb.style.backgroundImage = 'url("' + url.replace(/"/g, '%22') + '")'; }
		var actions = h('div', { class: 'ks-img-actions' }, [
			h('button', { type: 'button', class: 'button', text: url ? T.change : T.select, onclick: function () { pickImage(function (u) { obj[def.key] = u; changed(); rerender(); }); } }),
			url ? h('button', { type: 'button', class: 'button-link ks-remove', text: T.remove, onclick: function () { obj[def.key] = ''; changed(); rerender(); } }) : null
		]);
		box.appendChild(h('span', { class: 'ks-label', text: def.label }));
		box.appendChild(h('div', { class: 'ks-img-row' }, [thumb, actions]));
		return box;
	}

	/* ---------- panel ---------- */
	function showField(def, o) {
		if (!def.show) { return true; }
		return [].concat(def.show[1]).indexOf(String(o[def.show[0]])) > -1;
	}

	function renderPanel(keepScroll) {
		var scroll = el.body ? el.body.scrollTop : 0;
		el.tabs.textContent = '';
		tabKeys.forEach(function (k) {
			if (cfg.mode === 'carousel' && k === 'content' && !custom) { return; }
			el.tabs.appendChild(h('button', { type: 'button', role: 'tab', class: 'ks-tab' + (k === state.tab ? ' is-active' : ''), 'aria-selected': k === state.tab ? 'true' : 'false', text: D.tabs[k], onclick: function () { state.tab = k; renderPanel(); } }));
		});
		el.body.textContent = '';
		el.fxBtn = null;
		if (custom) {
			el.body.appendChild(h('p', { class: 'ks-slide-label', text: T.slide + ' ' + (state.cur + 1) + ' / ' + cfg.slides.length }));
		} else if (['background', 'typography', 'buttons', 'layout'].indexOf(state.tab) > -1) {
			el.body.appendChild(h('p', { class: 'description ks-note', text: T.dynNote }));
		}
		if (state.tab === 'source') { renderSource(el.body); }
		var obj = target();
		var add = function (def, o) {
			if (def.tab !== state.tab) { return; }
			/* Hero/split presets are not meaningful inside a multi-card carousel; use Carousel layout instead. */
			if (cfg.mode === 'carousel' && ['layout', 'split_side', 'split_stack'].indexOf(def.key) > -1) { return; }
			if (def.key && def.key.indexOf('carousel_') === 0 && cfg.mode !== 'carousel') { return; }
			if (def.scope === 'custom' && !custom) { return; }
			if (!showField(def, o)) { return; }
			el.body.appendChild(control(def, o));
		};
		D.slideFields.forEach(function (d) { add(d, obj); });
		D.globalFields.forEach(function (d) {
			if (cfg.mode === 'carousel' && ['transition', 'direction', 'duration'].indexOf(d.key) > -1) { return; }
			add(d, cfg.settings);
		});
		if (state.tab === 'animation' && cfg.mode !== 'carousel') { renderFxPreview(el.body); }
		if (keepScroll) { el.body.scrollTop = scroll; }
	}

	/* ---------- animation preview control ---------- */
	function renderFxPreview(box) {
		el.fxMsg = h('p', { class: 'description ks-fx-msg', role: 'status', 'aria-live': 'polite' });
		el.fxBtn = h('button', { type: 'button', class: 'button ks-fx-btn', text: fxBusy ? T.fxPlaying : T.fxPreview, disabled: fxBusy, onclick: previewFx });
		box.appendChild(h('div', { class: 'ks-field ks-fx-box' }, [el.fxBtn, h('p', { class: 'description', text: T.fxHelp }), el.fxMsg]));
	}
	function fxEnd(ok) {
		clearTimeout(fxGuard);
		fxBusy = false;
		if (el.fxBtn) { el.fxBtn.disabled = false; el.fxBtn.textContent = T.fxPreview; }
		if (!ok && el.fxMsg) { el.fxMsg.textContent = T.fxNeedTwo; }
	}
	function previewFx() {
		if (fxBusy) { return; }
		el.fxMsg.textContent = '';
		if ((custom && cfg.slides.length < 2) || !el.frame.contentWindow) { el.fxMsg.textContent = T.fxNeedTwo; return; }
		var s = cfg.settings;
		fxBusy = true;
		el.fxBtn.disabled = true;
		el.fxBtn.textContent = T.fxPlaying;
		fxGuard = setTimeout(function () { fxEnd(true); }, (s.duration || 0) * 2 + 3000);
		el.frame.contentWindow.postMessage({ ks: 'fx', fx: s.transition, direction: s.direction, dur: s.duration }, '*');
	}

	/* ---------- dynamic source ---------- */
	function sel(label, key, choices, S, extra) {
		var id = 'ks-s-' + key;
		var s = h('select', { id: id, onchange: function (e) { S[key] = e.target.value; changed(); if (extra) { extra(); } } },
			choices.map(function (c) { return h('option', { value: c[0], text: c[1] }); }));
		s.value = S[key];
		return h('div', { class: 'ks-field' }, [h('label', { for: id, text: label }), s]);
	}
	function num(label, key, S, min, max, help) {
		var id = 'ks-s-' + key;
		var i = h('input', { id: id, type: 'number', min: min, max: max, oninput: function (e) { S[key] = Math.max(min, Math.min(max, parseInt(e.target.value, 10) || min)); changed(); } });
		i.value = S[key];
		return h('div', { class: 'ks-field' }, [h('label', { for: id, text: label }), i, help ? h('p', { class: 'description', text: help }) : null]);
	}
	function syncSourceUI() {
		if (el.addSlideButton) { el.addSlideButton.hidden = !custom; }
		if (el.strip) { el.strip.hidden = !custom; }
		if (el.dynHint) { el.dynHint.hidden = custom; }
		if (custom) { renderStrip(); }
		fit();
	}

	function renderSource(box) {
		var S = cfg.source, src = D.sources, type = S.type;
		var again = function () { renderPanel(true); };
		var types = (cfg.mode === 'carousel' ? [['custom', T.customItems || 'Custom images and items']] : []).concat([['post', T.post], ['pages', T.pages || 'Selected pages'], ['product', T.product], ['portfolio', T.portfolio]]);
		box.appendChild(sel(T.sourceType, 'type', types, S, function () {
			var previousType = type;
			S.terms = {};
			custom = cfg.mode === 'custom' || (cfg.mode === 'carousel' && S.type === 'custom');
			/* Custom carousel images belong only to the custom source. Clear them when switching source. */
			if (cfg.mode === 'carousel' && previousType === 'custom' && S.type !== 'custom') {
				cfg.slides.forEach(function (slide) { slide.bg_image = ''; slide.bg_image_m = ''; });
				state.cur = 0;
			}
			S.post_type = S.type === 'portfolio' ? (src.portfolio.suggested || '') : '';
			if (S.type !== 'pages') { S.page_ids = []; }
			if (cfg.mode === 'carousel') { state.tab = custom ? 'content' : 'source'; syncSourceUI(); }
			if (S.type !== 'product' && S.orderby === 'price') { S.orderby = 'date'; }
			again();
		}));
		if (type === 'custom' && cfg.mode === 'carousel') {
			box.appendChild(h('p', { class: 'description', text: T.customItemsHelp || 'Add and manage carousel items in the Content tab.' }));
			box.appendChild(h('button', { type: 'button', class: 'button button-primary ks-add-media-items', text: T.addImages || 'Add multiple images', onclick: function () {
				pickImages(function (attachments) {
					if (!attachments.length) { return; }
					var added = 0;
					attachments.forEach(function (a) {
						if (cfg.slides.length >= D.maxSlides) { return; }
						var slide = clone(D.defaults.slides[0]);
						slide.bg_image = a.url || (a.sizes && a.sizes.full ? a.sizes.full.url : '');
						if (a.title && (!slide.heading || slide.heading === D.defaults.slides[0].heading)) { slide.heading = a.title; }
						cfg.slides.push(slide); added++;
					});
					if (added) { custom = true; state.cur = cfg.slides.length - 1; state.tab = 'content'; changed(); renderStrip(); renderPanel(); schedulePreview(0); }
					if (added < attachments.length) { window.alert(T.maxSlides || 'The maximum number of items has been reached.'); }
				});
			} }));
			if (cfg.slides.length) { box.appendChild(h('p', { class: 'description ks-custom-count', text: (T.customItemCount || 'Custom items') + ': ' + cfg.slides.length })); }
			return;
		}

		var taxes = {};
		if (type === 'pages') {
			var pageItems = (src.pages && src.pages.items) ? src.pages.items : [];
			var selected = Array.isArray(S.page_ids) ? S.page_ids.map(Number) : [];
			var search = h('input', { type: 'search', class: 'regular-text', placeholder: T.pageSearch || 'Search pages by title', 'aria-label': T.pageSearch || 'Search pages by title' });
			var list = h('div', { class: 'ks-page-picker' });
			function drawPages() {
				list.textContent = '';
				var q = search.value.toLowerCase();
				var matches = pageItems.filter(function (page) { return page.title.toLowerCase().indexOf(q) !== -1; });
				if (!matches.length) { list.appendChild(h('p', { class: 'description', text: T.pageNone || 'No published pages match your search.' })); }
				matches.forEach(function (page) {
					var id = Number(page.id), cb = h('input', { type: 'checkbox', checked: selected.indexOf(id) !== -1, onchange: function (e) { if (e.target.checked && selected.indexOf(id) === -1) { selected.push(id); } else if (!e.target.checked) { selected = selected.filter(function (x) { return x !== id; }); } S.page_ids = selected.slice(); changed(); drawPages(); } });
					list.appendChild(h('label', { class: 'ks-page-option' + (page.depth ? ' ks-page-option-child' : '') }, [cb, h('span', { class: 'ks-page-tree-title', text: (page.depth ? ('— '.repeat(Math.min(Number(page.depth), 8))) : '') + page.title })]));
				});
			}
			search.addEventListener('input', drawPages);
			box.appendChild(h('p', { class: 'description', text: T.pageHelp || 'Select pages and use the arrows to set their display order.' }));
			box.appendChild(h('div', { class: 'ks-field' }, [h('label', { text: T.pageSearch || 'Search pages by title' }), search]));
			box.appendChild(list);
			var orderBox = h('div', { class: 'ks-selected-pages' });
			function drawOrder() {
				orderBox.textContent = '';
				orderBox.appendChild(h('strong', { text: (T.pageSelected || 'Selected pages') + ': ' + selected.length }));
				selected.forEach(function (id, index) {
					var page = pageItems.find(function (p) { return Number(p.id) === id; });
					if (!page) { return; }
					var up = h('button', { type: 'button', class: 'button', text: '↑', title: T.moveUp || 'Move up', disabled: index === 0, onclick: function () { var a = selected[index - 1]; selected[index - 1] = selected[index]; selected[index] = a; S.page_ids = selected.slice(); changed(); drawPages(); drawOrder(); } });
					var down = h('button', { type: 'button', class: 'button', text: '↓', title: T.moveDown || 'Move down', disabled: index === selected.length - 1, onclick: function () { var a = selected[index + 1]; selected[index + 1] = selected[index]; selected[index] = a; S.page_ids = selected.slice(); changed(); drawPages(); drawOrder(); } });
					orderBox.appendChild(h('div', { class: 'ks-selected-page' }, [h('span', { text: (index + 1) + '. ' + page.title }), up, down]));
				});
			}
			drawPages(); drawOrder(); box.appendChild(orderBox);
		}
		if (type === 'product' && !src.product.available) {
			box.appendChild(h('div', { class: 'notice notice-warning inline' }, [h('p', { text: T.wooMissing })]));
			return;
		}
		if (type === 'portfolio') {
			if (!src.portfolio.available) {
				box.appendChild(h('div', { class: 'notice notice-warning inline' }, [h('p', { text: T.portfolioMissing })]));
				return;
			}
			if (!src.portfolio.types[S.post_type]) { S.post_type = src.portfolio.suggested; }
			box.appendChild(sel(T.postType, 'post_type', Object.keys(src.portfolio.types).map(function (k) { return [k, src.portfolio.types[k].label + ' (' + k + ')']; }), S, function () { S.terms = {}; again(); }));
			taxes = src.portfolio.types[S.post_type].taxes;
		} else {
			taxes = src[type].taxes;
		}

		box.appendChild(num(T.count, 'count', S, 1, 20));

		var tk = Object.keys(taxes);
		box.appendChild(h('span', { class: 'ks-label', text: T.filters }));
		if (!tk.length) { box.appendChild(h('p', { class: 'description', text: T.noTerms })); }
		tk.forEach(function (tax) {
			var list = h('div', { class: 'ks-terms' });
			taxes[tax].terms.forEach(function (t) {
				var cb = h('input', { type: 'checkbox', onchange: function (e) {
					var arr = S.terms[tax] || [];
					arr = e.target.checked ? arr.concat([t[0]]) : arr.filter(function (x) { return x !== t[0]; });
					if (arr.length) { S.terms[tax] = arr; } else { delete S.terms[tax]; }
					changed();
				} });
				cb.checked = (S.terms[tax] || []).indexOf(t[0]) > -1;
				list.appendChild(h('label', {}, [cb, ' ' + t[1]]));
			});
			box.appendChild(h('fieldset', { class: 'ks-fieldset' }, [h('legend', { text: taxes[tax].label }), list]));
		});

		var ob = [['date', T.date], ['title', T.title], ['menu_order', T.menuOrder]];
		if (type === 'product') { ob.push(['price', T.price]); }
		box.appendChild(sel(T.orderby, 'orderby', ob, S));
		box.appendChild(sel(T.order, 'order', [['DESC', T.desc], ['ASC', T.asc]], S));

		box.appendChild(h('span', { class: 'ks-label', text: T.fields }));
		var fl = [['show_image', T.fImage], ['show_title', T.fTitle], ['show_excerpt', T.fExcerpt]];
		if (type === 'post') { fl.push(['show_date', T.fDate]); }
		if (type === 'product') { fl.push(['show_price', T.fPrice]); }
		fl.push(['show_button', T.fButton]);
		fl.forEach(function (f) {
			var cb = h('input', { type: 'checkbox', onchange: function (e) { S[f[0]] = e.target.checked ? 1 : 0; changed(); } });
			cb.checked = !!S[f[0]];
			box.appendChild(h('div', { class: 'ks-field ks-field-toggle' }, [h('label', {}, [cb, ' ' + f[1]])]));
		});

		var bl = h('input', { id: 'ks-s-bl', type: 'text', oninput: function (e) { S.button_label = e.target.value; changed(); } });
		bl.value = S.button_label;
		box.appendChild(h('div', { class: 'ks-field' }, [h('label', { for: 'ks-s-bl', text: T.btnLabel }), bl]));
		box.appendChild(num(T.words, 'excerpt_words', S, 5, 80));
		box.appendChild(imageControl({ key: 'fallback_image', label: T.fallback }, S));
		box.appendChild(num(T.cache, 'cache_min', S, 0, 1440, T.cacheHelp));
	}

	/* ---------- slide strip ---------- */
	function moveSlide(from, to) {
		if (to < 0 || to >= cfg.slides.length || from === to) { return; }
		var s = cfg.slides.splice(from, 1)[0];
		cfg.slides.splice(to, 0, s);
		state.cur = to;
		changed(); renderStrip(); renderPanel();
	}
	function renderStrip() {
		if (!custom) { return; }
		el.strip.textContent = '';
		cfg.slides.forEach(function (s, i) {
			var th = h('div', { class: 'ks-thumb-img' }, [h('span', { class: 'ks-thumb-num', text: String(i + 1) })]);
			th.style.backgroundColor = s.bg_color;
			if (s.bg_image) { th.style.backgroundImage = 'url("' + s.bg_image.replace(/"/g, '%22') + '")'; }
			var btn = function (label, icon, fn, dis, flip) { return h('button', { type: 'button', class: 'ks-ico-btn' + (flip ? ' ks-flip' : ''), 'aria-label': label, title: label, disabled: dis, text: icon, onclick: function (e) { e.stopPropagation(); fn(); } }); };
			var item = h('div', { class: 'ks-thumb' + (i === state.cur ? ' is-active' : ''), draggable: 'true' }, [
				h('button', { type: 'button', class: 'ks-thumb-sel', 'aria-label': T.slide + ' ' + (i + 1), 'aria-pressed': i === state.cur ? 'true' : 'false', onclick: function () { state.cur = i; renderStrip(); renderPanel(); schedulePreview(0); } }, [th]),
				h('div', { class: 'ks-thumb-actions' }, [
					btn(T.moveBack, '‹', function () { moveSlide(i, i - 1); }, i === 0, true),
					btn(T.duplicate, '⧉', function () { addSlide(clone(s), i + 1); }),
					btn(T.delete, '✕', function () { removeSlide(i); }),
					btn(T.moveFwd, '›', function () { moveSlide(i, i + 1); }, i === cfg.slides.length - 1, true)
				])
			]);
			item.addEventListener('dragstart', function (e) { dragFrom = i; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(i)); item.classList.add('is-drag'); });
			item.addEventListener('dragend', function () { dragFrom = null; item.classList.remove('is-drag'); });
			item.addEventListener('dragover', function (e) { if (dragFrom !== null) { e.preventDefault(); item.classList.add('is-over'); } });
			item.addEventListener('dragleave', function () { item.classList.remove('is-over'); });
			item.addEventListener('drop', function (e) { e.preventDefault(); item.classList.remove('is-over'); if (dragFrom !== null) { moveSlide(dragFrom, i); } dragFrom = null; });
			el.strip.appendChild(item);
		});
	}
	function addSlide(data, at) {
		if (cfg.slides.length >= D.maxSlides) { window.alert(T.maxSlides); return; }
		var s = data || clone(D.defaults.slides[0]);
		at = at === undefined ? cfg.slides.length : at;
		cfg.slides.splice(at, 0, s);
		state.cur = at;
		changed(); renderStrip(); renderPanel();
	}
	function removeSlide(i) {
		if (cfg.slides.length < 2) { window.alert(T.lastSlide); return; }
		if (!window.confirm(T.confirmDelete)) { return; }
		cfg.slides.splice(i, 1);
		state.cur = Math.min(state.cur, cfg.slides.length - 1);
		changed(); renderStrip(); renderPanel();
	}

	/* ---------- preview ---------- */
	function fit() {
		var w = SIZES[state.device];
		var configuredHeight = Number(cfg.settings[{ desktop: 'h_d', tablet: 'h_t', mobile: 'h_m' }[state.device]]) || 560;
		var contentHeight = configuredHeight;
		try {
			var doc = el.frame.contentDocument;
			if (doc && doc.documentElement && doc.body) {
				var rootPreview = doc.querySelector('.ks-carousel, .ks-slider');
				var measured = rootPreview ? Math.ceil(rootPreview.getBoundingClientRect().height) : Math.max(doc.body.scrollHeight || 0, doc.documentElement.scrollHeight || 0);
				if (measured > 0) { contentHeight = Math.min(configuredHeight, measured); }
			}
		} catch (ignore) {}
		var avail = Math.max(240, el.stage.clientWidth - 32);
		var sc = Math.min(1, avail / w);
		el.frame.style.width = w + 'px';
		el.frame.style.height = contentHeight + 'px';
		el.frame.style.transform = 'scale(' + sc + ')';
		el.frameWrap.style.width = Math.round(w * sc) + 'px';
		el.frameWrap.style.height = Math.round(contentHeight * sc) + 'px';
	}
	function schedulePreview(delay) {
		clearTimeout(previewTimer);
		previewTimer = setTimeout(doPreview, delay === undefined ? 350 : delay);
	}
	function doPreview() {
		if (previewAbort) { previewAbort.abort(); }
		previewAbort = new AbortController();
		if (fxBusy) { fxEnd(true); }
		var fd = new FormData();
		fd.append('action', 'ks_preview');
		fd.append('nonce', D.nonce);
		fd.append('id', D.id);
		fd.append('start', state.cur);
		fd.append('config', JSON.stringify(cfg));
		el.stage.classList.add('is-loading');
		fetch(D.ajax, { method: 'POST', body: fd, credentials: 'same-origin', signal: previewAbort.signal })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				el.stage.classList.remove('is-loading');
				if (j && j.success) { el.frame.onload = function () { fit(); setTimeout(fit, 100); }; el.frame.srcdoc = j.data.html; el.err.hidden = true; }
				else { el.err.hidden = false; el.err.textContent = (j && j.data && j.data.message) || T.previewFailed; }
			})
			.catch(function (e) {
				if (e && e.name === 'AbortError') { return; }
				el.stage.classList.remove('is-loading');
				el.err.hidden = false; el.err.textContent = T.previewFailed;
			});
	}
	window.addEventListener('message', function (e) {
		if (!e.data || e.source !== el.frame.contentWindow) { return; }
		if (e.data.ks === 'fx-done') { fxEnd(e.data.ok !== false); return; }
		if (e.data.ks !== 'select') { return; }
		var map = custom
			? { heading: ['content', 'heading'], desc: ['content', 'desc'], btn1: ['content', 'btn1_label'], btn2: ['content', 'btn2_label'] }
			: { heading: ['typography', 'h_color'], desc: ['typography', 'd_color'], btn1: ['buttons', 'b1_bg'], btn2: ['buttons', 'b2_bg'] };
		var m = map[e.data.el];
		if (!m) { return; }
		state.tab = m[0]; renderPanel();
		var f = el.body.querySelector('[data-key="' + m[1] + '"]');
		if (f) { f.focus(); }
	});

	/* ---------- save ---------- */
	function save() {
		if (state.saving) { return; }
		state.saving = true;
		setStatus('saving', T.saving);
		var fd = new FormData();
		fd.append('action', 'ks_save');
		fd.append('nonce', D.nonce);
		fd.append('id', D.id);
		fd.append('config', JSON.stringify(cfg));
		fetch(D.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				state.saving = false;
				if (j && j.success) { state.dirty = false; setStatus('saved', T.saved); }
				else { setStatus('error', (j && j.data && j.data.message) || T.saveFailed); }
			})
			.catch(function () { state.saving = false; setStatus('error', T.saveFailed); });
	}
	document.addEventListener('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); }
	});

	/* ---------- build ---------- */
	function copyShortcode(btn) {
		var text = '[kashida_slider id="' + D.id + '"]', orig = btn.textContent;
		var done = function () { btn.textContent = T.copied; setTimeout(function () { btn.textContent = orig; }, 1500); };
		if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done); return; }
		var ta = h('textarea'); ta.value = text; document.body.appendChild(ta); ta.select();
		try { document.execCommand('copy'); done(); } catch (err) { /* ignore */ }
		document.body.removeChild(ta);
	}
	function build() {
		app.textContent = '';
		var nameInput = h('input', { type: 'text', class: 'ks-name', maxlength: 100, 'aria-label': T.name, oninput: function (e) { cfg.name = e.target.value; changed(true); } });
		nameInput.value = cfg.name;
		el.status = h('span', { class: 'ks-status is-saved', role: 'status', 'aria-live': 'polite', text: T.saved });
		var devBtns = ['desktop', 'tablet', 'mobile'].map(function (d) {
			return h('button', { type: 'button', class: 'ks-dev' + (d === state.device ? ' is-active' : ''), 'data-dev': d, 'aria-pressed': d === state.device ? 'true' : 'false', text: T[d], onclick: function () {
				state.device = d;
				devBtns.forEach(function (b) { var on = b.getAttribute('data-dev') === d; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
				fit(); schedulePreview(0);
			} });
		});
		var scBtn = h('button', { type: 'button', class: 'button ks-sc', dir: 'ltr', title: T.shortcode, text: '[kashida_slider id="' + D.id + '"]', onclick: function (e) { copyShortcode(e.currentTarget); } });
		el.addSlideButton = h('button', { type: 'button', class: 'button', text: '+ ' + T.addSlide, hidden: !custom, onclick: function () { addSlide(); } });
		var toolbar = h('div', { class: 'ks-toolbar' }, [
			h('a', { class: 'ks-back', href: D.listUrl }, [h('span', { class: 'ks-flip', 'aria-hidden': 'true', text: '←' }), ' ' + T.back]),
			nameInput,
			h('div', { class: 'ks-devices', role: 'group' }, devBtns),
			el.addSlideButton,
			h('span', { class: 'ks-spacer' }),
			el.status, scBtn,
			h('a', { class: 'button', href: D.previewUrl, target: '_blank', rel: 'noopener', text: T.preview }),
			h('button', { type: 'button', class: 'button button-primary ks-btn-primary', text: T.save, onclick: save })
		]);

		el.frame = h('iframe', { class: 'ks-frame', title: T.preview, sandbox: 'allow-scripts allow-same-origin' });
		el.frameWrap = h('div', { class: 'ks-frame-wrap' }, [el.frame]);
		el.err = h('p', { class: 'ks-preview-err', hidden: true });
		el.stage = h('div', { class: 'ks-stage' }, [el.frameWrap, el.err]);
		el.strip = h('div', { class: 'ks-strip', role: 'list' });
		el.dynHint = h('p', { class: 'description ks-hint', text: T.dynNote, hidden: custom });
		el.strip.hidden = !custom;
		var main = h('div', { class: 'ks-main' }, [el.stage, el.strip, el.dynHint, h('p', { class: 'description ks-hint', text: T.clickHint })]);
		el.main = main;

		el.tabs = h('div', { class: 'ks-tabs', role: 'tablist' });
		el.body = h('div', { class: 'ks-panel-body' });
		var panel = h('div', { class: 'ks-panel' }, [el.tabs, el.body]);

		app.appendChild(toolbar);
		app.appendChild(h('div', { class: 'ks-workspace' }, [main, panel]));
		renderStrip(); renderPanel(); fit(); doPreview();
		window.addEventListener('resize', fit);
	}
	build();
})();
