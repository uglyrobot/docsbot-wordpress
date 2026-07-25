<?php
/**
 * Main plugin bootstrap.
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates the plugin components.
 */
final class DocsBot_Plugin {

	/**
	 * Plugin singleton.
	 *
	 * @var DocsBot_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return DocsBot_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Seed the single non-autoloaded settings record.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( 'docsbot_settings', false ) ) {
			add_option( 'docsbot_settings', self::defaults(), '', false );
		}
	}

	/**
	 * Register plugin services.
	 *
	 * @return void
	 */
	public function run() {
		$api         = new DocsBot_API();
		$memberships = new DocsBot_Memberships();
		$widget      = new DocsBot_Widget( $memberships );
		$admin       = new DocsBot_Admin( $api, $memberships );

		add_action( 'init', array( $this, 'load_bundled_textdomain' ) );

		$widget->register();

		if ( is_admin() ) {
			$admin->register();
		}
	}

	/**
	 * Load bundled translations as a fallback for WordPress.org language packs.
	 *
	 * @return void
	 */
	public function load_bundled_textdomain() {
		$locale = determine_locale();

		// Give a WordPress.org language pack the first chance to load.
		get_translations_for_domain( 'docsbot' );
		if ( is_textdomain_loaded( 'docsbot' ) ) {
			return;
		}

		if ( ! preg_match( '/^[A-Za-z0-9_@-]+$/', $locale ) ) {
			return;
		}

		$mofile = DOCSBOT_PATH . 'languages/docsbot-' . $locale . '.mo';
		if ( is_readable( $mofile ) ) {
			load_textdomain( 'docsbot', $mofile );
		}
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'api_key'             => '',
			'team_id'             => '',
			'team_name'           => '',
			'bot_id'              => '',
			'bot_name'            => '',
			'bot_privacy'         => 'public',
			'signature_key'       => '',
			'enabled'             => false,
			'allowed_domains'     => '',
			'include_prefixes'    => '',
			'exclude_prefixes'    => '',
			'logged_in_only'      => false,
			'allowed_roles'       => array(),
			'membership_provider' => 'none',
			'membership_rule'     => '',
			'share_name'          => false,
			'share_email'         => false,
			'share_user_id'       => false,
			'jwt_ttl'             => 3600,
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
			'link_safety_enabled' => true,
			'header_alignment'    => 'center',
			'horizontal_margin'   => 20,
			'vertical_margin'     => 20,
		);
	}

	/**
	 * Return merged settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function settings() {
		$stored = get_option( 'docsbot_settings', array() );

		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * Update selected settings without dropping unknown future fields.
	 *
	 * @param array<string,mixed> $changes Settings to merge.
	 * @return bool
	 */
	public static function update_settings( $changes ) {
		$settings = array_merge( self::settings(), $changes );

		return update_option( 'docsbot_settings', $settings, false );
	}
}
