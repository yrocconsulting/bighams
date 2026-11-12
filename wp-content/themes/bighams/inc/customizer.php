<?php
/**
 * Customizer: "Bigham's Homepage" panel — images, video, hero copy.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$wp->add_panel( 'bh_home', array( 'title' => "Bigham's Homepage", 'priority' => 25 ) );

	$wp->add_section( 'bh_hero', array( 'title' => 'Hero', 'panel' => 'bh_home' ) );
	$wp->add_section( 'bh_images', array( 'title' => 'Section images', 'panel' => 'bh_home' ) );

	$text = array(
		'bh_hero_eyebrow'  => array( 'Eyebrow', 'Lubbock, Texas · Since 1978', 'bh_hero' ),
		'bh_hero_headline' => array( 'Headline', 'Real Texas Barbecue, Smoked Low & Slow', 'bh_hero' ),
		'bh_hero_subhead'  => array( 'Subheadline', 'Family-owned for over 45 years. Brisket, ribs, sausage and scratch-made sides at three Lubbock locations — dine in, drive-thru, pickup, delivery and catering.', 'bh_hero' ),
	);
	foreach ( $text as $id => $def ) {
		$wp->add_setting( $id, array( 'default' => $def[1], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
		$wp->add_control( $id, array( 'label' => $def[0], 'section' => $def[2], 'type' => 'bh_hero_subhead' === $id ? 'textarea' : 'text' ) );
	}

	$wp->add_setting( 'bh_logo_style', array( 'default' => 'both', 'sanitize_callback' => function ( $v ) { return in_array( $v, array( 'both', 'wordmark', 'logo' ), true ) ? $v : 'both'; } ) );
	$wp->add_control( 'bh_logo_style', array(
		'label'       => 'Header & footer logo style',
		'description' => 'Uses the Logo above for the circle badge. The logo is also used for Google, AI assistants and the browser tab icon.',
		'section'     => 'title_tagline',
		'type'        => 'radio',
		'choices'     => array(
			'both'     => 'Circle logo + Bigham\'s wordmark (recommended)',
			'wordmark' => 'Wordmark only',
			'logo'     => 'Logo image only',
		),
	) );

	$wp->add_setting( 'bh_hero_video', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp->add_control( 'bh_hero_video', array( 'label' => 'Hero video URL (.mp4, muted)', 'description' => 'Upload to Media Library and paste the file URL. Hero image is used as the poster and on slow connections.', 'section' => 'bh_hero', 'type' => 'url' ) );

	$images = array(
		'bh_hero_image'     => array( 'Hero image', 'bh_hero' ),
		'bh_about_image'    => array( 'Our story image', 'bh_images' ),
		'bh_catering_image' => array( 'Catering image', 'bh_images' ),
		'bh_parties_image'  => array( 'Private parties image', 'bh_images' ),
	);
	foreach ( $images as $id => $def ) {
		$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
		$wp->add_control(
			new WP_Customize_Media_Control(
				$wp,
				$id,
				array(
					'label'     => $def[0],
					'section'   => $def[1],
					'mime_type' => 'image',
				)
			)
		);
	}
} );

function bht_mod( $key, $default = '' ) {
	$defaults = array(
		'bh_hero_eyebrow'  => 'Lubbock, Texas · Since 1978',
		'bh_hero_headline' => 'Real Texas Barbecue, Smoked Low & Slow',
		'bh_hero_subhead'  => 'Family-owned for over 45 years. Brisket, ribs, sausage and scratch-made sides at three Lubbock locations — dine in, drive-thru, pickup, delivery and catering.',
	);
	$val = get_theme_mod( $key, $defaults[ $key ] ?? $default );
	return '' === $val ? ( $defaults[ $key ] ?? $default ) : $val;
}
