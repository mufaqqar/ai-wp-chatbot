<?php
/**
 * Plugin Name:       AI Website Chatbot
 * Plugin URI:        https://mufaqar.com
 * Description:       AI-powered website chatbot with website knowledge base, FAQ system, WooCommerce integration, lead generation and analytics.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Mufaqar
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-website-chatbot
 * Domain Path:       /languages
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

define( 'AIWC_VERSION', '1.0.0' );
define( 'AIWC_DB_VERSION', '1.0.0' );
define( 'AIWC_FILE', __FILE__ );
define( 'AIWC_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIWC_URL', plugin_dir_url( __FILE__ ) );
define( 'AIWC_BASENAME', plugin_basename( __FILE__ ) );

require_once AIWC_PATH . 'includes/class-loader.php';
require_once AIWC_PATH . 'includes/helpers.php';

AIWebsiteChatbot\Loader::register();

register_activation_hook( __FILE__, array( 'AIWebsiteChatbot\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AIWebsiteChatbot\Deactivator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded.
 *
 * @return void
 */
function aiwc_bootstrap() {
	AIWebsiteChatbot\Plugin::instance()->run();
}
add_action( 'plugins_loaded', 'aiwc_bootstrap' );