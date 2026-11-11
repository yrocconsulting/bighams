<?php
/**
 * 404.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="page-hero page-hero--404" aria-labelledby="page-title">
	<div class="container page-hero__inner">
		<p class="eyebrow eyebrow--light"><?php esc_html_e( 'Error 404', 'bighams' ); ?></p>
		<h1 id="page-title" class="page-hero__title"><?php esc_html_e( 'This Page Went Up in Smoke', 'bighams' ); ?></h1>
		<p class="page-hero__summary"><?php esc_html_e( 'We couldn\'t find what you were looking for — but the brisket is right where we left it.', 'bighams' ); ?></p>
		<div class="btn-row">
			<a class="btn btn--primary btn--lg" href="<?php echo esc_url( home_url( '/menu/' ) ); ?>"><?php esc_html_e( 'View the menu', 'bighams' ); ?></a>
			<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( bht_locations_url() ); ?>"><?php esc_html_e( 'Find a location', 'bighams' ); ?></a>
		</div>
		<div class="search-404"><?php get_search_form(); ?></div>
	</div>
</section>
<?php
get_footer();
