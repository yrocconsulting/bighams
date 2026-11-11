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

	/* ---------- Menu tabs: active section highlight ---------- */
	var tabs = doc.querySelector('[data-menu-tabs]');
	if (tabs && 'IntersectionObserver' in window) {
		var links = {};
		tabs.querySelectorAll('a[href^="#"]').forEach(function (a) {
			links[a.getAttribute('href').slice(1)] = a;
		});
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (!en.isIntersecting) return;
				Object.keys(links).forEach(function (k) { links[k].classList.remove('is-active'); });
				var a = links[en.target.id];
				if (a) {
					a.classList.add('is-active');
					var list = a.closest('ul');
					if (list) list.scrollTo({ left: a.offsetLeft - 24, behavior: reduceMotion ? 'auto' : 'smooth' });
				}
			});
		}, { rootMargin: '-35% 0px -60% 0px' });
		Object.keys(links).forEach(function (id) {
			var sec = doc.getElementById(id);
			if (sec) io.observe(sec);
		});
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
