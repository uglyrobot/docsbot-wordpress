<?php
/**
 * Main plugin bootstrap.
 *
 * @package DocsBot_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates the plugin components.
 */
final class DocsBot_AI_Plugin {

	/**
	 * Plugin singleton.
	 *
	 * @var DocsBot_AI_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return DocsBot_AI_Plugin
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
		if ( false === get_option( 'docsbot_ai_settings', false ) ) {
			add_option( 'docsbot_ai_settings', self::defaults(), '', false );
		}
	}

	/**
	 * Register plugin services.
	 *
	 * @return void
	 */
	public function run() {
		$api         = new DocsBot_AI_API();
		$memberships = new DocsBot_AI_Memberships();
		$widget      = new DocsBot_AI_Widget( $memberships );
		$admin       = new DocsBot_AI_Admin( $api, $memberships );

		$widget->register();

		if ( is_admin() ) {
			$admin->register();
		}
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'api_key'               => '',
			'team_id'               => '',
			'team_name'             => '',
			'bot_id'                => '',
			'bot_name'              => '',
			'bot_privacy'           => 'public',
			'signature_key'         => '',
			'enabled'               => false,
			'allowed_domains'       => '',
			'include_prefixes'      => '',
			'exclude_prefixes'      => '',
			'logged_in_only'        => false,
			'allowed_roles'         => array(),
			'membership_provider'   => 'none',
			'membership_rule'       => '',
			'share_name'            => false,
			'share_email'           => false,
			'share_user_id'         => false,
			'jwt_ttl'               => 3600,
			'use_feedback'          => true,
			'use_escalation'        => true,
			'use_web_search'        => false,
			'use_calendly'          => false,
			'use_calcom'            => false,
			'use_tidycal'           => false,
			'use_custom_buttons'    => false,
			'show_agent_activity'   => true,
			'link_safety_enabled'   => true,
			'header_alignment'      => 'center',
			'horizontal_margin'     => 20,
			'vertical_margin'       => 20,
		);
	}

	/**
	 * Return merged settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function settings() {
		$stored = get_option( 'docsbot_ai_settings', array() );

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

		return update_option( 'docsbot_ai_settings', $settings, false );
	}
}
