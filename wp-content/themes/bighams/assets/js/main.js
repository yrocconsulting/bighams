/* Bigham's Smokehouse — theme interactions. Vanilla, no dependencies. */
(function () {
	'use strict';
	var doc = document;
	doc.documentElement.classList.add('js');
	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ---------- Mobile nav ---------- */
	var toggle = doc.querySelector('[data-nav-toggle]');
	var nav = doc.getElementById('primary-nav');
	var header = doc.getElementById('site-header');
	function setNav(open) {
		if (!toggle || !nav) return;
		if (open && header) {
			nav.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
		}
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		nav.classList.toggle('is-open', open);
		doc.body.classList.toggle('nav-open', open);
		if (open) {
			var first = nav.querySelector('a');
			if (first) first.focus();
		}
	}
	if (toggle) {
		toggle.addEventListener('click', function () {
			setNav(toggle.getAttribute('aria-expanded') !== 'true');
		});
		window.addEventListener('resize', function () {
			if (window.innerWidth >= 1024) {
				setNav(false);
				nav.style.top = '';
			}
		});
	}

	/* ---------- Order modal ---------- */
	var modal = doc.getElementById('order-modal');
	var lastFocus = null;
	function openModal() {
		if (!modal) return;
		setNav(false);
		lastFocus = doc.activeElement;
		if (typeof modal.showModal === 'function') {
			modal.showModal();
		} else {
			modal.setAttribute('open', '');
		}
		doc.body.classList.add('modal-open');
	}
	function closeModal() {
		if (!modal) return;
		if (typeof modal.close === 'function') modal.close();
		else modal.removeAttribute('open');
	}
	if (modal) {
		modal.addEventListener('close', function () {
			doc.body.classList.remove('modal-open');
			if (lastFocus && lastFocus.focus) lastFocus.focus();
		});
		modal.addEventListener('click', function (e) {
			if (e.target === modal) closeModal(); // backdrop click
		});
	}
	doc.addEventListener('click', function (e) {
		if (e.target.closest('[data-order-open]')) {
			e.preventDefault();
			openModal();
		} else if (e.target.closest('[data-order-close]')) {
			closeModal();
		}
	});
	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && toggle && toggle.getAttribute('aria-expanded') === 'true') {
			setNav(false);
			toggle.focus();
		}
	});
	if (location.hash === '#order') openModal();

	/* ---------- Scroller prev/next ---------- */
	doc.querySelectorAll('[data-scroller-nav]').forEach(function (navEl) {
		var track = doc.getElementById(navEl.getAttribute('data-scroller-nav'));
		if (!track) return;
		navEl.addEventListener('click', function (e) {
			var btn = e.target.closest('button[data-dir]');
			if (!btn) return;
			var item = track.firstElementChild;
			var step = item ? item.getBoundingClientRect().width + 16 : track.clientWidth * 0.8;
			track.scrollBy({ left: step * Number(btn.getAttribute('data-dir')), behavior: reduceMotion ? 'auto' : 'smooth' });
		});
	});

	/* ---------- Menu: one category at a time (old-site style) ---------- */
	var tabs = doc.querySelector('[data-menu-tabs]');
	if (tabs) {
		var list = tabs.querySelector('[data-tabs-list]');
		var tabLinks = Array.prototype.slice.call(tabs.querySelectorAll('a[role="tab"]'));
		var panels = Array.prototype.slice.call(doc.querySelectorAll('[data-menu-panel]'));
		var page = doc.querySelector('[data-menu-page]');
		var show = function (id, focus) {
			var all = !id || id === 'cat-all';
			var found = all;
			panels.forEach(function (p) { var on = all || p.id === id; p.hidden = !on; found = found || p.id === id; });
			if (!found) { all = true; id = 'cat-all'; panels.forEach(function (p) { p.hidden = false; }); }
			if (all) id = 'cat-all';
			if (page) page.classList.toggle('is-all', all);
			tabLinks.forEach(function (a) {
				var on = a.getAttribute('aria-controls') === id;
				a.classList.toggle('is-active', on);
				a.setAttribute('aria-selected', on ? 'true' : 'false');
				a.tabIndex = on ? 0 : -1;
				if (on && list) list.scrollTo({ left: a.offsetLeft - list.clientWidth / 2 + a.offsetWidth / 2, behavior: reduceMotion ? 'auto' : 'smooth' });
				if (on && focus) a.focus();
			});
		};
		var startId = (location.hash || '').slice(1);
		// A link to a section (#pinto-beans) opens the category that holds it.
		var startEl = startId && startId !== 'cat-all' ? doc.getElementById(startId) : null;
		if (startEl && !startEl.matches('[data-menu-panel]')) {
			var holder = startEl.closest('[data-menu-panel]');
			startId = holder ? holder.id : '';
		} else if (!startEl) {
			startId = '';
		}
		show(startId || 'cat-all');
		tabLinks.forEach(function (a, i) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				var id = a.getAttribute('aria-controls');
				show(id);
				history.replaceState(null, '', '#' + id);
				var panel = doc.getElementById(id);
				if (panel && panel.getBoundingClientRect().top < tabs.getBoundingClientRect().bottom) {
					window.scrollTo({ top: panel.getBoundingClientRect().top + window.scrollY - tabs.offsetHeight - 80, behavior: reduceMotion ? 'auto' : 'smooth' });
				}
			});
			a.addEventListener('keydown', function (e) {
				var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
				if (!d) return;
				e.preventDefault();
				var n = tabLinks[(i + d + tabLinks.length) % tabLinks.length];
				show(n.getAttribute('aria-controls'), true);
			});
		});
		// In "All", highlight the category being read and keep it in view in the bar.
		if ('IntersectionObserver' in window) {
			var spy = new IntersectionObserver(function (entries) {
				if (!page || !page.classList.contains('is-all')) return;
				entries.forEach(function (en) {
					if (!en.isIntersecting) return;
					tabLinks.forEach(function (a) {
						var on = a.getAttribute('aria-controls') === en.target.id;
						a.classList.toggle('is-current', on);
						if (on && list) list.scrollTo({ left: a.offsetLeft - list.clientWidth / 2 + a.offsetWidth / 2, behavior: reduceMotion ? 'auto' : 'smooth' });
					});
				});
			}, { rootMargin: '-30% 0px -65% 0px' });
			panels.forEach(function (p) { spy.observe(p); });
		}
		tabs.addEventListener('click', function () { tabLinks.forEach(function (a) { a.classList.remove('is-current'); }); });

		// Arrow buttons + visible scrollbar for the category bar.
		var prev = tabs.querySelector('[data-tabs-scroll="-1"]');
		var next = tabs.querySelector('[data-tabs-scroll="1"]');
		var updateArrows = function () {
			if (!list || !prev || !next) return;
			var max = list.scrollWidth - list.clientWidth;
			prev.hidden = list.scrollLeft <= 4;
			next.hidden = list.scrollLeft >= max - 4;
		};
		[prev, next].forEach(function (btn) {
			if (!btn) return;
			btn.addEventListener('click', function () {
				list.scrollBy({ left: parseInt(btn.getAttribute('data-tabs-scroll'), 10) * list.clientWidth * 0.7, behavior: reduceMotion ? 'auto' : 'smooth' });
			});
		});
		if (list) {
			list.addEventListener('scroll', updateArrows, { passive: true });
			window.addEventListener('resize', updateArrows);
			updateArrows();
		}
	}

	/* ---------- E-gift card pop-up: first visit only ---------- */
	var gift = doc.getElementById('gift-pop');
	if (gift && typeof gift.show === 'function') {
		var gKey = 'bh_gift_' + gift.getAttribute('data-gift-pop');
		var seen = false;
		try { seen = !!localStorage.getItem(gKey); } catch (err) { seen = true; } // No storage → don't nag.
		var params = new URLSearchParams(location.search);
		if (params.has('giftcard')) seen = false; // ?giftcard previews it.
		var markSeen = function () { try { localStorage.setItem(gKey, String(Date.now())); } catch (err) {} };
		var closeGift = function () { if (gift.open) gift.close(); markSeen(); };
		gift.addEventListener('close', markSeen);
		gift.addEventListener('click', function (e) {
			if (e.target === gift) closeGift(); // Backdrop click.
			if (e.target.closest('[data-gift-close]')) closeGift();
			if (e.target.closest('[data-gift-go]')) markSeen();
			var copy = e.target.closest('[data-gift-copy]');
			if (copy) {
				var code = copy.getAttribute('data-gift-copy');
				var done = function () {
					copy.classList.add('is-copied');
					var em = copy.querySelector('[data-gift-copied]');
					if (em) em.textContent = 'Copied!';
				};
				if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code).then(done, done); else done();
			}
		});
		var mobile = window.matchMedia('(max-width: 599px)').matches;
		var open = function () {
			if (seen || gift.open) return;
			var order = doc.getElementById('order-modal');
			if (order && order.open) return; // Never stack on the order picker.
			seen = true;
			if (mobile) gift.show(); else gift.showModal();
		};
		if (!seen) {
			setTimeout(open, mobile ? 8000 : 5000);
		}
	}

	/* ---------- Hero video: only on capable connections ---------- */
	var video = doc.querySelector('[data-hero-video]');
	if (video && !reduceMotion) {
		var conn = navigator.connection || {};
		var slow = conn.saveData || /(^|-)2g$/.test(conn.effectiveType || '');
		if (!slow && window.innerWidth >= 600) {
			video.preload = 'auto';
			video.addEventListener('playing', function () { video.classList.add('is-playing'); });
			var p = video.play();
			if (p && p.catch) p.catch(function () {});
		}
	}

	/* ---------- Package "Get a quote" → preselect package in form ---------- */
	doc.addEventListener('click', function (e) {
		var a = e.target.closest('[data-pkg]');
		if (!a) return;
		var sel = doc.getElementById('bh-package');
		if (!sel) return;
		var name = a.getAttribute('data-pkg');
		for (var i = 0; i < sel.options.length; i++) {
			if (sel.options[i].text === name) { sel.selectedIndex = i; break; }
		}
		var form = doc.getElementById('inquiry');
		if (form) {
			e.preventDefault();
			form.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
			var first = doc.getElementById('bh-name');
			if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 400);
		}
	});

	/* ---------- Reveal on scroll ---------- */
	if (!reduceMotion && 'IntersectionObserver' in window) {
		var targets = doc.querySelectorAll('.section-head, .loc-card, .pkg, .step, .feature, .story__media, .mini-pkg');
		var rio = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					en.target.classList.add('is-visible');
					rio.unobserve(en.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px' });
		targets.forEach(function (t) {
			// Only animate things below the fold, to avoid flashing visible content.
			if (t.getBoundingClientRect().top > window.innerHeight) {
				t.classList.add('reveal');
				rio.observe(t);
			}
		});
	}
})();
