<?php
/**
 * Template Name: Menu
 *
 * Auto-applies to the page with slug "menu".
 */

defined( 'ABSPATH' ) || exit;
get_header();

$sections   = bht_menu( 'restaurant' );
$specials   = bht_menu( 'specials' );
$happy      = bht_menu( 'happy-hour' );
$all        = array_merge( $sections, $specials, $happy );
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

	<?php if ( $all ) : ?>
		<nav class="menu-tabs" aria-label="<?php esc_attr_e( 'Menu sections', 'bighams' ); ?>" data-menu-tabs>
			<div class="container">
				<ul>
					<?php foreach ( $all as $s ) : ?>
						<li><a href="#<?php echo esc_attr( $s['slug'] ); ?>"><?php echo esc_html( $s['name'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>
	<?php endif; ?>

	<div class="section section--paper menu-page">
		<div class="container">
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content menu-page__intro"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( ! $all ) : ?>
				<p><?php esc_html_e( 'Our menu is being updated. Please call us or order online.', 'bighams' ); ?></p>
			<?php endif; ?>

			<?php foreach ( $all as $s ) : ?>
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
