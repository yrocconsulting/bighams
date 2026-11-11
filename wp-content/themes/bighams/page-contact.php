<?php
/**
 * Template Name: Contact
 *
 * Auto-applies to the page with slug "contact".
 */

defined( 'ABSPATH' ) || exit;
get_header();
$phone = bht_setting( 'phone' );
$email = bht_setting( 'email' );

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => __( 'Get in touch', 'bighams' ),
			'summary' => sprintf( __( 'Call Bigham\'s Smokehouse at %1$s or email %2$s. For catering and private events, use the inquiry form and our office will reply within one business day.', 'bighams' ), $phone, $email ),
		)
	);
	?>
	<section class="section section--paper" aria-labelledby="contact-title">
		<div class="container contact__grid">
			<div>
				<?php bht_heading( 'contact-title', __( 'Contact Us', 'bighams' ) ); ?>
				<ul class="contact__list">
					<li><?php echo bht_icon( 'phone' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Main office & catering', 'bighams' ); ?></strong><a href="tel:<?php echo esc_attr( bht_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></div></li>
					<li><?php echo bht_icon( 'mail' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Email', 'bighams' ); ?></strong><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div></li>
					<?php if ( bht_setting( 'jobs_url' ) ) : ?>
						<li><?php echo bht_icon( 'users' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Careers', 'bighams' ); ?></strong><a href="<?php echo esc_url( bht_setting( 'jobs_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Apply to join the team', 'bighams' ); ?></a></div></li>
					<?php endif; ?>
				</ul>
				<?php if ( get_the_content() ) : ?>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
			<div class="card">
				<h2 class="h3"><?php esc_html_e( 'Planning an event?', 'bighams' ); ?></h2>
				<?php bht_inquiry_form( array( 'type' => 'catering', 'id' => 'inquiry' ) ); ?>
			</div>
		</div>
	</section>

	<?php $locs = bht_locations(); ?>
	<?php if ( $locs ) : ?>
		<section class="section" aria-labelledby="contact-locs-title">
			<div class="container">
				<?php bht_heading( 'contact-locs-title', __( 'Our Locations', 'bighams' ) ); ?>
				<div class="loc-grid">
					<?php foreach ( $locs as $loc ) : ?>
						<?php get_template_part( 'template-parts/location-card', null, array( 'loc' => $loc ) ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
