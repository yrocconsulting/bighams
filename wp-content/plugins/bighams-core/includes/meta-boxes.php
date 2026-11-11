<?php
/**
 * Plain-English edit screens for every content type. No ACF dependency:
 * staff edit simple fields, the plugin turns them into schema + templates.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions per post type.
 */
function bh_meta_fields() {
	return array(
		'bh_location' => array(
			'title'  => 'Location Details',
			'fields' => array(
				'short_name'   => array( 'label' => 'Short name', 'help' => 'Used on buttons, e.g. "82nd St".' ),
				'street'       => array( 'label' => 'Street address' ),
				'city'         => array( 'label' => 'City', 'default' => 'Lubbock' ),
				'state'        => array( 'label' => 'State', 'default' => 'TX' ),
				'zip'          => array( 'label' => 'ZIP' ),
				'cross_street' => array( 'label' => 'Cross streets / landmark', 'help' => 'e.g. "82nd St & Indiana Ave". Helps people and AI search find you.' ),
				'phone'        => array( 'label' => 'Phone' ),
				'lat'          => array( 'label' => 'Latitude', 'help' => 'Right-click the pin in Google Maps to copy coordinates.' ),
				'lng'          => array( 'label' => 'Longitude' ),
				'maps_url'     => array( 'label' => 'Google Business Profile / Maps URL', 'type' => 'url' ),
				'order_url'    => array( 'label' => 'Toast online ordering URL', 'type' => 'url' ),
				'catering_url' => array( 'label' => 'Toast catering ordering URL', 'type' => 'url' ),
				'ubereats_url' => array( 'label' => 'Uber Eats URL', 'type' => 'url' ),
				'doordash_url' => array( 'label' => 'DoorDash URL', 'type' => 'url' ),
				'toast_guid'   => array( 'label' => 'Toast Restaurant GUID', 'help' => 'From Toast Web → Integrations. Connects sales stats to this location.' ),
				'features'     => array( 'label' => 'Amenities (one per line)', 'type' => 'textarea', 'help' => 'e.g. Drive-thru, Private dining room (up to 20), Happy hour 3–6pm, Patio.' ),
				'happy_hour'   => array( 'label' => 'Happy hour', 'help' => 'e.g. "Every day 3–6pm". Leave blank if none.' ),
				'hours_note'   => array( 'label' => 'Hours note', 'help' => 'Holiday closures etc. Shown under hours.' ),
			),
		),
		'bh_menu_item' => array(
			'title'  => 'Menu Item Details',
			'fields' => array(
				'prices'   => array( 'label' => 'Prices (one per line)', 'type' => 'textarea', 'help' => 'Single price: 13.95  —  Multiple sizes: "Single | 2.49" then "Pint | 8.49". Add a note with a third column: "Pound | 29.95 | about 3 servings".' ),
				'unit'     => array( 'label' => 'Unit (optional)', 'help' => 'e.g. "per pound", "per person". Shown after a single price.' ),
				'badges'   => array( 'label' => 'Badges', 'help' => 'Comma separated: Popular, Spicy, Sunday Only, New.' ),
				'featured' => array( 'label' => 'Feature on homepage', 'type' => 'checkbox' ),
				'diet'     => array( 'label' => 'Dietary tags', 'help' => 'Comma separated: Gluten Free, Vegetarian.' ),
				'toast_guid' => array( 'label' => 'Toast item GUID', 'help' => 'Filled automatically by Toast menu sync.' ),
			),
		),
		'bh_catering_pkg' => array(
			'title'  => 'Package Details',
			'fields' => array(
				'tagline'  => array( 'label' => 'Tagline', 'help' => 'e.g. "Our Most Popular Package!"' ),
				'price'    => array( 'label' => 'Starting price', 'help' => 'Number only, e.g. 17.95' ),
				'unit'     => array( 'label' => 'Price unit', 'default' => 'per person' ),
				'minimum'  => array( 'label' => 'Minimum / serving note', 'help' => 'e.g. "Minimum 10 guests, in increments of 5"' ),
				'min_guests' => array( 'label' => 'Minimum guests (number)', 'type' => 'number' ),
				'includes' => array( 'label' => 'What\'s included (one per line)', 'type' => 'textarea' ),
				'addons'   => array( 'label' => 'Upgrades & add-ons (one per line)', 'type' => 'textarea', 'help' => '"Banana Pudding | 2.50 | per person"' ),
				'featured' => array( 'label' => 'Highlight this package', 'type' => 'checkbox' ),
			),
		),
		'bh_review' => array(
			'title'  => 'Review Details',
			'fields' => array(
				'author' => array( 'label' => 'Reviewer name' ),
				'source' => array( 'label' => 'Source', 'default' => 'Google' ),
				'rating' => array( 'label' => 'Rating (1–5)', 'type' => 'number', 'default' => '5' ),
				'url'    => array( 'label' => 'Link to review', 'type' => 'url' ),
			),
		),
	);
}

add_action( 'add_meta_boxes', function () {
	foreach ( bh_meta_fields() as $post_type => $box ) {
		add_meta_box( 'bh_meta_' . $post_type, $box['title'], 'bh_render_meta_box', $post_type, 'normal', 'high', $box );
	}
	add_meta_box( 'bh_hours', 'Opening Hours', 'bh_render_hours_box', 'bh_location', 'normal', 'high' );
	add_meta_box( 'bh_lead_details', 'Inquiry', 'bh_render_lead_box', 'bh_lead', 'normal', 'high' );
	add_meta_box( 'bh_lead_status', 'Pipeline', 'bh_render_lead_status_box', 'bh_lead', 'side', 'high' );
} );

function bh_render_meta_box( $post, $args ) {
	wp_nonce_field( 'bh_meta_save', 'bh_meta_nonce' );
	echo '<table class="form-table bh-meta"><tbody>';
	foreach ( $args['args']['fields'] as $key => $f ) {
		$type  = $f['type'] ?? 'text';
		$value = get_post_meta( $post->ID, '_bh_' . $key, true );
		if ( '' === $value && isset( $f['default'] ) && 'auto-draft' === $post->post_status ) {
			$value = $f['default'];
		}
		$id = 'bh_' . $key;
		echo '<tr><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label></th><td>';
		switch ( $type ) {
			case 'textarea':
				printf( '<textarea class="large-text" rows="5" id="%1$s" name="%1$s">%2$s</textarea>', esc_attr( $id ), esc_textarea( $value ) );
				break;
			case 'checkbox':
				printf( '<input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s>', esc_attr( $id ), checked( $value, '1', false ) );
				break;
			default:
				printf( '<input class="regular-text" type="%3$s" id="%1$s" name="%1$s" value="%2$s"%4$s>', esc_attr( $id ), esc_attr( $value ), esc_attr( $type ), 'number' === $type ? ' step="any"' : '' );
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="description">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

function bh_days() {
	return array(
		'mon' => 'Monday',
		'tue' => 'Tuesday',
		'wed' => 'Wednesday',
		'thu' => 'Thursday',
		'fri' => 'Friday',
		'sat' => 'Saturday',
		'sun' => 'Sunday',
	);
}

function bh_render_hours_box( $post ) {
	$hours = get_post_meta( $post->ID, '_bh_hours', true );
	$hours = is_array( $hours ) ? $hours : array();
	echo '<table class="widefat striped" style="max-width:560px"><thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed</th></tr></thead><tbody>';
	foreach ( bh_days() as $k => $label ) {
		$d = wp_parse_args( $hours[ $k ] ?? array(), array( 'open' => '11:00', 'close' => '20:00', 'closed' => '' ) );
		printf(
			'<tr><td>%1$s</td><td><input type="time" name="bh_hours[%2$s][open]" value="%3$s"></td><td><input type="time" name="bh_hours[%2$s][close]" value="%4$s"></td><td><input type="checkbox" name="bh_hours[%2$s][closed]" value="1"%5$s></td></tr>',
			esc_html( $label ),
			esc_attr( $k ),
			esc_attr( $d['open'] ),
			esc_attr( $d['close'] ),
			checked( $d['closed'], '1', false )
		);
	}
	echo '</tbody></table><p class="description">Hours feed the "Open now" badge, Google-ready schema and the site footer automatically.</p>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['bh_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bh_meta_nonce'] ) ), 'bh_meta_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$defs = bh_meta_fields();
	if ( isset( $defs[ $post->post_type ] ) ) {
		foreach ( $defs[ $post->post_type ]['fields'] as $key => $f ) {
			$type = $f['type'] ?? 'text';
			$raw  = isset( $_POST[ 'bh_' . $key ] ) ? wp_unslash( $_POST[ 'bh_' . $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			switch ( $type ) {
				case 'textarea':
					$val = sanitize_textarea_field( $raw );
					break;
				case 'url':
					$val = esc_url_raw( $raw );
					break;
				case 'checkbox':
					$val = $raw ? '1' : '';
					break;
				default:
					$val = sanitize_text_field( $raw );
			}
			update_post_meta( $post_id, '_bh_' . $key, $val );
		}
	}
	if ( 'bh_location' === $post->post_type && isset( $_POST['bh_hours'] ) && is_array( $_POST['bh_hours'] ) ) {
		$clean = array();
		foreach ( bh_days() as $k => $label ) {
			$d           = (array) ( $_POST['bh_hours'][ $k ] ?? array() ); // phpcs:ignore
			$clean[ $k ] = array(
				'open'   => preg_match( '/^\d{2}:\d{2}$/', $d['open'] ?? '' ) ? $d['open'] : '',
				'close'  => preg_match( '/^\d{2}:\d{2}$/', $d['close'] ?? '' ) ? $d['close'] : '',
				'closed' => empty( $d['closed'] ) ? '' : '1',
			);
		}
		update_post_meta( $post_id, '_bh_hours', $clean );
	}
	if ( 'bh_lead' === $post->post_type ) {
		$status = sanitize_key( wp_unslash( $_POST['bh_lead_status'] ?? 'new' ) ); // phpcs:ignore
		if ( array_key_exists( $status, bh_lead_statuses() ) ) {
			update_post_meta( $post_id, '_bh_status', $status );
		}
		update_post_meta( $post_id, '_bh_quote', (float) ( $_POST['bh_lead_quote'] ?? 0 ) ); // phpcs:ignore
		update_post_meta( $post_id, '_bh_notes', sanitize_textarea_field( wp_unslash( $_POST['bh_lead_notes'] ?? '' ) ) ); // phpcs:ignore
	}
	delete_transient( 'bh_llms_txt' );
}, 10, 2 );

function bh_render_lead_box( $post ) {
	wp_nonce_field( 'bh_meta_save', 'bh_meta_nonce' );
	$fields = bh_lead_fields();
	echo '<table class="form-table"><tbody>';
	foreach ( $fields as $key => $label ) {
		$val = get_post_meta( $post->ID, '_bh_' . $key, true );
		if ( 'location' === $key && $val ) {
			$val = get_the_title( (int) $val );
		}
		if ( 'email' === $key && $val ) {
			$val = '<a href="mailto:' . esc_attr( $val ) . '">' . esc_html( $val ) . '</a>';
		} elseif ( 'phone' === $key && $val ) {
			$val = '<a href="tel:' . esc_attr( bh_phone_e164( $val ) ) . '">' . esc_html( $val ) . '</a>';
		} else {
			$val = nl2br( esc_html( $val ) );
		}
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . wp_kses_post( $val ) . '</td></tr>';
	}
	$src = get_post_meta( $post->ID, '_bh_source', true );
	echo '<tr><th>Submitted from</th><td>' . esc_html( $src ) . '</td></tr>';
	echo '</tbody></table>';
}

function bh_render_lead_status_box( $post ) {
	$status = get_post_meta( $post->ID, '_bh_status', true ) ?: 'new';
	$quote  = get_post_meta( $post->ID, '_bh_quote', true );
	$notes  = get_post_meta( $post->ID, '_bh_notes', true );
	echo '<p><label><strong>Status</strong><br><select name="bh_lead_status" style="width:100%">';
	foreach ( bh_lead_statuses() as $k => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $status, $k, false ), esc_html( $label ) );
	}
	echo '</select></label></p>';
	printf( '<p><label><strong>Quoted / booked value ($)</strong><br><input type="number" step="0.01" name="bh_lead_quote" value="%s" style="width:100%%"></label></p>', esc_attr( $quote ) );
	printf( '<p><label><strong>Internal notes</strong><br><textarea name="bh_lead_notes" rows="5" style="width:100%%">%s</textarea></label></p>', esc_textarea( $notes ) );
	echo '<p class="description">Booked value feeds the catering revenue numbers on the dashboard.</p>';
}
