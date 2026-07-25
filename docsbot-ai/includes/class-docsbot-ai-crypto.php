<?php
/**
 * Secret storage and JWT signing.
 *
 * @package DocsBot_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypts plugin secrets with WordPress installation keys.
 */
final class DocsBot_AI_Crypto {

	/**
	 * Encrypt a secret for storage.
	 *
	 * @param string $plaintext Secret text.
	 * @return string|WP_Error
	 */
	public static function encrypt( $plaintext ) {
		if ( '' === $plaintext ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return new WP_Error(
				'docsbot_crypto_unavailable',
				__( 'OpenSSL is required to store DocsBot credentials securely.', 'docsbot-ai' )
			);
		}

		try {
			$iv = random_bytes( 12 );
		} catch ( Exception $exception ) {
			return new WP_Error(
				'docsbot_random_unavailable',
				__( 'Secure random data is unavailable on this server.', 'docsbot-ai' )
			);
		}

		$tag        = '';
		$ciphertext = openssl_encrypt(
			$plaintext,
			'aes-256-gcm',
			self::key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		if ( false === $ciphertext ) {
			return new WP_Error(
				'docsbot_encrypt_failed',
				__( 'The DocsBot credential could not be encrypted.', 'docsbot-ai' )
			);
		}

		return 'v1:' . base64_encode( $iv . $tag . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a stored secret.
	 *
	 * @param string $encoded Stored encrypted value.
	 * @return string
	 */
	public static function decrypt( $encoded ) {
		if ( '' === $encoded || 0 !== strpos( $encoded, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$decoded = base64_decode( substr( $encoded, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded || strlen( $decoded ) < 29 ) {
			return '';
		}

		$plaintext = openssl_decrypt(
			substr( $decoded, 28 ),
			'aes-256-gcm',
			self::key(),
			OPENSSL_RAW_DATA,
			substr( $decoded, 0, 12 ),
			substr( $decoded, 12, 16 )
		);

		return false === $plaintext ? '' : $plaintext;
	}

	/**
	 * Create an HS256 JWT without introducing a runtime dependency.
	 *
	 * @param array<string,mixed> $payload Payload claims.
	 * @param string              $secret  Signing secret.
	 * @return string
	 */
	public static function sign_jwt( $payload, $secret ) {
		$header   = self::base64url( wp_json_encode( array( 'alg' => 'HS256', 'typ' => 'JWT' ) ) );
		$body     = self::base64url( wp_json_encode( $payload ) );
		$unsigned = $header . '.' . $body;
		$hash     = hash_hmac( 'sha256', $unsigned, $secret, true );

		return $unsigned . '.' . self::base64url( $hash );
	}

	/**
	 * Derive an installation-specific encryption key.
	 *
	 * @return string
	 */
	private static function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . '|docsbot-ai', true );
	}

	/**
	 * Base64 URL encode bytes.
	 *
	 * @param string $value Bytes to encode.
	 * @return string
	 */
	private static function base64url( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
}
