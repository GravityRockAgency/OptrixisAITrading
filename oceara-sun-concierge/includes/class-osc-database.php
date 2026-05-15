<?php
/**
 * Database layer.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Database
{
    public static function table(string $name): string
    {
        global $wpdb;
        return $wpdb->prefix . 'osc_' . $name;
    }

    public static function create_tables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $conversations = self::table('conversations');
        $recommendations = self::table('recommendations');
        $webhook_logs = self::table('webhook_logs');

        $sql = "CREATE TABLE {$conversations} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) DEFAULT '',
            email VARCHAR(190) DEFAULT '',
            phone VARCHAR(60) DEFAULT '',
            topic VARCHAR(190) DEFAULT '',
            message LONGTEXT,
            status VARCHAR(50) DEFAULT 'new',
            source_page TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};

        CREATE TABLE {$recommendations} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            skin_type VARCHAR(100) DEFAULT '',
            usage_type VARCHAR(100) DEFAULT '',
            texture_preference VARCHAR(100) DEFAULT '',
            zone VARCHAR(100) DEFAULT '',
            recommended_product_id BIGINT UNSIGNED DEFAULT 0,
            customer_email VARCHAR(190) DEFAULT '',
            action_clicked VARCHAR(100) DEFAULT 'none',
            source_page TEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY recommended_product_id (recommended_product_id),
            KEY created_at (created_at)
        ) {$charset_collate};

        CREATE TABLE {$webhook_logs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(100) DEFAULT '',
            payload LONGTEXT,
            response LONGTEXT,
            status VARCHAR(50) DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function insert_conversation(array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert(
            self::table('conversations'),
            array(
                'name' => sanitize_text_field($data['name'] ?? ''),
                'email' => sanitize_email($data['email'] ?? ''),
                'phone' => OSC_Security::sanitize_phone($data['phone'] ?? ''),
                'topic' => sanitize_text_field($data['topic'] ?? ''),
                'message' => sanitize_textarea_field($data['message'] ?? ''),
                'status' => 'new',
                'source_page' => OSC_Security::source_page($data['source_page'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        return (int) $wpdb->insert_id;
    }

    public static function insert_recommendation(array $data): int
    {
        global $wpdb;
        $wpdb->insert(
            self::table('recommendations'),
            array(
                'skin_type' => sanitize_text_field($data['skin_type'] ?? ''),
                'usage_type' => sanitize_text_field($data['usage_type'] ?? ''),
                'texture_preference' => sanitize_text_field($data['texture_preference'] ?? ''),
                'zone' => sanitize_text_field($data['zone'] ?? ''),
                'recommended_product_id' => absint($data['recommended_product_id'] ?? 0),
                'customer_email' => sanitize_email($data['customer_email'] ?? ''),
                'action_clicked' => sanitize_text_field($data['action_clicked'] ?? 'none'),
                'source_page' => OSC_Security::source_page($data['source_page'] ?? ''),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        return (int) $wpdb->insert_id;
    }

    public static function update_recommendation_action(int $id, string $action_clicked): bool
    {
        global $wpdb;
        if ($id <= 0) {
            return false;
        }

        $updated = $wpdb->update(
            self::table('recommendations'),
            array('action_clicked' => sanitize_text_field($action_clicked)),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        return false !== $updated;
    }

    public static function log_webhook(string $event_type, array $payload, string $response, string $status): void
    {
        global $wpdb;
        $wpdb->insert(
            self::table('webhook_logs'),
            array(
                'event_type' => sanitize_text_field($event_type),
                'payload' => wp_json_encode($payload),
                'response' => wp_kses_post($response),
                'status' => sanitize_text_field($status),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }

    public static function get_stats(): array
    {
        global $wpdb;
        $conversations = self::table('conversations');
        $recommendations = self::table('recommendations');
        $logs = self::table('webhook_logs');

        return array(
            'total_conversations' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$conversations}"),
            'open_conversations' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$conversations} WHERE status IN (%s,%s)", 'new', 'in_progress')),
            'recommendations' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$recommendations}"),
            'whatsapp_clicks' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$logs} WHERE event_type = %s", 'whatsapp_click')),
            'top_product_id' => (int) $wpdb->get_var("SELECT recommended_product_id FROM {$recommendations} WHERE recommended_product_id > 0 GROUP BY recommended_product_id ORDER BY COUNT(*) DESC LIMIT 1"),
        );
    }
}
