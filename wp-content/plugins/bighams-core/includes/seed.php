<?php
/**
 * Starter content imported once on activation, taken from the current
 * bighamsbbq.com (Nov 2026). Everything is editable afterwards in wp-admin.
 *
 * Re-run manually (only fills what's missing): wp eval 'bh_seed_content(true);'
 */

defined( 'ABSPATH' ) || exit;

function bh_seed_content( $force = false ) {
	if ( get_option( 'bh_seeded' ) && ! $force ) {
		return;
	}
	bh_register_post_types();

	if ( ! get_option( 'bighams_settings' ) ) {
		update_option( 'bighams_settings', bh_default_settings() );
	}

	bh_seed_locations();
	bh_seed_menu();
	bh_seed_packages();
	bh_seed_faqs();
	bh_seed_reviews();
	bh_seed_pages();

	update_option( 'bh_seeded', time() );
}

/**
 * Insert a post unless one with the same title + type exists.
 */
function bh_seed_post( $type, $title, $content = '', $meta = array(), $extra = array() ) {
	$existing = get_posts(
		array(
			'post_type'      => $type,
			'title'          => $title,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	$id = wp_insert_post(
		array_merge(
			array(
				'post_type'    => $type,
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
			),
			$extra
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, '_bh_' . $k, $v );
		}
	}
	return (int) $id;
}

function bh_seed_hours( $spec ) {
	$out = array();
	foreach ( array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ) as $d ) {
		$out[ $d ] = isset( $spec[ $d ] ) ? array( 'open' => $spec[ $d ][0], 'close' => $spec[ $d ][1], 'closed' => '' ) : array( 'open' => '', 'close' => '', 'closed' => '1' );
	}
	return $out;
}

function bh_seed_locations() {
	$std = array(
		'tue' => array( '11:00', '20:00' ),
		'wed' => array( '11:00', '20:00' ),
		'thu' => array( '11:00', '20:00' ),
		'fri' => array( '11:00', '21:00' ),
		'sat' => array( '11:00', '21:00' ),
		'sun' => array( '11:00', '20:00' ),
	);
	$late = array(
		'tue' => array( '11:00', '21:00' ),
		'wed' => array( '11:00', '21:00' ),
		'thu' => array( '11:00', '21:00' ),
		'fri' => array( '11:00', '22:00' ),
		'sat' => array( '11:00', '22:00' ),
		'sun' => array( '11:00', '21:00' ),
	);
	$locs = array(
		array(
			'title'   => '82nd Street',
			'slug'    => '82nd-street',
			'excerpt' => 'South Lubbock BBQ at 82nd St & Indiana Ave, with a private dining room for up to 20 guests.',
			'content' => "Our 82nd Street smokehouse sits near 82nd Street and Indiana Avenue in South Lubbock. Grab slow-smoked brisket, ribs and sausage for dine-in or carry-out, order ahead online for pickup, or book our private dining room for a party of up to 20.",
			'meta'    => array(
				'short_name'   => '82nd St',
				'street'       => '3312 82nd St',
				'city'         => 'Lubbock',
				'state'        => 'TX',
				'zip'          => '79423',
				'cross_street' => '82nd St & Indiana Ave',
				'phone'        => '(806) 797-9931',
				'order_url'    => 'https://order.toasttab.com/online/bigham-s-smokehouse-82nd-st-3312-82nd-st',
				'ubereats_url' => 'https://www.ubereats.com/store/bighams-smokehouse-82nd/917BKKDgWJOULp9Oc4Qb7w',
				'features'     => "Dine-in\nCarry-out\nOnline ordering for pickup\nPrivate dining room (up to 20 guests)\nCatering pickup",
			),
			'hours'   => $std,
			'order'   => 1,
		),
		array(
			'title'   => '19th Street & Quaker',
			'slug'    => '19th-street-quaker',
			'excerpt' => 'Central Lubbock BBQ at 19th St & Quaker Ave, minutes from Texas Tech, with daily happy hour 3–6pm.',
			'content' => "Our 19th Street location at 19th and Quaker is a quick drive from Texas Tech and central Lubbock. Order online for pickup or delivery, swing by for daily happy hour from 3 to 6pm, or pick up catering for your office or game day.",
			'meta'    => array(
				'short_name'   => '19th St & Quaker',
				'street'       => '4302 19th St',
				'city'         => 'Lubbock',
				'state'        => 'TX',
				'zip'          => '79407',
				'cross_street' => '19th St & Quaker Ave',
				'phone'        => '(806) 797-9931',
				'order_url'    => 'https://order.toasttab.com/online/bigham-s-smokehouse-19th-st-4302-19th-street',
				'features'     => "Dine-in\nCarry-out\nOnline ordering for pickup & delivery\nHappy hour daily 3–6pm\nCatering pickup",
				'happy_hour'   => 'Every day, 3–6pm',
			),
			'hours'   => $std,
			'order'   => 2,
		),
		array(
			'title'   => 'Milwaukee Avenue',
			'slug'    => 'milwaukee-avenue',
			'excerpt' => 'West Lubbock BBQ at 50th St & Milwaukee Ave, open late on weekends with daily happy hour 3–6pm.',
			'content' => "Find us at 50th and Milwaukee Avenue in West Lubbock. We're open until 10pm on Fridays and Saturdays, pour happy hour drinks every day from 3 to 6pm, and have plenty of room for groups.",
			'meta'    => array(
				'short_name'   => 'Milwaukee Ave',
				'street'       => '5012 Milwaukee Ave',
				'city'         => 'Lubbock',
				'state'        => 'TX',
				'zip'          => '79407',
				'cross_street' => '50th St & Milwaukee Ave',
				'phone'        => '(806) 797-9931',
				'order_url'    => 'https://order.toasttab.com/online/bighams-milwaukee-5012-milwaukee-avenue',
				'ubereats_url' => 'https://www.ubereats.com/store/bighams-smokehouse-milwaukee/0gNC2PxeXLm4O67cg1AlYw',
				'features'     => "Dine-in\nCarry-out\nOnline ordering for pickup & delivery\nHappy hour daily 3–6pm\nOpen until 10pm Fri & Sat\nCatering pickup",
				'happy_hour'   => 'Every day, 3–6pm',
			),
			'hours'   => $late,
			'order'   => 3,
		),
	);
	foreach ( $locs as $l ) {
		$id = bh_seed_post( 'bh_location', $l['title'], $l['content'], $l['meta'], array( 'post_name' => $l['slug'], 'post_excerpt' => $l['excerpt'], 'menu_order' => $l['order'] ) );
		if ( $id && ! get_post_meta( $id, '_bh_hours', true ) ) {
			update_post_meta( $id, '_bh_hours', bh_seed_hours( $l['hours'] ) );
		}
	}
}

function bh_seed_section( $name, $group, $order, $desc = '', $note = '' ) {
	$term = term_exists( $name, 'bh_menu_section' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'bh_menu_section', array( 'description' => $desc, 'slug' => sanitize_title( ( 'restaurant' === $group ? '' : $group . '-' ) . $name ) ) );
	}
	if ( is_wp_error( $term ) ) {
		return 0;
	}
	$tid = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	update_term_meta( $tid, 'bh_group', $group );
	update_term_meta( $tid, 'bh_order', $order );
	if ( $note ) {
		update_term_meta( $tid, 'bh_note', $note );
	}
	return $tid;
}

function bh_seed_menu() {
	$sizes  = fn( $s, $h, $p, $q, $pan ) => "Single | $s\n1/2 Pint | $h\nPint | $p\nQuart | $q\nPan | $pan";
	$people = fn( $a, $b, $c, $d ) => "2 People | $a\n4 People | $b\n6 People | $c\n8 People | $d";

	$menu = array(
		array( 'Plates', 'restaurant', 10, 'Slow-smoked meats with three basic sides and warm Texas toast.', '', array(
			array( 'Little Meat Plate', 'Perfect for a lighter appetite — 1/4 lb of your choice of one slow-smoked meat, served with three basic sides and a slice of warm Texas toast.', '13.95', array( 'featured' => '1' ) ),
			array( 'Big Meat Plate', 'Built to satisfy — 1/2 lb of your choice of two slow-smoked meats, served with three basic sides and a slice of warm Texas toast.', '19.95', array( 'featured' => '1', 'badges' => 'Popular' ) ),
			array( 'Bigger Meat Plate', 'A hearty step up — 3/4 lb of your choice of three slow-smoked meats, paired with three basic sides and a slice of warm Texas toast.', '26.95' ),
			array( 'Biggest Meat Plate', 'Go all in — 1 lb of your choice of four slow-smoked meats, served with three basic sides and a slice of warm Texas toast.', '33.95' ),
			array( 'Little Sandwich Plate', 'Just the right start — 1/4 lb of your choice of one slow-smoked meat, served on a toasted bun with three basic sides.', '13.95' ),
			array( 'Big Sandwich Plate', 'More to love — 1/2 lb of your choice of two slow-smoked meats, served on a toasted bun with three basic sides.', '19.95' ),
			array( 'Bigger Sandwich Plate', 'Stacked and hearty — 3/4 lb of your choice of three slow-smoked meats, served on a toasted bun with three basic sides.', '26.95' ),
			array( 'Biggest Sandwich Plate', 'No holding back — 1 lb of your choice of four slow-smoked meats piled high on a toasted bun with three basic sides.', '33.95' ),
		) ),
		array( 'Meat by the Pound', 'restaurant', 20, 'Smoked low and slow, sliced to order.', '', array(
			array( 'Sliced Brisket', 'Tender, hand-sliced brisket with rich smokehouse flavor.', 'Pound | 29.95', array( 'featured' => '1', 'badges' => 'Popular' ) ),
			array( 'Chopped Brisket', 'Freshly chopped daily for bold, juicy flavor in every bite.', 'Pound | 29.95' ),
			array( 'Turkey Breast', 'Lean, tender, and perfectly smoked for a lighter option.', 'Pound | 20.95' ),
			array( 'St. Louis Style Pork Ribs', 'Slow-smoked and finished with our signature BBQ rub.', 'Pound | 16.95 | about half a rack (5–7 ribs)', array( 'featured' => '1' ) ),
			array( 'Original Sausage', 'Classic Texas smoked sausage with savory, seasoned flavor.', 'Pound | 19.95' ),
			array( 'Spicy Sausage', 'Pepper jack cheese sausage with a smoky kick and just the right amount of heat.', 'Pound | 19.95', array( 'badges' => 'Spicy' ) ),
			array( 'Pulled Pork', 'Slow-smoked and hand-pulled for juicy, flavorful bites.', 'Pound | 19.95' ),
		) ),
		array( 'Basic Sides', 'restaurant', 30, 'Southern staples made to pair with BBQ.', '', array(
			array( 'Pinto Beans', 'Texas-style pinto beans garnished with fresh onion and cilantro.', $sizes( '2.49', '4.25', '8.49', '15.95', '39.95' ) ),
			array( 'Potato Salad', 'A fresh take on a classic with a savory finish and a hint of dill.', $sizes( '2.49', '4.25', '8.49', '15.95', '39.45' ) ),
			array( 'Coleslaw', "Good ol' Texas coleslaw — cool, crisp, and made to pair with BBQ.", $sizes( '2.49', '4.25', '8.49', '15.95', '39.95' ) ),
			array( 'Green Beans', 'Southern-style green beans sautéed with fresh onions, garlic, and smoked bacon.', $sizes( '2.49', '4.25', '8.49', '15.95', '39.95' ) ),
		) ),
		array( 'Premium Sides', 'restaurant', 40, 'Elevated comfort food and signature favorites.', '', array(
			array( 'Fire Roasted Street Corn', 'Fire-roasted corn topped with creamy street-style flavors, charred jalapeños, and a bold blend of spices.', $sizes( '3.49', '5.95', '11.95', '23.95', '54.95' ), array( 'featured' => '1' ) ),
			array( 'Mac & Cheese', 'Rich white cheddar mac-n-cheese topped with parmesan bread crumbles.', $sizes( '3.49', '5.95', '11.95', '23.95', '54.95' ) ),
			array( 'Honey Skillet Cornbread', 'Baked fresh daily — sweet Southern cornbread finished with butter and a honey drizzle.', "Slice | 3.49\nPan | 19.95" ),
			array( 'Baked Potato', 'A fluffy baked potato loaded your way with butter, cheese, sour cream, and scallions. Add bacon for $0.95.', 'Single | 3.49' ),
			array( 'Fried Okra', 'Golden fried okra — a true Southern staple.', 'Single | 3.49' ),
			array( 'Garlic Parm Fries', 'Hand-cut fries tossed with garlic and parmesan.', 'Single | 3.49' ),
			array( "Twisty 'Tater Chips", 'Kettle-cooked for the perfect crunch and seasoned to savory perfection.', 'Bag | 3.49' ),
		) ),
		array( 'Spuds, Pies & Bowls', 'restaurant', 50, 'Smokehouse meats loaded onto Texas comfort food.', '', array(
			array( 'Little Stuffed Baker', 'One fluffy baked potato loaded with 1/4 lb of your choice of slow-smoked meat, butter, melted cheese, sour cream, and fresh scallions. Add bacon for $1.', '9.95' ),
			array( 'Big Stuffed Baker', 'Two potatoes loaded with 1/2 lb of your choice of slow-smoked meat and all the fixin\'s.', '17.95' ),
			array( 'Little Frito Pie', 'Frito corn chips topped with 1/4 lb of chopped brisket, slow-cooked beans, melted cheese, and our BBQ sauce.', '9.95', array( 'featured' => '1' ) ),
			array( 'Big Frito Pie', 'Frito corn chips piled high with 1/2 lb of chopped brisket, slow-cooked beans, melted cheese, and our BBQ sauce.', '17.95' ),
			array( 'Little Mac Bowl', 'White cheddar mac-n-cheese topped with 1/4 lb of your choice of slow-smoked meat, scallions and a drizzle of our sweet BBQ sauce.', '9.95' ),
			array( 'Big Mac Bowl', '1/2 lb of your choice of slow-smoked meat over rich white cheddar mac-n-cheese.', '17.95' ),
			array( 'Little Loaded Hog Heaven Fries', 'House-made fries topped with 1/4 lb of pulled pork, shredded cheese, crispy bacon, sweet BBQ sauce, lemon-garlic crema and scallions.', '9.95', array( 'featured' => '1' ) ),
			array( 'Big Loaded Hog Heaven Fries', 'A loaded fry feast with 1/2 lb of pulled pork, shredded cheese, crispy bacon, sweet BBQ sauce, lemon-garlic crema and scallions.', '17.95' ),
		) ),
		array( 'Family Packs', 'restaurant', 60, 'Feed the whole crew — priced for 2, 4, 6 or 8 people.', '', array(
			array( 'The Number 2', 'Smoked brisket paired with your choice of sausage, served with 3 basic sides, BBQ sauce, and warm Texas toast.', $people( '39.95', '74.95', '109.95', '144.95' ), array( 'featured' => '1', 'badges' => 'Popular' ) ),
			array( 'Sandwich Pack', 'Your choice of 1 or 2 meats, 3 basic sides, BBQ sauce, and toasted buns.', $people( '23.95', '44.95', '66.95', '89.95' ) ),
			array( 'Big Baker Bag', 'Baked potatoes with pulled pork and all the fixin\'s: butter, cheese, sour cream, and scallions. Substitute chopped brisket +$3.95 or turkey +$1.95 per person.', $people( '14.95', '29.95', '44.95', '59.95' ) ),
			array( 'The Turkey Platter', 'Slow-smoked turkey breast served with 3 basic sides, BBQ sauce, and warm Texas toast.', $people( '19.95', '39.95', '59.65', '79.95' ) ),
			array( 'The Frito Pie Bar', 'Crispy Fritos topped with chopped brisket, slow-cooked pinto beans, melted cheese, and our sweet BBQ sauce.', $people( '19.95', '39.95', '59.95', '79.95' ) ),
		) ),
		array( 'Sweets', 'restaurant', 70, 'Homestyle cobblers and Southern desserts.', '', array(
			array( 'Peach Cobbler', 'Sweet, juicy peaches baked with a golden, buttery topping.', $sizes( '3.25', '5.25', '9.95', '19.95', '49.95' ), array( 'featured' => '1' ) ),
			array( 'Cherry Cobbler', 'Bold, juicy cherries baked into a warm, comforting dessert.', $sizes( '3.25', '5.25', '9.95', '19.95', '49.95' ) ),
			array( 'Seasonal Cobbler', 'Pecan (fall/winter), apple (spring) or blackberry (summer).', $sizes( '3.25', '5.25', '9.95', '19.95', '49.95' ), array( 'badges' => 'Seasonal' ) ),
			array( 'Banana Pudding', 'Creamy vanilla pudding, fresh bananas, and layers of classic wafers.', $sizes( '3.25', '5.25', '9.95', '19.95', '49.95' ) ),
			array( 'Skillet Cookie', 'A warm chocolate chip skillet cookie topped with ice cream, chocolate sauce, and caramel drizzle.', '6.25' ),
			array( 'Key Lime Pie', 'A bright and creamy citrus classic with the perfect balance of sweet and tangy.', "Slice | 6.25\nWhole Pie | 65.00" ),
			array( 'Strawberry Shortcake', 'Fluffy cake layered with sweet strawberries and cream.', 'Slice | 6.25' ),
			array( 'Vanilla Ice Cream', 'Add a scoop to make any cobbler even sweeter.', 'Scoop | 3.00' ),
		) ),
		array( 'Daily Specials', 'specials', 10, '', '', array(
			array( 'Sunday Chicken Plate', 'Smoked half chicken served on the bone with your choice of 3 basic sides. A Sunday-only favorite.', 'Plate | 13.95', array( 'badges' => 'Sunday Only' ) ),
			array( 'Little Sandwich Special', 'Any little sandwich with a bag of our signature twisty \'tater chips.', '9.95' ),
			array( 'Big Sandwich Special', 'Any big sandwich with a bag of our signature twisty \'tater chips.', '15.95' ),
		) ),
		array( 'Happy Hour Beer', 'happy-hour', 10, 'Every day 3–6pm at 19th St and Milwaukee Ave.', '', array(
			array( 'Lone Star', '', '3.00' ),
			array( 'Miller Lite', '', '3.00' ),
			array( 'Bud Light', '', '3.00' ),
			array( 'Coors Original', '', '3.00' ),
			array( 'Texas Red', '', '3.00' ),
			array( 'Hopadillo', '', '3.00' ),
			array( 'Shiner Bock', '', '3.00' ),
			array( 'Michelob Ultra', '', '3.00' ),
		) ),
		array( 'Happy Hour Canned Cocktails', 'happy-hour', 20, '', '', array(
			array( 'Margarita with Cutwater Tequila', '', '5.00' ),
			array( 'Crown & Cola', '', '5.00' ),
			array( 'Ranch Water by Lone River', '', '5.00' ),
			array( 'Chilton by Ranch Rider', '', '5.00' ),
		) ),
		array( 'Catering Meats', 'catering', 10, 'Plan on 1/2 lb of meat per person. Order in 1/4 lb increments.', '1/2 lb per person recommended', array(
			array( 'Original Sausage', '', 'Pound | 20.00' ),
			array( 'Spicy Sausage', '', 'Pound | 20.00' ),
			array( 'Pulled Pork', '', 'Pound | 20.00' ),
			array( 'Turkey Breast', '', 'Pound | 22.00' ),
			array( 'Chopped Brisket', '', 'Pound | 30.00' ),
			array( 'Sliced Brisket', '', 'Pound | 30.00' ),
		) ),
		array( 'Catering Sides', 'catering', 20, 'Each pan serves about 16–20 people.', 'Pans serve 16–20', array(
			array( 'Pinto Beans', '', 'Pan | 34.95' ),
			array( 'Green Beans', '', 'Pan | 39.95' ),
			array( 'Potato Salad', '', 'Pan | 39.95' ),
			array( 'Coleslaw', '', 'Pan | 39.95' ),
			array( 'Mac-N-Cheese', '', 'Pan | 54.95' ),
			array( 'Seasonal Corn', 'Spring: fire-roasted jalapeño street corn · Summer: pit-smoked corn on the cob · Fall: green chile cream corn.', "Pan | 54.95\nCorn on the cob | 3.49" ),
			array( 'Honey Skillet Cornbread', '12 slices per pan, served with butter and a honey drizzle.', 'Pan | 19.95' ),
			array( 'Loaf of Texas Toast', '12 slices.', 'Loaf | 5.00' ),
		) ),
		array( 'Catering Drinks', 'catering', 30, '', '', array(
			array( 'Sweet Tea', '', 'Gallon | 6.95' ),
			array( 'Unsweet Tea', '', 'Gallon | 6.95' ),
			array( 'Lemonade', '', 'Gallon | 6.95' ),
		) ),
		array( 'Catering Desserts', 'catering', 40, 'Pans feed 12–15 people.', 'Pans feed 12–15', array(
			array( 'Peach Cobbler', '', 'Pan | 49.95' ),
			array( 'Cherry Cobbler', '', 'Pan | 49.95' ),
			array( 'Seasonal Cobbler', 'Blackberry (spring/summer), apple (fall/winter), pecan (holidays).', 'Pan | 49.95' ),
			array( 'Banana Pudding', '', 'Pan | 49.95' ),
		) ),
	);

	foreach ( $menu as $sec ) {
		list( $name, $group, $order, $desc, $note, $items ) = $sec;
		$tid = bh_seed_section( $name, $group, $order, $desc, $note );
		$i   = 0;
		foreach ( $items as $it ) {
			$meta     = array_merge( array( 'prices' => $it[2] ), $it[3] ?? array() );
			$existing = get_posts(
				array(
					'post_type'      => 'bh_menu_item',
					'title'          => $it[0],
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => array( array( 'taxonomy' => 'bh_menu_section', 'terms' => $tid ) ), // phpcs:ignore
				)
			);
			if ( $existing ) {
				continue;
			}
			$id = wp_insert_post( array( 'post_type' => 'bh_menu_item', 'post_title' => $it[0], 'post_content' => $it[1], 'post_status' => 'publish', 'menu_order' => ++$i ) );
			if ( $id && ! is_wp_error( $id ) ) {
				foreach ( $meta as $k => $v ) {
					update_post_meta( $id, '_bh_' . $k, $v );
				}
			}
			if ( $id && $tid ) {
				wp_set_object_terms( $id, $tid, 'bh_menu_section', true );
			}
		}
	}
}

function bh_seed_packages() {
	$pk = array(
		array(
			'All The Fixin\'s',
			'Our full Texas BBQ spread: three slow-smoked meats, three sides, Texas toast, BBQ sauce, pickles, onions and paper goods.',
			array(
				'tagline'    => 'Our most popular package',
				'price'      => '17.95',
				'minimum'    => 'Minimum 10 guests, in increments of 5',
				'min_guests' => '10',
				'featured'   => '1',
				'includes'   => "Choice of 3 meats: sliced brisket, pulled pork, smoked turkey breast, original or spicy sausage\nChoice of 3 sides: pinto beans, potato salad, coleslaw, green beans\nTexas toast & BBQ sauce\nPickles, onions, peppers & paper goods",
				'addons'     => "St. Louis pork ribs | 2.00 | per person\nDouble sliced brisket | 5.00 | per person\nPremium side (mac-n-cheese, seasonal corn or 'tater chips) | 1.95 | per person\nHoney skillet cornbread instead of Texas toast | 1.00 | per person\nChocolate chip cookie | 1.00 | per person\nCobbler | 2.00 | per person\nBanana pudding | 2.50 | per person",
			),
		),
		array(
			'Sandwich Meal Deal',
			'Individually built smoked-meat sandwiches with sides — the easiest way to feed an office or team.',
			array(
				'tagline'    => 'The best value',
				'price'      => '10.95',
				'minimum'    => 'Minimum 20 guests, in increments of 5',
				'min_guests' => '20',
				'includes'   => "Pulled pork or original sausage — $10.95/person\nSmoked turkey breast — $11.95/person\nSliced or chopped brisket — $13.95/person\nBasic sides, BBQ sauce & paper goods",
				'addons'     => "Premium side | 1.95 | per person\nChocolate chip cookie | 1.00 | per person\nCobbler | 1.50 | per person\nBanana pudding | 2.00 | per person",
			),
		),
		array(
			'Frito Pie Bar',
			'A build-your-own Texas favorite: crispy Fritos, chopped brisket, beans, cheese and our BBQ sauce, set up buffet-style.',
			array(
				'tagline'    => 'A crowd pleaser',
				'price'      => '10.95',
				'minimum'    => 'Minimum 10 guests, in increments of 5',
				'min_guests' => '10',
				'includes'   => "Fritos, chopped brisket, pinto beans & melted cheese\nSignature BBQ sauce\nPickles, peppers, onions & paper goods\nEverything packed separately, buffet-style",
				'addons'     => "Honey skillet cornbread | 2.00 | per person\nChocolate chip cookie | 1.00 | per person\nCobbler | 1.50 | per person\nBanana pudding | 2.00 | per person",
			),
		),
		array(
			'Stuffed Baker Bar',
			'Fluffy baked potatoes with butter, cheese, sour cream, scallions and your choice of slow-smoked meat, set up buffet-style.',
			array(
				'tagline'    => 'Build-your-own',
				'price'      => '',
				'minimum'    => 'Call for pricing',
				'includes'   => "Baked potatoes with butter, cheese, sour cream & scallions\nYour choice of smoked meat\nBBQ sauce, pickles, peppers, onions & paper goods",
				'addons'     => "Chopped or sliced brisket | 3.00 | per person\nTurkey breast | 1.00 | per person\nBacon topper | 1.00 | per person\nHoney skillet cornbread | 2.00 | per person\nCobbler | 1.50 | per person\nBanana pudding | 2.00 | per person",
			),
		),
		array(
			'TX Breakfast Taco Pack',
			'A dozen individually wrapped breakfast tacos: 4 bacon, egg & cheese, 4 potato, egg & cheese, and 4 brisket, egg & cheese, with BBQ sauce.',
			array(
				'tagline'    => 'Our morning favorite',
				'price'      => '49.95',
				'unit'       => 'per pack of 12',
				'minimum'    => 'Feeds 12 people',
				'includes'   => "12 breakfast tacos, individually wrapped\n4 bacon, egg & cheese · 4 potato, egg & cheese · 4 brisket, egg & cheese\nBBQ sauce",
			),
		),
	);
	$i = 0;
	foreach ( $pk as $p ) {
		$meta = wp_parse_args( $p[2], array( 'unit' => 'per person' ) );
		bh_seed_post( 'bh_catering_pkg', $p[0], $p[1], $meta, array( 'menu_order' => ++$i ) );
	}
}

function bh_seed_faqs() {
	$faqs = array(
		'general' => array(
			array( "Where are Bigham's Smokehouse locations?", "Bigham's Smokehouse has three locations in Lubbock, Texas: 3312 82nd St (near Indiana Ave), 4302 19th St (at Quaker Ave), and 5012 Milwaukee Ave (at 50th St)." ),
			array( "What are Bigham's Smokehouse hours?", 'All three locations are closed Mondays. 82nd St and 19th St are open Tuesday–Thursday and Sunday 11am–8pm and Friday–Saturday 11am–9pm. Milwaukee Ave is open Tuesday–Thursday and Sunday 11am–9pm and Friday–Saturday 11am–10pm.' ),
			array( 'Can I order online?', 'Yes. Order online for pickup from any location through our Toast online ordering, or get delivery through Uber Eats and DoorDash where available.' ),
			array( "How long has Bigham's been in business?", "Bigham's Smokehouse was opened in 1978 by Don Bigham and is still family-owned and operated by his wife and son, Joyce and Nick Bigham." ),
			array( 'What kind of barbecue do you serve?', 'Texas-style barbecue: slow-smoked sliced and chopped brisket, St. Louis style pork ribs, original and spicy smoked sausage, smoked turkey breast and pulled pork, plus scratch-made Southern sides and cobblers.' ),
			array( 'Do you have happy hour?', 'Yes — happy hour runs every day from 3 to 6pm at our 19th St and Milwaukee Ave locations, with $3 beers and $5 canned cocktails.' ),
		),
		'catering' => array(
			array( 'How far in advance do I need to order catering?', 'Catering orders need at least 24 hours notice. Orders for more than 200 people need 7 days notice.' ),
			array( 'How much BBQ should I order per person?', 'We recommend 1/2 pound of meat per person when feeding a crowd. Side pans serve about 16–20 people and dessert pans serve 12–15.' ),
			array( 'What does catering cost?', "Catering packages start at $10.95 per person (Sandwich Meal Deal or Frito Pie Bar). Our most popular package, All The Fixin's, is $17.95 per person with three meats and three sides. A dozen breakfast tacos are $49.95." ),
			array( 'Is there a minimum for catering?', "All The Fixin's and the Frito Pie Bar start at 10 guests, and the Sandwich Meal Deal starts at 20 guests, ordered in increments of 5." ),
			array( 'Do you deliver catering or serve on site?', 'Yes. Catering can be picked up, delivered for a fee, or fully catered and served by our team. For catered-and-served events call our office at (806) 797-9931.' ),
			array( 'What events do you cater?', 'Office lunches, corporate events, weddings and rehearsal dinners, birthday and holiday parties, school, church and team events, and tailgates — any size.' ),
		),
		'parties' => array(
			array( 'Can I book a private party at Bigham\'s?', 'Yes. Our private dining room at the 82nd Street location seats up to 20 guests, and all of our main dining rooms can be rented for larger events.' ),
			array( 'How much notice do I need to rent out the restaurant?', 'Renting out a full restaurant for a private event requires 7 days notice. Call (806) 797-9931 to book.' ),
		),
		'menu' => array(
			array( 'How many ribs are in a pound?', 'A pound of our St. Louis style pork ribs is about half a rack, or 5 to 7 ribs.' ),
			array( 'Do you have any Sunday specials?', 'Yes — the smoked half chicken plate with three sides is a Sunday-only special for $13.95.' ),
		),
		'locations' => array(
			array( 'Which Bigham\'s location is closest to Texas Tech?', 'Our 19th Street & Quaker location at 4302 19th St is the closest to the Texas Tech campus.' ),
			array( 'Are you open on Mondays?', 'No. All three Bigham\'s Smokehouse locations are closed on Mondays.' ),
		),
	);
	foreach ( $faqs as $topic => $list ) {
		$term = term_exists( $topic, 'bh_faq_topic' ) ?: wp_insert_term( ucfirst( $topic ), 'bh_faq_topic', array( 'slug' => $topic ) );
		$tid  = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		$i    = 0;
		foreach ( $list as $f ) {
			$id = bh_seed_post( 'bh_faq', $f[0], $f[1], array(), array( 'menu_order' => ++$i ) );
			if ( $id && $tid ) {
				wp_set_object_terms( $id, $tid, 'bh_faq_topic', true );
			}
		}
	}
}

function bh_seed_reviews() {
	$reviews = array(
		array( 'Norma K.', 'Love the atmosphere. Everyone was friendly. The cashier was super nice and friendly! The food was so good and fresh. The cherry cobbler was to die for! I will continue to return!' ),
		array( 'Christian T.', 'Great food and sides! I wish I could bring those green beans back to Alabama with me!! My goodness, what a spot! I had the sausage and brisket plate.' ),
		array( 'Neteja L.', 'Love this place! The ribs are SO good. Thank you Crystal for your amazing customer service.' ),
		array( 'Colton C.', 'Great place to eat some wonderful BBQ.' ),
	);
	$i = 0;
	foreach ( $reviews as $r ) {
		bh_seed_post( 'bh_review', 'Review from ' . $r[0], $r[1], array( 'author' => $r[0], 'source' => 'Google', 'rating' => '5' ), array( 'menu_order' => ++$i ) );
	}
}

function bh_seed_pages() {
	$pages = array(
		'home'     => array( 'Home', '' ),
		'menu'     => array( 'Menu', '' ),
		'catering' => array( 'Catering', '' ),
		'parties'  => array( 'Private Parties', '' ),
		'about'    => array( 'Our Story', "<!-- wp:paragraph -->\n<p>Bigham's Smokehouse first opened its doors in Lubbock in 1978, when Don Bigham started smoking brisket the slow, West Texas way. More than four decades later, Bigham's is still family-owned and operated by his wife Joyce and son Nick.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Today we serve Lubbock from three smokehouses on 82nd Street, 19th Street and Milwaukee Avenue, with dine-in, carry-out, online ordering, delivery and catering for groups of every size. The recipe hasn't changed much: good wood, plenty of time, scratch-made sides, and treating every guest like family.</p>\n<!-- /wp:paragraph -->" ),
		'faq'      => array( 'FAQ', '' ),
		'contact'  => array( 'Contact', '' ),
		'news'     => array( 'News', '' ),
	);
	$ids = array();
	foreach ( $pages as $slug => $p ) {
		$existing = get_page_by_path( $slug );
		$ids[ $slug ] = $existing ? $existing->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $p[0],
				'post_name'    => $slug,
				'post_content' => $p[1],
			)
		);
	}
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		update_option( 'page_for_posts', $ids['news'] );
	}
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
}
