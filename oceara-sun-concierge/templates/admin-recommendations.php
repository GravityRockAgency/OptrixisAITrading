<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="wrap osc-admin">
    <h1><?php echo esc_html__('Recommandations Oceara', 'oceara-sun-concierge'); ?></h1>
    <table class="widefat striped osc-table">
        <thead><tr><th><?php echo esc_html__('Type de peau', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Usage', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Préférence', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Zone', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Produit recommandé', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Action cliquée', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Date', 'oceara-sun-concierge'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($rows)) : ?>
            <tr><td colspan="7"><?php echo esc_html__('Aucune recommandation pour le moment.', 'oceara-sun-concierge'); ?></td></tr>
        <?php else : foreach ($rows as $row) : ?>
            <tr>
                <td><?php echo esc_html($row->skin_type); ?></td>
                <td><?php echo esc_html($row->usage_type); ?></td>
                <td><?php echo esc_html($row->texture_preference); ?></td>
                <td><?php echo esc_html($row->zone); ?></td>
                <td><?php echo esc_html($row->recommended_product_id ? get_the_title((int) $row->recommended_product_id) : __('Produit V1 non lié', 'oceara-sun-concierge')); ?></td>
                <td><span class="osc-badge"><?php echo esc_html($row->action_clicked ?: __('Aucun clic', 'oceara-sun-concierge')); ?></span></td>
                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row->created_at)); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
