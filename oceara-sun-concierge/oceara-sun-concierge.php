<?php
/**
 * Plugin Name: Oceara Sun Concierge
 * Plugin URI: https://ocearasuncare.com/
 * Description: Conciergerie digitale premium pour conseiller les visiteurs Oceara, générer des recommandations solaires, suivre les commandes WooCommerce et connecter WhatsApp/n8n.
 * Version: 1.0.0
 * Author: Oceara Sun Care
 * Author URI: https://ocearasuncare.com/
 * Text Domain: oceara-sun-concierge
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

define('OSC_VERSION', '1.0.0');
define('OSC_VERSION_NAME', 'Oceara Sun Concierge MVP');
define('OSC_PLUGIN_FILE', __FILE__);
define('OSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OSC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OSC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('OSC_TEXT_DOMAIN', 'oceara-sun-concierge');

require_once OSC_PLUGIN_DIR . 'includes/class-osc-security.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-database.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-activator.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-woocommerce.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-n8n.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-settings.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-admin.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-widget.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-rest-api.php';
require_once OSC_PLUGIN_DIR . 'includes/class-osc-plugin.php';

register_activation_hook(__FILE__, array('OSC_Activator', 'activate'));

add_action('before_woocommerce_init', static function (): void {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

function osc_run_plugin(): void
{
    $plugin = new OSC_Plugin();
    $plugin->run();
}
osc_run_plugin();
