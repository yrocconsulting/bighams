<?php
/**
 * Menu item row. Expects $args['item'] (array from bh_get_menu_item); $args['style'] = 'row'|'card'.
 */

defined( 'ABSPATH' ) || exit;
$item  = $args['item'] ?? array();
$style = $args['style'] ?? 'row';
if ( ! $item ) {
	return;
}
$multi = count( array_filter( $item['prices'], fn( $p ) => '' !== $p['price'] ) ) > 1;
?>
<?php if ( 'card' === $style ) : ?>
	<article class="dish-card">
		<div class="dish-card__media"><?php bht_thumb( $item['image_id'], 'bh-square', $item['title'], 'dish-card__img' ); ?></div>
		<div class="dish-card__body">
			<?php if ( $item['badges'] ) : ?>
				<span class="badge"><?php echo esc_html( $item['badges'][0] ); ?></span>
			<?php endif; ?>
			<h3 class="dish-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
			<?php if ( $item['description'] ) : ?>
				<p class="dish-card__desc"><?php echo esc_html( wp_trim_words( $item['description'], 18 ) ); ?></p>
			<?php endif; ?>
			<p class="dish-card__price"><?php echo esc_html( $item['price_label'] ); ?></p>
		</div>
	</article>
<?php else : ?>
	<article class="menu-item" id="item-<?php echo esc_attr( $item['id'] ); ?>">
		<?php if ( $item['image_id'] ) : ?>
			<div class="menu-item__media"><?php bht_thumb( $item['image_id'], 'bh-square', $item['title'], 'menu-item__img' ); ?></div>
		<?php endif; ?>
		<div class="menu-item__body">
			<header class="menu-item__head">
				<h3 class="menu-item__title"><?php echo esc_html( $item['title'] ); ?>
					<?php foreach ( $item['badges'] as $b ) : ?>
						<span class="badge"><?php echo esc_html( $b ); ?></span>
					<?php endforeach; ?>
				</h3>
				<?php if ( ! $multi && $item['price_label'] ) : ?>
					<span class="menu-item__leader" aria-hidden="true"></span>
					<span class="menu-item__price"><?php echo esc_html( $item['price_label'] ); ?></span>
				<?php endif; ?>
			</header>
			<?php if ( $item['description'] ) : ?>
				<p class="menu-item__desc"><?php echo esc_html( $item['description'] ); ?></p>
			<?php endif; ?>
			<?php if ( $multi ) : ?>
				<ul class="price-rows">
					<?php foreach ( $item['prices'] as $p ) : ?>
						<?php if ( '' === $p['price'] ) { continue; } ?>
						<li><span><?php echo esc_html( $p['label'] ?: '—' ); ?></span><strong><?php echo esc_html( bht_money( $p['price'] ) ); ?></strong><?php if ( $p['note'] ) : ?><em><?php echo esc_html( $p['note'] ); ?></em><?php endif; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $item['diet'] ) : ?>
				<p class="menu-item__diet"><?php echo esc_html( implode( ' · ', $item['diet'] ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
<?php endif; ?>
