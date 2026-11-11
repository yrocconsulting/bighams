/* Bigham's first-party click tracking. Cookie-free; sends {event, location, page}. */
(function () {
	if (!window.bhTrack || !navigator.sendBeacon) return;
	var send = function (e, l) {
		var fd = new FormData();
		fd.append('e', e);
		fd.append('l', l || 0);
		fd.append('p', location.pathname);
		navigator.sendBeacon(bhTrack.url, fd);
	};
	document.addEventListener('click', function (ev) {
		var a = ev.target.closest('[data-bh-track], a[href^="tel:"]');
		if (!a) return;
		var e = a.getAttribute('data-bh-track') || 'call';
		send(e, a.getAttribute('data-bh-loc'));
		// Mirror to GA4 / GTM if present.
		if (window.gtag) window.gtag('event', 'bh_' + e, { location_id: a.getAttribute('data-bh-loc') || 0 });
		if (window.dataLayer) window.dataLayer.push({ event: 'bh_' + e, location_id: a.getAttribute('data-bh-loc') || 0 });
	}, { capture: true });
	if (document.body && document.body.classList.contains('bh-menu-page')) send('menu_view', 0);
})();
