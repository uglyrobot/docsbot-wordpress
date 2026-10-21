<?php
/**
 * Small WordPress compatibility layer for pure unit tests.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DOCSBOT_VERSION', '1.0.0' );

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct( $code, $message, $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_data() {
		return $this->data;
	}
}

function __( $text ) {
	return $text;
}

function wp_salt( $scheme = 'auth' ) {
	return 'unit-test-' . $scheme . '-salt';
}

function wp_json_encode( $value ) {
	return json_encode( $value );
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

$GLOBALS['docsbot_test_filters'] = array();
$GLOBALS['docsbot_test_user_id'] = 0;
$GLOBALS['docsbot_test_users']   = array();

/**
 * Register a test filter callback.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Filter callback.
 * @param int      $priority      Priority, lower runs first.
 * @param int      $accepted_args Number of arguments passed to the callback.
 * @return void
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	if ( ! isset( $GLOBALS['docsbot_test_filters'][ $hook ] ) ) {
		$GLOBALS['docsbot_test_filters'][ $hook ] = array();
	}

	$GLOBALS['docsbot_test_filters'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => (int) $priority,
		'accepted_args' => (int) $accepted_args,
	);
}

/**
 * Remove registered test filters.
 *
 * @param string|null $hook Hook name, or null to clear every hook.
 * @return void
 */
function remove_all_filters( $hook = null ) {
	if ( null === $hook ) {
		$GLOBALS['docsbot_test_filters'] = array();
		return;
	}

	unset( $GLOBALS['docsbot_test_filters'][ $hook ] );
}

/**
 * Apply registered test filters, matching WordPress argument slicing.
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Unfiltered value.
 * @param mixed  ...$args Additional arguments.
 * @return mixed
 */
function apply_filters( $hook, $value, ...$args ) {
	if ( empty( $GLOBALS['docsbot_test_filters'][ $hook ] ) ) {
		return $value;
	}

	$callbacks = $GLOBALS['docsbot_test_filters'][ $hook ];
	usort(
		$callbacks,
		static function ( $left, $right ) {
			return $left['priority'] <=> $right['priority'];
		}
	);

	foreach ( $callbacks as $filter ) {
		$callback_args = array_merge( array( $value ), $args );
		if ( $filter['accepted_args'] >= 0 ) {
			$callback_args = array_slice( $callback_args, 0, $filter['accepted_args'] );
		}
		$value = call_user_func_array( $filter['callback'], $callback_args );
	}

	return $value;
}

/**
 * Reset filter and identity stubs between tests.
 *
 * @return void
 */
function docsbot_test_reset() {
	$GLOBALS['docsbot_test_filters'] = array();
	$GLOBALS['docsbot_test_user_id'] = 0;
	$GLOBALS['docsbot_test_users']   = array();
	DocsBot_Plugin::$settings        = array();
}

function get_current_user_id() {
	return (int) ( $GLOBALS['docsbot_test_user_id'] ?? 0 );
}

function get_userdata( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 || empty( $GLOBALS['docsbot_test_users'][ $user_id ] ) ) {
		return false;
	}

	return $GLOBALS['docsbot_test_users'][ $user_id ];
}

class WP_User {
	public $ID;
	public $display_name;
	public $user_email;
	public $roles;

	public function __construct( $data = array() ) {
		$this->ID           = (int) ( $data['ID'] ?? 0 );
		$this->display_name = (string) ( $data['display_name'] ?? '' );
		$this->user_email   = (string) ( $data['user_email'] ?? '' );
		$this->roles        = (array) ( $data['roles'] ?? array() );
	}
}

class WP_REST_Request {
	private $params = array();

	public function __construct( $params = array() ) {
		$this->params = $params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

class WP_REST_Response {
	private $data;
	private $status;
	private $headers = array();

	public function __construct( $data = null, $status = 200 ) {
		$this->data   = $data;
		$this->status = $status;
	}

	public function header( $name, $value ) {
		$this->headers[ $name ] = $value;
	}

	public function get_data() {
		return $this->data;
	}

	public function get_status() {
		return $this->status;
	}

	public function get_headers() {
		return $this->headers;
	}
}

class DocsBot_Plugin {
	/**
	 * Settings returned to widget tests.
	 *
	 * @var array<string,mixed>
	 */
	public static $settings = array();

	/**
	 * @return array<string,mixed>
	 */
	public static function settings() {
		return self::$settings;
	}
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

function untrailingslashit( $value ) {
	return rtrim( $value, '/\\' );
}

function trailingslashit( $value ) {
	return untrailingslashit( $value ) . '/';
}

function wpmem_get_user_products( $user_id ) {
	return 123 === $user_id ? array( 'gold' => true ) : array();
}

function wpmem_user_has_access( $products, $user_id ) {
	return 123 === $user_id && in_array( 'gold', (array) $products, true );
}

require_once dirname( __DIR__ ) . '/docsbot/includes/class-docsbot-crypto.php';
require_once dirname( __DIR__ ) . '/docsbot/includes/class-docsbot-api.php';
require_once dirname( __DIR__ ) . '/docsbot/includes/class-docsbot-memberships.php';
require_once dirname( __DIR__ ) . '/docsbot/includes/class-docsbot-widget.php';
