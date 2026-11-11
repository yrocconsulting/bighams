<?php
/**
 * Inline SVG icon set (no icon font, no requests).
 */

defined( 'ABSPATH' ) || exit;

function bht_icon( $name, $class = '' ) {
	$paths = array(
		'bag'       => '<path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
		'phone'     => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2"/>',
		'pin'       => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'truck'     => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
		'users'     => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14a5 5 0 0 1 5 5"/>',
		'flame'     => '<path d="M12 3c1 4 5 5.5 5 10a5 5 0 0 1-10 0c0-2.5 1.5-4 2.5-5 .3 1.5 1 2.5 2 3 0-3 0-5.5.5-8Z"/>',
		'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'check'     => '<path d="m5 12 5 5 9-10"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'directions'=> '<path d="m12 2 10 10-10 10L2 12z"/><path d="M9 13v-2h5V9l3 3-3 3v-2"/>',
		'star'      => '<path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z" fill="currentColor" stroke="none"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'gift'      => '<rect x="3" y="8" width="18" height="13" rx="1"/><path d="M12 8v13M3 12h18M12 8S10 3 7.5 4.5 9 8 12 8Zm0 0s2-5 4.5-3.5S15 8 12 8Z"/>',
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v7h4v-7h3l1-4h-4V8a0 0 0 0 1 0 0Z" fill="currentColor" stroke="none"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>',
		'google'    => '<path d="M20 12.2c0-.6-.1-1.2-.2-1.7H12v3.3h4.5a4 4 0 0 1-1.7 2.5v2h2.8c1.6-1.5 2.4-3.6 2.4-6.1Z" fill="currentColor" stroke="none"/><path d="M12 21c2.3 0 4.2-.8 5.6-2l-2.8-2.1a5.3 5.3 0 0 1-7.9-2.8H4v2.2A9 9 0 0 0 12 21Z" fill="currentColor" stroke="none"/><path d="M6.9 14.1a5.3 5.3 0 0 1 0-3.4V8.5H4a9 9 0 0 0 0 8z" fill="currentColor" stroke="none"/><path d="M12 6.6c1.3 0 2.5.5 3.4 1.3L17.9 5.4A9 9 0 0 0 4 8.5l2.9 2.2A5.3 5.3 0 0 1 12 6.6Z" fill="currentColor" stroke="none"/>',
		'yelp'      => '<path d="M11 3v9L6 8.5C7 5.5 9 3.5 11 3Zm2 9 6-2c.5 2 0 4-1 5.5Zm-1.5 2.5 1 6c-2 .3-4-.2-5.5-1.5Zm-1-1.5-5.5 2c-.8-2-.6-4 .3-5.5Z" fill="currentColor" stroke="none"/>',
		'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.5 2.5 4.5 5 4.8"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon icon-' . esc_attr( $name ) . ' ' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}
