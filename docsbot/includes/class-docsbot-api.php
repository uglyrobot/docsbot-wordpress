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
	 * Add a WordPress hostname to an existing, non-empty domain allowlist.
	 *
	 * An empty allowlist deliberately remains empty because DocsBot treats it
	 * as unrestricted.
	 *
	 * @param array<int,mixed> $domains Existing allowed domains.
	 * @param string           $host    WordPress site hostname.
	 * @return array<int,string>
	 */
	public function with_allowed_domain( $domains, $host ) {
		if ( empty( $domains ) || ! is_array( $domains ) ) {
			return array();
		}

		$clean      = array();
		$normalized = array();
		foreach ( $domains as $domain ) {
			if ( ! is_string( $domain ) ) {
				continue;
			}
			$domain     = trim( $domain );
			$comparison = strtolower( rtrim( $domain, '.' ) );
			if ( '' !== $comparison && ! in_array( $comparison, $normalized, true ) ) {
				$clean[]      = $domain;
				$normalized[] = $comparison;
			}
		}

		$host = strtolower( rtrim( trim( $host ), '.' ) );
		if ( '' !== $host && ! in_array( $host, $normalized, true ) ) {
			$clean[] = $host;
		}

		return $clean;
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
	 * List skills available to a bot.
	 *
	 * @param string $team_id Team ID.
	 * @param string $bot_id  Bot ID.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function list_skills( $team_id, $bot_id ) {
		if ( ! $this->valid_id( $team_id ) || ! $this->valid_id( $bot_id ) ) {
			return new WP_Error( 'docsbot_invalid_bot', __( 'The selected bot ID is invalid.', 'docsbot' ) );
		}

		$response = $this->request(
			'GET',
			'/teams/' . rawurlencode( $team_id ) . '/bots/' . rawurlencode( $bot_id ) . '/skills'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return isset( $response['skills'] ) && is_array( $response['skills'] )
			? array_values( $response['skills'] )
			: array();
	}

	/**
	 * Enable or disable a skill in the widget.
	 *
	 * @param string $team_id  Team ID.
	 * @param string $bot_id   Bot ID.
	 * @param string $skill_id Skill ID.
	 * @param bool   $enabled  Enabled state.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_widget_skill( $team_id, $bot_id, $skill_id, $enabled ) {
		if ( ! $this->valid_id( $team_id ) || ! $this->valid_id( $bot_id ) || ! $this->valid_id( $skill_id ) ) {
			return new WP_Error( 'docsbot_invalid_skill', __( 'The selected skill ID is invalid.', 'docsbot' ) );
		}

		return $this->request(
			'PUT',
			'/teams/' . rawurlencode( $team_id ) . '/bots/' . rawurlencode( $bot_id ) . '/skills/' . rawurlencode( $skill_id ),
			array(
				'manifest' => array( 'enabledWidget' => (bool) $enabled ),
			)
		);
	}

	/**
	 * Generate an unsaved custom button draft from a natural-language prompt.
	 *
	 * @param string $team_id Team ID.
	 * @param string $bot_id  Bot ID.
	 * @param string $input   Button purpose.
	 * @return array<string,mixed>|WP_Error
	 */
	public function draft_custom_button( $team_id, $bot_id, $input ) {
		if ( ! $this->valid_id( $team_id ) || ! $this->valid_id( $bot_id ) ) {
			return new WP_Error( 'docsbot_invalid_bot', __( 'The selected bot ID is invalid.', 'docsbot' ) );
		}

		$input = trim( (string) $input );
		if ( '' === $input ) {
			return new WP_Error( 'docsbot_missing_draft_input', __( 'Describe what the custom button should do.', 'docsbot' ) );
		}

		return $this->request(
			'POST',
			'/teams/' . rawurlencode( $team_id ) . '/bots/' . rawurlencode( $bot_id ) . '/custom-button-draft',
			array( 'input' => $input ),
			'',
			true
		);
	}

	/**
	 * Return only healthy MCP servers enabled for widget actions.
	 *
	 * @param array<int,mixed> $servers MCP server records.
	 * @return array<int,array<string,mixed>>
	 */
	public function available_mcp_servers( $servers ) {
		if ( ! is_array( $servers ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$servers,
				static function ( $server ) {
					return is_array( $server )
						&& true === ( $server['enabled'] ?? false )
						&& true === ( $server['isConnected'] ?? false )
						&& true !== ( $server['tokenExpired'] ?? false );
				}
			)
		);
	}

	/**
	 * Return only skills enabled for widget actions.
	 *
	 * @param array<int,mixed> $skills Skill summaries.
	 * @return array<int,array<string,mixed>>
	 */
	public function available_widget_skills( $skills ) {
		if ( ! is_array( $skills ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$skills,
				static function ( $skill ) {
					return is_array( $skill ) && true === ( $skill['enabledWidget'] ?? false );
				}
			)
		);
	}

	/**
	 * Build a safe, actionable API error message.
	 *
	 * @param int                 $status                     HTTP status.
	 * @param array<string,mixed> $decoded                    Decoded response.
	 * @param bool                $preserve_forbidden_message Whether to preserve a trusted endpoint's 403 explanation.
	 * @return string
	 */
	public function error_message_for_status( $status, $decoded = array(), $preserve_forbidden_message = false ) {
		if ( 401 === $status ) {
			return __( 'DocsBot authentication failed. Replace the API key and reconnect.', 'docsbot' );
		}
		if ( 403 === $status ) {
			if ( $preserve_forbidden_message && ! empty( $decoded['message'] ) ) {
				return sanitize_text_field( $decoded['message'] );
			}
			return __( 'This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.', 'docsbot' );
		}
		if ( ! empty( $decoded['message'] ) ) {
			return sanitize_text_field( $decoded['message'] );
		}
		return sprintf(
			/* translators: %d: HTTP status code. */
			__( 'DocsBot returned HTTP %d.', 'docsbot' ),
			$status
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
			'tools',
			'mcpServers',
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
	 * @param string                   $method                     HTTP method.
	 * @param string                   $path                       API path.
	 * @param array<string,mixed>|null $body                       Optional JSON body.
	 * @param string                   $api_key                    Optional key override.
	 * @param bool                     $preserve_forbidden_message Whether to preserve a trusted endpoint's 403 explanation.
	 * @return array<string,mixed>|array<int,mixed>|WP_Error
	 */
	private function request( $method, $path, $body = null, $api_key = '', $preserve_forbidden_message = false ) {
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
			$message = $this->error_message_for_status( $status, is_array( $decoded ) ? $decoded : array(), $preserve_forbidden_message );

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
