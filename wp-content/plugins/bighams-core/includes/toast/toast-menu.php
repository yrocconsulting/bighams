<?php
/**
 * Website menu ← Toast menu.
 *
 * Makes the website menu match what guests see on Toast online ordering:
 * the same sections, items, order and prices. Uses Toast's Menus API
 * (GET /menus/v2/menus), the same read-only credentials as the sales sync.
 *
 * What it keeps from the website: photos, badges, "feature on homepage",
 * and the longer website description when Toast has none.
 * What Toast controls: which items exist, their names, sections, order and prices.
 * Items no longer on Toast are moved to Draft (never deleted), so nothing is lost.
 *
 *   Bigham's → Settings → Toast tools → "Preview menu import" / "Import menu now"
 *   wp bighams toast-menu --dry-run      (preview)
 *   wp bighams toast-menu                (apply)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which website menu a Toast menu feeds, from its name.
 */
function bh_toast_menu_group_for( $menu_name ) {
	$n = strtolower( (string) $menu_name );
	if ( false !== strpos( $n, 'cater' ) ) {
		return 'catering';
	}
	if ( false !== strpos( $n, 'happy' ) ) {
		return 'happy-hour';
	}
	if ( false !== strpos( $n, 'special' ) ) {
		return 'specials';
	}
	return 'restaurant';
}

/**
 * Is a Toast menu entity shown on Toast online ordering?
 * Entities without a visibility list are treated as visible.
 */
function bh_toast_visible_online( $entity ) {
	if ( empty( $entity['visibility'] ) || ! is_array( $entity['visibility'] ) ) {
		return true;
	}
	return in_array( 'TOAST_ONLINE_ORDERING', $entity['visibility'], true );
}

/**
 * Price rows for a Toast menu item: a single price, or one row per size.
 *
 * @param array $item Toast MenuItem.
 * @param array $refs Toast response (for modifierGroupReferences / modifierOptionReferences).
 * @return array<int,array{label:string,price:string,note:string}>
 */
function bh_toast_item_prices( $item, $refs ) {
	$groups  = (array) ( $refs['modifierGroupReferences'] ?? array() );
	$options = (array) ( $refs['modifierOptionReferences'] ?? array() );
	$unit    = array( 'LB' => 'Pound', 'OZ' => 'Ounce', 'KG' => 'Kilogram', 'G' => 'Gram' )[ $item['unitOfMeasure'] ?? '' ] ?? '';

	// Size-priced items: find the size group (by the pricing rule's guid, or a SIZE_PRICE / "Size" group).
	$size_guid = $item['pricingRules']['sizeSpecificPricingGuid'] ?? '';
	foreach ( (array) ( $item['modifierGroupReferences'] ?? array() ) as $ref_id ) {
		$g = $groups[ $ref_id ] ?? $groups[ (string) $ref_id ] ?? null;
		if ( ! $g ) {
			continue;
		}
		$is_size = ( $size_guid && ( $g['guid'] ?? '' ) === $size_guid )
			|| 'SIZE_PRICE' === ( $g['pricingStrategy'] ?? '' )
			|| ( ( 'SIZE_PRICE' === ( $item['pricingStrategy'] ?? '' ) ) && preg_match( '/size/i', (string) ( $g['name'] ?? '' ) ) );
		if ( ! $is_size ) {
			continue;
		}
		$rows = array();
		foreach ( (array) ( $g['modifierOptionReferences'] ?? array() ) as $oid ) {
			$o = $options[ $oid ] ?? $options[ (string) $oid ] ?? null;
			if ( ! $o || ! bh_toast_visible_online( $o ) ) {
				continue;
			}
			$price = $o['price'] ?? null;
			if ( null === $price && isset( $o['prices'][0]['price'] ) ) {
				$price = $o['prices'][0]['price'];
			}
			$rows[] = array(
				'label' => trim( (string) ( $o['name'] ?? '' ) ),
				'price' => null === $price ? '' : number_format( (float) $price, 2, '.', '' ),
				'note'  => '',
			);
		}
		if ( $rows ) {
			return $rows;
		}
	}

	if ( isset( $item['price'] ) && null !== $item['price'] ) {
		return array(
			array(
				'label' => $unit,
				'price' => number_format( (float) $item['price'], 2, '.', '' ),
				'note'  => '',
			),
		);
	}
	return array();
}

/**
 * Price rows → the website's "Label | Price" lines.
 */
function bh_price_rows_to_lines( $rows ) {
	$lines = array();
	foreach ( $rows as $r ) {
		if ( '' === $r['price'] ) {
			continue;
		}
		$lines[] = '' === $r['label'] ? $r['price'] : $r['label'] . ' | ' . $r['price'] . ( $r['note'] ? ' | ' . $r['note'] : '' );
	}
	return implode( "\n", $lines );
}

/**
 * Flatten Toast menus into [group, section path, section guid, section description, items[]].
 */
function bh_toast_flatten_menus( $data ) {
	$sections = array();
	$walk     = function ( $groups, $website_group, $prefix ) use ( &$walk, &$sections ) {
		foreach ( (array) $groups as $g ) {
			if ( ! bh_toast_visible_online( $g ) ) {
				continue;
			}
			$name  = trim( (string) ( $g['name'] ?? '' ) );
			$items = array();
			foreach ( (array) ( $g['menuItems'] ?? array() ) as $it ) {
				if ( bh_toast_visible_online( $it ) && '' !== trim( (string) ( $it['name'] ?? '' ) ) ) {
					$items[] = $it;
				}
			}
			if ( $items ) {
				$sections[] = array(
					'group'       => $website_group,
					'name'        => $prefix ? $prefix . ' – ' . $name : $name,
					'guid'        => (string) ( $g['guid'] ?? '' ),
					'description' => trim( (string) ( $g['description'] ?? '' ) ),
					'items'       => $items,
				);
			}
			if ( ! empty( $g['menuGroups'] ) ) {
				$walk( $g['menuGroups'], $website_group, $name );
			}
		}
	};
	foreach ( (array) ( $data['menus'] ?? array() ) as $menu ) {
		if ( ! bh_toast_visible_online( $menu ) ) {
			continue;
		}
		$walk( $menu['menuGroups'] ?? array(), bh_toast_menu_group_for( $menu['name'] ?? '' ), '' );
	}
	return $sections;
}

/**
 * Find or create a website menu section for a Toast menu group.
 *
 * @return int term ID (0 on dry run when new).
 */
function bh_toast_menu_section( $sec, $order, $dry ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'bh_menu_section',
			'hide_empty' => false,
			'meta_key'   => 'bh_toast_guid', // phpcs:ignore
			'meta_value' => $sec['guid'], // phpcs:ignore
		)
	);
	$term = ( $sec['guid'] && ! is_wp_error( $terms ) && $terms ) ? $terms[0] : null;
	if ( ! $term ) {
		foreach ( get_terms( array( 'taxonomy' => 'bh_menu_section', 'hide_empty' => false ) ) as $t ) {
			$tg = get_term_meta( $t->term_id, 'bh_group', true ) ?: 'restaurant';
			if ( $tg === $sec['group'] && 0 === strcasecmp( html_entity_decode( $t->name ), $sec['name'] ) ) {
				$term = $t;
				break;
			}
		}
	}
	if ( $dry ) {
		return $term ? (int) $term->term_id : 0;
	}
	if ( ! $term ) {
		$slug = sanitize_title( ( 'restaurant' === $sec['group'] ? '' : $sec['group'] . '-' ) . $sec['name'] );
		$res  = wp_insert_term( $sec['name'], 'bh_menu_section', array( 'slug' => wp_unique_term_slug( $slug, (object) array( 'taxonomy' => 'bh_menu_section', 'parent' => 0 ) ), 'description' => $sec['description'] ) );
		if ( is_wp_error( $res ) ) {
			return 0;
		}
		$tid = (int) $res['term_id'];
	} else {
		$tid = (int) $term->term_id;
		if ( $term->name !== $sec['name'] ) {
			wp_update_term( $tid, 'bh_menu_section', array( 'name' => $sec['name'] ) );
		}
	}
	update_term_meta( $tid, 'bh_toast_guid', $sec['guid'] );
	update_term_meta( $tid, 'bh_group', $sec['group'] );
	update_term_meta( $tid, 'bh_order', $order );
	return $tid;
}

/**
 * Find the website item for a Toast item: by Toast GUID, then by name within the same menu group.
 */
function bh_toast_find_item( $toast_item, $group ) {
	$guid = (string) ( $toast_item['guid'] ?? '' );
	if ( $guid ) {
		$ids = get_posts(
			array(
				'post_type'      => 'bh_menu_item',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_bh_toast_guid', // phpcs:ignore
				'meta_value'     => $guid, // phpcs:ignore
			)
		);
		if ( $ids ) {
			return (int) $ids[0];
		}
	}
	$name = trim( (string) ( $toast_item['name'] ?? '' ) );
	foreach ( get_posts( array( 'post_type' => 'bh_menu_item', 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => -1, 'title' => $name ) ) as $p ) {
		foreach ( wp_get_object_terms( $p->ID, 'bh_menu_section' ) as $t ) {
			if ( ( get_term_meta( $t->term_id, 'bh_group', true ) ?: 'restaurant' ) === $group ) {
				return (int) $p->ID;
			}
		}
	}
	return 0;
}

/**
 * Import (or preview) the Toast menu.
 *
 * @param bool $dry_run Only report what would change.
 * @return array|WP_Error Report.
 */
function bh_toast_import_menu( $dry_run = true ) {
	$client = new BH_Toast_Client();
	if ( ! $client->is_configured() ) {
		return new WP_Error( 'bh_toast_config', 'Add the Toast API credentials in Bigham\'s → Settings first.' );
	}
	$guid = bh_setting( 'toast_menu_location' );
	if ( ! $guid ) {
		foreach ( bh_get_locations() as $l ) {
			if ( $l['toast_guid'] ) {
				$guid = $l['toast_guid'];
				break;
			}
		}
	}
	if ( ! $guid ) {
		return new WP_Error( 'bh_toast_noloc', 'Add a Toast Restaurant GUID to at least one Location first.' );
	}
	$data = $client->menus( $guid );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	return bh_apply_toast_menu( $data, $dry_run );
}

/**
 * Normalise a name for matching: lowercase, straight quotes, single spaces.
 */
function bh_toast_norm( $name ) {
	$name = str_replace( array( "\u{2019}", "\u{2018}", '`', '&#8217;', '&#039;' ), "'", (string) $name );
	return strtolower( trim( preg_replace( '/\s+/', ' ', html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ) ) ) );
}

/**
 * Split a size or variant out of an item name, using the section's name:
 *   "Pan of Pinto Beans" (Pinto Beans)              → Pinto Beans · "Pan"
 *   "Small Chopped BBQ Sandwich Combo" (Chopped BBQ) → Chopped BBQ Sandwich · "Small Combo"
 *   "Pulled Pork – Big Baker Bag" (Big Baker Bag)    → Big Baker Bag · "Pulled Pork"
 *   "Sandwich Pack – Turkey Breast" (Sandwich Pack)  → Sandwich Pack · "Turkey Breast"
 *   "Small Frito Pie" (Frito Pies)                   → Frito Pie · "Small"
 *
 * @return array{0:string,1:string,2:string}|null [match key, display name, size label]
 */
function bh_toast_split_size( $name, $section ) {
	$low  = mb_strtolower( str_replace( array( "\u{2019}", "\u{2018}" ), "'", trim( $name ) ) );
	$keys = array( trim( $section ) );
	if ( preg_match( '/s$/i', trim( $section ) ) && mb_strlen( $section ) > 4 ) {
		$keys[] = mb_substr( trim( $section ), 0, -1 ); // "Frito Pies" → "Frito Pie".
	}
	foreach ( $keys as $key ) {
		$k   = mb_strtolower( str_replace( array( "\u{2019}", "\u{2018}" ), "'", $key ) );
		$pos = mb_strpos( $low, $k );
		if ( false === $pos || mb_strlen( $k ) < 3 ) {
			continue;
		}
		$pre  = trim( mb_substr( $name, 0, $pos ) );
		$post = trim( mb_substr( $name, $pos + mb_strlen( $k ) ) );
		$pre  = trim( preg_replace( '/\s*(\bof|[\x{2013}\x{2014}-])$/iu', '', $pre ) );
		$post = trim( preg_replace( '/^[\x{2013}\x{2014}-]\s*/u', '', $post ) );
		if ( '' === $post ) {
			return array( $k, $key, $pre );
		}
		if ( '' === $pre ) {
			return array( $k, $key, $post );
		}
		$words = preg_split( '/\s+/', $post );
		$first = array_shift( $words );
		return array( $k . ' ' . mb_strtolower( $first ), $key . ' ' . $first, trim( $pre . ' ' . implode( ' ', $words ) ) );
	}
	// Fallback: one word of the section name, with a size before it and a word after it
	// ("Small Sausage Sandwich" in "Original Sausage" → Original Sausage Sandwich · "Small").
	foreach ( preg_split( '/\s+/', trim( $section ) ) as $word ) {
		$w = mb_strtolower( $word );
		if ( mb_strlen( $w ) < 4 || ! preg_match( '/^(.+?)\s+' . preg_quote( $w, '/' ) . '\s+(.+)$/u', $low, $mm ) ) {
			continue;
		}
		if ( ! preg_match( "/^(small|medium|large|single|regular|half|full|one|two|three|four|kid'?s|\\d+ ?oz)$/i", trim( $mm[1] ) ) ) {
			continue; // Only real size words ("Little Texan …" is a dish name, not a size).
		}
		$pos   = mb_strlen( $mm[1] ) + 1;
		$pre   = trim( mb_substr( $name, 0, $pos ) );
		$post  = trim( mb_substr( $name, $pos + mb_strlen( $w ) ) );
		$words = preg_split( '/\s+/', $post );
		$first = array_shift( $words );
		$k     = mb_strtolower( trim( $section ) );
		return array( $k . ' ' . mb_strtolower( $first ), trim( $section ) . ' ' . $first, trim( $pre . ' ' . implode( ' ', $words ) ) );
	}
	return null;
}

/**
 * Website display rules applied to the Toast menu (Settings → Toast POS):
 *  - toast_hide:  Toast sections or items kept in Toast POS but left off the website.
 *  - toast_merge: "New section = Section A, Section B" combines size-per-section groups
 *                 (e.g. 20 oz Drinks / 32 oz Drinks) into one section, each item listed once
 *                 with a price per size.
 *
 * @param array $sections From bh_toast_flatten_menus().
 * @param array $data     Toast response (for prices).
 * @param array $report   Report, receives 'hidden'.
 */
function bh_toast_apply_display_rules( $sections, $data, &$report ) {
	$hide = array_flip( array_map( 'bh_toast_norm', bh_lines( bh_setting( 'toast_hide' ) ) ) );

	// Parse merge rules.
	$rules = array();
	foreach ( bh_lines( bh_setting( 'toast_merge' ) ) as $line ) {
		if ( false === strpos( $line, '=' ) ) {
			continue;
		}
		list( $target, $sources ) = array_map( 'trim', explode( '=', $line, 2 ) );
		$sources = bh_split_names( $sources );
		if ( $target && $sources ) {
			$rules[] = array( 'target' => $target, 'sources' => array_map( 'bh_toast_norm', $sources ) );
		}
	}

	$out    = array();
	$merged = array(); // rule index → position in $out.
	foreach ( $sections as $sec ) {
		if ( isset( $hide[ bh_toast_norm( $sec['name'] ) ] ) ) {
			$report['hidden'][] = $sec['name'] . ' (section, ' . count( $sec['items'] ) . ' items)';
			continue;
		}
		$items = array();
		foreach ( $sec['items'] as $it ) {
			if ( isset( $hide[ bh_toast_norm( $it['name'] ) ] ) ) {
				$report['hidden'][] = $sec['name'] . ' › ' . $it['name'];
				continue;
			}
			$items[] = $it;
		}
		if ( ! $items ) {
			continue;
		}
		$sec['items'] = $items;

		// Does this section feed a merge rule?
		$rule_i = null;
		foreach ( $rules as $ri => $r ) {
			if ( in_array( bh_toast_norm( $sec['name'] ), $r['sources'], true ) ) {
				$rule_i = $ri;
				break;
			}
		}
		if ( null === $rule_i ) {
			$out[] = $sec;
			continue;
		}

		// Two shapes of "size" in Toast:
		//  A) size per SECTION: "20 oz Drinks" › "20 oz Sweet Tea"      → Sweet Tea, label "20 oz"
		//  B) size per ITEM:    "Peach Cobbler" › "Pan of Peach Cobbler" → Peach Cobbler, label "Pan"
		//  Otherwise (e.g. "Other Sweets") items are kept as they are.
		$target   = $rules[ $rule_i ]['target'];
		$sec_size = trim( preg_replace( '/\b' . preg_quote( $target, '/' ) . '\b/i', '', $sec['name'] ) );
		$shape_a  = '' !== $sec_size && bh_toast_norm( $sec_size ) !== bh_toast_norm( $sec['name'] );

		if ( ! isset( $merged[ $rule_i ] ) ) {
			$out[]             = array(
				'group'       => $sec['group'],
				'name'        => $target,
				'guid'        => 'merge:' . sanitize_title( $target ),
				'description' => '',
				'items'       => array(),
				'_index'      => array(),
			);
			$merged[ $rule_i ] = count( $out ) - 1;
		}
		$m        = &$out[ $merged[ $rule_i ] ];
		$nsec     = bh_toast_norm( $sec['name'] );
		foreach ( $items as $it ) {
			$nit  = bh_toast_norm( $it['name'] );
			$name = trim( $it['name'] );
			$split = bh_toast_split_size( $name, $sec['name'] );
			if ( $split ) {
				// Size/variant is part of the item name ("Pan of Pinto Beans", "Small Chopped BBQ Sandwich Combo",
				// "Pulled Pork – Big Baker Bag", "Small Frito Pie" in "Frito Pies").
				list( $base, $disp, $size ) = $split;
			} elseif ( $shape_a ) {
				$size = $sec_size;
				$base = trim( preg_replace( '/^' . preg_quote( bh_toast_norm( $size ), '/' ) . '\s*/i', '', $nit ) );
				$disp = trim( preg_replace( '/^' . str_replace( "'", "['\x{2019}]", preg_quote( $size, '/' ) ) . '\s*/iu', '', $name ) );
			} else {
				$size = '';
				$base = $nit;
				$disp = $name;
			}
			$disp = '' === $disp ? $name : $disp;
			$base = '' === $base ? $nit : $base;
			$rows = bh_toast_item_prices( $it, $data );
			foreach ( $rows as &$row ) {
				$row['label'] = trim( $size . ( '' !== $row['label'] && '' !== $size ? ' ' : '' ) . $row['label'] );
			}
			unset( $row );
			if ( ! isset( $m['_index'][ $base ] ) ) {
				$m['items'][]           = array(
					'name'        => $disp,
					'guid'        => 'merge:' . sanitize_title( $target ) . ':' . sanitize_title( $base ),
					'description' => (string) ( $it['description'] ?? '' ),
					'_rows'       => array(),
					'_src_guids'  => array(),
				);
				$m['_index'][ $base ] = count( $m['items'] ) - 1;
			}
			$k = $m['_index'][ $base ];
			$m['items'][ $k ]['_rows']        = array_merge( $m['items'][ $k ]['_rows'], $rows );
			$m['items'][ $k ]['_src_guids'][] = (string) ( $it['guid'] ?? '' );
			if ( '' === $m['items'][ $k ]['description'] && ! empty( $it['description'] ) ) {
				$m['items'][ $k ]['description'] = (string) $it['description'];
			}
		}
		unset( $m );
	}
	foreach ( $out as &$sec ) {
		foreach ( $sec['items'] as &$it ) {
			if ( isset( $it['_rows'] ) && count( $it['_rows'] ) > 1 ) {
				foreach ( $it['_rows'] as &$r ) {
					$r['label'] = '' === $r['label'] ? 'Regular' : $r['label'];
				}
				unset( $r );
			}
		}
		unset( $it );
	}
	unset( $sec );
	return $out;
}

/**
 * Apply a Toast menus/v2 response to the website menu. Split out so it can be
 * tested with a saved response.
 */
function bh_apply_toast_menu( $data, $dry_run = true ) {
	$report   = array(
		'hidden'    => array(),
		'dry_run'   => $dry_run,
		'time'      => time(),
		'sections'  => 0,
		'added'     => array(),
		'updated'   => array(),
		'unchanged' => 0,
		'drafted'   => array(),
		'groups'    => array(),
	);
	$sections           = bh_toast_apply_display_rules( bh_toast_flatten_menus( $data ), $data, $report );
	$report['sections'] = count( $sections );
	if ( ! $sections ) {
		return new WP_Error( 'bh_toast_empty', 'Toast returned no menu items visible on online ordering. Nothing was changed.' );
	}
	$seen = array();
	foreach ( $sections as $si => $sec ) {
		$report['groups'][ $sec['group'] ] = true;
		$tid = bh_toast_menu_section( $sec, ( $si + 1 ) * 10, $dry_run );
		foreach ( $sec['items'] as $ii => $it ) {
			$name   = trim( (string) $it['name'] );
			$prices = bh_price_rows_to_lines( isset( $it['_rows'] ) ? $it['_rows'] : bh_toast_item_prices( $it, $data ) );
			$desc   = trim( wp_strip_all_tags( (string) ( $it['description'] ?? '' ) ) );
			$id     = bh_toast_find_item( $it, $sec['group'] );
			$label  = $sec['name'] . ' › ' . $name;

			if ( ! $id ) {
				$report['added'][] = $label . ( $prices ? ' (' . str_replace( "\n", ', ', $prices ) . ')' : '' );
				if ( ! $dry_run ) {
					$id = wp_insert_post( array( 'post_type' => 'bh_menu_item', 'post_status' => 'publish', 'post_title' => $name, 'post_content' => $desc, 'menu_order' => $ii + 1 ) );
				}
			} else {
				$changes = array();
				$post    = get_post( $id );
				if ( 'publish' !== $post->post_status ) {
					$changes[] = 'back on the menu';
				}
				if ( $post->post_title !== $name ) {
					$changes[] = 'name';
				}
				if ( (string) get_post_meta( $id, '_bh_prices', true ) !== $prices ) {
					$changes[] = 'price ' . str_replace( "\n", ', ', (string) get_post_meta( $id, '_bh_prices', true ) ) . ' → ' . str_replace( "\n", ', ', $prices );
				}
				if ( $tid && ! has_term( $tid, 'bh_menu_section', $id ) ) {
					$changes[] = 'moved to ' . $sec['name'];
				}
				if ( $changes ) {
					$report['updated'][] = $label . ': ' . implode( '; ', $changes );
				} else {
					$report['unchanged']++;
				}
				if ( ! $dry_run ) {
					$update = array( 'ID' => $id, 'post_title' => $name, 'post_status' => 'publish', 'menu_order' => $ii + 1 );
					if ( $desc ) {
						$update['post_content'] = $desc;
					}
					wp_update_post( $update );
				}
			}
			if ( $id && ! is_wp_error( $id ) ) {
				$seen[ (int) $id ] = true;
				if ( ! $dry_run ) {
					update_post_meta( $id, '_bh_prices', $prices );
					update_post_meta( $id, '_bh_unit', '' );
					update_post_meta( $id, '_bh_toast_guid', (string) ( $it['guid'] ?? '' ) );
					if ( $tid ) {
						wp_set_object_terms( $id, array( $tid ), 'bh_menu_section', false );
					}
					if ( ! has_post_thumbnail( $id ) && ! empty( $it['_src_guids'] ) ) {
						// Combined item: reuse the photo of one of the Toast items it replaces.
						foreach ( array_filter( $it['_src_guids'] ) as $sg ) {
							$src = get_posts( array( 'post_type' => 'bh_menu_item', 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_bh_toast_guid', 'meta_value' => $sg ) ); // phpcs:ignore
							if ( $src && has_post_thumbnail( $src[0] ) ) {
								set_post_thumbnail( $id, get_post_thumbnail_id( $src[0] ) );
								break;
							}
						}
					}
					if ( ! has_post_thumbnail( $id ) && ! empty( $it['image'] ) && empty( $it['_rows'] ) && is_string( $it['image'] ) && function_exists( 'bh_import_media' ) ) {
						$img = bh_import_media( $it['image'], $name, $name . " at Bigham's Smokehouse, Lubbock TX" );
						if ( ! is_wp_error( $img ) ) {
							set_post_thumbnail( $id, $img );
						}
					}
				}
			}
		}
	}

	// Website items in the menus Toast now controls, but not on Toast → Draft.
	foreach ( get_posts( array( 'post_type' => 'bh_menu_item', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $p ) {
		if ( isset( $seen[ $p->ID ] ) ) {
			continue;
		}
		$in_synced = false;
		$sec_name  = '';
		foreach ( wp_get_object_terms( $p->ID, 'bh_menu_section' ) as $t ) {
			$g = get_term_meta( $t->term_id, 'bh_group', true ) ?: 'restaurant';
			if ( isset( $report['groups'][ $g ] ) ) {
				$in_synced = true;
				$sec_name  = html_entity_decode( $t->name );
			}
		}
		if ( ! $in_synced ) {
			continue;
		}
		$report['drafted'][] = $sec_name . ' › ' . $p->post_title;
		if ( ! $dry_run ) {
			wp_update_post( array( 'ID' => $p->ID, 'post_status' => 'draft' ) );
		}
	}
	$report['groups'] = array_keys( $report['groups'] );

	if ( ! $dry_run ) {
		update_option( 'bh_toast_menu_import', $report, false );
		delete_transient( 'bh_llms_txt' );
		delete_transient( 'bh_llms_txt_full' );
		delete_transient( 'bh_featured_fill' );
		wp_cache_flush();
	}
	return $report;
}

/* Daily automatic sync (if turned on in Settings). */
add_action( 'bh_toast_sync_event', function () {
	if ( 'auto' !== bh_setting( 'toast_menu_mode', 'manual' ) ) {
		return;
	}
	if ( (int) bh_now()->format( 'G' ) !== 5 ) {
		return;
	}
	$r = bh_toast_import_menu( false );
	if ( is_wp_error( $r ) ) {
		update_option( 'bh_toast_menu_import_error', $r->get_error_message(), false );
	}
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bighams toast-menu',
		function ( $args, $assoc ) {
			$dry = ! empty( $assoc['dry-run'] );
			$r   = ! empty( $assoc['file'] )
				? bh_apply_toast_menu( json_decode( (string) file_get_contents( $assoc['file'] ), true ), $dry )
				: bh_toast_import_menu( $dry );
			if ( is_wp_error( $r ) ) {
				WP_CLI::error( $r->get_error_message() );
			}
			foreach ( array( 'hidden' => 'Hidden on website', 'added' => 'Add', 'updated' => 'Update', 'drafted' => 'Remove (to Draft)' ) as $k => $label ) {
				foreach ( $r[ $k ] as $line ) {
					WP_CLI::line( "$label: $line" );
				}
			}
			WP_CLI::success( sprintf( '%s%d sections · %d added · %d updated · %d unchanged · %d moved to Draft', $dry ? 'PREVIEW — nothing changed. ' : '', $r['sections'], count( $r['added'] ), count( $r['updated'] ), $r['unchanged'], count( $r['drafted'] ) ) );
		},
		array(
			'shortdesc' => 'Make the website menu match the Toast online ordering menu.',
			'synopsis'  => array(
				array( 'type' => 'flag', 'name' => 'dry-run', 'optional' => true, 'description' => 'Preview the changes without applying them.' ),
				array( 'type' => 'assoc', 'name' => 'file', 'optional' => true, 'description' => 'Use a saved Toast /menus/v2/menus JSON response instead of calling Toast.' ),
			),
		)
	);
}
