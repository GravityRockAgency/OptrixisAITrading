<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="wrap osc-admin">
    <h1><?php echo esc_html__('Oceara Concierge — Dashboard', 'oceara-sun-concierge'); ?></h1>
    <?php if (! OSC_WooCommerce::is_active()) : ?>
        <div class="notice notice-warning"><p><?php echo esc_html__('WooCommerce est désactivé : le widget, le quiz, WhatsApp et les formulaires restent actifs, mais les fonctions e-commerce sont masquées.', 'oceara-sun-concierge'); ?></p></div>
    <?php endif; ?>
    <div class="osc-stat-grid">
        <div class="osc-stat"><span><?php echo esc_html(number_format_i18n($stats['total_conversations'])); ?></span><strong><?php echo esc_html__('Conversations', 'oceara-sun-concierge'); ?></strong></div>
        <div class="osc-stat"><span><?php echo esc_html(number_format_i18n($stats['open_conversations'])); ?></span><strong><?php echo esc_html__('Demandes non traitées', 'oceara-sun-concierge'); ?></strong></div>
        <div class="osc-stat"><span><?php echo esc_html(number_format_i18n($stats['recommendations'])); ?></span><strong><?php echo esc_html__('Recommandations', 'oceara-sun-concierge'); ?></strong></div>
        <div class="osc-stat"><span><?php echo esc_html(number_format_i18n($stats['whatsapp_clicks'])); ?></span><strong><?php echo esc_html__('Clics WhatsApp', 'oceara-sun-concierge'); ?></strong></div>
        <div class="osc-stat"><span><?php echo esc_html($stats['top_product_id'] ? get_the_title($stats['top_product_id']) : __('Aucun', 'oceara-sun-concierge')); ?></span><strong><?php echo esc_html__('Produit le plus recommandé', 'oceara-sun-concierge'); ?></strong></div>
    </div>
</div>
