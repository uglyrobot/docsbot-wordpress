<?php
/**
 * Frontend deployment and private-bot signing.
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the DocsBot widget only when deployment and access rules allow it.
 */
final class DocsBot_Widget {

	/**
	 * Membership adapter registry.
	 *
	 * @var DocsBot_Memberships
	 */
	private $memberships;

	/**
	 * Constructor.
	 *
	 * @param DocsBot_Memberships $memberships Membership service.
	 */
	public function __construct( $memberships ) {
		$this->memberships = $memberships;
	}

	/**
	 * Register frontend and REST hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_bootstrap' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
	}

	/**
	 * Enqueue a small same-origin bootstrap with no identity data or secrets.
	 *
	 * @return void
	 */
	public function enqueue_bootstrap() {
		$settings = DocsBot_Plugin::settings();
		$path     = $this->current_path();

		if (
			empty( $settings['enabled'] )
			|| empty( $settings['team_id'] )
			|| empty( $settings['bot_id'] )
			|| ! $this->path_is_allowed( $path, $settings )
		) {
			return;
		}

		wp_enqueue_script(
			'docsbot-widget',
			DOCSBOT_URL . 'assets/js/widget.js',
			array(),
			DOCSBOT_VERSION,
			true
		);

		wp_add_inline_script(
			'docsbot-widget',
			'window.docsbotWordPress=' . wp_json_encode(
				array(
					'endpoint' => esc_url_raw( rest_url( 'docsbot/v1/widget-config' ) ),
					'nonce'    => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
					'path'     => $path,
					'ticket'   => $this->deployment_ticket( $path ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Register the dynamic config endpoint.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			'docsbot/v1',
			'/widget-config',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_widget_config' ),
				'permission_callback' => array( $this, 'rest_widget_permission' ),
				'args'                => array(
					'path' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => array( $this, 'sanitize_path' ),
						'validate_callback' => array( $this, 'validate_path' ),
					),
					'ticket' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Confirm that a config request originated from this site's deployed embed.
	 *
	 * Audience authorization remains in the callback because it depends on the
	 * current WordPress user, role, and membership state.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function rest_widget_permission( $request ) {
		$path   = (string) $request->get_param( 'path' );
		$ticket = (string) $request->get_param( 'ticket' );

		if ( ! $this->validate_path( $path ) || ! $this->deployment_ticket_is_valid( $path, $ticket ) ) {
			return new WP_Error( 'docsbot_forbidden', __( 'This widget request could not be verified.', 'docsbot' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Return an access-checked, non-cacheable widget configuration.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_widget_config( $request ) {
		$settings = DocsBot_Plugin::settings();
		$path     = (string) $request->get_param( 'path' );

		if (
			empty( $settings['enabled'] )
			|| empty( $settings['team_id'] )
			|| empty( $settings['bot_id'] )
			|| ! $this->path_is_allowed( $path, $settings )
		) {
			return new WP_Error( 'docsbot_not_deployed', __( 'The widget is not deployed on this page.', 'docsbot' ), array( 'status' => 404 ) );
		}

		$user_id = get_current_user_id();

		if ( ! empty( $settings['logged_in_only'] ) && $user_id <= 0 ) {
			return new WP_Error( 'docsbot_login_required', __( 'Log in to use this chat.', 'docsbot' ), array( 'status' => 403 ) );
		}

		if ( ! empty( $settings['allowed_roles'] ) ) {
			$user = $user_id > 0 ? get_userdata( $user_id ) : false;
			if ( ! $user instanceof WP_User || empty( array_intersect( (array) $settings['allowed_roles'], (array) $user->roles ) ) ) {
				return new WP_Error( 'docsbot_role_required', __( 'This chat is not available for your account.', 'docsbot' ), array( 'status' => 403 ) );
			}
		}

		$provider = (string) $settings['membership_provider'];
		if ( ! $this->memberships->user_is_allowed( $provider, (string) $settings['membership_rule'], $user_id ) ) {
			return new WP_Error( 'docsbot_membership_required', __( 'An eligible membership is required for this chat.', 'docsbot' ), array( 'status' => 403 ) );
		}

		$identify = $this->build_identify( $user_id, $settings );
		$config   = array(
			'id'      => $settings['team_id'] . '/' . $settings['bot_id'],
			'options' => array(
				'useFeedback'        => (bool) $settings['use_feedback'],
				'useEscalation'      => (bool) $settings['use_escalation'],
				'useWebSearch'       => (bool) $settings['use_web_search'],
				'useCalendly'        => (bool) $settings['use_calendly'],
				'useCalCom'          => (bool) $settings['use_calcom'],
				'useTidyCal'         => (bool) $settings['use_tidycal'],
				'useCustomButtons'   => (bool) $settings['use_custom_buttons'],
				'showAgentActivity'  => (bool) $settings['show_agent_activity'],
				'linkSafetyEnabled'  => (bool) $settings['link_safety_enabled'],
				'headerAlignment'    => (string) $settings['header_alignment'],
				'horizontalMargin'   => (int) $settings['horizontal_margin'],
				'verticalMargin'     => (int) $settings['vertical_margin'],
			),
		);

		if ( ! empty( $identify ) ) {
			$config['identify'] = $identify;
		}

		$signature_key = defined( 'DOCSBOT_SIGNATURE_KEY' ) && '' !== trim( (string) DOCSBOT_SIGNATURE_KEY )
			? trim( (string) DOCSBOT_SIGNATURE_KEY )
			: DocsBot_Crypto::decrypt( (string) $settings['signature_key'] );

		if ( 'private' === $settings['bot_privacy'] && '' === $signature_key ) {
			return new WP_Error( 'docsbot_signature_required', __( 'Private bot signing is not configured.', 'docsbot' ), array( 'status' => 503 ) );
		}

		if ( 'private' === $settings['bot_privacy'] && '' !== $signature_key ) {
			$now                 = time();
			$config['signature'] = DocsBot_Crypto::sign_jwt(
				array(
					'team_id' => (string) $settings['team_id'],
					'bot_id'  => (string) $settings['bot_id'],
					'iat'     => $now,
					'exp'     => $now + min( 3600, max( 300, (int) $settings['jwt_ttl'] ) ),
					'metadata' => empty( $identify ) ? new stdClass() : $identify,
				),
				$signature_key
			);
		}

		$response = new WP_REST_Response( $config, 200 );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );

		return $response;
	}

	/**
	 * Test a literal path against newline-delimited include/exclude prefixes.
	 *
	 * @param string              $path     Normalized request path.
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return bool
	 */
	public function path_is_allowed( $path, $settings ) {
		$includes = $this->prefixes( $settings['include_prefixes'] );
		$excludes = $this->prefixes( $settings['exclude_prefixes'] );

		foreach ( $excludes as $prefix ) {
			if ( $this->prefix_matches( $path, $prefix ) ) {
				return false;
			}
		}

		if ( empty( $includes ) ) {
			return true;
		}

		foreach ( $includes as $prefix ) {
			if ( $this->prefix_matches( $path, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate a REST path.
	 *
	 * @param mixed $value Path value.
	 * @return bool
	 */
	public function validate_path( $value ) {
		return is_string( $value ) && strlen( $value ) <= 2048 && 0 === strpos( $value, '/' ) && false === strpos( $value, '://' );
	}

	/**
	 * Sanitize a REST path.
	 *
	 * @param string $value Path value.
	 * @return string
	 */
	public function sanitize_path( $value ) {
		$path = wp_parse_url( (string) $value, PHP_URL_PATH );
		return '/' . ltrim( is_string( $path ) ? $path : '/', '/' );
	}

	/**
	 * Add suggested privacy policy text.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		wp_add_privacy_policy_content(
			__( 'DocsBot', 'docsbot' ),
			wp_kses_post(
				'<p>' . __( 'This site may use DocsBot to provide an interactive chat. Chat messages and any identity fields enabled by the site administrator may be sent to DocsBot for processing and retained according to the site owner’s DocsBot settings. Do not submit passwords, payment details, or other sensitive information in chat.', 'docsbot' ) . '</p>'
			)
		);
	}

	/**
	 * Build opt-in user identity fields.
	 *
	 * @param int                 $user_id  User ID.
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return array<string,string>
	 */
	private function build_identify( $user_id, $settings ) {
		if ( $user_id <= 0 ) {
			return array();
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return array();
		}

		$identify = array();
		if ( ! empty( $settings['share_name'] ) ) {
			$identify['name'] = (string) $user->display_name;
		}
		if ( ! empty( $settings['share_email'] ) ) {
			$identify['email'] = (string) $user->user_email;
		}
		if ( ! empty( $settings['share_user_id'] ) ) {
			$identify['uid'] = 'wp_' . hash_hmac( 'sha256', (string) $user_id, wp_salt( 'nonce' ) );
		}

		return $identify;
	}

	/**
	 * Parse safe literal path prefixes.
	 *
	 * @param string $value Newline-delimited prefixes.
	 * @return array<int,string>
	 */
	private function prefixes( $value ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
		$lines = is_array( $lines ) ? $lines : array();

		return array_values(
			array_filter(
				array_map(
					function ( $line ) {
						$path = $this->sanitize_path( trim( $line ) );
						return '/' === $path && '/' !== trim( $line ) ? '' : $path;
					},
					$lines
				)
			)
		);
	}

	/**
	 * Match a path prefix on a complete URL-path segment boundary.
	 *
	 * @param string $path   Request path.
	 * @param string $prefix Configured prefix.
	 * @return bool
	 */
	private function prefix_matches( $path, $prefix ) {
		if ( '/' === $prefix ) {
			return true;
		}

		$prefix = untrailingslashit( $prefix );
		return $path === $prefix || 0 === strpos( $path, trailingslashit( $prefix ) );
	}

	/**
	 * Create a path-bound proof that WordPress rendered this eligible page.
	 *
	 * @param string $path Page path.
	 * @return string
	 */
	private function deployment_ticket( $path ) {
		return hash_hmac( 'sha256', $path, wp_salt( 'nonce' ) );
	}

	/**
	 * Validate a deployment ticket without trusting the caller-supplied path.
	 *
	 * @param string $path   Requested page path.
	 * @param string $ticket Deployment ticket.
	 * @return bool
	 */
	private function deployment_ticket_is_valid( $path, $ticket ) {
		if ( 64 !== strlen( $ticket ) ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $path, wp_salt( 'nonce' ) );
		return hash_equals( $expected, $ticket );
	}

	/**
	 * Current path without query string.
	 *
	 * @return string
	 */
	private function current_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		return $this->sanitize_path( $request_uri );
	}
}
