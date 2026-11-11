<?php
/**
 * FAQ accordion section. $args: topic, title, eyebrow, id, class.
 * Silent when there are no FAQs for the topic.
 */

defined( 'ABSPATH' ) || exit;
$topic = $args['topic'] ?? '';
$faqs  = bht_faqs( $topic );
if ( ! $faqs ) {
	return;
}
$id    = $args['id'] ?? 'faq-' . ( $topic ?: 'all' );
$title = $args['title'] ?? __( 'Frequently Asked Questions', 'bighams' );
?>
<section class="section faq <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>">
	<div class="container container--narrow">
		<?php bht_heading( $id, $title, $args['eyebrow'] ?? __( 'Good to know', 'bighams' ) ); ?>
		<div class="faq__list">
			<?php foreach ( $faqs as $i => $faq ) : ?>
				<details class="faq__item"<?php echo 0 === $i && ! empty( $args['open_first'] ) ? ' open' : ''; ?>>
					<summary><h3 class="faq__q"><?php echo esc_html( $faq['question'] ); ?></h3><span class="faq__icon" aria-hidden="true"></span></summary>
					<div class="faq__a"><?php echo wp_kses_post( $faq['answer'] ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
