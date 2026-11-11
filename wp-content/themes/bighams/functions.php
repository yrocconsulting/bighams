<?php
/**
 * Bigham's Smokehouse theme bootstrap.
 *
 * Presentation only — content and business logic live in the
 * "Bigham's Core" plugin. Every plugin call goes through the guarded
 * wrappers in inc/template-functions.php.
 */

defined( 'ABSPATH' ) || exit;

define( 'BHT_VERSION', '1.0.0' );
define( 'BHT_DIR', get_template_directory() );
define( 'BHT_URI', get_template_directory_uri() );

require BHT_DIR . '/inc/template-functions.php';
require BHT_DIR . '/inc/icons.php';
require BHT_DIR . '/inc/customizer.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'bighams', BHT_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 160, 'width' => 400, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary navigation', 'bighams' ),
			'footer'  => __( 'Footer navigation', 'bighams' ),
		)
	);

	add_image_size( 'bh-hero', 1920, 1080, true );
	add_image_size( 'bh-card', 720, 540, true );
	add_image_size( 'bh-square', 600, 600, true );
	add_image_size( 'bh-wide', 1440, 810, true );
} );

/**
 * Assets.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'bh-fonts', bht_fonts_url(), array(), null );
	wp_enqueue_style( 'bh-main', BHT_URI . '/assets/css/main.css', array(), BHT_VERSION );
	wp_enqueue_script( 'bh-main', BHT_URI . '/assets/js/main.js', array(), BHT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

function bht_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Rokkitt:wght@500;700;800&family=Rye&family=Source+Sans+3:ital,wght@0,400;0,600;0,700;1,400&display=swap';
}

/* Preconnect to font hosts. */
add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/* Preload the hero image on the homepage (LCP). */
add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$id = (int) get_theme_mod( 'bh_hero_image' );
	if ( $id ) {
		$src    = wp_get_attachment_image_src( $id, 'bh-hero' );
		$srcset = wp_get_attachment_image_srcset( $id, 'bh-hero' );
		if ( $src ) {
			printf(
				'<link rel="preload" as="image" href="%s"%s fetchpriority="high">' . "\n",
				esc_url( $src[0] ),
				$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="100vw"' : ''
			);
		}
	}
}, 2 );

/* Strip emoji + other head clutter. */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
} );

/* Body classes. */
add_filter( 'body_class', function ( $classes ) {
	if ( is_page_template( 'page-menu.php' ) || is_page( 'menu' ) ) {
		$classes[] = 'bh-menu-page';
	}
	if ( bht_setting( 'announcement' ) ) {
		$classes[] = 'has-announcement';
	}
	return $classes;
} );

/* Theme color for mobile browsers. */
add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#1c1715">' . "\n";
}, 1 );

/* Excerpt length. */
add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );
