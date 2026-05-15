<?php
/**
 * Settings API.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Settings
{
    public const OPTION = 'osc_settings';

    public static function defaults(): array
    {
        return array(
            'logo_url' => '',
            'primary_color' => '#062F4F',
            'secondary_color' => '#D9B66F',
            'welcome_message' => 'Bonjour, je suis votre concierge solaire Oceara. Je peux vous aider à choisir votre protection, trouver votre routine ou suivre votre commande.',
            'whatsapp_number' => '',
            'support_email' => '',
            'opening_hours' => 'Lundi - Vendredi, 9h - 18h',
            'offline_message' => 'L’équipe Oceara vous répondra dès que possible.',
            'privacy_url' => '',
            'n8n_webhook_url' => '',
            'product_stick_id' => 0,
            'product_cream_id' => 0,
            'product_hair_oil_id' => 0,
            'product_routine_pack_id' => 0,
            'enable_quiz' => 1,
            'enable_whatsapp' => 1,
            'enable_order_tracking' => 1,
            'enable_n8n' => 0,
        );
    }

    public function hooks(): void
    {
        add_action('admin_init', array($this, 'register'));
    }

    public static function get(): array
    {
        return wp_parse_args(get_option(self::OPTION, array()), self::defaults());
    }

    public function register(): void
    {
        register_setting('osc_settings_group', self::OPTION, array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize'),
            'default' => self::defaults(),
        ));
    }

    public function sanitize($input): array
    {
        $defaults = self::defaults();
        $input = is_array($input) ? $input : array();
        $output = array();

        $output['logo_url'] = esc_url_raw($input['logo_url'] ?? '');
        $output['primary_color'] = sanitize_hex_color($input['primary_color'] ?? $defaults['primary_color']) ?: $defaults['primary_color'];
        $output['secondary_color'] = sanitize_hex_color($input['secondary_color'] ?? $defaults['secondary_color']) ?: $defaults['secondary_color'];
        $output['welcome_message'] = sanitize_textarea_field($input['welcome_message'] ?? $defaults['welcome_message']);
        $output['whatsapp_number'] = OSC_Security::sanitize_phone($input['whatsapp_number'] ?? '');
        $output['support_email'] = sanitize_email($input['support_email'] ?? '');
        $output['opening_hours'] = sanitize_text_field($input['opening_hours'] ?? '');
        $output['offline_message'] = sanitize_textarea_field($input['offline_message'] ?? '');
        $output['privacy_url'] = esc_url_raw($input['privacy_url'] ?? '');
        $output['n8n_webhook_url'] = esc_url_raw($input['n8n_webhook_url'] ?? '');

        foreach (array('product_stick_id', 'product_cream_id', 'product_hair_oil_id', 'product_routine_pack_id') as $key) {
            $output[$key] = absint($input[$key] ?? 0);
        }

        foreach (array('enable_quiz', 'enable_whatsapp', 'enable_order_tracking', 'enable_n8n') as $key) {
            $output[$key] = empty($input[$key]) ? 0 : 1;
        }

        return wp_parse_args($output, $defaults);
    }
}
