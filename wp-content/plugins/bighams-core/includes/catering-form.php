<?php
/**
 * Catering + private event inquiry form → bh_lead posts, email alert,
 * optional CRM webhook (e.g. GoHighLevel inbound webhook).
 *
 * Usage in templates:  bh_catering_form( array( 'type' => 'catering' ) );
 * Shortcode:           [bighams_inquiry type="party"]
 */

defined( 'ABSPATH' ) || exit;

function bh_lead_statuses() {
	return array(
		'new'       => 'New',
		'contacted' => 'Contacted',
		'quoted'    => 'Quote sent',
		'booked'    => 'Booked',
		'completed' => 'Completed',
		'lost'      => 'Lost',
	);
}

function bh_lead_fields() {
	return array(
		'type'       => 'Inquiry type',
		'name'       => 'Name',
		'email'      => 'Email',
		'phone'      => 'Phone',
		'company'    => 'Company / organization',
		'event_type' => 'Event type',
		'event_date' => 'Event date',
		'event_time' => 'Serve time',
		'guests'     => 'Guest count',
		'service'    => 'Service style',
		'location'   => 'Preferred location',
		'package'    => 'Package of interest',
		'message'    => 'Details',
	);
}

function bh_event_types() {
	return array( 'Corporate / office lunch', 'Wedding / rehearsal dinner', 'Birthday / family party', 'Holiday party', 'School / church / team event', 'Tailgate', 'Other' );
}

function bh_service_styles() {
	return array(
		'pickup'   => 'Pick up',
		'delivery' => 'Delivery (fee applies)',
		'full'     => 'Full service — catered & served',
	);
}

/**
 * Render the inquiry form.
 */
function bh_catering_form( $args = array() ) {
	$args  = wp_parse_args( $args, array( 'type' => 'catering', 'id' => 'inquiry' ) );
	$party = 'party' === $args['type'];
	$sent  = isset( $_GET['inquiry'] ) && 'sent' === $_GET['inquiry']; // phpcs:ignore WordPress.Security.NonceVerification
	$err   = isset( $_GET['inquiry'] ) && 'error' === $_GET['inquiry']; // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	?>
	<form class="bh-form" id="<?php echo esc_attr( $args['id'] ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-bh-form="<?php echo esc_attr( $args['type'] ); ?>">
		<?php if ( $sent ) : ?>
			<div class="bh-form__notice bh-form__notice--ok" role="status">
				<strong>Thanks — we got it!</strong> Our catering team will reach out within one business day. Need it sooner? Call <a href="tel:<?php echo esc_attr( bh_phone_e164( bh_setting( 'catering_phone' ) ) ); ?>"><?php echo esc_html( bh_setting( 'catering_phone' ) ); ?></a>.
			</div>
		<?php elseif ( $err ) : ?>
			<div class="bh-form__notice bh-form__notice--err" role="alert">Something was missing. Please add your name, email or phone, and event date.</div>
		<?php endif; ?>
		<input type="hidden" name="action" value="bh_inquiry">
		<input type="hidden" name="bh_type" value="<?php echo esc_attr( $args['type'] ); ?>">
		<input type="hidden" name="bh_source" value="<?php echo esc_attr( esc_url_raw( home_url( add_query_arg( array() ) ) ) ); ?>">
		<?php wp_nonce_field( 'bh_inquiry', 'bh_nonce' ); ?>
		<div class="bh-hp" aria-hidden="true"><label>Leave blank<input type="text" name="bh_website" tabindex="-1" autocomplete="off"></label></div>

		<div class="bh-form__grid">
			<p class="bh-field"><label for="bh-name">Your name *</label><input id="bh-name" name="bh_name" type="text" autocomplete="name" required></p>
			<p class="bh-field"><label for="bh-company">Company / organization</label><input id="bh-company" name="bh_company" type="text" autocomplete="organization"></p>
			<p class="bh-field"><label for="bh-email">Email *</label><input id="bh-email" name="bh_email" type="email" autocomplete="email" required></p>
			<p class="bh-field"><label for="bh-phone">Phone *</label><input id="bh-phone" name="bh_phone" type="tel" autocomplete="tel" required></p>
			<p class="bh-field"><label for="bh-event-type">Event type</label>
				<select id="bh-event-type" name="bh_event_type">
					<?php foreach ( bh_event_types() as $t ) : ?>
						<option><?php echo esc_html( $t ); ?></option>
					<?php endforeach; ?>
				</select></p>
			<p class="bh-field"><label for="bh-guests">Guest count *</label><input id="bh-guests" name="bh_guests" type="number" min="1" inputmode="numeric" required></p>
			<p class="bh-field"><label for="bh-date">Event date *</label><input id="bh-date" name="bh_event_date" type="date" required min="<?php echo esc_attr( bh_now()->modify( '+1 day' )->format( 'Y-m-d' ) ); ?>"></p>
			<p class="bh-field"><label for="bh-time">Serve time</label><input id="bh-time" name="bh_event_time" type="time"></p>
			<?php if ( ! $party ) : ?>
				<fieldset class="bh-field bh-field--full"><legend>Service style</legend>
					<div class="bh-choices">
						<?php foreach ( bh_service_styles() as $k => $label ) : ?>
							<label class="bh-choice"><input type="radio" name="bh_service" value="<?php echo esc_attr( $k ); ?>" <?php checked( 'pickup', $k ); ?>> <span><?php echo esc_html( $label ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<p class="bh-field"><label for="bh-package">Package of interest</label>
					<select id="bh-package" name="bh_package">
						<option value="">Not sure yet — help me choose</option>
						<?php foreach ( bh_get_packages() as $p ) : ?>
							<option><?php echo esc_html( $p['title'] ); ?></option>
						<?php endforeach; ?>
						<option>A la carte / custom</option>
					</select></p>
			<?php endif; ?>
			<p class="bh-field"><label for="bh-location"><?php echo $party ? 'Which dining room?' : 'Closest location'; ?></label>
				<select id="bh-location" name="bh_location">
					<option value="">No preference</option>
					<?php foreach ( bh_get_locations() as $loc ) : ?>
						<option value="<?php echo esc_attr( $loc['id'] ); ?>"><?php echo esc_html( $loc['title'] ); ?></option>
					<?php endforeach; ?>
				</select></p>
			<p class="bh-field bh-field--full"><label for="bh-message">Tell us about your event</label><textarea id="bh-message" name="bh_message" rows="4" placeholder="<?php echo esc_attr( $party ? 'Occasion, preferred time, menu ideas…' : 'Venue address, meats you love, dietary needs, setup details…' ); ?>"></textarea></p>
		</div>
		<p class="bh-form__fine">Catering orders need 24 hours notice; 200+ guests or a full restaurant buyout need 7 days.</p>
		<button class="btn btn--primary btn--lg" type="submit"><?php echo $party ? 'Request my party' : 'Get my catering quote'; ?></button>
	</form>
	<?php
	return ob_get_clean();
}

add_shortcode(
	'bighams_inquiry',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'type' => 'catering' ), $atts );
		return bh_catering_form( array( 'type' => 'party' === $atts['type'] ? 'party' : 'catering' ) );
	}
);

add_action( 'admin_post_nopriv_bh_inquiry', 'bh_handle_inquiry' );
add_action( 'admin_post_bh_inquiry', 'bh_handle_inquiry' );

function bh_handle_inquiry() {
	$back = wp_get_referer() ?: home_url( '/catering/' );
	$back = remove_query_arg( 'inquiry', $back );

	if ( ! isset( $_POST['bh_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bh_nonce'] ) ), 'bh_inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'error', $back ) . '#inquiry' );
		exit;
	}
	// Honeypot: pretend success for bots.
	if ( ! empty( $_POST['bh_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'sent', $back ) . '#inquiry' );
		exit;
	}
	// Basic rate limit: 5 per hour per IP.
	$ip_key = 'bh_rl_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$hits   = (int) get_transient( $ip_key );
	if ( $hits >= 5 ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'error', $back ) . '#inquiry' );
		exit;
	}
	set_transient( $ip_key, $hits + 1, HOUR_IN_SECONDS );

	$p    = wp_unslash( $_POST );
	$data = array(
		'type'       => 'party' === ( $p['bh_type'] ?? '' ) ? 'Private event' : 'Catering',
		'name'       => sanitize_text_field( $p['bh_name'] ?? '' ),
		'email'      => sanitize_email( $p['bh_email'] ?? '' ),
		'phone'      => sanitize_text_field( $p['bh_phone'] ?? '' ),
		'company'    => sanitize_text_field( $p['bh_company'] ?? '' ),
		'event_type' => sanitize_text_field( $p['bh_event_type'] ?? '' ),
		'event_date' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $p['bh_event_date'] ?? '' ) ? $p['bh_event_date'] : '',
		'event_time' => preg_match( '/^\d{2}:\d{2}$/', $p['bh_event_time'] ?? '' ) ? $p['bh_event_time'] : '',
		'guests'     => absint( $p['bh_guests'] ?? 0 ),
		'service'    => array_key_exists( $p['bh_service'] ?? '', bh_service_styles() ) ? bh_service_styles()[ $p['bh_service'] ] : '',
		'location'   => absint( $p['bh_location'] ?? 0 ),
		'package'    => sanitize_text_field( $p['bh_package'] ?? '' ),
		'message'    => sanitize_textarea_field( $p['bh_message'] ?? '' ),
	);
	if ( ! $data['name'] || ( ! is_email( $data['email'] ) && ! $data['phone'] ) || ! $data['event_date'] ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'error', $back ) . '#inquiry' );
		exit;
	}

	$title   = sprintf( '%s — %s, %d guests, %s', $data['type'], $data['name'], $data['guests'], $data['event_date'] );
	$lead_id = wp_insert_post(
		array(
			'post_type'   => 'bh_lead',
			'post_status' => 'publish',
			'post_title'  => $title,
		)
	);
	if ( $lead_id && ! is_wp_error( $lead_id ) ) {
		foreach ( $data as $k => $v ) {
			update_post_meta( $lead_id, '_bh_' . $k, $v );
		}
		update_post_meta( $lead_id, '_bh_status', 'new' );
		update_post_meta( $lead_id, '_bh_source', esc_url_raw( $p['bh_source'] ?? '' ) );
		// Rough estimate for pipeline value: guests × average package price.
		update_post_meta( $lead_id, '_bh_estimate', round( $data['guests'] * (float) apply_filters( 'bh_avg_catering_pp', 16 ), 2 ) );
		bh_track_event( 'lead_' . ( 'Catering' === $data['type'] ? 'catering' : 'party' ), $data['location'] );
		bh_notify_lead( $lead_id, $data );
		do_action( 'bh_lead_created', $lead_id, $data );
	}

	wp_safe_redirect( add_query_arg( 'inquiry', 'sent', $back ) . '#inquiry' );
	exit;
}

function bh_notify_lead( $lead_id, $data ) {
	$to = array_filter( array_map( 'trim', explode( ',', bh_setting( 'catering_notify', bh_setting( 'catering_email', get_option( 'admin_email' ) ) ) ) ) );
	$lines = array();
	foreach ( bh_lead_fields() as $k => $label ) {
		$v = $data[ $k ] ?? '';
		if ( 'location' === $k ) {
			$v = $v ? get_the_title( $v ) : 'No preference';
		}
		$lines[] = $label . ': ' . $v;
	}
	$lines[] = '';
	$lines[] = 'Open lead: ' . admin_url( 'post.php?post=' . $lead_id . '&action=edit' );
	$headers = array();
	if ( is_email( $data['email'] ) ) {
		$headers[] = 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>';
	}
	wp_mail( $to, '[Bigham\'s] New ' . $data['type'] . ' inquiry: ' . $data['name'] . ' (' . $data['guests'] . ' guests)', implode( "\n", $lines ), $headers );

	$hook = bh_setting( 'lead_webhook_url' );
	if ( $hook ) {
		$payload             = $data;
		$payload['location'] = $data['location'] ? get_the_title( $data['location'] ) : '';
		$payload['lead_id']  = $lead_id;
		wp_remote_post(
			$hook,
			array(
				'timeout'  => 5,
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'body'     => wp_json_encode( $payload ),
			)
		);
	}
}

/* Leads list columns. */
add_filter( 'manage_bh_lead_posts_columns', function ( $cols ) {
	return array(
		'cb'         => $cols['cb'],
		'title'      => 'Inquiry',
		'bh_status'  => 'Status',
		'bh_event'   => 'Event date',
		'bh_guests'  => 'Guests',
		'bh_value'   => 'Value',
		'date'       => 'Received',
	);
} );
add_action( 'manage_bh_lead_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'bh_status':
			$s = get_post_meta( $id, '_bh_status', true ) ?: 'new';
			echo '<span class="bh-pill bh-pill--' . esc_attr( $s ) . '">' . esc_html( bh_lead_statuses()[ $s ] ?? $s ) . '</span>';
			break;
		case 'bh_event':
			echo esc_html( get_post_meta( $id, '_bh_event_date', true ) );
			break;
		case 'bh_guests':
			echo esc_html( get_post_meta( $id, '_bh_guests', true ) );
			break;
		case 'bh_value':
			$q = (float) get_post_meta( $id, '_bh_quote', true );
			echo $q ? esc_html( bh_money( $q ) ) : '<span style="color:#888">~' . esc_html( bh_money( get_post_meta( $id, '_bh_estimate', true ) ) ) . '</span>';
			break;
	}
}, 10, 2 );
