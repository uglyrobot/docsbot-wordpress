<?php

use PHPUnit\Framework\TestCase;

final class WidgetHooksTest extends TestCase {

	private $widget;

	protected function setUp(): void {
		docsbot_test_reset();
		$this->widget = new DocsBot_Widget( new DocsBot_Memberships() );
	}

	protected function tearDown(): void {
		docsbot_test_reset();
	}

	public function test_private_metadata_filter_can_add_trusted_priv_fields() {
		add_filter(
			'docsbot_private_metadata',
			static function ( $metadata, $user_id, $identify ) {
				$metadata['priv_wc_customer_id'] = (string) $user_id;
				$metadata['plan']                = $identify['name'];
				return $metadata;
			},
			10,
			3
		);

		$this->assertSame(
			array(
				'name'               => 'Ada',
				'priv_user_id'       => '42',
				'priv_wc_customer_id' => '42',
				'plan'               => 'Ada',
			),
			$this->widget->build_private_metadata( 42, array( 'name' => 'Ada' ) )
		);
	}

	public function test_private_metadata_filter_non_array_falls_back() {
		add_filter(
			'docsbot_private_metadata',
			static function () {
				return 'not-an-array';
			}
		);

		$this->assertSame(
			array(
				'name'         => 'Ada',
				'priv_user_id' => '42',
			),
			$this->widget->build_private_metadata( 42, array( 'name' => 'Ada' ) )
		);
	}

	public function test_private_metadata_filter_cannot_override_or_inject_priv_user_id() {
		add_filter(
			'docsbot_private_metadata',
			static function ( $metadata ) {
				$metadata['priv_user_id'] = 'spoofed';
				return $metadata;
			}
		);

		$this->assertSame(
			array( 'priv_user_id' => '42' ),
			$this->widget->build_private_metadata( 42 )
		);

		$guest = $this->widget->build_private_metadata( 0 );
		$this->assertInstanceOf( stdClass::class, $guest );
		$this->assertSame( array(), get_object_vars( $guest ) );
	}

	public function test_private_metadata_filter_empty_result_still_encodes_as_object() {
		add_filter(
			'docsbot_private_metadata',
			static function () {
				return array();
			}
		);

		$guest = $this->widget->build_private_metadata( 0, array() );
		$this->assertInstanceOf( stdClass::class, $guest );
		$this->assertSame( array(), get_object_vars( $guest ) );
	}

	public function test_widget_config_filter_can_add_options() {
		DocsBot_Plugin::$settings = $this->deployed_settings();
		$captured                 = array();

		add_filter(
			'docsbot_widget_config',
			static function ( $config, $user_id, $path, $settings ) use ( &$captured ) {
				$captured = array( $user_id, $path, $settings );
				$config['options']['horizontalMargin'] = 32;
				return $config;
			},
			10,
			4
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/docs/' ) ) );
		$config   = $response->get_data();

		$this->assertSame( 32, $config['options']['horizontalMargin'] );
		$this->assertSame( 0, $captured[0] );
		$this->assertSame( '/docs/', $captured[1] );
		$this->assertSame( 'team1', $captured[2]['team_id'] );
		$this->assertArrayNotHasKey( 'signature', $config );
	}

	public function test_widget_config_filter_non_array_falls_back() {
		DocsBot_Plugin::$settings = $this->deployed_settings();

		add_filter(
			'docsbot_widget_config',
			static function () {
				return null;
			}
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/' ) ) );
		$config   = $response->get_data();

		$this->assertSame( 'team1/bot1', $config['id'] );
		$this->assertSame( 20, $config['options']['horizontalMargin'] );
	}

	public function test_widget_config_filter_cannot_inject_signature() {
		$secret                       = 'unit-test-signature-key';
		DocsBot_Plugin::$settings     = $this->deployed_settings(
			array(
				'bot_privacy'   => 'private',
				'signature_key' => DocsBot_Crypto::encrypt( $secret ),
			)
		);

		add_filter(
			'docsbot_widget_config',
			static function ( $config, $user_id, $path, $settings ) {
				$config['id']                          = 'otherteam/otherbot';
				$config['signature']                   = 'forged-token';
				$config['options']['horizontalMargin'] = 48;
				$GLOBALS['docsbot_filter_args']        = array( $config, $user_id, $path, $settings );
				return $config;
			},
			10,
			4
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/' ) ) );
		$config   = $response->get_data();

		$this->assertSame( 'team1/bot1', $config['id'] );
		$this->assertSame( 48, $config['options']['horizontalMargin'] );
		$this->assertArrayHasKey( 'signature', $config );
		$this->assertNotSame( 'forged-token', $config['signature'] );
		$this->assertStringNotContainsString( $secret, wp_json_encode( $GLOBALS['docsbot_filter_args'] ) );

		$payload = $this->decode_jwt_payload( $config['signature'] );
		$this->assertSame( 'team1', $payload['team_id'] );
		$this->assertSame( 'bot1', $payload['bot_id'] );
		$this->assertIsArray( $payload['metadata'] );
		$this->assertSame( array(), $payload['metadata'] );
	}

	public function test_widget_identify_filter_adds_public_fields_used_in_jwt_metadata() {
		$GLOBALS['docsbot_test_user_id']     = 42;
		$GLOBALS['docsbot_test_users'][42]   = new WP_User(
			array(
				'ID'           => 42,
				'display_name' => 'Ada',
				'user_email'   => 'ada@example.com',
				'roles'        => array( 'customer' ),
			)
		);
		DocsBot_Plugin::$settings            = $this->deployed_settings(
			array(
				'bot_privacy'   => 'private',
				'share_name'    => true,
				'signature_key' => DocsBot_Crypto::encrypt( 'unit-test-signature-key' ),
			)
		);

		add_filter(
			'docsbot_widget_identify',
			static function ( $identify, $user_id ) {
				$identify['company'] = 'Analytical Engines';
				$identify['uid']     = $user_id;
				return $identify;
			},
			10,
			2
		);
		add_filter(
			'docsbot_private_metadata',
			static function ( $metadata ) {
				$metadata['priv_wc_customer_id'] = '42';
				return $metadata;
			}
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/' ) ) );
		$config   = $response->get_data();

		$this->assertSame(
			array(
				'name'    => 'Ada',
				'company' => 'Analytical Engines',
				'uid'     => 42,
			),
			$config['identify']
		);

		$payload = $this->decode_jwt_payload( $config['signature'] );
		$this->assertSame(
			array(
				'name'               => 'Ada',
				'company'            => 'Analytical Engines',
				'uid'                => 42,
				'priv_user_id'       => '42',
				'priv_wc_customer_id' => '42',
			),
			$payload['metadata']
		);
	}

	public function test_widget_identify_filter_non_array_falls_back() {
		$GLOBALS['docsbot_test_user_id']   = 42;
		$GLOBALS['docsbot_test_users'][42] = new WP_User(
			array(
				'ID'           => 42,
				'display_name' => 'Ada',
				'user_email'   => 'ada@example.com',
			)
		);
		DocsBot_Plugin::$settings          = $this->deployed_settings(
			array(
				'share_name' => true,
			)
		);

		add_filter(
			'docsbot_widget_identify',
			static function () {
				return false;
			}
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/' ) ) );
		$config   = $response->get_data();

		$this->assertSame( array( 'name' => 'Ada' ), $config['identify'] );
	}

	public function test_widget_config_filter_cannot_change_private_bot_id() {
		DocsBot_Plugin::$settings = $this->deployed_settings(
			array(
				'bot_privacy'   => 'private',
				'signature_key' => DocsBot_Crypto::encrypt( 'unit-test-signature-key' ),
			)
		);

		add_filter(
			'docsbot_widget_config',
			static function ( $config ) {
				$config['id'] = 'otherteam/otherbot';
				return $config;
			}
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/' ) ) );
		$config   = $response->get_data();

		$this->assertSame( 'team1/bot1', $config['id'] );
		$payload = $this->decode_jwt_payload( $config['signature'] );
		$this->assertSame( 'team1', $payload['team_id'] );
		$this->assertSame( 'bot1', $payload['bot_id'] );
	}

	public function test_widget_config_filter_can_change_public_bot_id() {
		DocsBot_Plugin::$settings = $this->deployed_settings();

		add_filter(
			'docsbot_widget_config',
			static function ( $config ) {
				$config['id'] = 'otherteam/otherbot';
				return $config;
			}
		);

		$response = $this->widget->rest_widget_config( new WP_REST_Request( array( 'path' => '/' ) ) );
		$config   = $response->get_data();

		$this->assertSame( 'otherteam/otherbot', $config['id'] );
		$this->assertArrayNotHasKey( 'signature', $config );
	}

	/**
	 * @param array<string,mixed> $overrides Settings overrides.
	 * @return array<string,mixed>
	 */
	private function deployed_settings( $overrides = array() ) {
		return array_merge(
			array(
				'enabled'             => true,
				'team_id'             => 'team1',
				'bot_id'              => 'bot1',
				'include_prefixes'    => '',
				'exclude_prefixes'    => '',
				'logged_in_only'      => false,
				'allowed_roles'       => array(),
				'membership_provider' => 'none',
				'membership_rule'     => '',
				'share_name'          => false,
				'share_email'         => false,
				'share_user_id'       => false,
				'theme'               => 'light',
				'header_alignment'    => 'center',
				'horizontal_margin'   => 20,
				'vertical_margin'     => 20,
				'use_feedback'        => true,
				'use_escalation'      => true,
				'use_web_search'      => false,
				'use_calendly'        => false,
				'use_calcom'          => false,
				'use_tidycal'         => false,
				'use_custom_buttons'  => false,
				'show_agent_activity' => true,
				'use_image_upload'    => false,
				'use_audio_upload'    => false,
				'use_voice_agent'     => false,
				'link_safety_enabled' => true,
				'bot_privacy'         => 'public',
				'signature_key'       => '',
				'jwt_ttl'             => 3600,
			),
			$overrides
		);
	}

	/**
	 * @param string $jwt Signed token.
	 * @return array<string,mixed>
	 */
	private function decode_jwt_payload( $jwt ) {
		$parts = explode( '.', $jwt );
		$this->assertCount( 3, $parts );
		$part = $parts[1] . str_repeat( '=', ( 4 - strlen( $parts[1] ) % 4 ) % 4 );
		return json_decode( base64_decode( strtr( $part, '-_', '+/' ) ), true );
	}
}
