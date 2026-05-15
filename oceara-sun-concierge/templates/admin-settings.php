<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="wrap osc-admin osc-settings">
    <h1><?php echo esc_html__('Réglages Oceara Concierge', 'oceara-sun-concierge'); ?></h1>
    <?php if (! OSC_WooCommerce::is_active()) : ?>
        <div class="notice notice-info"><p><?php echo esc_html__('WooCommerce n’est pas actif. Vous pouvez configurer la conciergerie, WhatsApp et n8n ; les sélecteurs produits seront appliqués lorsque WooCommerce sera actif.', 'oceara-sun-concierge'); ?></p></div>
    <?php endif; ?>
    <form method="post" action="options.php">
        <?php settings_fields('osc_settings_group'); ?>
        <section class="osc-card"><h2><?php echo esc_html__('Identité & expérience widget', 'oceara-sun-concierge'); ?></h2>
            <label>Logo URL<input type="url" name="osc_settings[logo_url]" value="<?php echo esc_attr($settings['logo_url']); ?>"></label>
            <label>Couleur principale<input type="color" name="osc_settings[primary_color]" value="<?php echo esc_attr($settings['primary_color']); ?>"></label>
            <label>Couleur secondaire<input type="color" name="osc_settings[secondary_color]" value="<?php echo esc_attr($settings['secondary_color']); ?>"></label>
            <label>Message d’accueil<textarea name="osc_settings[welcome_message]" rows="3"><?php echo esc_textarea($settings['welcome_message']); ?></textarea></label>
            <label>Lien politique de confidentialité<input type="url" name="osc_settings[privacy_url]" value="<?php echo esc_attr($settings['privacy_url']); ?>"></label>
        </section>
        <section class="osc-card"><h2><?php echo esc_html__('Contact & disponibilité', 'oceara-sun-concierge'); ?></h2>
            <label>Numéro WhatsApp<input type="text" name="osc_settings[whatsapp_number]" value="<?php echo esc_attr($settings['whatsapp_number']); ?>" placeholder="+212..."></label>
            <label>Email support<input type="email" name="osc_settings[support_email]" value="<?php echo esc_attr($settings['support_email']); ?>"></label>
            <label>Horaires d’ouverture<input type="text" name="osc_settings[opening_hours]" value="<?php echo esc_attr($settings['opening_hours']); ?>"></label>
            <label>Message offline<textarea name="osc_settings[offline_message]" rows="2"><?php echo esc_textarea($settings['offline_message']); ?></textarea></label>
        </section>
        <section class="osc-card"><h2><?php echo esc_html__('Produits WooCommerce', 'oceara-sun-concierge'); ?></h2>
            <?php foreach (array('product_stick_id' => 'Stick SPF50+', 'product_cream_id' => 'Cream SPF50+', 'product_hair_oil_id' => 'Hair Protection Oil', 'product_routine_pack_id' => 'Full Sun Routine Pack') as $key => $label) : ?>
                <label><?php echo esc_html($label); ?><input type="number" min="0" name="osc_settings[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings[$key]); ?>" placeholder="ID produit WooCommerce"></label>
            <?php endforeach; ?>
        </section>
        <section class="osc-card"><h2><?php echo esc_html__('Automatisation n8n', 'oceara-sun-concierge'); ?></h2>
            <label>Webhook n8n<input type="url" name="osc_settings[n8n_webhook_url]" value="<?php echo esc_attr($settings['n8n_webhook_url']); ?>"></label>
        </section>
        <section class="osc-card"><h2><?php echo esc_html__('Modules actifs', 'oceara-sun-concierge'); ?></h2>
            <?php foreach (array('enable_quiz' => 'Activer le quiz', 'enable_whatsapp' => 'Activer WhatsApp', 'enable_order_tracking' => 'Activer le suivi commande', 'enable_n8n' => 'Activer n8n') as $key => $label) : ?>
                <label class="osc-checkbox"><input type="checkbox" name="osc_settings[<?php echo esc_attr($key); ?>]" value="1" <?php checked($settings[$key], 1); ?>> <?php echo esc_html($label); ?></label>
            <?php endforeach; ?>
        </section>
        <?php submit_button(__('Enregistrer les réglages', 'oceara-sun-concierge')); ?>
    </form>
</div>
