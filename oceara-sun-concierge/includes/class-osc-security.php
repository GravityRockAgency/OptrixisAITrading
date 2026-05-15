<?php
/**
 * Security helpers.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Security
{
    public static function sanitize_key_choice(string $value, array $allowed, string $fallback = ''): string
    {
        $value = sanitize_text_field(wp_unslash($value));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public static function sanitize_phone(string $phone): string
    {
        return substr(preg_replace('/[^0-9+().\s-]/', '', sanitize_text_field(wp_unslash($phone))), 0, 60);
    }

    public static function clean_whatsapp_number(string $number): string
    {
        return preg_replace('/\D+/', '', $number);
    }

    public static function source_page(?string $source): string
    {
        $source = $source ? esc_url_raw(wp_unslash($source)) : '';
        return $source ?: home_url('/');
    }

    public static function current_url(): string
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return esc_url_raw(home_url($request_uri));
    }

    public static function rest_nonce_permission(\WP_REST_Request $request): bool|\WP_Error
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (! $nonce) {
            $nonce = (string) $request->get_param('_wpnonce');
        }

        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('osc_invalid_nonce', __('Session expirée. Merci de rafraîchir la page.', 'oceara-sun-concierge'), array('status' => 403));
        }

        return true;
    }

    public static function admin_permission(): bool|\WP_Error
    {
        if (! current_user_can('manage_options')) {
            return new WP_Error('osc_forbidden', __('Action non autorisée.', 'oceara-sun-concierge'), array('status' => 403));
        }
        return true;
    }
}
