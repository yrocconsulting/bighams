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
		// Meta Pixel standard events (so ads can optimize for real actions).
		if (window.fbq) {
			var loc = a.getAttribute('data-bh-loc') || '';
			var meta = {
				order: ['InitiateCheckout', { content_category: 'Order Online' }],
				delivery: ['InitiateCheckout', { content_category: 'Delivery app' }],
				catering_order: ['InitiateCheckout', { content_category: 'Catering order' }],
				lead_catering: ['Lead', { content_category: 'Catering request' }],
				lead_party: ['Lead', { content_category: 'Private party request' }],
				call: ['Contact', {}],
				directions: ['FindLocation', {}]
			}[e];
			if (meta) { if (loc) meta[1].location_id = loc; window.fbq('track', meta[0], meta[1]); }
		}
		// Mirror to GA4 / GTM if present.
		if (window.gtag) window.gtag('event', 'bh_' + e, { location_id: a.getAttribute('data-bh-loc') || 0 });
		if (window.dataLayer) window.dataLayer.push({ event: 'bh_' + e, location_id: a.getAttribute('data-bh-loc') || 0 });
	}, { capture: true });
	if (document.body && document.body.classList.contains('bh-menu-page')) {
		send('menu_view', 0);
		if (window.fbq) window.fbq('track', 'ViewContent', { content_name: 'Menu', content_category: 'Menu' });
	}
})();
