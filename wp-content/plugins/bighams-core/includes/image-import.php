<?php
/**
 * One-time import of Bigham's existing photography from the old SpotHopper
 * site into the Media Library, then attaches each image to its menu item,
 * catering package, location and homepage section.
 *
 * Images are copied (not hotlinked), so the site keeps working after the
 * SpotHopper account is cancelled. Safe to re-run: each source URL is only
 * downloaded once (tracked in _bh_source_url), and existing featured images
 * are never replaced.
 *
 *   wp bighams import-images            (run by the deploy workflow)
 *   Bigham's → Settings → "Import photos from old site"
 */

defined( 'ABSPATH' ) || exit;

function bh_image_manifest() {
	$s = 'https://static.spotapps.co/spots/';
	$w = 'https://static.spotapps.co/website_images/ab_websites/312907_website_v1/';
	return array(
		// [ post type, menu group (menu items only), title, source URL, alt text ].
		'items'   => array(
			array( 'restaurant', 'Little Meat Plate', $s . '57/809e3e715c4962b65d7a92514b124d/full' ),
			array( 'restaurant', 'Big Meat Plate', $s . '5d/30df0fe4304ab39911972ccf9c2762/full' ),
			array( 'restaurant', 'Bigger Meat Plate', $s . '21/5b1a98e7e34e748465ec28c5397962/full' ),
			array( 'restaurant', 'Biggest Meat Plate', $s . '65/f77f3a05fa43f2b1bb0a2a81326299/full' ),
			array( 'restaurant', 'Little Sandwich Plate', $s . 'f0/0f8f50d5f54f179d33d645943f3be9/full' ),
			array( 'restaurant', 'Big Sandwich Plate', $s . 'e2/1cfbea08d443cc8769350c4cff4bdc/full' ),
			array( 'restaurant', 'Bigger Sandwich Plate', $s . 'cd/3e61f68e774943b704394b149fedf9/full' ),
			array( 'restaurant', 'Biggest Sandwich Plate', $s . '4e/4e1ef0552b4bf3b8afd9c4720f898f/full' ),
			array( 'restaurant', 'Pinto Beans', $s . 'bc/5bb936277044609fa879943154f08a/full' ),
			array( 'restaurant', 'Potato Salad', $s . 'bc/5bb936277044609fa879943154f08a/full' ),
			array( 'restaurant', 'Coleslaw', $s . 'bc/5bb936277044609fa879943154f08a/full' ),
			array( 'restaurant', 'Fire Roasted Street Corn', $s . 'e4/616ffdf54f43669b70e29a90f60049/full' ),
			array( 'restaurant', 'Peach Cobbler', $s . '05/62235821074294a9af10576cfb23e4/full' ),
			array( 'restaurant', 'Skillet Cookie', $s . '42/814165ce0e480dab9367de1b662658/full' ),
			array( 'restaurant', 'Key Lime Pie', $s . 'e1/5aa809e35b436c9f253fc32601bdf7/full' ),
			array( 'restaurant', 'Banana Pudding', $s . 'a0/16aaf32eeb4a508778d51c3dc83809/full' ),
			array( 'restaurant', 'Strawberry Shortcake', $s . 'e5/2ea3219216476c817fbce79fc95608/full' ),
			array( 'restaurant', 'Sliced Brisket', $s . 'aa/04229aac754f9381f32d7a9b7d9beb/full' ),
			array( 'restaurant', 'Chopped Brisket', $s . 'aa/04229aac754f9381f32d7a9b7d9beb/full' ),
			array( 'restaurant', 'Turkey Breast', $s . '02/38578e770d46f7946879a44df674a2/full' ),
			array( 'restaurant', 'St. Louis Style Pork Ribs', $s . 'b5/81856480a84c84b256266a441907ad/full' ),
			array( 'restaurant', 'Original Sausage', $s . '15/45038c1b774cf28884d1ecda79c8fb/full' ),
			array( 'restaurant', 'Spicy Sausage', $s . '15/45038c1b774cf28884d1ecda79c8fb/full' ),
			array( 'restaurant', 'Pulled Pork', $s . 'ea/c30156605f41549983049e584deb82/full' ),
			array( 'restaurant', 'Little Stuffed Baker', $s . '8d/4f77e08b904115a4801b6e849254f9/full' ),
			array( 'restaurant', 'Big Stuffed Baker', $s . '8d/4f77e08b904115a4801b6e849254f9/full' ),
			array( 'restaurant', 'Little Frito Pie', $s . 'fd/d87fc99ec84ff5a7c4278ee2b4cf97/full' ),
			array( 'restaurant', 'Big Frito Pie', $s . 'fd/d87fc99ec84ff5a7c4278ee2b4cf97/full' ),
			array( 'restaurant', 'Little Loaded Hog Heaven Fries', $s . '99/79e482ab9d4644994bcf5ce654a112/full' ),
			array( 'restaurant', 'Big Loaded Hog Heaven Fries', $s . '99/79e482ab9d4644994bcf5ce654a112/full' ),
			array( 'restaurant', 'The Number 2', $s . '8f/5b624ea472407e93e1c7993317d37c/full' ),
			array( 'restaurant', 'Sandwich Pack', $s . 'de/c5c3b851fd4e2f96ec2e87672a483b/full' ),
			array( 'restaurant', 'Big Baker Bag', $s . 'a3/33f4fbdf59447bb2005c7672aaa0d7/full' ),
			array( 'restaurant', 'The Turkey Platter', $s . '57/29ceacf5304b6287f32fb15f006296/full' ),
			array( 'restaurant', 'The Frito Pie Bar', $s . 'b5/e6f29a995e4c36b8844eab800f18fc/full' ),
			// The old site labels this photo "Lone Star", but it shows a Miller Lite can.
			array( 'happy-hour', 'Miller Lite', $s . '3f/4529b7ddf940348f1f713687f57e30/full' ),
			array( 'happy-hour', 'Margarita with Cutwater Tequila', $s . '31/5f3ed2c71f459eaf12fd89d73169fa/full' ),
			array( 'catering', 'Original Sausage', $s . '9d/fab2f026874760ac83774bb6b8105a/full' ),
			array( 'catering', 'Spicy Sausage', $s . '9d/fab2f026874760ac83774bb6b8105a/full' ),
			array( 'catering', 'Pinto Beans', $s . '19/a6111f07f9497ea4fd86bbcc11206c/full' ),
			array( 'catering', 'Peach Cobbler', $s . 'bd/dcbd6406694f928d8b43c74ff440cf/full' ),
			array( 'specials', 'Sunday Chicken Plate', $s . '50/1d4991a67a4e8398120379d661024f/full' ),
			array( 'specials', 'Little Sandwich Special', $s . '3f/0461b83cdf4ee585be4ba11d2ad4dc/full' ),
			array( 'specials', 'Big Sandwich Special', $s . '3f/0461b83cdf4ee585be4ba11d2ad4dc/full' ),
		),
		'packages' => array(
			'All The Fixin\'s'       => $s . '3f/0ca3265ab646f39190fa284fe01ffb/full',
			'Sandwich Meal Deal'     => $s . '7a/e09a48babe4f2cac5815b7082dccd1/full',
			'TX Breakfast Taco Pack' => $s . '09/33ef7033744b20a8b2b49638c4a978/full',
			'Frito Pie Bar'          => $s . 'ca/24486dc8b34b1caeccf24fca0b5a07/full',
			'Stuffed Baker Bar'      => $s . '9d/f3c318411f46bb86f343d154f5cb48/full',
		),
		'locations' => array(
			'82nd Street'          => array( $w . 'indiana.jpg', "Bigham's Smokehouse on 82nd Street in Lubbock, TX" ),
			'19th Street & Quaker' => array( $w . 'quaker.jpg', "Bigham's Smokehouse at 19th Street & Quaker Ave in Lubbock, TX" ),
			'Milwaukee Avenue'     => array( $w . 'milwaukee.jpg', "Bigham's Smokehouse on Milwaukee Avenue in Lubbock, TX" ),
		),
		// Theme Customizer image settings → [ URL, alt ].
		'theme'   => array(
			'bh_hero_image'     => array( $w . 'catering_img.jpg', 'Smoked brisket, sausage, Texas toast and sides on a Bigham\'s tray' ),
			'bh_catering_image' => array( $w . 'food.jpg', 'Bigham\'s Frito pie bar catering spread' ),
			'bh_parties_image'  => array( $w . 'party_informations10.jpg', 'Private dining room set for a birthday party at Bigham\'s' ),
			'bh_about_image'    => array( $w . 'party_informations2.jpg', 'Texas flag and memorabilia at Bigham\'s Smokehouse' ),
		),
		'logo'    => array( $w . 'bigham_logo.png', "Bigham's Smokehouse logo, established 1978" ),
		'video'   => 'https://static.spotapps.co/website_videos/Bighams_Edited_V1_Vimeo720p30.mp4',
		'gallery' => array(
			array( $w . 'party_informations11.jpg', 'A birthday celebration in a Bigham\'s private dining room' ),
			array( $w . 'party_informations5.jpg', 'A business meeting in the Bigham\'s private dining room' ),
			array( $w . 'party_informations4.jpg', 'Private dining room set for a group at Bigham\'s' ),
			array( $w . 'party_informations6.jpg', 'Bigham\'s dining room with Western memorabilia' ),
			array( $w . 'party_informations8.jpg', 'Long tables in the Bigham\'s dining room' ),
			array( $w . 'parties_img.jpg', 'Booths and mural in the Bigham\'s dining room' ),
		),
	);
}

/**
 * Download a URL into the Media Library once; returns attachment ID.
 *
 * @return int|WP_Error
 */
function bh_import_media( $url, $title, $alt = '', $ext = '' ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bh_source_url', // phpcs:ignore
			'meta_value'     => $url, // phpcs:ignore
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 120 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	if ( ! $ext ) {
		$ext  = strtolower( (string) pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$mime = function_exists( 'mime_content_type' ) ? (string) mime_content_type( $tmp ) : '';
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'mp4' ), true ) ) {
			$ext = 'image/png' === $mime ? 'png' : ( 'image/webp' === $mime ? 'webp' : 'jpg' );
		}
	}
	$file = array(
		'name'     => sanitize_title( $title ) . '.' . $ext,
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file, 0, $title );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return $id;
	}
	update_post_meta( $id, '_bh_source_url', $url );
	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}
	return (int) $id;
}

/**
 * Find a menu item by exact title within a menu group.
 */
function bh_find_menu_item( $title, $group ) {
	foreach ( bh_get_menu( $group ) as $section ) {
		foreach ( $section['items'] as $item ) {
			if ( 0 === strcasecmp( $item['title'], $title ) ) {
				return (int) $item['id'];
			}
		}
	}
	return 0;
}

function bh_find_post_by_title( $type, $title ) {
	$ids = get_posts(
		array(
			'post_type'      => $type,
			'title'          => $title,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Run the import.
 *
 * @param bool $with_video Also import the ~14MB hero video.
 * @return array{ok:int,skipped:int,errors:array<int,string>}
 */
function bh_import_images( $with_video = true ) {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 600 ); // phpcs:ignore
	}
	$m   = bh_image_manifest();
	$res = array( 'ok' => 0, 'skipped' => 0, 'errors' => array() );

	$attach = function ( $post_id, $url, $title, $alt ) use ( &$res ) {
		if ( ! $post_id ) {
			$res['errors'][] = "Not found: $title";
			return;
		}
		if ( has_post_thumbnail( $post_id ) ) {
			$res['skipped']++;
			return;
		}
		$id = bh_import_media( $url, $title, $alt );
		if ( is_wp_error( $id ) ) {
			$res['errors'][] = "$title: " . $id->get_error_message();
			return;
		}
		set_post_thumbnail( $post_id, $id );
		$res['ok']++;
	};

	foreach ( $m['items'] as $row ) {
		list( $group, $title, $url ) = $row;
		$attach( bh_find_menu_item( $title, $group ), $url, $title, $title . " at Bigham's Smokehouse, Lubbock TX" );
	}
	foreach ( $m['packages'] as $title => $url ) {
		$attach( bh_find_post_by_title( 'bh_catering_pkg', $title ), $url, $title . ' catering', $title . " BBQ catering package from Bigham's Smokehouse" );
	}
	foreach ( $m['locations'] as $title => $row ) {
		$attach( bh_find_post_by_title( 'bh_location', $title ), $row[0], "Bigham's Smokehouse " . $title, $row[1] );
	}
	foreach ( $m['theme'] as $mod => $row ) {
		if ( get_theme_mod( $mod ) ) {
			$res['skipped']++;
			continue;
		}
		$id = bh_import_media( $row[0], str_replace( array( 'bh_', '_' ), array( '', ' ' ), $mod ), $row[1] );
		if ( is_wp_error( $id ) ) {
			$res['errors'][] = "$mod: " . $id->get_error_message();
			continue;
		}
		set_theme_mod( $mod, $id );
		$res['ok']++;
	}

	// Logo → used for Google / AI knowledge panels and the browser tab icon.
	// The header keeps the text wordmark unless "Show logo image in header" is ticked.
	$logo = bh_import_media( $m['logo'][0], "Bigham's Smokehouse logo", $m['logo'][1] );
	if ( ! is_wp_error( $logo ) ) {
		if ( ! get_theme_mod( 'custom_logo' ) ) {
			set_theme_mod( 'custom_logo', $logo );
		}
		if ( ! get_option( 'site_icon' ) ) {
			update_option( 'site_icon', $logo );
		}
	} else {
		$res['errors'][] = 'logo: ' . $logo->get_error_message();
	}

	// Parties page gallery.
	$parties = get_page_by_path( 'parties' );
	if ( $parties && false === strpos( $parties->post_content, 'wp:gallery' ) ) {
		$ids = array();
		foreach ( $m['gallery'] as $i => $g ) {
			$id = bh_import_media( $g[0], 'Private dining ' . ( $i + 1 ), $g[1] );
			if ( ! is_wp_error( $id ) ) {
				$ids[] = $id;
			}
		}
		if ( $ids ) {
			$block = '<!-- wp:gallery {"linkTo":"none","columns":3} --><figure class="wp-block-gallery has-nested-images columns-3 is-cropped">';
			foreach ( $ids as $id ) {
				$block .= sprintf(
					'<!-- wp:image {"id":%1$d,"sizeSlug":"large","linkDestination":"none"} --><figure class="wp-block-image size-large"><img src="%2$s" alt="%3$s" class="wp-image-%1$d"/></figure><!-- /wp:image -->',
					$id,
					esc_url( wp_get_attachment_image_url( $id, 'large' ) ),
					esc_attr( get_post_meta( $id, '_wp_attachment_image_alt', true ) )
				);
			}
			$block .= '</figure><!-- /wp:gallery -->';
			wp_update_post( array( 'ID' => $parties->ID, 'post_content' => trim( $parties->post_content . "\n\n" . $block ) ) );
			$res['ok']++;
		}
	}

	if ( $with_video && ! get_theme_mod( 'bh_hero_video' ) ) {
		$vid = bh_import_media( $m['video'], "Bigham's Smokehouse hero video", '', 'mp4' );
		if ( is_wp_error( $vid ) ) {
			$res['errors'][] = 'video: ' . $vid->get_error_message();
		} else {
			set_theme_mod( 'bh_hero_video', wp_get_attachment_url( $vid ) );
			$res['ok']++;
		}
	}

	update_option( 'bh_images_imported', time() );
	delete_transient( 'bh_llms_txt' );
	delete_transient( 'bh_llms_txt_full' );
	return $res;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bighams import-images',
		function ( $args, $assoc ) {
			$r = bh_import_images( empty( $assoc['skip-video'] ) );
			foreach ( $r['errors'] as $e ) {
				WP_CLI::warning( $e );
			}
			WP_CLI::success( sprintf( 'Imported/attached %d, already set %d, problems %d.', $r['ok'], $r['skipped'], count( $r['errors'] ) ) );
		},
		array( 'shortdesc' => 'Import Bigham\'s photos from the old site and attach them to menu items, packages, locations and homepage sections.' )
	);
}

add_action( 'admin_post_bh_import_images', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}
	check_admin_referer( 'bh_import_images' );
	$r   = bh_import_images();
	$msg = sprintf( 'Photos: %d attached, %d already set.', $r['ok'], $r['skipped'] ) . ( $r['errors'] ? ' Problems: ' . implode( '; ', array_slice( $r['errors'], 0, 5 ) ) : '' );
	wp_safe_redirect( add_query_arg( 'bh_msg', rawurlencode( $msg ), admin_url( 'admin.php?page=bighams-settings' ) ) );
	exit;
} );
