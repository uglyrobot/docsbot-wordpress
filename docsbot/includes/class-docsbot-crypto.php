<?php
/**
 * Secret storage and JWT signing.
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypts plugin secrets with WordPress-bundled authenticated cryptography.
 */
final class DocsBot_Crypto {

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

		if (
			! function_exists( 'sodium_crypto_secretbox' ) ||
			! defined( 'SODIUM_CRYPTO_SECRETBOX_NONCEBYTES' ) ||
			! defined( 'SODIUM_CRYPTO_SECRETBOX_MACBYTES' )
		) {
			return new WP_Error(
				'docsbot_crypto_unavailable',
				__( 'WordPress cryptography is unavailable on this server.', 'docsbot' )
			);
		}

		try {
			$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, self::key() );
		} catch ( Throwable $exception ) {
			return new WP_Error(
				'docsbot_encrypt_failed',
				__( 'The DocsBot credential could not be encrypted.', 'docsbot' )
			);
		}

		return 'v1:' . base64_encode( $nonce . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a stored secret.
	 *
	 * @param string $encoded Stored encrypted value.
	 * @return string
	 */
	public static function decrypt( $encoded ) {
		if (
			'' === $encoded ||
			0 !== strpos( $encoded, 'v1:' ) ||
			! function_exists( 'sodium_crypto_secretbox_open' ) ||
			! defined( 'SODIUM_CRYPTO_SECRETBOX_NONCEBYTES' ) ||
			! defined( 'SODIUM_CRYPTO_SECRETBOX_MACBYTES' )
		) {
			return '';
		}

		$decoded = base64_decode( substr( $encoded, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$minimum_length = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;
		if ( false === $decoded || strlen( $decoded ) < $minimum_length ) {
			return '';
		}

		try {
			$plaintext = sodium_crypto_secretbox_open(
				substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
				substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
				self::key()
			);
		} catch ( Throwable $exception ) {
			return '';
		}

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
		$header   = self::base64url(
			wp_json_encode(
				array(
					'alg' => 'HS256',
					'typ' => 'JWT',
				)
			)
		);
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
		return hash( 'sha256', wp_salt( 'auth' ) . '|docsbot', true );
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
