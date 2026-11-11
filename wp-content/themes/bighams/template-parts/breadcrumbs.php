<?php
/**
 * Visual breadcrumbs (JSON-LD BreadcrumbList is output by the plugin).
 */

defined( 'ABSPATH' ) || exit;
if ( is_front_page() ) {
	return;
}
$trail = function_exists( 'bh_breadcrumb_trail' ) ? (array) bh_breadcrumb_trail() : array();
if ( ! $trail ) {
	$trail = array( array( 'name' => __( 'Home', 'bighams' ), 'url' => home_url( '/' ) ) );
	if ( is_singular( 'bh_location' ) ) {
		$trail[] = array( 'name' => __( 'Locations', 'bighams' ), 'url' => bht_locations_url() );
	}
	if ( is_singular() ) {
		$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_post_type_archive() ) {
		$trail[] = array( 'name' => post_type_archive_title( '', false ), 'url' => '' );
	} elseif ( is_archive() ) {
		$trail[] = array( 'name' => wp_strip_all_tags( get_the_archive_title() ), 'url' => '' );
	}
}
$last = count( $trail ) - 1;
?>
<nav class="crumbs <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bighams' ); ?>">
	<ol>
		<?php foreach ( $trail as $i => $c ) : ?>
			<li>
				<?php if ( $i === $last || empty( $c['url'] ) ) : ?>
					<span aria-current="page"><?php echo esc_html( $c['name'] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['name'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
