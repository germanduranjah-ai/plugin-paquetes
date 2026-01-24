<?php
if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_Template {

    public static function init() {
        // Override single template
        add_filter('single_template', [__CLASS__, 'get_single_template']);
        // Enqueue assets for single page
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_single_assets']);
    }

    /**
     * Get custom single template
     */
    public static function get_single_template($template) {
        global $post;

        if ($post && $post->post_type === UDPAQUETES_CPT::POST_TYPE) {
            $custom_template = UDPQ_PATH . 'templates/single-ud_paquete.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Enqueue assets for single page
     */
    public static function enqueue_single_assets() {
        if (is_singular(UDPAQUETES_CPT::POST_TYPE)) {
            // Optional: icon + font libraries for a more "brand" look in the template.
            // You can disable these enqueues via:
            // add_filter('udpq_enqueue_icon_library', '__return_false');
            if (apply_filters('udpq_enqueue_icon_library', true)) {
                wp_enqueue_style(
                    'udpq-fontawesome',
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                    [],
                    '6.5.1'
                );
            }
            // Bree font is expected to be loaded by the theme/Elementor (custom font).

            wp_enqueue_style('udpq-public');
            wp_enqueue_script('udpq-public');
            wp_enqueue_style('udpq-single', UDPQ_URL . 'assets/single.css', ['udpq-public'], UDPQ_VERSION);
            wp_enqueue_script('udpq-single', UDPQ_URL . 'assets/single.js', ['udpq-public', 'jquery'], UDPQ_VERSION, true);

            // Localize script for AJAX
            wp_localize_script('udpq-single', 'udpqSingle', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('udpq_reserva_nonce'),
            ]);
        }
    }

    /**
     * Render single paquete template
     */
    public static function render_single_template() {
        $post_id = get_the_ID();
        $active = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, true);

        // Get all meta data
        $destino = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_DESTINO, true);
        $salida_vuelo = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_VUELO, true);
        $compania = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_COMPANIA, true);
        $noches = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_NOCHES, true);
        $salida = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA, true);
        $regreso = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO, true);
        $equipaje = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_EQUIPAJE, true);
        $hotel = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_HOTEL, true);
        $regimen = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGIMEN, true);
        $seguro_traslados = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SEGURO_TRASLADOS, true);
        $servicios = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SERVICIOS, true);
        $beneficios = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_BENEFICIOS, true);

        // Get price options
        $opts = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
        if (!is_array($opts) || empty($opts)) {
            $opts = [
                ['active'=>1,'label'=>'Base doble','price'=>floatval(get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_BASE_DOBLE, true)),'currency'=>'USD','mp_url'=>get_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, true),'note'=>''],
                ['active'=>0,'label'=>'Niño < 12','price'=>floatval(get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_NINO, true)),'currency'=>'USD','mp_url'=>'','note'=>''],
                ['active'=>0,'label'=>'Infante','price'=>floatval(get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_INFANTE, true)),'currency'=>'USD','mp_url'=>'','note'=>''],
            ];
        }

        // Calculate min price
        [$min_price, $currency] = self::get_price_from_meta($post_id);

        // Get featured image
        $featured_image = has_post_thumbnail() ? get_the_post_thumbnail_url($post_id, 'full') : '';

        // Parse servicios and beneficios (one per line)
        $servicios_list = !empty($servicios) ? array_filter(array_map('trim', explode("\n", $servicios))) : [];
        $beneficios_list = !empty($beneficios) ? array_filter(array_map('trim', explode("\n", $beneficios))) : [];

        include UDPQ_PATH . 'templates/single-ud_paquete.php';
    }

    /**
     * Get price from meta (same logic as shortcodes)
     */
    private static function get_price_from_meta($post_id) {
        $opts = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
        $min = null;
        $currency = 'USD';

        if (is_array($opts)) {
            foreach ($opts as $row) {
                $active = !empty($row['active']);
                $price = isset($row['price']) ? floatval($row['price']) : 0;
                if (!$active || $price <= 0) continue;
                if ($min === null || $price < $min) {
                    $min = $price;
                    $currency = !empty($row['currency']) ? $row['currency'] : 'USD';
                }
            }
        }

        if ($min === null) {
            $quick = floatval(get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_BASE_DOBLE, true));
            if ($quick > 0) $min = $quick;
        }

        return [$min, $currency];
    }
}
