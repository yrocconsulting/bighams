<?php
/**
 * Default page.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-hero', null, array( 'summary' => has_excerpt() ? get_the_excerpt() : '' ) );
	?>
	<article <?php post_class( 'section section--paper' ); ?>>
		<div class="container container--narrow entry-content">
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
