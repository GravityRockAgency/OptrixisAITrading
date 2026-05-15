<?php
/**
 * Frontend widget.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Widget
{
    public function hooks(): void
    {
        add_action('wp_enqueue_scripts', array($this, 'assets'));
        add_action('wp_footer', array($this, 'render'));
    }

    public function assets(): void
    {
        $settings = OSC_Settings::get();
        wp_enqueue_style('osc-widget', OSC_PLUGIN_URL . 'assets/css/widget.css', array(), OSC_VERSION);
        wp_add_inline_style('osc-widget', ':root{--osc-primary:' . esc_html($settings['primary_color']) . ';--osc-secondary:' . esc_html($settings['secondary_color']) . ';}');
        wp_enqueue_script('osc-widget', OSC_PLUGIN_URL . 'assets/js/widget.js', array(), OSC_VERSION, true);
        wp_localize_script('osc-widget', 'OSC_WIDGET', array(
            'restUrl' => esc_url_raw(rest_url('osc/v1/')),
            'ajaxUrl' => esc_url_raw(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('wp_rest'),
            'sourcePage' => OSC_Security::current_url(),
            'settings' => array(
                'whatsappNumber' => OSC_Security::clean_whatsapp_number($settings['whatsapp_number']),
                'whatsappEnabled' => (bool) $settings['enable_whatsapp'],
                'orderTrackingEnabled' => (bool) $settings['enable_order_tracking'] && OSC_WooCommerce::is_active(),
                'quizEnabled' => (bool) $settings['enable_quiz'],
                'woocommerceActive' => OSC_WooCommerce::is_active(),
                'privacyUrl' => esc_url_raw($settings['privacy_url']),
            ),
            'i18n' => array(
                'networkError' => __('Une erreur réseau est survenue. Merci de réessayer.', 'oceara-sun-concierge'),
                'whatsappUnavailable' => __('WhatsApp n’est pas encore configuré.', 'oceara-sun-concierge'),
            ),
        ));
    }

    public function render(): void
    {
        $settings = OSC_Settings::get();
        include OSC_PLUGIN_DIR . 'templates/widget.php';
    }
}
