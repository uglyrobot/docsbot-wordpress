<?php

use PHPUnit\Framework\TestCase;

final class CryptoTest extends TestCase {

	public function test_secret_round_trip_uses_versioned_authenticated_ciphertext() {
		$encrypted = DocsBot_AI_Crypto::encrypt( 'secret-value' );

		$this->assertIsString( $encrypted );
		$this->assertStringStartsWith( 'v1:', $encrypted );
		$this->assertStringNotContainsString( 'secret-value', $encrypted );
		$this->assertSame( 'secret-value', DocsBot_AI_Crypto::decrypt( $encrypted ) );
	}

	public function test_tampered_ciphertext_fails_closed() {
		$encrypted = DocsBot_AI_Crypto::encrypt( 'secret-value' );
		$tampered  = substr( $encrypted, 0, -2 ) . 'aa';

		$this->assertSame( '', DocsBot_AI_Crypto::decrypt( $tampered ) );
	}

	public function test_jwt_has_hs256_header_and_valid_signature() {
		$payload = array(
			'team_id' => 'team123',
			'bot_id'  => 'bot123',
			'iat'     => 100,
			'exp'     => 200,
		);
		$jwt     = DocsBot_AI_Crypto::sign_jwt( $payload, 'test-key' );
		$parts   = explode( '.', $jwt );

		$this->assertCount( 3, $parts );
		$this->assertSame( array( 'alg' => 'HS256', 'typ' => 'JWT' ), $this->decode_part( $parts[0] ) );
		$this->assertSame( $payload, $this->decode_part( $parts[1] ) );
		$expected = rtrim( strtr( base64_encode( hash_hmac( 'sha256', $parts[0] . '.' . $parts[1], 'test-key', true ) ), '+/', '-_' ), '=' );
		$this->assertSame( $expected, $parts[2] );
	}

	private function decode_part( $part ) {
		$part .= str_repeat( '=', ( 4 - strlen( $part ) % 4 ) % 4 );
		return json_decode( base64_decode( strtr( $part, '-_', '+/' ) ), true );
	}
}
