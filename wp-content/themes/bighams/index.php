<?php
/**
 * Fallback + blog index.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$title = is_home() && get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : __( 'News', 'bighams' );
if ( is_archive() ) {
	$title = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_search() ) {
	/* translators: %s search query */
	$title = sprintf( __( 'Search results for “%s”', 'bighams' ), get_search_query() );
}
get_template_part( 'template-parts/page-hero', null, array( 'title' => $title, 'summary' => is_archive() ? wp_strip_all_tags( get_the_archive_description() ) : '' ) );
?>
<section class="section section--paper">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'post-card card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" class="post-card__media" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'bh-card', array( 'loading' => 'lazy' ) ); ?></a>
						<?php endif; ?>
						<div class="post-card__body">
							<p class="eyebrow"><?php echo esc_html( get_the_date() ); ?></p>
							<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found. Try a search or head back to the menu.', 'bighams' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
