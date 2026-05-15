<?php
/**
 * WooCommerce bridge and recommendation rules.
 *
 * @package OcearaSunConcierge
 */

if (! defined('ABSPATH')) {
    exit;
}

class OSC_WooCommerce
{
    public static function is_active(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    public static function product_map(): array
    {
        $settings = OSC_Settings::get();
        return array(
            'stick' => array(
                'option' => 'product_stick_id',
                'id' => absint($settings['product_stick_id']),
                'fallback_name' => 'Mineral Sunscreen Stick SPF50+',
                'advice' => __('Idéal pour les zones sensibles, le sport, le surf et les retouches rapides.', 'oceara-sun-concierge'),
            ),
            'cream' => array(
                'option' => 'product_cream_id',
                'id' => absint($settings['product_cream_id']),
                'fallback_name' => 'Mineral Sunscreen Cream SPF50+',
                'advice' => __('Une protection solaire quotidienne confortable pour visage, corps et peaux sensibles.', 'oceara-sun-concierge'),
            ),
            'hair_oil' => array(
                'option' => 'product_hair_oil_id',
                'id' => absint($settings['product_hair_oil_id']),
                'fallback_name' => 'Sun Hair Protection Oil — UV + Salt Protection',
                'advice' => __('Protège les cheveux exposés au soleil, au sel et aux sessions plage/surf.', 'oceara-sun-concierge'),
            ),
            'routine_pack' => array(
                'option' => 'product_routine_pack_id',
                'id' => absint($settings['product_routine_pack_id']),
                'fallback_name' => 'Oceara Full Sun Routine Pack',
                'advice' => __('La routine solaire complète pour partir léger avec une protection premium.', 'oceara-sun-concierge'),
            ),
        );
    }

    public static function get_recommended_product(array $answers): array
    {
        $skin = sanitize_text_field($answers['skin_type'] ?? '');
        $usage = sanitize_text_field($answers['usage_type'] ?? '');
        $texture = sanitize_text_field($answers['texture_preference'] ?? '');
        $zone = sanitize_text_field($answers['zone'] ?? '');
        $key = 'cream';

        if ('Cheveux exposés au soleil' === $usage || 'Cheveux' === $zone) {
            $key = 'hair_oil';
        } elseif ('Format stick' === $texture || 'Lèvres / nez / zones sensibles' === $zone) {
            $key = 'stick';
        } elseif ('Sensible' === $skin && 'Minérale' === $texture) {
            $key = 'cream';
        } elseif ('Routine complète' === $zone || 'Voyage' === $usage) {
            $key = 'routine_pack';
        } elseif ('Sèche' === $skin || 'Hydratante' === $texture) {
            $key = 'cream';
        } elseif ('Sport / surf' === $usage) {
            $key = 'stick';
        }

        return self::get_product_data($key);
    }

    public static function get_product_data(string $key): array
    {
        $map = self::product_map();
        $item = $map[$key] ?? $map['cream'];
        $data = array(
            'key' => $key,
            'id' => (int) $item['id'],
            'name' => $item['fallback_name'],
            'image' => '',
            'price' => '',
            'permalink' => '',
            'add_to_cart_url' => '',
            'available' => false,
            'woocommerce_active' => self::is_active(),
            'advice' => $item['advice'],
        );

        if (! self::is_active() || empty($item['id'])) {
            return $data;
        }

        $product = wc_get_product($item['id']);
        if (! $product) {
            return $data;
        }

        $image_id = $product->get_image_id();
        $data['name'] = $product->get_name();
        $data['image'] = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';
        $data['price'] = wp_strip_all_tags($product->get_price_html());
        $data['permalink'] = get_permalink($product->get_id());
        $data['add_to_cart_url'] = $product->is_purchasable() && $product->is_in_stock() ? $product->add_to_cart_url() : '';
        $data['available'] = $product->is_purchasable() && $product->is_in_stock();

        return $data;
    }

    public static function readable_status(string $status): string
    {
        $statuses = array(
            'pending' => __('En attente de paiement', 'oceara-sun-concierge'),
            'processing' => __('En traitement', 'oceara-sun-concierge'),
            'completed' => __('Expédiée / Terminée', 'oceara-sun-concierge'),
            'cancelled' => __('Annulée', 'oceara-sun-concierge'),
            'refunded' => __('Remboursée', 'oceara-sun-concierge'),
            'failed' => __('Échec paiement', 'oceara-sun-concierge'),
            'on-hold' => __('En attente de paiement', 'oceara-sun-concierge'),
        );
        return $statuses[$status] ?? wc_get_order_status_name($status);
    }

    public static function track_order(string $email, int $order_id): array
    {
        if (! self::is_active() || ! function_exists('wc_get_order')) {
            return array('success' => false, 'message' => __('Le suivi de commande WooCommerce est momentanément indisponible.', 'oceara-sun-concierge'));
        }

        $order = wc_get_order($order_id);
        if (! $order || strtolower($order->get_billing_email()) !== strtolower($email)) {
            return array('success' => false, 'message' => __('Nous n’avons pas trouvé cette commande. Vérifiez votre email et votre numéro de commande, ou contactez Oceara sur WhatsApp.', 'oceara-sun-concierge'));
        }

        return array(
            'success' => true,
            'order' => array(
                'status' => self::readable_status($order->get_status()),
                'date' => $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '',
                'total' => wp_strip_all_tags($order->get_formatted_order_total()),
                'message' => __('Votre commande a bien été retrouvée. Voici son statut actuel.', 'oceara-sun-concierge'),
            ),
        );
    }
}
