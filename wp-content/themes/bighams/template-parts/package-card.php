<?php
/**
 * Catering package card. Expects $args['pkg']; $args['compact'] bool.
 */

defined( 'ABSPATH' ) || exit;
$pkg     = $args['pkg'] ?? array();
$compact = ! empty( $args['compact'] );
if ( ! $pkg ) {
	return;
}
?>
<article class="pkg<?php echo $pkg['featured'] ? ' pkg--featured' : ''; ?>">
	<?php if ( $pkg['tagline'] ) : ?>
		<p class="pkg__tag"><?php echo esc_html( $pkg['tagline'] ); ?></p>
	<?php endif; ?>
	<h3 class="pkg__title"><?php echo esc_html( $pkg['title'] ); ?></h3>
	<?php if ( '' !== $pkg['price'] ) : ?>
		<p class="pkg__price"><span class="pkg__from"><?php esc_html_e( 'from', 'bighams' ); ?></span> <strong><?php echo esc_html( bht_money( $pkg['price'] ) ); ?></strong> <span class="pkg__unit"><?php echo esc_html( $pkg['unit'] ); ?></span></p>
	<?php endif; ?>
	<?php if ( $pkg['minimum'] ) : ?>
		<p class="pkg__min"><?php echo bht_icon( 'users' ); // phpcs:ignore ?> <?php echo esc_html( $pkg['minimum'] ); ?></p>
	<?php endif; ?>
	<?php if ( $pkg['description'] ) : ?>
		<p class="pkg__desc"><?php echo esc_html( $compact ? wp_trim_words( $pkg['description'], 22 ) : $pkg['description'] ); ?></p>
	<?php endif; ?>
	<?php if ( $pkg['includes'] && ! $compact ) : ?>
		<ul class="checklist">
			<?php foreach ( $pkg['includes'] as $inc ) : ?>
				<li><?php echo bht_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $inc ); ?></span></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $pkg['addons'] && ! $compact ) : ?>
		<details class="pkg__addons">
			<summary><?php esc_html_e( 'Upgrades & add-ons', 'bighams' ); ?></summary>
			<ul class="price-rows">
				<?php foreach ( $pkg['addons'] as $a ) : ?>
					<li><span><?php echo esc_html( $a['label'] ); ?></span><strong><?php echo esc_html( '' !== $a['price'] ? '+' . bht_money( $a['price'] ) : '' ); ?></strong><?php if ( $a['note'] ) : ?><em><?php echo esc_html( $a['note'] ); ?></em><?php endif; ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
	<a class="btn <?php echo $pkg['featured'] ? 'btn--primary' : 'btn--outline'; ?> btn--block" href="<?php echo esc_url( home_url( '/catering/#inquiry' ) ); ?>" data-pkg="<?php echo esc_attr( $pkg['title'] ); ?>"><?php esc_html_e( 'Get a quote', 'bighams' ); ?><span class="screen-reader-text"> — <?php echo esc_html( $pkg['title'] ); ?></span></a>
</article>
