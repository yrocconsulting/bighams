<?php
/**
 * "Join the Pitmaster's Club" — Toast loyalty program (one program, all locations).
 */

defined( 'ABSPATH' ) || exit;
$url = bht_setting( 'loyalty_url' );
if ( ! $url ) {
	return;
}
$perks = array(
	array( 'gift', __( 'A birthday gift', 'bighams' ), __( 'A treat from us to celebrate your birthday.', 'bighams' ) ),
	array( 'mail', __( 'Insider info', 'bighams' ), __( 'Be in the know on specials and what\'s new at Bigham\'s.', 'bighams' ) ),
	array( 'flame', __( 'Loyalty rewards', 'bighams' ), __( 'Every dollar you spend earns you BBQ back.', 'bighams' ) ),
);
?>
<section class="section loyalty" id="pitmasters-club" aria-labelledby="loyalty-title">
	<div class="container loyalty__inner">
		<div class="loyalty__copy">
			<?php bht_heading( 'loyalty-title', __( "Join the Pitmaster's Club", 'bighams' ), __( 'Rewards', 'bighams' ) ); ?>
			<p class="lead"><?php esc_html_e( 'One rewards program across all three Bigham\'s locations.', 'bighams' ); ?></p>
			<div class="btn-row">
				<a class="btn btn--primary btn--lg" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"<?php echo bht_track( 'loyalty' ); // phpcs:ignore ?>><?php echo bht_icon( 'star' ); // phpcs:ignore ?> <?php esc_html_e( "Join the Pitmaster's Club", 'bighams' ); ?><span class="screen-reader-text"> (<?php esc_html_e( 'opens Toast in a new tab', 'bighams' ); ?>)</span></a>
			</div>
		</div>
		<ul class="loyalty__perks">
			<?php foreach ( $perks as $p ) : ?>
				<li class="perk">
					<span class="perk__icon" aria-hidden="true"><?php echo bht_icon( $p[0] ); // phpcs:ignore ?></span>
					<span class="perk__text"><strong><?php echo esc_html( $p[1] ); ?></strong><span><?php echo esc_html( $p[2] ); ?></span></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
