<?php
/**
 * Uninstall cleanup.
 *
 * @package DocsBot_AI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'docsbot_ai_settings' );
delete_option( 'docsbot_ai_cached_teams' );
delete_option( 'docsbot_ai_cached_bots' );
delete_option( 'docsbot_ai_cached_bot' );
