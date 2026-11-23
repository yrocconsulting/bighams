<?php
/**
 * 301 redirects from the old SpotHopper site (bighamsbbq.com before launch)
 * so Google rankings, bookmarks and printed links keep working.
 */

defined( 'ABSPATH' ) || exit;

function bh_legacy_redirects() {
	return apply_filters(
		'bh_legacy_redirects',
		array(
			'/lubbock-bigham-s-smokehouse-food-menu'             => '/menu/',
			'/lubbock-bigham-s-smokehouse-catering'              => '/catering/',
			'/lubbock-bigham-s-smokehouse-catering-information'  => '/catering/',
			'/lubbock-bigham-s-smokehouse-locations'             => '/locations/',
			'/lubbock-bigham-s-smokehouse-party'                 => '/parties/',
			'/lubbock-bigham-s-smokehouse-party-informations'    => '/parties/',
			'/lubbock-bigham-s-smokehouse-jobs'                  => '/',
			'/accessibility-page-01'                             => '/',
			'/-party'                                            => '/parties/',
			'/food-menu'                                         => '/menu/',
			'/menu.html'                                         => '/menu/',
		)
	);
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = '/' . trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore
	$map  = bh_legacy_redirects();
	$to   = $map[ strtolower( $path ) ] ?? null;
	// Old "/call-us/<phone>" style links → contact page.
	if ( ! $to && 0 === strpos( $path, '/call-us' ) ) {
		$to = '/contact/';
	}
	if ( $to ) {
		wp_safe_redirect( home_url( $to ), 301, "Bigham's legacy redirect" );
		exit;
	}
}, 1 );
