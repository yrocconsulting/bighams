<?php
/**
 * SEO + AI-search plumbing:
 *  - Staging guard: noindex everything unless on the production domain.
 *  - Meta description / Open Graph / Twitter (skipped if Yoast/Rank Math/SEOPress active).
 *  - Per-page "SEO" box (title override, description, social image).
 *  - robots.txt that welcomes AI crawlers + lists sitemaps.
 *  - /llms.txt: a plain-text fact sheet for LLM crawlers, built from live data.
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Staging guard
 * ------------------------------------------------------------------------ */

function bh_is_production() {
	$prod = bh_setting( 'production_domain', 'bighamsbbq.com' );
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	return $host === $prod || $host === 'www.' . $prod;
}

add_filter( 'wp_robots', function ( $robots ) {
	if ( ! bh_is_production() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}, 99 );

add_action( 'send_headers', function () {
	if ( ! bh_is_production() ) {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
} );

add_action( 'admin_notices', function () {
	if ( ! bh_is_production() && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Staging mode:</strong> search engines and AI crawlers are blocked on this domain. Indexing turns on automatically on <code>' . esc_html( bh_setting( 'production_domain', 'bighamsbbq.com' ) ) . '</code>.</p></div>';
	}
} );

/* ---------------------------------------------------------------------------
 * robots.txt
 * ------------------------------------------------------------------------ */

add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! bh_is_production() ) {
		return "# Staging site\nUser-agent: *\nDisallow: /\n";
	}
	$lines   = array( '# Bigham\'s Smokehouse — search engines and AI assistants are welcome.' );
	$lines[] = 'User-agent: *';
	$lines[] = 'Disallow: /wp-admin/';
	$lines[] = 'Allow: /wp-admin/admin-ajax.php';
	$lines[] = 'Disallow: /?s=';
	$lines[] = 'Disallow: /search/';
	$lines[] = '';
	foreach ( array( 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot', 'CCBot', 'meta-externalagent' ) as $bot ) {
		$lines[] = 'User-agent: ' . $bot;
		$lines[] = 'Allow: /';
		$lines[] = '';
	}
	$lines[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
	if ( defined( 'WPSEO_VERSION' ) ) {
		$lines[] = 'Sitemap: ' . home_url( '/sitemap_index.xml' );
	}
	return implode( "\n", $lines ) . "\n";
}, 99, 2 );

/* Keep internal CPTs out of core sitemaps; users too. */
add_filter( 'wp_sitemaps_add_provider', fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );

/* ---------------------------------------------------------------------------
 * /llms.txt
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	add_rewrite_rule( '^llms\.txt$', 'index.php?bh_llms=1', 'top' );
	add_rewrite_rule( '^llms-full\.txt$', 'index.php?bh_llms=2', 'top' );
} );
add_filter( 'query_vars', fn( $v ) => array_merge( $v, array( 'bh_llms' ) ) );
add_filter( 'redirect_canonical', fn( $r ) => get_query_var( 'bh_llms' ) ? false : $r );

add_action( 'template_redirect', function () {
	$mode = (int) get_query_var( 'bh_llms' );
	if ( ! $mode ) {
		return;
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo bh_llms_txt( 2 === $mode ); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text.
	exit;
} );

/**
 * Build llms.txt (markdown, per llmstxt.org) from live data.
 */
function bh_llms_txt( $full = false ) {
	$key    = 'bh_llms_txt' . ( $full ? '_full' : '' );
	$cached = get_transient( $key );
	if ( $cached ) {
		return $cached;
	}
	$name = bh_setting( 'business_name' );
	$o    = array();
	$o[]  = '# ' . $name;
	$o[]  = '';
	$o[]  = '> ' . bh_setting( 'description' );
	$o[]  = '';
	$o[]  = '## Key facts';
	$o[]  = '- Cuisine: ' . bh_setting( 'cuisines' );
	$o[]  = '- Founded: ' . bh_setting( 'founded' ) . ' by ' . bh_setting( 'founder' ) . ' in Lubbock, Texas';
	$o[]  = '- Owners: ' . bh_setting( 'owners' ) . ' (family-owned and locally operated)';
	$o[]  = '- Locations: ' . count( bh_get_locations() ) . ' in Lubbock, TX';
	$o[]  = '- Services: dine-in, carry-out, drive-thru, online ordering for pickup, delivery, catering (pickup, delivery, or full service), private events';
	$o[]  = '- Main phone: ' . bh_setting( 'phone' ) . ' · Email: ' . bh_setting( 'email' );
	$o[]  = '- Price range: ' . bh_setting( 'price_range' );
	$o[]  = '';
	$o[]  = '## Locations';
	foreach ( bh_get_locations() as $l ) {
		$hours = implode( '; ', array_map( fn( $r ) => $r['days'] . ' ' . $r['value'], $l['hours_rows'] ) );
		$o[]   = sprintf( '- [%s – %s](%s): %s%s. Phone %s. Hours: %s.%s%s', $name, $l['short_name'], $l['permalink'], $l['full_address'], $l['cross_street'] ? ' (' . $l['cross_street'] . ')' : '', $l['phone'], $hours, $l['happy_hour'] ? ' Happy hour: ' . $l['happy_hour'] . '.' : '', $l['order_url'] ? ' Order online: ' . $l['order_url'] : '' );
	}
	$o[] = '';
	$o[] = '## Catering';
	$o[] = '- [Catering packages and inquiry form](' . home_url( '/catering/' ) . '): orders need 24 hours notice; 200+ guests need 7 days notice. Pickup, delivery (fee) or catered-and-served.';
	foreach ( bh_get_packages() as $p ) {
		$o[] = sprintf( '- %s: %s%s. %s', $p['title'], $p['price'] ? bh_money( $p['price'] ) . ' ' . $p['unit'] : 'priced by selection', $p['minimum'] ? ' (' . $p['minimum'] . ')' : '', $p['description'] );
	}
	$o[] = '';
	$o[] = '## Pages';
	foreach ( array( 'menu' => 'Full menu with prices', 'catering' => 'Catering', 'parties' => 'Private parties & events', 'about' => 'Our story', 'faq' => 'Frequently asked questions', 'contact' => 'Contact' ) as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$o[] = '- [' . $label . '](' . get_permalink( $page ) . ')';
		}
	}
	$o[] = '- [Locations](' . get_post_type_archive_link( 'bh_location' ) . ')';

	if ( $full ) {
		$o[] = '';
		$o[] = '## Menu';
		foreach ( array( 'restaurant', 'specials', 'happy-hour', 'catering' ) as $group ) {
			foreach ( bh_get_menu( $group ) as $sec ) {
				$o[] = '';
				$o[] = '### ' . $sec['name'] . ( 'catering' === $group ? ' (catering)' : '' );
				foreach ( $sec['items'] as $it ) {
					$prices = implode( ', ', array_map( fn( $p ) => trim( bh_money( $p['price'] ) . ' ' . $p['label'] ), $it['prices'] ) );
					$o[]    = '- ' . $it['title'] . ( $prices ? ' — ' . $prices : '' ) . ( $it['description'] ? ': ' . $it['description'] : '' );
				}
			}
		}
		$faqs = bh_get_faqs();
		if ( $faqs ) {
			$o[] = '';
			$o[] = '## FAQ';
			foreach ( $faqs as $f ) {
				$o[] = '';
				$o[] = '### ' . $f['question'];
				$o[] = $f['text'];
			}
		}
	} else {
		$o[] = '';
		$o[] = '## Optional';
		$o[] = '- [Full menu, prices and FAQs in plain text](' . home_url( '/llms-full.txt' ) . ')';
	}
	$txt = implode( "\n", $o ) . "\n";
	set_transient( $key, $txt, 6 * HOUR_IN_SECONDS );
	return $txt;
}

foreach ( array( 'save_post', 'deleted_post', 'edited_term', 'update_option_bighams_settings' ) as $hook ) {
	add_action( $hook, function () {
		delete_transient( 'bh_llms_txt' );
		delete_transient( 'bh_llms_txt_full' );
	} );
}

/* ---------------------------------------------------------------------------
 * Meta tags (only when no SEO plugin is handling them)
 * ------------------------------------------------------------------------ */

function bh_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Auto descriptions for key templates (keeps every page unique & local).
 */
function bh_meta_description() {
	$id = get_queried_object_id();
	if ( is_singular() ) {
		$custom = get_post_meta( $id, '_bh_seo_desc', true );
		if ( $custom ) {
			return $custom;
		}
	}
	if ( is_front_page() ) {
		return "Family-owned Texas BBQ in Lubbock since 1978. Slow-smoked brisket, ribs, sausage & turkey at 3 locations — order online, drive-thru, catering & private parties.";
	}
	$slug = is_page() ? get_post_field( 'post_name', $id ) : '';
	$by_slug = bh_page_seo_defaults();
	if ( isset( $by_slug[ $slug ] ) ) {
		return $by_slug[ $slug ]['desc'];
	}
	if ( is_singular( 'bh_location' ) ) {
		$l     = bh_get_location( $id );
		$hours = $l['hours_rows'] ? ' Hours: ' . implode( ', ', array_map( fn( $r ) => $r['days'] . ' ' . $r['value'], $l['hours_rows'] ) ) . '.' : '';
		return sprintf( "Bigham's Smokehouse %s — Texas BBQ at %s. Order online for pickup, call %s.%s", $l['short_name'], $l['full_address'], $l['phone'], $hours );
	}
	if ( is_post_type_archive( 'bh_location' ) ) {
		return "Find Bigham's Smokehouse near you: 3 Lubbock BBQ locations on 82nd St, 19th St & Quaker and Milwaukee Ave. Hours, directions and online ordering.";
	}
	if ( is_singular() ) {
		$post = get_post( $id );
		$text = $post->post_excerpt ?: wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( $text ) {
			return wp_trim_words( $text, 28, '…' );
		}
	}
	return bh_setting( 'description' );
}

/**
 * Search-tuned defaults for the key pages (overridable per page in "Search & Social").
 */
function bh_page_seo_defaults() {
	$from = '';
	$prices = array_filter( array_map( fn( $p ) => (float) $p['price'], bh_get_packages() ), fn( $p ) => $p > 0 && $p < 40 );
	if ( $prices ) {
		$from = ' from ' . bh_money( min( $prices ) ) . '/person';
	}
	return array(
		'menu'     => array(
			'title' => 'BBQ Menu & Prices | Brisket, Ribs & Sides | Bigham\'s Smokehouse Lubbock',
			'desc'  => 'See the full Bigham\'s Smokehouse menu with prices: brisket, St. Louis ribs, sausage, turkey and pulled pork plates, family packs, scratch sides, cobblers and daily specials in Lubbock, TX.',
		),
		'catering' => array(
			'title' => 'BBQ Catering in Lubbock, TX | Packages' . $from . ' | Bigham\'s Smokehouse',
			'desc'  => 'Texas BBQ catering in Lubbock for offices, weddings, holiday parties and tailgates. Packages' . $from . ', pickup, delivery or full service. 24-hour notice. Get a quote today.',
		),
		'parties'  => array(
			'title' => 'Private Parties & Event Space in Lubbock | Bigham\'s Smokehouse',
			'desc'  => 'Book a private BBQ party in Lubbock: a private dining room for up to 20 at our 82nd St location, or rent a full dining room for birthdays, rehearsal dinners and company events.',
		),
		'about'    => array(
			'title' => 'Our Story | Family-Owned Lubbock BBQ Since 1978 | Bigham\'s Smokehouse',
			'desc'  => 'Bigham\'s Smokehouse was opened in Lubbock in 1978 by Don Bigham and is still family-owned and run by Joyce and Nick Bigham, serving slow-smoked Texas BBQ at three locations.',
		),
		'faq'      => array(
			'title' => 'FAQ | Hours, Ordering, Catering & Parties | Bigham\'s Smokehouse',
			'desc'  => 'Answers about Bigham\'s Smokehouse hours, locations, online ordering, delivery, catering minimums and pricing, how much BBQ to order per person, and private parties in Lubbock.',
		),
		'contact'  => array(
			'title' => 'Contact Bigham\'s Smokehouse | Lubbock BBQ Phone, Email & Locations',
			'desc'  => 'Contact Bigham\'s Smokehouse in Lubbock, TX: call (806) 797-9931, email info@bighamsbbq.com, or find hours and directions for our 82nd St, 19th St and Milwaukee Ave locations.',
		),
	);
}

add_filter( 'pre_get_document_title', function ( $title ) {
	if ( bh_seo_plugin_active() ) {
		return $title;
	}
	if ( is_front_page() ) {
		return bh_setting( 'business_name' ) . ' | Texas BBQ in Lubbock Since ' . bh_setting( 'founded' );
	}
	if ( is_post_type_archive( 'bh_location' ) ) {
		return 'BBQ Near Me in Lubbock | 3 Locations, Hours & Directions | ' . bh_setting( 'business_name' );
	}
	if ( is_singular( 'bh_location' ) ) {
		$custom = get_post_meta( get_queried_object_id(), '_bh_seo_title', true );
		if ( $custom ) {
			return $custom;
		}
		$l = bh_get_location( get_queried_object_id() );
		return sprintf( '%s %s | BBQ at %s, Lubbock TX', bh_setting( 'business_name' ), $l['short_name'], $l['cross_street'] ?: $l['street'] );
	}
	if ( ! is_singular() ) {
		return $title;
	}
	$custom = get_post_meta( get_queried_object_id(), '_bh_seo_title', true );
	if ( $custom ) {
		return $custom;
	}
	$slug     = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
	$defaults = bh_page_seo_defaults();
	return isset( $defaults[ $slug ] ) ? $defaults[ $slug ]['title'] : $title;
} );

add_filter( 'document_title_separator', fn() => '|' );

add_action( 'wp_head', function () {
	if ( bh_seo_plugin_active() ) {
		return;
	}
	$desc  = bh_meta_description();
	$title = wp_get_document_title();
	$url   = function_exists( 'bh_current_url' ) ? bh_current_url() : home_url( '/' );
	$img   = '';
	if ( is_singular() ) {
		$img_id = get_post_meta( get_queried_object_id(), '_bh_seo_image', true ) ?: get_post_thumbnail_id();
		$img    = $img_id ? wp_get_attachment_image_url( (int) $img_id, 'large' ) : '';
	}
	if ( ! $img ) {
		$img = get_theme_mod( 'bh_hero_image' ) ?: get_theme_mod( 'bh_og_image' );
		if ( is_numeric( $img ) ) {
			$img = wp_get_attachment_image_url( (int) $img, 'large' );
		}
	}
	printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $desc ) );
	printf( "<meta property=\"og:type\" content=\"%s\">\n", is_singular( 'post' ) ? 'article' : 'website' );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( bh_setting( 'business_name' ) ) );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $title ) );
	printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $desc ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );
	printf( "<meta property=\"og:locale\" content=\"en_US\">\n" );
	printf( "<meta name=\"twitter:card\" content=\"%s\">\n", $img ? 'summary_large_image' : 'summary' );
	if ( $img ) {
		printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $img ) );
	}
	// Local SEO geo hints (harmless, used by some aggregators).
	echo "<meta name=\"geo.region\" content=\"US-TX\">\n<meta name=\"geo.placename\" content=\"Lubbock\">\n";
	if ( is_post_type_archive( 'bh_location' ) && ! get_queried_object_id() ) {
		printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( get_post_type_archive_link( 'bh_location' ) ) );
	}
}, 2 );

/* LLM discovery link. */
add_action( 'wp_head', function () {
	printf( "<link rel=\"alternate\" type=\"text/plain\" title=\"LLM summary\" href=\"%s\">\n", esc_url( home_url( '/llms.txt' ) ) );
}, 3 );

/* ---------------------------------------------------------------------------
 * Per-page SEO box
 * ------------------------------------------------------------------------ */

add_action( 'add_meta_boxes', function () {
	if ( bh_seo_plugin_active() ) {
		return;
	}
	foreach ( array( 'page', 'post', 'bh_location' ) as $pt ) {
		add_meta_box( 'bh_seo', 'Search & Social', 'bh_render_seo_box', $pt, 'normal', 'low' );
	}
} );

function bh_render_seo_box( $post ) {
	wp_nonce_field( 'bh_seo_save', 'bh_seo_nonce' );
	$t = get_post_meta( $post->ID, '_bh_seo_title', true );
	$d = get_post_meta( $post->ID, '_bh_seo_desc', true );
	$i = get_post_meta( $post->ID, '_bh_seo_image', true );
	?>
	<p><label><strong>Search title</strong> (optional, ~60 characters)<br><input type="text" name="bh_seo_title" value="<?php echo esc_attr( $t ); ?>" class="large-text" maxlength="70" placeholder="<?php echo esc_attr( get_the_title( $post ) . ' | ' . bh_setting( 'business_name' ) ); ?>"></label></p>
	<p><label><strong>Meta description</strong> (~155 characters — answer the searcher's question)<br><textarea name="bh_seo_desc" class="large-text" rows="2" maxlength="180"><?php echo esc_textarea( $d ); ?></textarea></label></p>
	<p><label><strong>Social share image ID</strong> (optional, defaults to featured image)<br><input type="number" name="bh_seo_image" value="<?php echo esc_attr( $i ); ?>"></label></p>
	<?php
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['bh_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bh_seo_nonce'] ) ), 'bh_seo_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_bh_seo_title', sanitize_text_field( wp_unslash( $_POST['bh_seo_title'] ?? '' ) ) );
	update_post_meta( $post_id, '_bh_seo_desc', sanitize_textarea_field( wp_unslash( $_POST['bh_seo_desc'] ?? '' ) ) );
	update_post_meta( $post_id, '_bh_seo_image', absint( $_POST['bh_seo_image'] ?? 0 ) ?: '' );
} );
