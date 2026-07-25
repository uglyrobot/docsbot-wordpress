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
		'icon'              => 'robot',
		'alignment'         => 'right',
		'botIcon'           => 'robot',
		'logo'              => '',
		'branding'          => true,
		'headerAlignment'   => 'center',
		'linkSafetyEnabled' => true,
		'imageUploads'      => true,
		'audioUploads'      => true,
		'supportLink'       => 'https://docsbot.ai/support',
		'showButtonLabel'   => true,
		'showCopyButton'    => true,
		'hideSources'       => false,
		'allowedDomains'    => array( '127.0.0.1' ),
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

		if ( 'Bearer viewer-fixture-key' === $auth && 'PUT' === $method ) {
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
		} elseif ( '/api/teams/teamDemo12345/bots' === $path ) {
			$data = array( $bot );
		} elseif ( '/api/teams/teamDemo12345/bots/botDemo98765/skills' === $path ) {
			$data = array(
				'skills' => array(
					array(
						'id'            => 'skillDemo12345',
						'displayName'   => 'Product Recommendations',
						'enabledWidget' => true,
					),
					array(
						'id'            => 'skillHidden12345',
						'displayName'   => 'Internal Reports',
						'enabledWidget' => false,
					),
				),
			);
		} elseif ( '/api/teams/teamDemo12345/bots/botDemo98765' === $path && 'PUT' === $method ) {
			$changes = json_decode( $args['body'] ?? '{}', true );
			$bot     = array_merge( $bot, is_array( $changes ) ? $changes : array() );
			update_option( 'docsbot_fixture_bot', $bot, false );
			$data = $bot;
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
				'include_prefixes'    => "/documentation/\n/members/",
				'exclude_prefixes'    => '/members/billing/',
				'logged_in_only'      => false,
				'share_name'          => true,
				'share_user_id'       => true,
				'use_web_search'      => true,
				'use_custom_buttons'  => true,
				'use_image_upload'    => true,
				'use_audio_upload'    => true,
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
