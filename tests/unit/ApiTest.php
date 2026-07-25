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
}
