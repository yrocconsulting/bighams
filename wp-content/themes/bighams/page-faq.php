<?php
/**
 * Template Name: FAQ
 *
 * Auto-applies to the page with slug "faq". Shows all FAQs.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => __( 'Help & info', 'bighams' ),
			'summary' => __( 'Answers to the most common questions about Bigham\'s Smokehouse — hours, locations, ordering, catering, private events and our menu.', 'bighams' ),
		)
	);
	if ( get_the_content() ) :
		?>
		<section class="section section--paper"><div class="container container--narrow entry-content"><?php the_content(); ?></div></section>
		<?php
	endif;
	get_template_part(
		'template-parts/faq-list',
		null,
		array(
			'topic'      => '',
			'title'      => __( 'All Questions', 'bighams' ),
			'class'      => 'section--paper',
			'open_first' => true,
		)
	);
	get_template_part( 'template-parts/cta-band', null, array( 'title' => __( 'Still Have a Question?', 'bighams' ), 'text' => __( 'Give us a call — we\'re happy to help.', 'bighams' ) ) );
endwhile;

get_footer();
