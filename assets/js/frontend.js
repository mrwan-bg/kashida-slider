/*! Kashida Slider – frontend (vanilla JS, no dependencies) */
(function () {
	'use strict';

	var FX = ['fade', 'crossfade', 'slide', 'zoom_in', 'zoom_out', 'push', 'wipe'];
	var V = { left: [-1, 0], right: [1, 0], up: [0, -1], down: [0, 1] };
	var EASE = 'cubic-bezier(.4,0,.2,1)';

	function Slider(root) {
		var self = this;
		var o = {};
		try { o = JSON.parse(root.getAttribute('data-ks') || '{}'); } catch (e) { o = {}; }
		var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

		self.root = root;
		self.o = o;
		self.rtl = !!o.rtl;
		self.slides = [].slice.call(root.querySelectorAll('.ks-slide'));
		self.n = self.slides.length;
		self.i = Math.max(0, Math.min(self.n - 1, o.start || 0));
		self.reduce = reduce;
		/* Reduced-motion users still need functional navigation; keep transitions very short instead of disabling them. */
		self.dur = reduce ? 150 : Math.max(150, Math.min(3000, parseInt(o.duration, 10) || 600));
		self.fx = FX.indexOf(o.transition) > -1 ? o.transition : 'fade';
		self.dir = V[o.direction] ? o.direction : 'left';
		self.demoTimers = [];
		self.timer = null;
		self.leaveTimer = null;
		/* Reduced motion must not be treated as an explicit user pause; this previously prevented autoplay from starting. */
		self.hold = { hover: false, focus: false, hidden: false, off: false, user: false };
		self.playing = !!o.autoplay && self.n > 1;
		self.ac = new AbortController();
		self.prevBtn = root.querySelector('.ks-prev');
		self.nextBtn = root.querySelector('.ks-next');
		self.dots = [].slice.call(root.querySelectorAll('.ks-dot'));
		self.pauseBtn = root.querySelector('.ks-pause');
		self.track = root.querySelector('.ks-track');

		self.slides.forEach(function (s, k) { self.setState(s, k === self.i); });
		if (single(self)) { self.update(); return; }

		var sig = { signal: self.ac.signal };
		var on = function (t, ev, fn, opt) { t.addEventListener(ev, fn, opt ? { signal: self.ac.signal, passive: true } : sig); };

		if (self.prevBtn) { on(self.prevBtn, 'click', function () { self.prev(); }); }
		if (self.nextBtn) { on(self.nextBtn, 'click', function () { self.next(); }); }
		self.dots.forEach(function (d) {
			on(d, 'click', function () { self.go(parseInt(d.getAttribute('data-i'), 10)); });
		});
		if (self.pauseBtn) {
			on(self.pauseBtn, 'click', function () {
				self.hold.user = !self.hold.user;
				self.schedule();
				self.update();
			});
		}
		if (o.pauseHover) {
			on(root, 'mouseenter', function () { self.hold.hover = true; self.schedule(); });
			on(root, 'mouseleave', function () { self.hold.hover = false; self.schedule(); });
		}
		on(root, 'focusin', function () { self.hold.focus = true; self.schedule(); });
		on(root, 'focusout', function (e) {
			if (!e.relatedTarget || !root.contains(e.relatedTarget)) { self.hold.focus = false; self.schedule(); }
		});
		on(root, 'keydown', function (e) {
			if (/^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) { return; }
			/* In RTL the "next" side is the left side, so the arrow keys swap. */
			var fwd = self.rtl ? 'ArrowLeft' : 'ArrowRight', back = self.rtl ? 'ArrowRight' : 'ArrowLeft';
			if (e.key === back) { e.preventDefault(); self.prev(); }
			else if (e.key === fwd) { e.preventDefault(); self.next(); }
			else if (e.key === 'Home') { e.preventDefault(); self.go(0); }
			else if (e.key === 'End') { e.preventDefault(); self.go(self.n - 1); }
		});

		var sx = null, sy = 0;
		on(root, 'pointerdown', function (e) {
			if (e.pointerType === 'mouse') { return; }
			sx = e.clientX; sy = e.clientY;
		}, true);
		on(root, 'pointerup', function (e) {
			if (sx === null) { return; }
			var dx = e.clientX - sx, dy = e.clientY - sy;
			sx = null;
			if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.5) {
				/* Swiping toward the reading start goes forward: left in LTR, right in RTL. */
				if ((dx < 0) !== self.rtl) { self.next(); } else { self.prev(); }
			}
		}, true);
		on(root, 'pointercancel', function () { sx = null; }, true);

		on(document, 'visibilitychange', function () { self.hold.hidden = document.hidden; self.schedule(); });
		if ('IntersectionObserver' in window) {
			self.io = new IntersectionObserver(function (en) {
				self.hold.off = !en[0].isIntersecting;
				self.schedule();
			});
			self.io.observe(root);
		}
		self.update();
		self.schedule();
	}

	function single(s) {
		if (s.n > 1) { return false; }
		s.root.classList.add('ks-single');
		return true;
	}

	var P = Slider.prototype;

	P.setState = function (s, active) {
		s.classList.toggle('is-active', active);
		s.setAttribute('aria-hidden', active ? 'false' : 'true');
		if ('inert' in s) { s.inert = !active; }
	};

	/* Ends any running transition immediately and clears every inline animation style. */
	P.finish = function () {
		clearTimeout(this.leaveTimer);
		this.slides.forEach(function (s) {
			s.classList.remove('is-leaving');
			s.style.transform = '';
			s.style.opacity = '';
			s.style.clipPath = '';
			s.style.transition = '';
			s.style.zIndex = '';
		});
		this.root.classList.remove('is-animating');
	};

	/*
	 * Sets the incoming slide's start state (no transition) and returns a function that
	 * starts the animation. dir: +1 forward / -1 backward; dn: direction name.
	 */
	P.prepare = function (fx, dir, cur, nxt, d, dn) {
		var v = V[dn] || V.left;
		var sx = -v[0] * dir * 100, sy = -v[1] * dir * 100;
		var ox = -v[0] * dir, oy = -v[1] * dir;
		var tr = function (x, y) { return 'translate3d(' + x + '%,' + y + '%,0)'; };
		var from = {}, to = {}, out = {};
		var ease = fx === 'slide' ? 'ease' : EASE;
		if (fx === 'fade') {
			from.opacity = '0'; to.opacity = '1'; out.opacity = '0';
		} else if (fx === 'slide' || fx === 'push') {
			from.transform = tr(sx, sy); to.transform = tr(0, 0); out.transform = tr(-sx, -sy);
			if (fx === 'push') { out.opacity = '.55'; }
		} else if (fx === 'crossfade') {
			from.opacity = '0'; to.opacity = '1'; out.opacity = '0';
		} else if (fx === 'zoom_in') {
			from.opacity = '0'; from.transform = 'scale(.92)'; to.opacity = '1'; to.transform = 'scale(1)'; out.opacity = '0';
		} else if (fx === 'zoom_out') {
			from.opacity = '0'; from.transform = 'scale(1.08)'; to.opacity = '1'; to.transform = 'scale(1)'; out.opacity = '0';
		} else if (fx === 'wipe') {
			from.clipPath = ox > 0 ? 'inset(0 0 0 100%)' : ox < 0 ? 'inset(0 100% 0 0)' : oy > 0 ? 'inset(100% 0 0 0)' : 'inset(0 0 100% 0)';
			to.clipPath = 'inset(0 0 0 0)'; out.opacity = '0';
		}
		var t = 'transform ' + d + 'ms ' + ease + ',opacity ' + d + 'ms ' + ease + ',clip-path ' + d + 'ms ' + ease;
		var apply = function (el, o) { for (var k in o) { if (Object.prototype.hasOwnProperty.call(o, k)) { el.style[k] = o[k]; } } };
		/* Explicit stacking is essential: the incoming slide must animate above the outgoing slide. */
		nxt.style.zIndex = '3';
		cur.style.zIndex = '2';
		nxt.style.transition = 'none';
		apply(nxt, from);
		return function () {
			nxt.style.transition = t;
			cur.style.transition = t;
			apply(nxt, to);
			apply(cur, out);
		};
	};

	/* ov (optional) overrides effect/direction/duration; used only by the editor's animation preview. */
	P.go = function (to, dir, ov) {
		var n = this.n;
		if (n < 2 || isNaN(to)) { return; }
		if (to >= n || to < 0) {
			if (!this.o.loop) { return; }
			to = (to + n) % n;
		}
		if (to === this.i) { return; }
		this.finish();
		dir = dir || (to > this.i ? 1 : -1);
		var fx = ov && FX.indexOf(ov.fx) > -1 ? ov.fx : this.fx;
		var dn = ov && V[ov.direction] ? ov.direction : this.dir;
		/* RTL sites: horizontal directions are mirrored so "next" travels toward the reading direction. */
		if (this.rtl && (dn === 'left' || dn === 'right')) { dn = dn === 'left' ? 'right' : 'left'; }
		var requestedDuration = ov && ov.dur >= 0 ? Math.min(3000, ov.dur) : this.dur;
		var d = this.reduce ? Math.min(150, requestedDuration || 150) : requestedDuration;
		var cur = this.slides[this.i], nxt = this.slides[to], self = this, play = null;
		if (d > 0) { play = this.prepare(fx, dir, cur, nxt, d, dn); }
		cur.classList.add('is-leaving');
		this.setState(cur, false);
		this.setState(nxt, true);
		this.i = to;
		if (d > 0) { this.root.classList.add('is-animating'); }
		if (play) {
			/* Force the start state to commit before applying the destination styles. */
			void nxt.offsetWidth;
			requestAnimationFrame(function () { requestAnimationFrame(play); });
		}
		this.leaveTimer = setTimeout(function () { self.finish(); }, d + 60);
		this.update();
		this.schedule();
	};

	/* Editor preview: start -> next -> back to start with the given effect; ends where it began. */
	P.demo = function (ov, cb) {
		var self = this, start = this.i;
		if (this.n < 2 || this.demoing) { if (cb) { cb(false); } return false; }
		this.demoing = true;
		var d = this.reduce ? 150 : Math.min(3000, Math.max(150, ov.dur || this.dur));
		this.go((start + 1) % this.n, 1, ov);
		this.demoTimers.push(setTimeout(function () {
			self.go(start, -1, ov);
			self.demoTimers.push(setTimeout(function () { self.demoing = false; if (cb) { cb(true); } }, d + 150));
		}, d + 600));
		return true;
	};

	P.next = function () { this.go(this.i + 1, 1); };
	P.prev = function () { this.go(this.i - 1, -1); };

	P.canPlay = function () {
		var h = this.hold;
		return this.playing && !h.hover && !h.hidden && !h.off && !h.user && (this.o.loop || this.i < this.n - 1);
	};

	P.schedule = function () {
		var self = this;
		clearTimeout(this.timer);
		this.timer = null;
		if (this.track) { this.track.setAttribute('aria-live', this.canPlay() ? 'off' : 'polite'); }
		if (!this.canPlay()) { return; }
		this.timer = setTimeout(function () { self.next(); }, Math.max(1500, this.o.interval || 5000));
	};

	P.update = function () {
		var self = this;
		this.dots.forEach(function (d, k) {
			if (k === self.i) { d.setAttribute('aria-current', 'true'); } else { d.removeAttribute('aria-current'); }
		});
		if (!this.o.loop) {
			if (this.prevBtn) { this.prevBtn.disabled = this.i === 0; }
			if (this.nextBtn) { this.nextBtn.disabled = this.i === this.n - 1; }
		}
		if (this.pauseBtn) {
			var paused = this.hold.user, t = this.o.i18n || {};
			this.pauseBtn.classList.toggle('is-paused', paused);
			this.pauseBtn.setAttribute('aria-label', paused ? (t.play || 'Start autoplay') : (t.pause || 'Pause autoplay'));
		}
	};

	P.destroy = function () {
		clearTimeout(this.timer);
		clearTimeout(this.leaveTimer);
		this.demoTimers.forEach(clearTimeout);
		this.ac.abort();
		if (this.io) { this.io.disconnect(); }
		this.root.removeAttribute('data-ks-init');
		delete this.root.__ks;
	};

	function init(scope) {
		[].forEach.call((scope || document).querySelectorAll('.ks-slider:not([data-ks-init])'), function (el) {
			el.setAttribute('data-ks-init', '1');
			el.__ks = new Slider(el);
		});
	}

	function initCarousels(scope) {
		[].forEach.call((scope || document).querySelectorAll('.ks-carousel:not([data-ks-carousel-init])'), function (root) {
			var track = root.querySelector('.ks-carousel-track');
			if (!track) { return; }
			root.setAttribute('data-ks-carousel-init', '1');
			var prev = root.querySelector('.ks-carousel-prev');
			var next = root.querySelector('.ks-carousel-next');
			var dots = [].slice.call(root.querySelectorAll('.ks-carousel-dot'));
			var items = [].slice.call(track.querySelectorAll('.ks-carousel-item'));
			var rtl = root.getAttribute('dir') === 'rtl';
			var timer = null, hovered = false, focused = false, paused = false, destroyed = false;
			var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
			var ac = new AbortController();
			var on = function (node, event, fn, options) { if (node) { node.addEventListener(event, fn, Object.assign({}, options || {}, { signal: ac.signal })); } };
			var activeIndex = 0;

			function visibleCount() {
				var w = root.getBoundingClientRect().width;
				if (w <= 600) { return Math.max(1, parseInt(root.style.getPropertyValue('--ks-cv-m'), 10) || 1); }
				if (w <= 900) { return Math.max(1, parseInt(root.style.getPropertyValue('--ks-cv-t'), 10) || 2); }
				return Math.max(1, parseInt(root.style.getPropertyValue('--ks-cv-d'), 10) || 4);
			}
			function maxStart() { return Math.max(0, items.length - visibleCount()); }
			function measuredIndex() {
				if (!items.length) { return 0; }
				var edge = track.getBoundingClientRect();
				var target = rtl ? edge.right : edge.left;
				var nearest = 0, distance = Infinity;
				items.forEach(function (item, i) {
					var r = item.getBoundingClientRect();
					var d = Math.abs((rtl ? r.right : r.left) - target);
					if (d < distance) { distance = d; nearest = i; }
				});
				return Math.max(0, Math.min(maxStart(), nearest));
			}
			function updateDots() {
				var max = maxStart();
				activeIndex = Math.max(0, Math.min(max, activeIndex));
				dots.forEach(function (dot, i) {
					var usable = i <= max;
					dot.hidden = !usable;
					if (!usable) { dot.removeAttribute('aria-current'); return; }
					if (i === activeIndex) { dot.setAttribute('aria-current', 'true'); }
					else { dot.removeAttribute('aria-current'); }
				});
				if (prev) { prev.disabled = root.getAttribute('data-loop') !== '1' && activeIndex <= 0; }
				if (next) { next.disabled = root.getAttribute('data-loop') !== '1' && activeIndex >= max; }
			}
			function goTo(index, behavior) {
				if (!items.length) { return; }
				var max = maxStart();
				if (root.getAttribute('data-loop') === '1' && max > 0) {
					if (index > max) { index = 0; }
					if (index < 0) { index = max; }
				} else { index = Math.max(0, Math.min(max, index)); }
				var item = items[index];
				if (!item) { return; }
				activeIndex = index;
				updateDots();
				/* Scroll the carousel track only. scrollIntoView() can move the whole page,
				 * especially inside RTL layouts or nested page-builder containers. */
				var tr = track.getBoundingClientRect();
				var ir = item.getBoundingClientRect();
				var delta = rtl ? (ir.right - tr.right) : (ir.left - tr.left);
				if (Math.abs(delta) < 1) { return; }
				var behaviorMode = (reduce || behavior === 'auto') ? 'auto' : 'smooth';
				if (typeof track.scrollBy === 'function') {
					track.scrollBy({ left: delta, top: 0, behavior: behaviorMode });
				} else {
					track.scrollLeft += delta;
				}
			}
			function currentIndex() {
				return measuredIndex();
			}
			function move(dir) {
				if (items.length <= visibleCount()) { return; }
				goTo(activeIndex + dir * Math.max(1, parseInt(root.getAttribute('data-move'), 10) || 1));
			}

			function schedule() {
				clearTimeout(timer); timer = null;
				if (destroyed || root.getAttribute('data-autoplay') !== '1' || hovered || paused || document.hidden || items.length <= visibleCount()) { return; }
				timer = setTimeout(function () {
					if (destroyed) { return; }
					var current = currentIndex(), max = maxStart();
					if (current >= max && root.getAttribute('data-loop') !== '1') {
						/* Restart from the beginning so autoplay remains predictable even without looping. */
						goTo(0);
					} else {
						move(1);
					}
					schedule();
				}, Math.max(1500, parseInt(root.getAttribute('data-interval'), 10) || 5000));
			}
			if (prev) { on(prev, 'click', function (e) { e.preventDefault(); move(-1); schedule(); }); }
			if (next) { on(next, 'click', function (e) { e.preventDefault(); move(1); schedule(); }); }
			dots.forEach(function (dot) { on(dot, 'click', function (e) { e.preventDefault(); goTo(parseInt(dot.getAttribute('data-index'), 10) || 0); schedule(); }); });
			if (root.getAttribute('data-pause-hover') === '1') {
				on(root, 'mouseenter', function () { hovered = true; schedule(); });
				on(root, 'mouseleave', function () { hovered = false; schedule(); });
			}
			on(root, 'focusin', function () { focused = true; schedule(); });
			on(root, 'focusout', function (e) { if (!e.relatedTarget || !root.contains(e.relatedTarget)) { focused = false; schedule(); } });
			on(document, 'visibilitychange', schedule);
			var scrollSync = null;
			on(track, 'scroll', function () {
				clearTimeout(scrollSync);
				scrollSync = setTimeout(function () {
					/* Only sync after scrolling settles; do not overwrite a requested target mid-animation. */
					activeIndex = measuredIndex();
					updateDots();
				}, 120);
			}, { passive: true });
			var sx = null, sy = null;
			on(track, 'pointerdown', function (e) { if (e.pointerType !== 'mouse') { sx = e.clientX; sy = e.clientY; } });
			on(track, 'pointerup', function (e) {
				if (sx === null) { return; }
				var dx = e.clientX - sx, dy = e.clientY - sy; sx = null; sy = null;
				if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.3) { move((dx < 0) !== rtl ? 1 : -1); schedule(); }
			});
			on(track, 'pointercancel', function () { sx = null; sy = null; });
			on(window, 'resize', function () { updateDots(); schedule(); });
			root.__ksCarousel = { destroy: function () { destroyed = true; clearTimeout(timer); clearTimeout(scrollSync); ac.abort(); root.removeAttribute('data-ks-carousel-init'); delete root.__ksCarousel; } };
			updateDots(); schedule();
		});
	}

	window.KashidaSlider = {
		init: init,
		initCarousels: initCarousels,
		demo: function (el, ov, cb) {
			if (el && el.__ks) { return el.__ks.demo(ov || {}, cb); }
			if (cb) { cb(false); }
			return false;
		},
		destroy: function (el) { if (el && el.__ks) { el.__ks.destroy(); } }
	};

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); }
	else { init(); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { initCarousels(); }); }
	else { initCarousels(); }
})();
