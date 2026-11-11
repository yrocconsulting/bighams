<?php
/**
 * Interior page hero with H1 + answer-first summary.
 * $args: title, eyebrow, summary, image_id, image_mod, actions (html), class.
 */

defined( 'ABSPATH' ) || exit;
$title   = $args['title'] ?? get_the_title();
$img     = (int) ( $args['image_id'] ?? 0 );
if ( ! $img && ! empty( $args['image_mod'] ) ) {
	$img = (int) get_theme_mod( $args['image_mod'] );
}
if ( ! $img && is_singular() && has_post_thumbnail() ) {
	$img = (int) get_post_thumbnail_id();
}
?>
<section class="page-hero <?php echo $img ? 'page-hero--image' : ''; ?> <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-labelledby="page-title">
	<?php if ( $img ) : ?>
		<div class="page-hero__media" aria-hidden="true"><?php echo wp_get_attachment_image( $img, 'bh-hero', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw', 'alt' => '' ) ); ?></div>
	<?php endif; ?>
	<div class="container page-hero__inner">
		<?php get_template_part( 'template-parts/breadcrumbs', null, array( 'class' => 'crumbs--light' ) ); ?>
		<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
			<p class="eyebrow eyebrow--light"><?php echo esc_html( $args['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h1 id="page-title" class="page-hero__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( ! empty( $args['summary'] ) ) : ?>
			<p class="page-hero__summary"><?php echo esc_html( $args['summary'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $args['actions'] ) ) : ?>
			<div class="btn-row"><?php echo $args['actions']; // phpcs:ignore -- built from escaped parts in templates. ?></div>
		<?php endif; ?>
	</div>
	<div class="torn torn--bottom" aria-hidden="true"></div>
</section>
