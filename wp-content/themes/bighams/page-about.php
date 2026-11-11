<?php
/**
 * Template Name: About / Our Story
 *
 * Auto-applies to the page with slug "about".
 */

defined( 'ABSPATH' ) || exit;
get_header();

$founded = (int) bht_setting( 'founded', '1978' );
$founder = bht_setting( 'founder', 'Don Bigham' );
$owners  = str_replace( ', ', ' and ', bht_setting( 'owners', 'Joyce Bigham, Nick Bigham' ) );

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow'   => sprintf( __( 'Family-owned since %d', 'bighams' ), $founded ),
			'summary'   => sprintf(
				/* translators: 1: year 2: founder 3: owners 4: count */
				__( 'Bigham\'s Smokehouse is a family-owned Texas barbecue restaurant founded in Lubbock in %1$d by %2$s. Today it is owned and operated by %3$s, serving slow-smoked BBQ at %4$s Lubbock locations and catering events across West Texas.', 'bighams' ),
				$founded,
				$founder,
				$owners,
				bht_location_count_word()
			),
			'image_mod' => 'bh_about_image',
		)
	);
	?>

	<section class="section section--paper" aria-labelledby="timeline-title">
		<div class="container story__grid">
			<div class="story__copy">
				<?php bht_heading( 'timeline-title', __( 'Our Story', 'bighams' ), __( 'Lubbock BBQ', 'bighams' ) ); ?>
				<?php if ( get_the_content() ) : ?>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php else : ?>
					<p class="lead"><?php printf( esc_html__( 'In %1$d, %2$s opened Bigham\'s Smokehouse with a simple idea: smoke good meat low and slow, serve it with honest sides, and treat every guest like a neighbor.', 'bighams' ), (int) $founded, esc_html( $founder ) ); ?></p>
					<p><?php printf( esc_html__( 'More than four decades later, the family is still at it. %s run the business today, and the recipes — the rubs, the sauce, the cobblers — are the ones Lubbock has been lining up for since the beginning.', 'bighams' ), esc_html( $owners ) ); ?></p>
				<?php endif; ?>
			</div>
			<ol class="timeline">
				<li><strong><?php echo esc_html( $founded ); ?></strong><span><?php printf( esc_html__( '%s opens the first Bigham\'s Smokehouse in Lubbock.', 'bighams' ), esc_html( $founder ) ); ?></span></li>
				<li><strong><?php esc_html_e( 'Today', 'bighams' ); ?></strong><span><?php printf( esc_html__( '%1$s locations, run by %2$s.', 'bighams' ), esc_html( ucfirst( bht_location_count_word() ) ), esc_html( $owners ) ); ?></span></li>
				<li><strong><?php esc_html_e( 'Every day', 'bighams' ); ?></strong><span><?php esc_html_e( 'Brisket, ribs, sausage, turkey and pulled pork smoked fresh — plus catering for groups of 10 to 500+.', 'bighams' ); ?></span></li>
			</ol>
		</div>
	</section>

	<section class="section section--dark" aria-labelledby="values-title">
		<div class="container">
			<div class="section-head section-head--center"><?php bht_heading( 'values-title', __( 'How We Do Barbecue', 'bighams' ), __( 'The Bigham\'s way', 'bighams' ) ); ?></div>
			<div class="feature-grid">
				<article class="feature"><?php echo bht_icon( 'flame' ); // phpcs:ignore ?><h3><?php esc_html_e( 'Low & Slow', 'bighams' ); ?></h3><p><?php esc_html_e( 'Meats are smoked for hours and sliced or pulled by hand to order.', 'bighams' ); ?></p></article>
				<article class="feature"><?php echo bht_icon( 'star' ); // phpcs:ignore ?><h3><?php esc_html_e( 'Scratch-Made Sides', 'bighams' ); ?></h3><p><?php esc_html_e( 'Pinto beans, green beans with smoked bacon, white cheddar mac, cobblers and banana pudding.', 'bighams' ); ?></p></article>
				<article class="feature"><?php echo bht_icon( 'users' ); // phpcs:ignore ?><h3><?php esc_html_e( 'Family Hospitality', 'bighams' ); ?></h3><p><?php esc_html_e( 'Still family-owned, still local — and we still know a lot of our regulars by name.', 'bighams' ); ?></p></article>
			</div>
		</div>
	</section>

	<?php
	get_template_part( 'template-parts/cta-band' );
endwhile;

get_footer();
