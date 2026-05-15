<?php
/**
 * n8n webhook integration.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_N8N
{
    public static function send(string $event, array $payload): void
    {
        $settings = OSC_Settings::get();
        if (empty($settings['enable_n8n']) || empty($settings['n8n_webhook_url'])) {
            return;
        }

        $payload = array_merge(array(
            'event' => sanitize_text_field($event),
            'created_at' => current_time('mysql'),
        ), $payload);

        $response = wp_remote_post($settings['n8n_webhook_url'], array(
            'timeout' => 0.01,
            'blocking' => false,
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            OSC_Database::log_webhook($event, $payload, $response->get_error_message(), 'error');
            return;
        }

        OSC_Database::log_webhook($event, $payload, 'Webhook dispatched asynchronously.', 'queued');
    }
}
