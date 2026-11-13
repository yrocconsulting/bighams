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
 * Apply a Toast menus/v2 response to the website menu. Split out so it can be
 * tested with a saved response.
 */
function bh_apply_toast_menu( $data, $dry_run = true ) {
	$sections = bh_toast_flatten_menus( $data );
	$report   = array(
		'dry_run'   => $dry_run,
		'time'      => time(),
		'sections'  => count( $sections ),
		'added'     => array(),
		'updated'   => array(),
		'unchanged' => 0,
		'drafted'   => array(),
		'groups'    => array(),
	);
	if ( ! $sections ) {
		return new WP_Error( 'bh_toast_empty', 'Toast returned no menu items visible on online ordering. Nothing was changed.' );
	}
	$seen = array();
	foreach ( $sections as $si => $sec ) {
		$report['groups'][ $sec['group'] ] = true;
		$tid = bh_toast_menu_section( $sec, ( $si + 1 ) * 10, $dry_run );
		foreach ( $sec['items'] as $ii => $it ) {
			$name   = trim( (string) $it['name'] );
			$prices = bh_price_rows_to_lines( bh_toast_item_prices( $it, $data ) );
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
					if ( ! has_post_thumbnail( $id ) && ! empty( $it['image'] ) && is_string( $it['image'] ) && function_exists( 'bh_import_media' ) ) {
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
			foreach ( array( 'added' => 'Add', 'updated' => 'Update', 'drafted' => 'Remove (to Draft)' ) as $k => $label ) {
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
