<?php
/** @var array $settings */
if (! defined('ABSPATH')) {
    exit;
}
?>
<div id="osc-concierge" class="osc-concierge" data-open="false">
    <button class="osc-launcher" type="button" aria-label="<?php echo esc_attr__('Ouvrir la conciergerie solaire Oceara', 'oceara-sun-concierge'); ?>" aria-expanded="false">
        <span class="osc-launcher__icon">☼</span>
        <span class="osc-launcher__text"><?php echo esc_html__('Concierge', 'oceara-sun-concierge'); ?></span>
    </button>

    <section class="osc-panel" role="dialog" aria-modal="false" aria-label="<?php echo esc_attr__('Oceara Sun Concierge', 'oceara-sun-concierge'); ?>">
        <header class="osc-panel__header">
            <div class="osc-brand">
                <?php if (! empty($settings['logo_url'])) : ?>
                    <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="<?php echo esc_attr__('Oceara', 'oceara-sun-concierge'); ?>" />
                <?php else : ?>
                    <span class="osc-brand__mark">O</span>
                <?php endif; ?>
                <div>
                    <strong><?php echo esc_html__('Oceara', 'oceara-sun-concierge'); ?></strong>
                    <small><?php echo esc_html__('Sun Concierge', 'oceara-sun-concierge'); ?></small>
                </div>
            </div>
            <button class="osc-close" type="button" aria-label="<?php echo esc_attr__('Fermer', 'oceara-sun-concierge'); ?>">×</button>
        </header>

        <div class="osc-panel__body">
            <div class="osc-screen is-active" data-screen="home">
                <p class="osc-welcome"><?php echo esc_html($settings['welcome_message']); ?></p>
                <div class="osc-actions">
                    <?php if (! empty($settings['enable_quiz'])) : ?>
                        <button type="button" data-action="quiz" class="osc-choice"><?php echo esc_html__('Me recommander un produit', 'oceara-sun-concierge'); ?></button>
                        <button type="button" data-action="quiz" class="osc-choice"><?php echo esc_html__('Trouver ma routine solaire', 'oceara-sun-concierge'); ?></button>
                    <?php endif; ?>
                    <?php if (! empty($settings['enable_order_tracking']) && OSC_WooCommerce::is_active()) : ?>
                        <button type="button" data-screen-target="order" class="osc-choice"><?php echo esc_html__('Suivre ma commande', 'oceara-sun-concierge'); ?></button>
                    <?php endif; ?>
                    <button type="button" data-screen-target="info-delivery" class="osc-choice"><?php echo esc_html__('Livraison & retours', 'oceara-sun-concierge'); ?></button>
                    <button type="button" data-screen-target="info-promo" class="osc-choice"><?php echo esc_html__('Code promo', 'oceara-sun-concierge'); ?></button>
                    <button type="button" data-screen-target="contact" class="osc-choice"><?php echo esc_html__('Parler à Oceara', 'oceara-sun-concierge'); ?></button>
                    <button type="button" data-screen-target="contact" class="osc-choice"><?php echo esc_html__('Autre demande', 'oceara-sun-concierge'); ?></button>
                </div>
            </div>

            <div class="osc-screen" data-screen="quiz">
                <button class="osc-back" type="button" data-screen-target="home">← <?php echo esc_html__('Retour', 'oceara-sun-concierge'); ?></button>
                <div class="osc-quiz-progress"><span></span></div>
                <h3 class="osc-quiz-title"></h3>
                <div class="osc-quiz-options"></div>
            </div>

            <div class="osc-screen" data-screen="result">
                <button class="osc-back" type="button" data-screen-target="home">← <?php echo esc_html__('Accueil', 'oceara-sun-concierge'); ?></button>
                <div class="osc-product-card" data-result-card></div>
            </div>

            <div class="osc-screen" data-screen="order">
                <button class="osc-back" type="button" data-screen-target="home">← <?php echo esc_html__('Retour', 'oceara-sun-concierge'); ?></button>
                <h3><?php echo esc_html__('Suivre ma commande', 'oceara-sun-concierge'); ?></h3>
                <form class="osc-form" data-form="order">
                    <label><?php echo esc_html__('Email', 'oceara-sun-concierge'); ?><input type="email" name="email" required></label>
                    <label><?php echo esc_html__('Numéro de commande', 'oceara-sun-concierge'); ?><input type="number" name="order_id" required min="1"></label>
                    <button type="submit" class="osc-primary-btn"><?php echo esc_html__('Voir le statut', 'oceara-sun-concierge'); ?></button>
                </form>
                <div class="osc-status" data-order-status></div>
            </div>

            <div class="osc-screen" data-screen="contact">
                <button class="osc-back" type="button" data-screen-target="home">← <?php echo esc_html__('Retour', 'oceara-sun-concierge'); ?></button>
                <h3><?php echo esc_html__('Parler à Oceara', 'oceara-sun-concierge'); ?></h3>
                <form class="osc-form" data-form="contact">
                    <label><?php echo esc_html__('Nom', 'oceara-sun-concierge'); ?><input type="text" name="name" required maxlength="190"></label>
                    <label><?php echo esc_html__('Email', 'oceara-sun-concierge'); ?><input type="email" name="email" required maxlength="190"></label>
                    <label><?php echo esc_html__('Téléphone', 'oceara-sun-concierge'); ?><input type="tel" name="phone" maxlength="60"></label>
                    <label><?php echo esc_html__('Sujet', 'oceara-sun-concierge'); ?>
                        <select name="topic" required>
                            <option value="Question produit"><?php echo esc_html__('Question produit', 'oceara-sun-concierge'); ?></option>
                            <option value="Livraison"><?php echo esc_html__('Livraison', 'oceara-sun-concierge'); ?></option>
                            <option value="Retour / échange"><?php echo esc_html__('Retour / échange', 'oceara-sun-concierge'); ?></option>
                            <option value="Problème commande"><?php echo esc_html__('Problème commande', 'oceara-sun-concierge'); ?></option>
                            <option value="Paiement"><?php echo esc_html__('Paiement', 'oceara-sun-concierge'); ?></option>
                            <option value="Collaboration"><?php echo esc_html__('Collaboration', 'oceara-sun-concierge'); ?></option>
                            <option value="Devenir revendeur"><?php echo esc_html__('Devenir revendeur', 'oceara-sun-concierge'); ?></option>
                            <option value="Autre"><?php echo esc_html__('Autre', 'oceara-sun-concierge'); ?></option>
                        </select>
                    </label>
                    <label><?php echo esc_html__('Message', 'oceara-sun-concierge'); ?><textarea name="message" required rows="4"></textarea></label>
                    <label class="osc-honeypot">Website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                    <p class="osc-consent"><?php echo esc_html__('En envoyant ce message, vous acceptez que Oceara utilise ces informations pour répondre à votre demande.', 'oceara-sun-concierge'); ?>
                        <?php if (! empty($settings['privacy_url'])) : ?><a href="<?php echo esc_url($settings['privacy_url']); ?>" target="_blank" rel="noopener"><?php echo esc_html__('Politique de confidentialité', 'oceara-sun-concierge'); ?></a><?php endif; ?>
                    </p>
                    <button type="submit" class="osc-primary-btn"><?php echo esc_html__('Envoyer', 'oceara-sun-concierge'); ?></button>
                </form>
                <button type="button" class="osc-whatsapp-link" data-whatsapp="collaboration"><?php echo esc_html__('WhatsApp revendeur / collaboration', 'oceara-sun-concierge'); ?></button>
                <div class="osc-status" data-contact-status></div>
            </div>

            <div class="osc-screen" data-screen="info-delivery">
                <button class="osc-back" type="button" data-screen-target="home">← <?php echo esc_html__('Retour', 'oceara-sun-concierge'); ?></button>
                <h3><?php echo esc_html__('Livraison & retours', 'oceara-sun-concierge'); ?></h3>
                <p><?php echo esc_html__('Notre équipe peut vous orienter sur les délais, les retours et les échanges. Envoyez une demande ou contactez Oceara sur WhatsApp.', 'oceara-sun-concierge'); ?></p>
                <button type="button" class="osc-primary-btn" data-screen-target="contact"><?php echo esc_html__('Envoyer une demande', 'oceara-sun-concierge'); ?></button>
            </div>

            <div class="osc-screen" data-screen="info-promo">
                <button class="osc-back" type="button" data-screen-target="home">← <?php echo esc_html__('Retour', 'oceara-sun-concierge'); ?></button>
                <h3><?php echo esc_html__('Code promo', 'oceara-sun-concierge'); ?></h3>
                <p><?php echo esc_html__('Pour les offres en cours, contactez Oceara ou inscrivez-vous aux communications de la marque.', 'oceara-sun-concierge'); ?></p>
                <button type="button" class="osc-primary-btn" data-screen-target="contact"><?php echo esc_html__('Demander une offre', 'oceara-sun-concierge'); ?></button>
            </div>
        </div>
        <footer class="osc-panel__footer"><?php echo esc_html($settings['opening_hours']); ?></footer>
    </section>
</div>
