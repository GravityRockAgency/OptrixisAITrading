<?php
/**
 * Activation tasks.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Activator
{
    public static function activate(): void
    {
        OSC_Database::create_tables();

        if (false === get_option('osc_settings')) {
            add_option('osc_settings', OSC_Settings::defaults());
        } else {
            update_option('osc_settings', wp_parse_args(get_option('osc_settings', array()), OSC_Settings::defaults()));
        }

        update_option('osc_version', OSC_VERSION);
    }
}
