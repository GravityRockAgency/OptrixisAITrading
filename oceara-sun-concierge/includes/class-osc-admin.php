<?php
/**
 * Admin screens.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_Admin
{
    private string $hook_prefix = 'toplevel_page_oceara-concierge';

    public function hooks(): void
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_post_osc_update_conversation_status', array($this, 'update_conversation_status'));
        add_action('admin_post_osc_delete_conversation', array($this, 'delete_conversation'));
    }

    public function menu(): void
    {
        add_menu_page(__('Oceara Concierge', 'oceara-sun-concierge'), __('Oceara Concierge', 'oceara-sun-concierge'), 'manage_options', 'oceara-concierge', array($this, 'dashboard'), 'dashicons-palmtree', 56);
        add_submenu_page('oceara-concierge', __('Dashboard', 'oceara-sun-concierge'), __('Dashboard', 'oceara-sun-concierge'), 'manage_options', 'oceara-concierge', array($this, 'dashboard'));
        add_submenu_page('oceara-concierge', __('Conversations', 'oceara-sun-concierge'), __('Conversations', 'oceara-sun-concierge'), 'manage_options', 'oceara-concierge-conversations', array($this, 'conversations'));
        add_submenu_page('oceara-concierge', __('Recommandations', 'oceara-sun-concierge'), __('Recommandations', 'oceara-sun-concierge'), 'manage_options', 'oceara-concierge-recommendations', array($this, 'recommendations'));
        add_submenu_page('oceara-concierge', __('Réglages', 'oceara-sun-concierge'), __('Réglages', 'oceara-sun-concierge'), 'manage_options', 'oceara-concierge-settings', array($this, 'settings'));
    }

    public function assets(string $hook): void
    {
        if (! str_contains($hook, 'oceara-concierge')) {
            return;
        }
        wp_enqueue_style('osc-admin', OSC_PLUGIN_URL . 'assets/css/admin.css', array(), OSC_VERSION);
        wp_enqueue_script('osc-admin', OSC_PLUGIN_URL . 'assets/js/admin.js', array(), OSC_VERSION, true);
    }

    public function dashboard(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'oceara-sun-concierge'));
        }
        $stats = OSC_Database::get_stats();
        include OSC_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public function conversations(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'oceara-sun-concierge'));
        }
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . OSC_Database::table('conversations') . " ORDER BY created_at DESC LIMIT 100");
        include OSC_PLUGIN_DIR . 'templates/admin-conversations.php';
    }

    public function recommendations(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'oceara-sun-concierge'));
        }
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . OSC_Database::table('recommendations') . " ORDER BY created_at DESC LIMIT 100");
        include OSC_PLUGIN_DIR . 'templates/admin-recommendations.php';
    }

    public function settings(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'oceara-sun-concierge'));
        }
        $settings = OSC_Settings::get();
        include OSC_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    public function update_conversation_status(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'oceara-sun-concierge'));
        }
        check_admin_referer('osc_conversation_action');
        global $wpdb;
        $id = absint($_POST['conversation_id'] ?? 0);
        $status = OSC_Security::sanitize_key_choice((string) ($_POST['status'] ?? ''), array('new', 'in_progress', 'treated', 'closed'), 'new');
        if ($id) {
            $wpdb->update(OSC_Database::table('conversations'), array('status' => $status, 'updated_at' => current_time('mysql')), array('id' => $id), array('%s', '%s'), array('%d'));
        }
        wp_safe_redirect(admin_url('admin.php?page=oceara-concierge-conversations&updated=1'));
        exit;
    }

    public function delete_conversation(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Accès refusé.', 'oceara-sun-concierge'));
        }
        check_admin_referer('osc_delete_conversation');
        global $wpdb;
        $id = absint($_POST['conversation_id'] ?? 0);
        if ($id) {
            $wpdb->delete(OSC_Database::table('conversations'), array('id' => $id), array('%d'));
        }
        wp_safe_redirect(admin_url('admin.php?page=oceara-concierge-conversations&deleted=1'));
        exit;
    }
}
