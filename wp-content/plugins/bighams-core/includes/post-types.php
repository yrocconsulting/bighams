<?php
/**
 * Custom post types + taxonomies.
 *
 * bh_location     → /locations/{slug}/   (public)
 * bh_menu_item    → menu items, grouped by bh_menu_section
 * bh_catering_pkg → catering packages
 * bh_faq          → FAQs (drive FAQPage schema + answer-engine content)
 * bh_review       → hand-picked guest reviews
 * bh_lead         → catering / private event inquiries (private)
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'bh_register_post_types' );

function bh_register_post_types() {
	$menu_parent = 'bighams';

	register_post_type(
		'bh_location',
		array(
			'labels'        => bh_labels( 'Location', 'Locations' ),
			'public'        => true,
			'show_in_rest'  => true,
			'show_in_menu'  => $menu_parent,
			'has_archive'   => 'locations',
			'rewrite'       => array( 'slug' => 'locations', 'with_front' => false ),
			'menu_icon'     => 'dashicons-location',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		)
	);

	register_post_type(
		'bh_menu_item',
		array(
			'labels'       => bh_labels( 'Menu Item', 'Menu Items' ),
			'public'       => true,
			'publicly_queryable' => false,
			'show_in_rest' => true,
			'show_in_menu' => $menu_parent,
			'has_archive'  => false,
			'rewrite'      => false,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'bh_catering_pkg',
		array(
			'labels'       => bh_labels( 'Catering Package', 'Catering Packages' ),
			'public'       => true,
			'publicly_queryable' => false,
			'show_in_rest' => true,
			'show_in_menu' => $menu_parent,
			'rewrite'      => false,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'bh_faq',
		array(
			'labels'       => bh_labels( 'FAQ', 'FAQs' ),
			'public'       => true,
			'publicly_queryable' => false,
			'show_in_rest' => true,
			'show_in_menu' => $menu_parent,
			'rewrite'      => false,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_post_type(
		'bh_review',
		array(
			'labels'       => bh_labels( 'Review', 'Reviews' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => $menu_parent,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_post_type(
		'bh_lead',
		array(
			'labels'       => bh_labels( 'Catering Lead', 'Catering Leads' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => $menu_parent,
			'supports'     => array( 'title' ),
			'capability_type' => 'post',
			'capabilities' => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap' => true,
		)
	);

	register_taxonomy(
		'bh_menu_section',
		array( 'bh_menu_item' ),
		array(
			'labels'            => bh_labels( 'Menu Section', 'Menu Sections' ),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);

	register_taxonomy(
		'bh_faq_topic',
		array( 'bh_faq' ),
		array(
			'labels'            => bh_labels( 'FAQ Topic', 'FAQ Topics' ),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);
}

function bh_labels( $singular, $plural ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'add_new'            => 'Add New',
		'add_new_item'       => "Add New $singular",
		'edit_item'          => "Edit $singular",
		'new_item'           => "New $singular",
		'view_item'          => "View $singular",
		'search_items'       => "Search $plural",
		'not_found'          => "No $plural found",
		'all_items'          => $plural,
		'menu_name'          => $plural,
	);
}

/**
 * Top-level "Bigham's" admin menu that holds everything.
 */
add_action( 'admin_menu', function () {
	add_menu_page(
		"Bigham's",
		"Bigham's",
		'edit_posts',
		'bighams',
		'bh_render_dashboard_page',
		'dashicons-store',
		3
	);
	add_submenu_page( 'bighams', 'Dashboard', 'Dashboard', 'edit_posts', 'bighams', 'bh_render_dashboard_page' );
	add_submenu_page( 'bighams', 'Menu Sections', 'Menu Sections', 'manage_categories', 'edit-tags.php?taxonomy=bh_menu_section&post_type=bh_menu_item' );
	add_submenu_page( 'bighams', 'FAQ Topics', 'FAQ Topics', 'manage_categories', 'edit-tags.php?taxonomy=bh_faq_topic&post_type=bh_faq' );
}, 9 );

/* Keep the parent menu highlighted on taxonomy screens. */
add_filter( 'parent_file', function ( $parent ) {
	global $current_screen;
	if ( $current_screen && in_array( $current_screen->taxonomy, array( 'bh_menu_section', 'bh_faq_topic' ), true ) ) {
		return 'bighams';
	}
	return $parent;
} );

/**
 * Menu section term meta: display order + group.
 */
add_action( 'bh_menu_section_add_form_fields', function () {
	?>
	<div class="form-field"><label for="bh_order">Display order</label><input name="bh_order" id="bh_order" type="number" value="10"></div>
	<div class="form-field"><label for="bh_group">Menu</label>
		<select name="bh_group" id="bh_group"><?php bh_group_options( 'restaurant' ); ?></select>
		<p>Which menu this section belongs to.</p></div>
	<div class="form-field"><label for="bh_note">Section note</label><input name="bh_note" id="bh_note" type="text"><p>Short line shown under the heading, e.g. "Minimum 10 guests".</p></div>
	<?php
} );

add_action( 'bh_menu_section_edit_form_fields', function ( $term ) {
	$order = get_term_meta( $term->term_id, 'bh_order', true );
	$group = get_term_meta( $term->term_id, 'bh_group', true ) ?: 'restaurant';
	$note  = get_term_meta( $term->term_id, 'bh_note', true );
	?>
	<tr class="form-field"><th><label for="bh_order">Display order</label></th><td><input name="bh_order" id="bh_order" type="number" value="<?php echo esc_attr( $order ); ?>"></td></tr>
	<tr class="form-field"><th><label for="bh_group">Menu</label></th><td><select name="bh_group" id="bh_group"><?php bh_group_options( $group ); ?></select></td></tr>
	<tr class="form-field"><th><label for="bh_note">Section note</label></th><td><input name="bh_note" id="bh_note" type="text" value="<?php echo esc_attr( $note ); ?>"></td></tr>
	<?php
} );

function bh_menu_groups() {
	return array(
		'restaurant' => 'Restaurant Menu',
		'catering'   => 'Catering A La Carte',
		'happy-hour' => 'Happy Hour',
		'specials'   => 'Specials',
	);
}

function bh_group_options( $current ) {
	foreach ( bh_menu_groups() as $k => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $current, $k, false ), esc_html( $label ) );
	}
}

foreach ( array( 'created_bh_menu_section', 'edited_bh_menu_section' ) as $hook ) {
	add_action( $hook, function ( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		// Core verifies the term form nonce before these hooks fire.
		if ( isset( $_POST['bh_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_term_meta( $term_id, 'bh_order', (int) $_POST['bh_order'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( isset( $_POST['bh_group'] ) && array_key_exists( wp_unslash( $_POST['bh_group'] ), bh_menu_groups() ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'bh_group', sanitize_key( wp_unslash( $_POST['bh_group'] ) ) ); // phpcs:ignore
		}
		if ( isset( $_POST['bh_note'] ) ) { // phpcs:ignore
			update_term_meta( $term_id, 'bh_note', sanitize_text_field( wp_unslash( $_POST['bh_note'] ) ) ); // phpcs:ignore
		}
	} );
}
