<?php
/**
 * Single post (news / blog).
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'eyebrow' => get_the_date(),
			'summary' => has_excerpt() ? get_the_excerpt() : '',
		)
	);
	?>
	<article <?php post_class( 'section section--paper' ); ?>>
		<div class="container container--narrow">
			<div class="entry-content"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>
			<footer class="entry-meta">
				<?php the_tags( '<p class="tags-line">' . esc_html__( 'Tagged: ', 'bighams' ), ', ', '</p>' ); ?>
				<?php the_post_navigation( array( 'prev_text' => '← %title', 'next_text' => '%title →' ) ); ?>
			</footer>
		</div>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		echo '<div class="container container--narrow">';
		comments_template();
		echo '</div>';
	}
endwhile;

get_template_part( 'template-parts/cta-band' );
get_footer();
