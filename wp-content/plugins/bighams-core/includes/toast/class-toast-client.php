<?php
/**
 * Minimal Toast API client (machine-client OAuth).
 *
 * Credentials come from Toast Web → Integrations → Toast API access
 * ("Standard API access" for restaurant owners; read-only scopes needed:
 *  orders:read, menus:read, config:read, restaurants:read).
 *
 * Credentials can be set in wp-config.php instead of the database:
 *   define( 'BH_TOAST_CLIENT_ID', '...' );
 *   define( 'BH_TOAST_CLIENT_SECRET', '...' );
 *
 * Docs: https://doc.toasttab.com/doc/devguide/apiAuthentication.html
 */

defined( 'ABSPATH' ) || exit;

class BH_Toast_Client {

	const HOSTS = array(
		'production' => 'https://ws-api.toasttab.com',
		'sandbox'    => 'https://ws-sandbox-api.eng.toasttab.com',
	);

	/** @var string */
	private $host;
	/** @var string */
	private $client_id;
	/** @var string */
	private $client_secret;

	public function __construct() {
		$env                 = bh_setting( 'toast_env', 'production' );
		$this->host          = self::HOSTS[ $env ] ?? self::HOSTS['production'];
		$this->client_id     = defined( 'BH_TOAST_CLIENT_ID' ) ? BH_TOAST_CLIENT_ID : bh_setting( 'toast_client_id' );
		$this->client_secret = defined( 'BH_TOAST_CLIENT_SECRET' ) ? BH_TOAST_CLIENT_SECRET : bh_setting( 'toast_client_secret' );
	}

	public function is_configured() {
		return $this->client_id && $this->client_secret;
	}

	/**
	 * Bearer token, cached until shortly before it expires.
	 *
	 * @return string|WP_Error
	 */
	public function token( $force = false ) {
		$cache_key = 'bh_toast_token_' . md5( $this->host . $this->client_id );
		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( $cached ) {
				return $cached;
			}
		}
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'bh_toast_config', 'Toast client ID / secret not set.' );
		}
		$res = wp_remote_post(
			$this->host . '/authentication/v1/authentication/login',
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'clientId'       => $this->client_id,
						'clientSecret'   => $this->client_secret,
						'userAccessType' => 'TOAST_MACHINE_CLIENT',
					)
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		$tok  = $body['token']['accessToken'] ?? '';
		if ( ! $tok ) {
			return new WP_Error( 'bh_toast_auth', 'Toast authentication failed (HTTP ' . wp_remote_retrieve_response_code( $res ) . ').' );
		}
		$ttl = max( 300, (int) ( $body['token']['expiresIn'] ?? 3600 ) - 300 );
		set_transient( $cache_key, $tok, $ttl );
		return $tok;
	}

	/**
	 * GET a Toast endpoint for one restaurant.
	 *
	 * @param string $path            e.g. /orders/v2/ordersBulk
	 * @param array  $query           Query args.
	 * @param string $restaurant_guid Toast restaurant GUID.
	 * @return array|WP_Error Decoded JSON.
	 */
	public function get( $path, $query, $restaurant_guid, $retry = true ) {
		$tok = $this->token();
		if ( is_wp_error( $tok ) ) {
			return $tok;
		}
		$url = add_query_arg( array_map( 'rawurlencode', $query ), $this->host . $path );
		$res = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization'               => 'Bearer ' . $tok,
					'Toast-Restaurant-External-ID' => $restaurant_guid,
					'Accept'                      => 'application/json',
				),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 401 === $code && $retry ) {
			$this->token( true );
			return $this->get( $path, $query, $restaurant_guid, false );
		}
		if ( 429 === $code && $retry ) {
			sleep( 2 );
			return $this->get( $path, $query, $restaurant_guid, false );
		}
		if ( $code >= 400 ) {
			return new WP_Error( 'bh_toast_http', sprintf( 'Toast %s returned HTTP %d: %s', $path, $code, substr( wp_remote_retrieve_body( $res ), 0, 300 ) ) );
		}
		return json_decode( wp_remote_retrieve_body( $res ), true ) ?: array();
	}

	/**
	 * All orders for a business date (yyyymmdd), paginated.
	 *
	 * @return array|WP_Error
	 */
	public function orders_for_business_date( $restaurant_guid, $business_date ) {
		$all  = array();
		$page = 1;
		do {
			$batch = $this->get(
				'/orders/v2/ordersBulk',
				array(
					'businessDate' => $business_date,
					'pageSize'     => 100,
					'page'         => $page,
				),
				$restaurant_guid
			);
			if ( is_wp_error( $batch ) ) {
				return $batch;
			}
			$all = array_merge( $all, $batch );
			$page++;
		} while ( count( $batch ) >= 100 && $page < 200 );
		return $all;
	}

	/** @return array|WP_Error */
	public function dining_options( $restaurant_guid ) {
		$key    = 'bh_toast_dopt_' . md5( $restaurant_guid );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$res = $this->get( '/config/v2/diningOptions', array(), $restaurant_guid );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$map = array();
		foreach ( $res as $opt ) {
			$map[ $opt['guid'] ] = array(
				'name'     => $opt['name'] ?? '',
				'behavior' => $opt['behavior'] ?? '',
			);
		}
		set_transient( $key, $map, DAY_IN_SECONDS );
		return $map;
	}

	/** @return array|WP_Error */
	public function menus( $restaurant_guid ) {
		return $this->get( '/menus/v2/menus', array(), $restaurant_guid );
	}

	/** @return array|WP_Error */
	public function restaurant( $restaurant_guid ) {
		return $this->get( '/restaurants/v1/restaurants/' . rawurlencode( $restaurant_guid ), array(), $restaurant_guid );
	}
}
