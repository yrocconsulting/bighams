<?php
/**
 * Template Name: Menu
 *
 * Auto-applies to the page with slug "menu".
 */

defined( 'ABSPATH' ) || exit;
get_header();

$cats       = bht_menu_categories();
$all        = $cats;
$order_btn  = '<button class="btn btn--primary" type="button" data-order-open aria-haspopup="dialog" aria-controls="order-modal">' . bht_icon( 'bag' ) . ' ' . esc_html__( 'Order Online', 'bighams' ) . '</button>';
$order_btn .= '<a class="btn btn--outline-light" href="' . esc_url( home_url( '/catering/' ) ) . '">' . esc_html__( 'Catering Menu', 'bighams' ) . '</a>';

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => __( 'Slow-smoked daily', 'bighams' ),
			'summary' => __( 'Bigham\'s Smokehouse menu: hand-sliced brisket, St. Louis-style pork ribs, smoked sausage, turkey breast and pulled pork by the plate, sandwich or pound — with scratch-made sides, cobblers and banana pudding. Prices below; order online for pickup at any Lubbock location.', 'bighams' ),
			'actions' => $order_btn,
		)
	);
	?>

	<?php if ( $cats ) : ?>
		<nav class="menu-tabs" aria-label="<?php esc_attr_e( 'Menu categories', 'bighams' ); ?>" data-menu-tabs>
			<div class="container menu-tabs__inner">
				<button class="menu-tabs__arrow menu-tabs__arrow--prev" type="button" data-tabs-scroll="-1" aria-label="<?php esc_attr_e( 'Scroll categories left', 'bighams' ); ?>" hidden><?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></button>
				<ul role="tablist" data-tabs-list>
					<li role="presentation"><a role="tab" id="tab-cat-all" href="#cat-all" aria-controls="cat-all" aria-selected="true" class="is-active"><?php esc_html_e( 'All', 'bighams' ); ?></a></li>
					<?php foreach ( $cats as $i => $c ) : ?>
						<li role="presentation"><a role="tab" id="tab-<?php echo esc_attr( $c['slug'] ); ?>" href="#<?php echo esc_attr( $c['slug'] ); ?>" aria-controls="<?php echo esc_attr( $c['slug'] ); ?>" aria-selected="false"><?php echo esc_html( $c['name'] ); ?></a></li>
					<?php endforeach; ?>
					<li role="presentation"><a class="menu-tabs__link" href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php esc_html_e( 'Catering', 'bighams' ); ?> &rarr;</a></li>
				</ul>
				<button class="menu-tabs__arrow menu-tabs__arrow--next" type="button" data-tabs-scroll="1" aria-label="<?php esc_attr_e( 'Scroll categories right', 'bighams' ); ?>" hidden><?php echo bht_icon( 'arrow' ); // phpcs:ignore ?></button>
			</div>
		</nav>
	<?php endif; ?>

	<div class="section section--paper menu-page is-all" data-menu-page>
		<div class="container">
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content menu-page__intro"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( ! $cats ) : ?>
				<p><?php esc_html_e( 'Our menu is being updated. Please call us or order online.', 'bighams' ); ?></p>
			<?php endif; ?>

			<?php foreach ( $cats as $c ) : ?>
				<div class="menu-cat" id="<?php echo esc_attr( $c['slug'] ); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr( $c['slug'] ); ?>" data-menu-panel>
					<?php if ( count( $c['sections'] ) > 1 || strtolower( $c['sections'][0]['name'] ) !== strtolower( $c['name'] ) ) : ?>
						<p class="menu-cat__label" aria-hidden="true"><?php echo esc_html( $c['name'] ); ?></p>
					<?php endif; ?>
					<?php foreach ( $c['sections'] as $s ) : ?>
						<section class="menu-section<?php echo 'happy-hour' === $s['group'] ? ' menu-section--happy' : ''; ?>" id="<?php echo esc_attr( $s['slug'] ); ?>" aria-labelledby="sec-<?php echo esc_attr( $s['slug'] ); ?>">
							<header class="menu-section__head">
								<h2 id="sec-<?php echo esc_attr( $s['slug'] ); ?>" class="menu-section__title"><?php echo esc_html( $s['name'] ); ?></h2>
								<?php if ( $s['note'] ) : ?>
									<p class="menu-section__note"><?php echo esc_html( $s['note'] ); ?></p>
								<?php endif; ?>
								<?php if ( $s['description'] ) : ?>
									<p class="menu-section__desc"><?php echo esc_html( $s['description'] ); ?></p>
								<?php endif; ?>
							</header>
							<div class="menu-grid">
								<?php foreach ( $s['items'] as $item ) : ?>
									<?php get_template_part( 'template-parts/menu-item', null, array( 'item' => $item ) ); ?>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<p class="menu-page__fine"><?php esc_html_e( 'Prices and availability may vary by location and are subject to change. Basic sides: pinto beans, potato salad, coleslaw, green beans.', 'bighams' ); ?></p>
		</div>
	</div>

	<?php get_template_part( 'template-parts/faq-list', null, array( 'topic' => 'menu', 'title' => __( 'Menu Questions', 'bighams' ) ) ); ?>

	<?php
	get_template_part(
		'template-parts/cta-band',
		null,
		array(
			'title' => __( 'Feeding a Crowd?', 'bighams' ),
			'text'  => __( 'Family packs feed 2–8 people. Catering packages handle 10 to 500+.', 'bighams' ),
		)
	);
endwhile;

get_footer();
