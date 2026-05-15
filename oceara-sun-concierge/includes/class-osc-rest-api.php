<?php
/**
 * REST API routes.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_REST_API
{
    public function hooks(): void
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes(): void
    {
        register_rest_route('osc/v1', '/submit-conversation', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_conversation'),
            'permission_callback' => array('OSC_Security', 'rest_nonce_permission'),
        ));
        register_rest_route('osc/v1', '/save-recommendation', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_recommendation'),
            'permission_callback' => array('OSC_Security', 'rest_nonce_permission'),
        ));
        register_rest_route('osc/v1', '/track-whatsapp-click', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_whatsapp_click'),
            'permission_callback' => array('OSC_Security', 'rest_nonce_permission'),
        ));
        register_rest_route('osc/v1', '/track-order', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_order'),
            'permission_callback' => array('OSC_Security', 'rest_nonce_permission'),
        ));
        register_rest_route('osc/v1', '/get-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array('OSC_Security', 'rest_nonce_permission'),
        ));
    }

    public function submit_conversation(WP_REST_Request $request): WP_REST_Response
    {
        if ('' !== sanitize_text_field($request->get_param('company_website'))) {
            return rest_ensure_response(array('success' => true, 'message' => __('Merci, votre demande a bien été envoyée.', 'oceara-sun-concierge')));
        }

        $data = array(
            'name' => sanitize_text_field($request->get_param('name')),
            'email' => sanitize_email($request->get_param('email')),
            'phone' => OSC_Security::sanitize_phone((string) $request->get_param('phone')),
            'topic' => sanitize_text_field($request->get_param('topic')),
            'message' => sanitize_textarea_field($request->get_param('message')),
            'source_page' => OSC_Security::source_page((string) $request->get_param('source_page')),
        );

        if (empty($data['name']) || empty($data['email']) || empty($data['message']) || ! is_email($data['email'])) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Merci de compléter les champs obligatoires.', 'oceara-sun-concierge')), 400);
        }

        $id = OSC_Database::insert_conversation($data);
        OSC_N8N::send('new_conversation', array_merge($data, array('conversation_id' => $id)));

        return rest_ensure_response(array('success' => true, 'id' => $id, 'message' => __('Merci, votre demande a bien été envoyée. L’équipe Oceara vous répondra rapidement.', 'oceara-sun-concierge')));
    }

    public function save_recommendation(WP_REST_Request $request): WP_REST_Response
    {
        $action_clicked = sanitize_text_field($request->get_param('action_clicked') ?: 'none');
        $recommendation_id = absint($request->get_param('recommendation_id'));

        if ($recommendation_id && 'none' !== $action_clicked) {
            OSC_Database::update_recommendation_action($recommendation_id, $action_clicked);
            return rest_ensure_response(array('success' => true, 'id' => $recommendation_id));
        }

        $answers = array(
            'skin_type' => sanitize_text_field($request->get_param('skin_type')),
            'usage_type' => sanitize_text_field($request->get_param('usage_type')),
            'texture_preference' => sanitize_text_field($request->get_param('texture_preference')),
            'zone' => sanitize_text_field($request->get_param('zone')),
        );

        if (in_array('', $answers, true)) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Merci de compléter le quiz avant de générer une recommandation.', 'oceara-sun-concierge')), 400);
        }

        $product = OSC_WooCommerce::get_recommended_product($answers);
        $data = array_merge($answers, array(
            'recommended_product_id' => absint($product['id']),
            'customer_email' => sanitize_email($request->get_param('customer_email')),
            'action_clicked' => $action_clicked,
            'source_page' => OSC_Security::source_page((string) $request->get_param('source_page')),
        ));

        $id = OSC_Database::insert_recommendation($data);
        OSC_N8N::send('new_recommendation', array_merge($data, array('recommendation_id' => $id)));

        return rest_ensure_response(array('success' => true, 'id' => $id, 'product' => $product));
    }

    public function track_whatsapp_click(WP_REST_Request $request): WP_REST_Response
    {
        $payload = array(
            'context' => sanitize_text_field($request->get_param('context')),
            'message' => sanitize_textarea_field($request->get_param('message')),
            'source_page' => OSC_Security::source_page((string) $request->get_param('source_page')),
        );
        OSC_Database::log_webhook('whatsapp_click', $payload, 'local_event', 'tracked');
        OSC_N8N::send('whatsapp_click', $payload);

        return rest_ensure_response(array('success' => true));
    }

    public function track_order(WP_REST_Request $request): WP_REST_Response
    {
        $email = sanitize_email($request->get_param('email'));
        $order_id = absint($request->get_param('order_id'));
        if (! is_email($email) || ! $order_id) {
            return new WP_REST_Response(array('success' => false, 'message' => __('Email ou numéro de commande invalide.', 'oceara-sun-concierge')), 400);
        }

        $result = OSC_WooCommerce::track_order($email, $order_id);
        OSC_N8N::send('order_tracking_request', array(
            'email' => $email,
            'order_id' => $order_id,
            'found' => ! empty($result['success']),
            'source_page' => OSC_Security::source_page((string) $request->get_param('source_page')),
        ));

        return rest_ensure_response($result);
    }

    public function get_settings(): WP_REST_Response
    {
        $settings = OSC_Settings::get();
        return rest_ensure_response(array(
            'success' => true,
            'settings' => array(
                'welcome_message' => $settings['welcome_message'],
                'whatsapp_enabled' => (bool) $settings['enable_whatsapp'],
                'order_tracking_enabled' => (bool) $settings['enable_order_tracking'] && OSC_WooCommerce::is_active(),
                'quiz_enabled' => (bool) $settings['enable_quiz'],
                'woocommerce_active' => OSC_WooCommerce::is_active(),
            ),
        ));
    }
}
