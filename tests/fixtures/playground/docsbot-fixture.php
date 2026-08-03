<?php
/**
 * Local WordPress Playground fixture. Never included in the release ZIP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function docsbot_fixture_bot() {
	$default = array(
		'id'                => 'botDemo98765',
		'name'              => 'DocsBot Product Guide',
		'description'       => 'Instant answers from our product documentation.',
		'privacy'           => 'private',
		'signatureKey'      => 'fixture-signature-key',
		'status'            => 'ready',
		'color'             => '#0891b8',
		'theme'             => 'light',
		'isAgent'           => true,
		'icon'              => 'robot',
		'alignment'         => 'right',
		'botIcon'           => 'robot',
		'logo'              => '',
		'branding'          => true,
		'headerAlignment'   => 'center',
		'linkSafetyEnabled' => true,
		'imageUploads'      => true,
		'audioUploads'      => true,
		'voiceAgent'        => array(
			'enabled'                => false,
			'model'                  => 'gpt-realtime-2.1',
			'voice'                  => 'marin',
			'greeting'               => 'Thanks for calling DocsBot. How can I help?',
			'maxCallDurationSeconds' => 900,
		),
		'supportLink'       => 'https://docsbot.ai/support',
		'showButtonLabel'   => true,
		'showCopyButton'    => true,
		'hideSources'       => false,
		'allowedDomains'    => array( '127.0.0.1' ),
		'tools'             => array(
			'calendly'     => array(
				'enabled'          => true,
				'url'              => 'https://calendly.com/docsbot/demo',
				'instructions'     => 'Use this when the user asks to schedule a meeting, call, office hours, or demo.',
				'hideEventDetails' => false,
				'hideCookieBanner' => true,
			),
			'calcom'       => array(
				'enabled'          => false,
				'url'              => 'https://cal.com/docsbot/demo',
				'instructions'     => 'Use this when the user asks to schedule a meeting, call, office hours, or demo.',
				'hideEventDetails' => false,
			),
			'tidycal'      => array(
				'enabled'          => false,
				'url'              => 'https://tidycal.com/docsbot/demo',
				'instructions'     => 'Use this when the user asks to schedule a meeting, call, office hours, or demo.',
				'hideEventDetails' => false,
			),
			'customButtons' => array(
				array(
					'enabled'      => true,
					'name'         => 'View pricing',
					'functionKey'  => 'view_pricing',
					'instructions' => 'Use when the visitor asks about plans or pricing.',
					'buttonText'   => 'View pricing',
					'icon'         => 'BanknotesIcon',
					'url'          => 'https://docsbot.ai/pricing',
				),
			),
		),
		'mcpServers'        => array(
			array(
				'id'           => 'mcpDemo12345',
				'serverLabel'  => 'DocsBot CRM',
				'enabled'      => true,
				'isConnected'  => true,
				'tokenExpired' => false,
			),
			array(
				'id'           => 'mcpExpired12345',
				'serverLabel'  => 'Expired server',
				'enabled'      => true,
				'isConnected'  => true,
				'tokenExpired' => true,
			),
		),
		'labels'            => array(
			'firstMessage'     => 'Hi! What would you like to learn about DocsBot?',
			'inputPlaceholder' => 'Ask a product question…',
			'floatingButton'   => 'Ask DocsBot',
			'getSupport'       => 'Contact support',
			'footerMessage'    => 'AI answers can make mistakes. Please verify important details.',
		),
	);

	return wp_parse_args( get_option( 'docsbot_fixture_bot', array() ), $default );
}

add_action(
	'wp_ajax_docsbot_fixture_state',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(), 403 );
		}
		wp_send_json_success( docsbot_fixture_bot() );
	}
);

add_filter(
	'pre_http_request',
	function ( $preempt, $args, $url ) {
		if ( 0 !== strpos( $url, 'https://docsbot.ai/api/' ) ) {
			return $preempt;
		}

		$path   = wp_parse_url( $url, PHP_URL_PATH );
		$method = strtoupper( $args['method'] ?? 'GET' );
		$bot    = docsbot_fixture_bot();
		$data   = array();
		$status = 200;
		$auth   = $args['headers']['Authorization'] ?? '';

		if ( 'Bearer expired-fixture-key' === $auth ) {
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'message' => 'Invalid API key' ) ),
				'response' => array( 'code' => 401, 'message' => 'Unauthorized' ),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		if ( 'Bearer viewer-fixture-key' === $auth && in_array( $method, array( 'POST', 'PUT' ), true ) ) {
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'message' => 'You are not allowed to edit this bot.' ) ),
				'response' => array( 'code' => 403, 'message' => 'Forbidden' ),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		if ( '/api/teams' === $path ) {
			$data = array(
				array(
					'id'       => 'teamDemo12345',
					'name'     => 'DocsBot Demo Team',
					'botCount' => 1,
					'status'   => 'ready',
					'roles'    => array( 'fixture-user' => 'owner' ),
					'plan'     => array( 'name' => 'Business', 'bots' => 10 ),
				),
			);
		} elseif ( '/api/users/me' === $path ) {
			$data = array(
				'user'        => array(
					'id'          => 'fixture-user',
					'currentTeam' => 'teamDemo12345',
				),
				'currentTeam' => array(
					'id'   => 'teamDemo12345',
					'name' => 'DocsBot Demo Team',
				),
			);
		} elseif ( '/api/teams/teamDemo12345/bots' === $path ) {
			$data = array( $bot );
		} elseif ( '/api/teams/teamDemo12345/bots/botDemo98765/skills' === $path ) {
			$disabled_skills = (array) get_option( 'docsbot_fixture_disabled_skills', array() );
			$data = array(
				'skills' => array(
					array(
						'id'            => 'skillDemo12345',
						'displayName'   => 'Product Recommendations',
						'enabledWidget' => ! in_array( 'skillDemo12345', $disabled_skills, true ),
					),
					array(
						'id'            => 'skillHidden12345',
						'displayName'   => 'Internal Reports',
						'enabledWidget' => false,
					),
				),
			);
		} elseif ( '/api/teams/teamDemo12345/bots/botDemo98765/custom-button-draft' === $path && 'POST' === $method ) {
			$draft_request = json_decode( $args['body'] ?? '{}', true );
			if ( empty( $draft_request['input'] ) ) {
				$status = 400;
				$data   = array( 'message' => 'Missing input parameter' );
			} else {
				$existing_buttons = $bot['tools']['customButtons'] ?? array();
				$existing_keys    = array_column( $existing_buttons, 'functionKey' );
				$key              = 'pricing';
				$suffix           = 2;
				while ( in_array( $key, $existing_keys, true ) ) {
					$key = 'pricing_' . $suffix;
					++$suffix;
				}
				$data = array(
					'functionKey'  => $key,
					'name'         => 'Pricing',
					'instructions' => 'Use this when the visitor asks about plans, pricing tiers, billing, or cost.',
					'buttonText'   => 'View pricing',
					'icon'         => 'BanknotesIcon',
				);
			}
		} elseif ( preg_match( '#^/api/teams/teamDemo12345/bots/botDemo98765/skills/([A-Za-z0-9_-]+)$#', $path, $matches ) && 'PUT' === $method ) {
			$skill_changes = json_decode( $args['body'] ?? '{}', true );
			$disabled      = (array) get_option( 'docsbot_fixture_disabled_skills', array() );
			if ( false === ( $skill_changes['manifest']['enabledWidget'] ?? true ) ) {
				$disabled[] = $matches[1];
			}
			update_option( 'docsbot_fixture_disabled_skills', array_values( array_unique( $disabled ) ), false );
			$data = array( 'id' => $matches[1], 'manifest' => $skill_changes['manifest'] ?? array() );
		} elseif ( '/api/teams/teamDemo12345/bots/botDemo98765' === $path && 'PUT' === $method ) {
			$changes = json_decode( $args['body'] ?? '{}', true );
			$custom_buttons = $changes['tools']['customButtons'] ?? array();
			$force_error    = array_filter(
				(array) $custom_buttons,
				static function ( $button ) {
					return is_array( $button ) && 'https://plan-limit.example/' === ( $button['url'] ?? '' );
				}
			);
			if ( $force_error ) {
				$status = 403;
				$data   = array( 'message' => 'Your plan action limit has been reached.' );
			} else {
				$bot = array_merge( $bot, is_array( $changes ) ? $changes : array() );
				update_option( 'docsbot_fixture_bot', $bot, false );
				$data = $bot;
			}
		} elseif ( '/api/teams/teamDemo12345/bots/botDemo98765' === $path ) {
			$data = $bot;
		} else {
			$status = 404;
			$data   = array( 'message' => 'Fixture route not found.' );
		}

		return array(
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( $data ),
			'response' => array( 'code' => $status, 'message' => 200 === $status ? 'OK' : 'Not Found' ),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	10,
	3
);

add_action(
	'admin_init',
	function () {
		if ( current_user_can( 'manage_options' ) ) {
			set_user_setting( 'plugin_check_category_preferences', 'general__plugin_repo__security__performance__accessibility' );
		}

		if ( ! class_exists( 'DocsBot_Plugin' ) || get_option( 'docsbot_fixture_seeded' ) ) {
			return;
		}

		DocsBot_Plugin::update_settings(
			array(
				'api_key'             => DocsBot_Crypto::encrypt( 'fixture-api-key' ),
				'team_id'             => 'teamDemo12345',
				'team_name'           => 'DocsBot Demo Team',
				'bot_id'              => 'botDemo98765',
				'bot_name'            => 'DocsBot Product Guide',
				'bot_privacy'         => 'private',
				'signature_key'       => DocsBot_Crypto::encrypt( 'fixture-signature-key' ),
				'enabled'             => true,
				'allowed_domains'     => '127.0.0.1',
				'include_prefixes'    => '',
				'exclude_prefixes'    => '/members/billing/',
				'logged_in_only'      => false,
				'share_name'          => true,
				'share_email'         => true,
				'share_user_id'       => true,
				'use_web_search'      => true,
				'use_custom_buttons'  => true,
				'use_image_upload'    => true,
				'use_audio_upload'    => true,
				'use_voice_agent'     => false,
				'theme'               => 'light',
			)
		);

		update_option( 'docsbot_fixture_seeded', 1, false );
	}
);

add_action(
	'init',
	function () {
		if ( get_option( 'docsbot_fixture_pages' ) ) {
			return;
		}

		wp_insert_post(
			array(
				'post_title'   => 'Documentation',
				'post_name'    => 'documentation',
				'post_content' => '<!-- wp:heading --><h2 class="wp-block-heading">DocsBot documentation</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ask the AI assistant for help with this product guide.</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		update_option( 'docsbot_fixture_pages', 1, false );
	}
);
