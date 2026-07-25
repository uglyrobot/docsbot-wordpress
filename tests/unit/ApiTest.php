<?php

use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase {

	public function test_bot_list_never_exposes_signature_keys_to_callers_or_caches() {
		$api  = new DocsBot_API();
		$bots = $api->sanitize_bot_list(
			array(
				array(
					'id'           => 'bot12345',
					'name'         => 'Private bot',
					'signatureKey' => 'sentinel-raw-signing-key',
				),
			)
		);

		$this->assertSame( 'bot12345', $bots[0]['id'] );
		$this->assertArrayNotHasKey( 'signatureKey', $bots[0] );
		$this->assertStringNotContainsString( 'sentinel-raw-signing-key', serialize( $bots ) );
	}

	public function test_empty_allowed_domains_remain_unrestricted() {
		$api = new DocsBot_API();

		$this->assertSame( array(), $api->with_allowed_domain( array(), 'example.com' ) );
	}

	public function test_site_host_is_added_only_to_an_existing_allowlist() {
		$api = new DocsBot_API();

		$this->assertSame(
			array( 'APP.EXAMPLE.COM.', 'www.example.com' ),
			$api->with_allowed_domain( array( 'APP.EXAMPLE.COM.' ), 'www.example.com' )
		);
		$this->assertSame(
			array( 'www.example.com' ),
			$api->with_allowed_domain( array( 'www.example.com', 'WWW.EXAMPLE.COM.' ), 'www.example.com' )
		);
	}

	public function test_only_healthy_enabled_mcp_servers_are_available() {
		$api = new DocsBot_API();

		$available = $api->available_mcp_servers(
			array(
				array( 'id' => 'healthy', 'enabled' => true, 'isConnected' => true, 'tokenExpired' => false ),
				array( 'id' => 'disabled', 'enabled' => false, 'isConnected' => true, 'tokenExpired' => false ),
				array( 'id' => 'expired', 'enabled' => true, 'isConnected' => true, 'tokenExpired' => true ),
				array( 'id' => 'offline', 'enabled' => true, 'isConnected' => false, 'tokenExpired' => false ),
			)
		);

		$this->assertSame( array( 'healthy' ), array_column( $available, 'id' ) );
	}

	public function test_only_widget_enabled_skills_are_available() {
		$api = new DocsBot_API();

		$available = $api->available_widget_skills(
			array(
				array( 'id' => 'widget', 'enabledWidget' => true ),
				array( 'id' => 'disabled', 'enabledWidget' => false ),
				array( 'id' => 'missing' ),
			)
		);

		$this->assertSame( array( 'widget' ), array_column( $available, 'id' ) );
	}

	public function test_authentication_and_permission_errors_are_actionable() {
		$api = new DocsBot_API();

		$this->assertSame(
			'DocsBot authentication failed. Replace the API key and reconnect.',
			$api->error_message_for_status( 401, array( 'message' => 'Invalid API key' ) )
		);
		$this->assertSame(
			'This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.',
			$api->error_message_for_status( 403, array( 'message' => 'Forbidden' ) )
		);
	}
}
