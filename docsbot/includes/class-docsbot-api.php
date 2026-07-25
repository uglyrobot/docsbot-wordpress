<?php
/**
 * DocsBot Admin API client.
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Narrow server-side client for documented DocsBot endpoints.
 */
final class DocsBot_API {

	const BASE_URL = 'https://docsbot.ai/api';

	/**
	 * List teams accessible to the configured key.
	 *
	 * @param string $api_key Optional key override.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function list_teams( $api_key = '' ) {
		$response = $this->request( 'GET', '/teams', null, $api_key );

		return is_wp_error( $response ) || ! is_array( $response ) ? $response : array_values( $response );
	}

	/**
	 * List bots in a team.
	 *
	 * @param string $team_id Team ID.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function list_bots( $team_id ) {
		if ( ! $this->valid_id( $team_id ) ) {
			return new WP_Error( 'docsbot_invalid_team', __( 'The selected team ID is invalid.', 'docsbot' ) );
		}

		$response = $this->request( 'GET', '/teams/' . rawurlencode( $team_id ) . '/bots' );

		return is_wp_error( $response ) || ! is_array( $response ) ? $response : $this->sanitize_bot_list( array_values( $response ) );
	}

	/**
	 * Remove signing credentials from list responses before callers can cache them.
	 *
	 * @param array<int,mixed> $bots Bot list.
	 * @return array<int,mixed>
	 */
	public function sanitize_bot_list( $bots ) {
		foreach ( $bots as &$bot ) {
			if ( is_array( $bot ) ) {
				unset( $bot['signatureKey'] );
			}
		}
		unset( $bot );
		return $bots;
	}

	/**
	 * Fetch a bot.
	 *
	 * @param string $team_id Team ID.
	 * @param string $bot_id  Bot ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_bot( $team_id, $bot_id ) {
		if ( ! $this->valid_id( $team_id ) || ! $this->valid_id( $bot_id ) ) {
			return new WP_Error( 'docsbot_invalid_bot', __( 'The selected bot ID is invalid.', 'docsbot' ) );
		}

		return $this->request(
			'GET',
			'/teams/' . rawurlencode( $team_id ) . '/bots/' . rawurlencode( $bot_id )
		);
	}

	/**
	 * Update allowlisted bot fields.
	 *
	 * @param string              $team_id Team ID.
	 * @param string              $bot_id  Bot ID.
	 * @param array<string,mixed> $fields  Bot fields.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_bot( $team_id, $bot_id, $fields ) {
		if ( ! $this->valid_id( $team_id ) || ! $this->valid_id( $bot_id ) ) {
			return new WP_Error( 'docsbot_invalid_bot', __( 'The selected bot ID is invalid.', 'docsbot' ) );
		}

		$allowed = array(
			'name',
			'description',
			'allowedDomains',
			'color',
			'icon',
			'alignment',
			'botIcon',
			'logo',
			'branding',
			'headerAlignment',
			'linkSafetyEnabled',
			'supportLink',
			'showButtonLabel',
			'showCopyButton',
			'hideSources',
			'labels',
		);
		$body    = array_intersect_key( $fields, array_flip( $allowed ) );

		if ( empty( $body ) ) {
			return new WP_Error( 'docsbot_empty_update', __( 'No supported bot settings were provided.', 'docsbot' ) );
		}

		return $this->request(
			'PUT',
			'/teams/' . rawurlencode( $team_id ) . '/bots/' . rawurlencode( $bot_id ),
			$body
		);
	}

	/**
	 * Perform an authenticated request.
	 *
	 * @param string                   $method  HTTP method.
	 * @param string                   $path    API path.
	 * @param array<string,mixed>|null $body    Optional JSON body.
	 * @param string                   $api_key Optional key override.
	 * @return array<string,mixed>|array<int,mixed>|WP_Error
	 */
	private function request( $method, $path, $body = null, $api_key = '' ) {
		if ( '' === $api_key ) {
			if ( defined( 'DOCSBOT_API_KEY' ) && '' !== trim( (string) DOCSBOT_API_KEY ) ) {
				$api_key = trim( (string) DOCSBOT_API_KEY );
			} else {
				$settings = DocsBot_Plugin::settings();
				$api_key  = DocsBot_Crypto::decrypt( (string) $settings['api_key'] );
			}
		}

		if ( '' === $api_key ) {
			return new WP_Error( 'docsbot_missing_key', __( 'Connect a DocsBot API key first.', 'docsbot' ) );
		}

		$args = array(
			'method'              => $method,
			'timeout'             => 15,
			'redirection'         => 0,
			'limit_response_size' => 1024 * 1024,
			'headers'             => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
				'User-Agent'    => 'DocsBot-WordPress/' . DOCSBOT_VERSION,
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$base_url = apply_filters( 'docsbot_admin_api_base_url', self::BASE_URL );
		$response = wp_safe_remote_request( untrailingslashit( esc_url_raw( $base_url ) ) . $path, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'docsbot_request_failed',
				sprintf(
					/* translators: %s: low-level HTTP error. */
					__( 'DocsBot could not be reached: %s', 'docsbot' ),
					$response->get_error_message()
				)
			);
		}

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = is_array( $decoded ) && ! empty( $decoded['message'] )
				? sanitize_text_field( $decoded['message'] )
				: sprintf(
					/* translators: %d: HTTP status code. */
					__( 'DocsBot returned HTTP %d.', 'docsbot' ),
					$status
				);

			return new WP_Error( 'docsbot_api_error', $message, array( 'status' => $status ) );
		}

		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'docsbot_invalid_response', __( 'DocsBot returned an invalid response.', 'docsbot' ) );
		}

		return $decoded;
	}

	/**
	 * Validate opaque DocsBot resource IDs before placing them in paths.
	 *
	 * @param string $id Resource ID.
	 * @return bool
	 */
	private function valid_id( $id ) {
		return 1 === preg_match( '/^[A-Za-z0-9_-]{5,128}$/', (string) $id );
	}
}
