<?php
/**
 * Template Name: Private Parties
 *
 * Auto-applies to the page with slug "parties".
 */

defined( 'ABSPATH' ) || exit;
get_header();
$phone = bht_setting( 'phone' );

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow'   => __( 'Private events in Lubbock', 'bighams' ),
			'summary'   => __( 'Host your birthday, rehearsal dinner, team banquet or holiday party at Bigham\'s Smokehouse. Our private dining room at 82nd Street seats up to 20 guests, and every main dining room can be rented for larger groups with 7 days notice.', 'bighams' ),
			'actions'   => '<a class="btn btn--primary btn--lg" href="#inquiry">' . esc_html__( 'Book your party', 'bighams' ) . '</a><a class="btn btn--outline-light btn--lg" href="tel:' . esc_attr( bht_tel( $phone ) ) . '">' . esc_html( $phone ) . '</a>',
			'image_mod' => 'bh_parties_image',
		)
	);
	?>

	<section class="section section--paper" aria-labelledby="options-title">
		<div class="container">
			<div class="section-head section-head--center"><?php bht_heading( 'options-title', __( 'Choose Your Space', 'bighams' ), __( 'Party options', 'bighams' ) ); ?></div>
			<div class="feature-grid">
				<article class="feature card">
					<?php echo bht_icon( 'users' ); // phpcs:ignore ?>
					<h3><?php esc_html_e( 'Private Dining Room', 'bighams' ); ?></h3>
					<p class="feature__meta"><?php esc_html_e( '82nd Street · up to 20 guests', 'bighams' ); ?></p>
					<p><?php esc_html_e( 'An intimate room for family celebrations, office lunches and showers — with the full Bigham\'s menu.', 'bighams' ); ?></p>
				</article>
				<article class="feature card">
					<?php echo bht_icon( 'flame' ); // phpcs:ignore ?>
					<h3><?php esc_html_e( 'Full Dining Room Buyout', 'bighams' ); ?></h3>
					<p class="feature__meta"><?php esc_html_e( 'Any location · 7 days notice', 'bighams' ); ?></p>
					<p><?php esc_html_e( 'Rent out an entire dining room for rehearsal dinners, banquets, reunions and company parties.', 'bighams' ); ?></p>
				</article>
				<article class="feature card">
					<?php echo bht_icon( 'truck' ); // phpcs:ignore ?>
					<h3><?php esc_html_e( 'Bring the Party to You', 'bighams' ); ?></h3>
					<p class="feature__meta"><?php esc_html_e( 'Catering · 10 to 500+ guests', 'bighams' ); ?></p>
					<p><?php esc_html_e( 'Hosting at home, the office or a venue? Our catering team delivers or caters and serves.', 'bighams' ); ?> <a href="<?php echo esc_url( home_url( '/catering/' ) ); ?>"><?php esc_html_e( 'See catering', 'bighams' ); ?></a></p>
				</article>
			</div>
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content container--narrow" style="margin-top:3rem"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section inquiry" aria-labelledby="inquiry-title">
		<div class="container inquiry__grid">
			<div class="inquiry__copy">
				<?php bht_heading( 'inquiry-title', __( 'Private Event Inquiry', 'bighams' ), __( 'Let\'s celebrate', 'bighams' ) ); ?>
				<p class="lead"><?php esc_html_e( 'Send us your date and headcount and we\'ll confirm availability and menu options.', 'bighams' ); ?></p>
				<p class="inquiry__call"><?php esc_html_e( 'Or call', 'bighams' ); ?> <a href="tel:<?php echo esc_attr( bht_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
			</div>
			<div class="inquiry__form card"><?php bht_inquiry_form( array( 'type' => 'party', 'id' => 'inquiry' ) ); ?></div>
		</div>
	</section>

	<?php get_template_part( 'template-parts/faq-list', null, array( 'topic' => 'parties', 'title' => __( 'Private Event FAQ', 'bighams' ), 'class' => 'section--paper' ) ); ?>

	<?php
	get_template_part( 'template-parts/cta-band' );
endwhile;

get_footer();
