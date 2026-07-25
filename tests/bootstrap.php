<?php
/**
 * Small WordPress compatibility layer for pure unit tests.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DOCSBOT_VERSION', '1.0.4' );

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

function apply_filters( $hook, $value ) {
	return $value;
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
