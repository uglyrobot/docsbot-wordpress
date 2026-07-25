<?php
/**
 * Plugin Name:       DocsBot
 * Plugin URI:        https://docsbot.ai/
 * Description:       Connect a DocsBot account, configure your AI chat widget, and control where it appears on your WordPress site.
 * Version:           1.0.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            DocsBot
 * Author URI:        https://docsbot.ai/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       docsbot
 * Domain Path:       /languages
 *
 * @package DocsBot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DOCSBOT_VERSION', '1.0.2' );
define( 'DOCSBOT_FILE', __FILE__ );
define( 'DOCSBOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DOCSBOT_URL', plugin_dir_url( __FILE__ ) );

require_once DOCSBOT_PATH . 'includes/class-docsbot-crypto.php';
require_once DOCSBOT_PATH . 'includes/class-docsbot-api.php';
require_once DOCSBOT_PATH . 'includes/class-docsbot-memberships.php';
require_once DOCSBOT_PATH . 'includes/class-docsbot-widget.php';
require_once DOCSBOT_PATH . 'includes/class-docsbot-admin.php';
require_once DOCSBOT_PATH . 'includes/class-docsbot-plugin.php';

register_activation_hook( __FILE__, array( 'DocsBot_Plugin', 'activate' ) );

DocsBot_Plugin::instance()->run();
