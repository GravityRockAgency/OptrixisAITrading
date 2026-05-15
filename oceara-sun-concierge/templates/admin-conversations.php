<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="wrap osc-admin">
    <h1><?php echo esc_html__('Conversations Oceara', 'oceara-sun-concierge'); ?></h1>
    <table class="widefat striped osc-table">
        <thead><tr><th><?php echo esc_html__('Nom', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Email', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Téléphone', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Sujet', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Message', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Statut', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Date', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Source', 'oceara-sun-concierge'); ?></th><th><?php echo esc_html__('Actions', 'oceara-sun-concierge'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($rows)) : ?>
            <tr><td colspan="9"><?php echo esc_html__('Aucune conversation pour le moment.', 'oceara-sun-concierge'); ?></td></tr>
        <?php else : foreach ($rows as $row) : ?>
            <tr>
                <td><?php echo esc_html($row->name); ?></td>
                <td><a href="mailto:<?php echo esc_attr($row->email); ?>"><?php echo esc_html($row->email); ?></a></td>
                <td><?php echo esc_html($row->phone); ?></td>
                <td><?php echo esc_html($row->topic); ?></td>
                <td title="<?php echo esc_attr($row->message); ?>"><?php echo esc_html(wp_trim_words($row->message, 12)); ?></td>
                <td><span class="osc-badge osc-badge--<?php echo esc_attr($row->status); ?>"><?php echo esc_html($row->status); ?></span></td>
                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row->created_at)); ?></td>
                <td><a href="<?php echo esc_url($row->source_page); ?>" target="_blank" rel="noopener"><?php echo esc_html__('Ouvrir', 'oceara-sun-concierge'); ?></a></td>
                <td class="osc-actions-admin">
                    <details><summary><?php echo esc_html__('Voir détail', 'oceara-sun-concierge'); ?></summary><p><?php echo nl2br(esc_html($row->message)); ?></p></details>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('osc_conversation_action'); ?>
                        <input type="hidden" name="action" value="osc_update_conversation_status">
                        <input type="hidden" name="conversation_id" value="<?php echo esc_attr($row->id); ?>">
                        <select name="status">
                            <?php foreach (array('new' => 'Nouveau', 'in_progress' => 'En cours', 'treated' => 'Traité', 'closed' => 'Fermé') as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($row->status, $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button" type="submit"><?php echo esc_html__('Modifier statut', 'oceara-sun-concierge'); ?></button>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Supprimer cette conversation ?');">
                        <?php wp_nonce_field('osc_delete_conversation'); ?>
                        <input type="hidden" name="action" value="osc_delete_conversation">
                        <input type="hidden" name="conversation_id" value="<?php echo esc_attr($row->id); ?>">
                        <button class="button button-link-delete" type="submit"><?php echo esc_html__('Supprimer', 'oceara-sun-concierge'); ?></button>
                    </form>
                    <?php $wa = OSC_Security::clean_whatsapp_number(OSC_Settings::get()['whatsapp_number']); if ($wa) : ?>
                        <a class="button" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/' . $wa . '?text=' . rawurlencode('Bonjour Oceara, je réponds à votre demande.')); ?>"><?php echo esc_html__('Ouvrir WhatsApp', 'oceara-sun-concierge'); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
