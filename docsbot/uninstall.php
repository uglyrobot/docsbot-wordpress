<?php
/**
 * Uninstall cleanup.
 *
 * @package DocsBot
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'docsbot_settings' );
delete_option( 'docsbot_cached_teams' );
delete_option( 'docsbot_cached_bots' );
delete_option( 'docsbot_cached_bot' );
