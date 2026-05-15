<?php
/**
 * Main plugin orchestrator.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Plugin
{
    public function run(): void
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        (new OSC_Settings())->hooks();
        (new OSC_Admin())->hooks();
        (new OSC_Widget())->hooks();
        (new OSC_REST_API())->hooks();
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('oceara-sun-concierge', false, dirname(OSC_PLUGIN_BASENAME) . '/languages');
    }
}
