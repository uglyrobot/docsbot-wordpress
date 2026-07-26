<?php

use PHPUnit\Framework\TestCase;

final class CryptoTest extends TestCase {

	public function test_secret_round_trip_uses_versioned_authenticated_ciphertext() {
		$encrypted = DocsBot_Crypto::encrypt( 'secret-value' );

		$this->assertIsString( $encrypted );
		$this->assertStringStartsWith( 'v1:', $encrypted );
		$this->assertStringNotContainsString( 'secret-value', $encrypted );
		$this->assertSame( 'secret-value', DocsBot_Crypto::decrypt( $encrypted ) );
	}

	public function test_secret_encryption_uses_a_fresh_nonce() {
		$first  = DocsBot_Crypto::encrypt( 'secret-value' );
		$second = DocsBot_Crypto::encrypt( 'secret-value' );

		$this->assertIsString( $first );
		$this->assertIsString( $second );
		$this->assertNotSame( $first, $second );
	}

	public function test_tampered_ciphertext_fails_closed() {
		$encrypted = DocsBot_Crypto::encrypt( 'secret-value' );
		$tampered  = substr( $encrypted, 0, -2 ) . 'aa';

		$this->assertSame( '', DocsBot_Crypto::decrypt( $tampered ) );
	}

	public function test_malformed_and_unknown_ciphertext_fails_closed() {
		$this->assertSame( '', DocsBot_Crypto::decrypt( 'v1:not-valid-base64!' ) );
		$this->assertSame( '', DocsBot_Crypto::decrypt( 'v0:legacy-value' ) );
	}

	public function test_jwt_has_hs256_header_and_valid_signature() {
		$payload = array(
			'team_id' => 'team123',
			'bot_id'  => 'bot123',
			'iat'     => 100,
			'exp'     => 200,
		);
		$jwt     = DocsBot_Crypto::sign_jwt( $payload, 'test-key' );
		$parts   = explode( '.', $jwt );

		$this->assertCount( 3, $parts );
		$this->assertStringNotContainsString( '=', $jwt );
		$this->assertSame( array( 'alg' => 'HS256', 'typ' => 'JWT' ), $this->decode_part( $parts[0] ) );
		$this->assertSame( $payload, $this->decode_part( $parts[1] ) );
		$expected = rtrim(
			strtr(
				base64_encode( hash_hmac( 'sha256', $parts[0] . '.' . $parts[1], 'test-key', true ) ),
				'+/',
				'-_'
			),
			'='
		);
		$this->assertSame( $expected, $parts[2] );
	}

	public function test_jwt_encodes_empty_metadata_as_an_object() {
		$jwt   = DocsBot_Crypto::sign_jwt(
			array(
				'team_id'  => 'team123',
				'bot_id'   => 'bot123',
				'iat'      => 100,
				'exp'      => 200,
				'metadata' => new stdClass(),
			),
			'test-key'
		);
		$parts = explode( '.', $jwt );
		$body  = $this->decode_part( $parts[1], false );

		$this->assertIsObject( $body );
		$this->assertIsObject( $body->metadata );
		$this->assertSame( array(), get_object_vars( $body->metadata ) );
	}

	private function decode_part( $part, $associative = true ) {
		$part .= str_repeat( '=', ( 4 - strlen( $part ) % 4 ) % 4 );
		return json_decode( base64_decode( strtr( $part, '-_', '+/' ) ), $associative );
	}
}
