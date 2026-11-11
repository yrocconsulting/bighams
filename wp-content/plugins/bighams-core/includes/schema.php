<?php
/**
 * JSON-LD knowledge graph (schema.org) for Google + AI answer engines.
 *
 * One connected @graph per page:
 *   Organization (brand, founder, sameAs)
 *     └─ subOrganization → Restaurant × 3 (NAP, geo, hours, OrderAction, menu)
 *   WebSite, WebPage, BreadcrumbList
 *   Menu → MenuSection → MenuItem → Offer   (menu page)
 *   Service "BBQ Catering" → OfferCatalog     (catering page)
 *   FAQPage                                   (pages that show FAQs)
 *
 * Stable @id values let crawlers merge entities across pages, which is
 * what builds entity confidence (see ai-search-optimization-framework).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'bh_output_schema', 20 );

function bh_schema_ids() {
	$home = trailingslashit( home_url() );
	return array(
		'org'      => $home . '#organization',
		'website'  => $home . '#website',
		'menu'     => home_url( '/menu/' ) . '#menu',
		'catering' => home_url( '/catering/' ) . '#catering',
		'founder'  => $home . '#founder',
	);
}

function bh_output_schema() {
	if ( ! apply_filters( 'bh_output_schema', true ) || is_admin() ) {
		return;
	}
	$graph = bh_build_schema_graph();
	if ( ! $graph ) {
		return;
	}
	echo "\n<script type=\"application/ld+json\">" . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}

function bh_build_schema_graph() {
	$ids   = bh_schema_ids();
	$graph = array();
	$locs  = bh_get_locations();

	$graph[] = bh_schema_organization( $locs );
	$graph[] = array(
		'@type'      => 'WebSite',
		'@id'        => $ids['website'],
		'url'        => home_url( '/' ),
		'name'       => bh_setting( 'business_name' ),
		'inLanguage' => get_bloginfo( 'language' ),
		'publisher'  => array( '@id' => $ids['org'] ),
	);

	// Restaurants are emitted on every page so each page reinforces NAP.
	foreach ( $locs as $loc ) {
		$graph[] = bh_schema_restaurant( $loc );
	}

	$page = bh_schema_webpage();
	if ( $page ) {
		$graph[] = $page;
	}

	$slug = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';

	if ( 'menu' === $slug ) {
		$graph[] = bh_schema_menu();
	}
	if ( 'catering' === $slug || is_front_page() ) {
		$graph[] = bh_schema_catering();
	}

	$faq_topic = bh_schema_faq_topic( $slug );
	if ( null !== $faq_topic ) {
		$faqs = bh_get_faqs( $faq_topic );
		if ( $faqs ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => bh_current_url() . '#faq',
				'mainEntity' => array_map(
					fn( $f ) => array(
						'@type'          => 'Question',
						'name'           => $f['question'],
						'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['text'] ),
					),
					$faqs
				),
			);
		}
	}

	$crumbs = bh_schema_breadcrumbs();
	if ( $crumbs ) {
		$graph[] = $crumbs;
	}

	return apply_filters( 'bh_schema_graph', $graph );
}

/**
 * Which FAQ topic (if any) is visible on the current page.
 * Must match what the theme renders; filterable.
 *
 * @return string|null  '' = all topics, null = no FAQ on this page.
 */
function bh_schema_faq_topic( $slug ) {
	$map = array(
		'faq'      => '',
		'catering' => 'catering',
		'parties'  => 'parties',
		'menu'     => 'menu',
	);
	$topic = null;
	if ( is_front_page() ) {
		$topic = 'general';
	} elseif ( isset( $map[ $slug ] ) ) {
		$topic = $map[ $slug ];
	} elseif ( is_post_type_archive( 'bh_location' ) || is_singular( 'bh_location' ) ) {
		$topic = 'locations';
	}
	return apply_filters( 'bh_schema_faq_topic', $topic, $slug );
}

function bh_current_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_post_type_archive() ) {
		return get_post_type_archive_link( get_query_var( 'post_type' ) ?: 'bh_location' );
	}
	return home_url( add_query_arg( array() ) );
}

function bh_schema_logo() {
	$logo_id = get_theme_mod( 'custom_logo' );
	$url     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	return $url ?: '';
}

function bh_schema_organization( $locs ) {
	$ids = bh_schema_ids();
	$org = array(
		'@type'        => 'Organization',
		'@id'          => $ids['org'],
		'name'         => bh_setting( 'business_name' ),
		'legalName'    => bh_setting( 'legal_name' ),
		'alternateName' => array( "Bigham's BBQ", 'Bighams Smokehouse', "Bigham's Smokehouse Lubbock" ),
		'url'          => home_url( '/' ),
		'description'  => bh_setting( 'description' ),
		'slogan'       => bh_setting( 'tagline' ),
		'foundingDate' => bh_setting( 'founded' ),
		'foundingLocation' => array( '@type' => 'Place', 'name' => 'Lubbock, Texas' ),
		'founder'      => array( '@type' => 'Person', '@id' => $ids['founder'], 'name' => bh_setting( 'founder' ) ),
		'telephone'    => bh_phone_e164( bh_setting( 'phone' ) ),
		'email'        => bh_setting( 'email' ),
		'areaServed'   => array( '@type' => 'City', 'name' => 'Lubbock', 'containedInPlace' => array( '@type' => 'State', 'name' => 'Texas' ) ),
		'knowsAbout'   => array( 'Texas barbecue', 'Smoked brisket', 'BBQ catering', 'Private event dining' ),
		'sameAs'       => array_values( bh_social_links() ),
		'subOrganization' => array_map( fn( $l ) => array( '@id' => $l['permalink'] . '#restaurant' ), $locs ),
		'contactPoint' => array(
			array(
				'@type'       => 'ContactPoint',
				'contactType' => 'catering',
				'telephone'   => bh_phone_e164( bh_setting( 'catering_phone' ) ),
				'email'       => bh_setting( 'catering_email' ),
				'areaServed'  => 'US-TX',
			),
			array(
				'@type'       => 'ContactPoint',
				'contactType' => 'customer service',
				'telephone'   => bh_phone_e164( bh_setting( 'phone' ) ),
				'email'       => bh_setting( 'email' ),
			),
		),
	);
	$owners = array_filter( array_map( 'trim', explode( ',', bh_setting( 'owners' ) ) ) );
	if ( $owners ) {
		$org['employee'] = array_map( fn( $n ) => array( '@type' => 'Person', 'name' => $n, 'jobTitle' => 'Owner' ), $owners );
	}
	$logo = bh_schema_logo();
	if ( $logo ) {
		$org['logo']  = array( '@type' => 'ImageObject', 'url' => $logo );
		$org['image'] = $logo;
	}
	return array_filter( $org );
}

function bh_schema_hours( $hours ) {
	$map = array( 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' );
	$out = array();
	// Group days that share open/close.
	$groups = array();
	foreach ( $map as $k => $day ) {
		$d = $hours[ $k ] ?? array();
		if ( empty( $d ) || ! empty( $d['closed'] ) || empty( $d['open'] ) ) {
			continue;
		}
		$groups[ $d['open'] . '-' . $d['close'] ]['days'][] = 'https://schema.org/' . $day;
		$groups[ $d['open'] . '-' . $d['close'] ]['open']   = $d['open'];
		$groups[ $d['open'] . '-' . $d['close'] ]['close']  = $d['close'];
	}
	foreach ( $groups as $g ) {
		$out[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => $g['days'],
			'opens'     => $g['open'],
			'closes'    => $g['close'],
		);
	}
	return $out;
}

function bh_schema_restaurant( $loc ) {
	$ids = bh_schema_ids();
	$r   = array(
		'@type'          => 'Restaurant',
		'@id'            => $loc['permalink'] . '#restaurant',
		'name'           => bh_setting( 'business_name' ) . ' – ' . $loc['short_name'],
		'alternateName'  => bh_setting( 'business_name' ) . ' ' . $loc['title'],
		'url'            => $loc['permalink'],
		'telephone'      => $loc['phone_e164'],
		'priceRange'     => bh_setting( 'price_range' ),
		'servesCuisine'  => array_map( 'trim', explode( ',', bh_setting( 'cuisines' ) ) ),
		'parentOrganization' => array( '@id' => $ids['org'] ),
		'brand'          => array( '@id' => $ids['org'] ),
		'address'        => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $loc['street'],
			'addressLocality' => $loc['city'],
			'addressRegion'   => $loc['state'],
			'postalCode'      => $loc['zip'],
			'addressCountry'  => 'US',
		),
		'hasMap'         => $loc['maps_url'],
		'hasMenu'        => array( '@id' => $ids['menu'] ),
		'menu'           => home_url( '/menu/' ),
		'acceptsReservations' => false,
		'currenciesAccepted'  => 'USD',
		'openingHoursSpecification' => bh_schema_hours( $loc['hours'] ),
		'areaServed'     => array( '@type' => 'City', 'name' => $loc['city'] ),
		'makesOffer'     => array( '@id' => $ids['catering'] ),
	);
	if ( $loc['lat'] && $loc['lng'] ) {
		$r['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $loc['lat'], 'longitude' => (float) $loc['lng'] );
	}
	if ( $loc['image_id'] ) {
		$r['image'] = wp_get_attachment_image_url( $loc['image_id'], 'large' );
	} elseif ( bh_schema_logo() ) {
		$r['image'] = bh_schema_logo();
	}
	if ( $loc['features'] ) {
		$r['amenityFeature'] = array_map( fn( $f ) => array( '@type' => 'LocationFeatureSpecification', 'name' => $f, 'value' => true ), $loc['features'] );
	}
	$actions = array();
	if ( $loc['order_url'] ) {
		$actions[] = array(
			'@type'  => 'OrderAction',
			'target' => array(
				'@type'          => 'EntryPoint',
				'urlTemplate'    => $loc['order_url'],
				'inLanguage'     => 'en-US',
				'actionPlatform' => array( 'https://schema.org/DesktopWebPlatform', 'https://schema.org/MobileWebPlatform' ),
			),
			'deliveryMethod' => array( 'http://purl.org/goodrelations/v1#DeliveryModePickUp' ),
		);
	}
	foreach ( array( 'ubereats_url', 'doordash_url' ) as $k ) {
		if ( $loc[ $k ] ) {
			$actions[] = array(
				'@type'          => 'OrderAction',
				'target'         => $loc[ $k ],
				'deliveryMethod' => array( 'http://purl.org/goodrelations/v1#DeliveryModeOwnFleet' ),
			);
		}
	}
	if ( $actions ) {
		$r['potentialAction'] = $actions;
	}
	if ( bh_setting( 'rating_value' ) && bh_setting( 'rating_count' ) ) {
		$r['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => bh_setting( 'rating_value' ),
			'reviewCount' => bh_setting( 'rating_count' ),
		);
	}
	return array_filter( $r );
}

function bh_schema_menu() {
	$ids      = bh_schema_ids();
	$sections = array();
	foreach ( array( 'restaurant', 'specials', 'happy-hour' ) as $group ) {
		foreach ( bh_get_menu( $group ) as $sec ) {
			$sections[] = array(
				'@type'       => 'MenuSection',
				'name'        => $sec['name'],
				'description' => $sec['description'],
				'hasMenuItem' => array_map( 'bh_schema_menu_item', $sec['items'] ),
			);
		}
	}
	return array(
		'@type'          => 'Menu',
		'@id'            => $ids['menu'],
		'name'           => bh_setting( 'business_name' ) . ' Menu',
		'url'            => home_url( '/menu/' ),
		'inLanguage'     => 'en-US',
		'hasMenuSection' => $sections,
	);
}

function bh_schema_menu_item( $item ) {
	$offers = array();
	foreach ( $item['prices'] as $p ) {
		if ( '' === $p['price'] ) {
			continue;
		}
		$offers[] = array_filter(
			array(
				'@type'         => 'Offer',
				'name'          => $p['label'],
				'price'         => number_format( (float) $p['price'], 2, '.', '' ),
				'priceCurrency' => 'USD',
			)
		);
	}
	$out = array(
		'@type'       => 'MenuItem',
		'name'        => $item['title'],
		'description' => $item['description'],
		'offers'      => 1 === count( $offers ) ? $offers[0] : $offers,
	);
	if ( $item['image_id'] ) {
		$out['image'] = wp_get_attachment_image_url( $item['image_id'], 'large' );
	}
	$diet_map = array( 'gluten free' => 'GlutenFreeDiet', 'vegetarian' => 'VegetarianDiet', 'vegan' => 'VeganDiet' );
	foreach ( $item['diet'] as $d ) {
		if ( isset( $diet_map[ strtolower( $d ) ] ) ) {
			$out['suitableForDiet'][] = 'https://schema.org/' . $diet_map[ strtolower( $d ) ];
		}
	}
	return array_filter( $out );
}

function bh_schema_catering() {
	$ids   = bh_schema_ids();
	$items = array();
	foreach ( bh_get_packages() as $p ) {
		$offer = array(
			'@type'       => 'Offer',
			'name'        => $p['title'],
			'description' => $p['description'],
			'itemOffered' => array( '@type' => 'Service', 'name' => $p['title'] . ' catering package' ),
		);
		if ( $p['price'] ) {
			$offer['priceSpecification'] = array(
				'@type'         => 'UnitPriceSpecification',
				'price'         => number_format( (float) $p['price'], 2, '.', '' ),
				'priceCurrency' => 'USD',
				'unitText'      => $p['unit'],
			);
		}
		if ( $p['min_guests'] ) {
			$offer['eligibleQuantity'] = array( '@type' => 'QuantitativeValue', 'minValue' => $p['min_guests'], 'unitText' => 'guests' );
		}
		$items[] = $offer;
	}
	return array(
		'@type'       => 'Service',
		'@id'         => $ids['catering'],
		'name'        => bh_setting( 'business_name' ) . ' BBQ Catering',
		'serviceType' => 'Barbecue catering',
		'description' => 'Texas BBQ catering in Lubbock for office lunches, weddings, holiday parties, tailgates and events of any size — pickup, delivery or full-service catered and served.',
		'url'         => home_url( '/catering/' ),
		'provider'    => array( '@id' => $ids['org'] ),
		'areaServed'  => array( '@type' => 'City', 'name' => 'Lubbock', 'containedInPlace' => array( '@type' => 'State', 'name' => 'Texas' ) ),
		'hasOfferCatalog' => array(
			'@type'           => 'OfferCatalog',
			'name'            => 'Catering packages',
			'itemListElement' => $items,
		),
	);
}

function bh_schema_webpage() {
	$ids = bh_schema_ids();
	$url = bh_current_url();
	$type = 'WebPage';
	$slug = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
	if ( 'about' === $slug ) {
		$type = 'AboutPage';
	} elseif ( 'contact' === $slug ) {
		$type = 'ContactPage';
	} elseif ( is_post_type_archive() ) {
		$type = 'CollectionPage';
	}
	$page = array(
		'@type'      => $type,
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => wp_get_document_title(),
		'isPartOf'   => array( '@id' => $ids['website'] ),
		'about'      => array( '@id' => $ids['org'] ),
		'inLanguage' => get_bloginfo( 'language' ),
	);
	if ( is_singular( 'bh_location' ) ) {
		$page['about']      = array( '@id' => get_permalink() . '#restaurant' );
		$page['mainEntity'] = array( '@id' => get_permalink() . '#restaurant' );
	} elseif ( 'menu' === $slug ) {
		$page['mainEntity'] = array( '@id' => $ids['menu'] );
	} elseif ( 'catering' === $slug ) {
		$page['mainEntity'] = array( '@id' => $ids['catering'] );
	}
	if ( is_singular() ) {
		$page['datePublished'] = get_the_date( 'c' );
		$page['dateModified']  = get_the_modified_date( 'c' );
	}
	if ( ! is_front_page() && bh_schema_breadcrumbs() ) {
		$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}
	return $page;
}

/**
 * Breadcrumb trail used for both schema and the theme's visual crumbs.
 *
 * @return array<int,array{name:string,url:string}>
 */
function bh_breadcrumb_trail() {
	if ( is_front_page() ) {
		return array();
	}
	$trail = array( array( 'name' => 'Home', 'url' => home_url( '/' ) ) );
	if ( is_singular( 'bh_location' ) ) {
		$trail[] = array( 'name' => 'Locations', 'url' => get_post_type_archive_link( 'bh_location' ) );
		$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_post_type_archive( 'bh_location' ) ) {
		$trail[] = array( 'name' => 'Locations', 'url' => get_post_type_archive_link( 'bh_location' ) );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $anc ) {
			$trail[] = array( 'name' => get_the_title( $anc ), 'url' => get_permalink( $anc ) );
		}
		$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_singular( 'post' ) ) {
		$blog = get_option( 'page_for_posts' );
		if ( $blog ) {
			$trail[] = array( 'name' => get_the_title( $blog ), 'url' => get_permalink( $blog ) );
		}
		$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_home() ) {
		$trail[] = array( 'name' => 'News', 'url' => bh_current_url() );
	} else {
		return array();
	}
	return $trail;
}

function bh_schema_breadcrumbs() {
	$trail = bh_breadcrumb_trail();
	if ( count( $trail ) < 2 ) {
		return null;
	}
	$items = array();
	foreach ( $trail as $i => $c ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $c['name'],
			'item'     => $c['url'],
		);
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => bh_current_url() . '#breadcrumb',
		'itemListElement' => $items,
	);
}
