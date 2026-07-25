<?php
/**
 * Plugin Name:       DocsBot
 * Plugin URI:        https://docsbot.ai/
 * Description:       Connect a DocsBot account, configure your AI chat widget, and control where it appears on your WordPress site.
 * Version:           1.0.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            DocsBot
 * Author URI:        https://docsbot.ai/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       docsbot-ai
 * Domain Path:       /languages
 *
 * @package DocsBot_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DOCSBOT_AI_VERSION', '1.0.1' );
define( 'DOCSBOT_AI_FILE', __FILE__ );
define( 'DOCSBOT_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'DOCSBOT_AI_URL', plugin_dir_url( __FILE__ ) );

require_once DOCSBOT_AI_PATH . 'includes/class-docsbot-ai-crypto.php';
require_once DOCSBOT_AI_PATH . 'includes/class-docsbot-ai-api.php';
require_once DOCSBOT_AI_PATH . 'includes/class-docsbot-ai-memberships.php';
require_once DOCSBOT_AI_PATH . 'includes/class-docsbot-ai-widget.php';
require_once DOCSBOT_AI_PATH . 'includes/class-docsbot-ai-admin.php';
require_once DOCSBOT_AI_PATH . 'includes/class-docsbot-ai-plugin.php';

register_activation_hook( __FILE__, array( 'DocsBot_AI_Plugin', 'activate' ) );

DocsBot_AI_Plugin::instance()->run();
