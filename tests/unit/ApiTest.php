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
}
