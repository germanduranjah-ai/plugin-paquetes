<?php
if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_Shortcodes {

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);

        /**
         * Shortcodes
         * ==========
         * Legacy (compat):
         * - [ud_paquetes] (main) => supports type="grid" | type="carousel"
         * - [ud_paquetes_grid] => alias for grid
         * - [ud_paquetes_carousel] => alias for carousel
         *
         * Pro (new):
         * - [udpq_paquetes] => grid/list (same engine)
         * - [udpq_paquetes_carousel] => carousel (Swiper)
         * - [udpq_paquetes_filter] => UI filters + results
         */
        add_shortcode('ud_paquetes', [__CLASS__, 'shortcode_paquetes']);
        add_shortcode('ud_paquetes_grid', [__CLASS__, 'shortcode_paquetes_grid']);
        add_shortcode('ud_paquetes_carousel', [__CLASS__, 'shortcode_paquetes_carousel']);

        add_shortcode('udpq_paquetes', [__CLASS__, 'shortcode_udpq_paquetes']);
        add_shortcode('udpq_paquetes_carousel', [__CLASS__, 'shortcode_udpq_paquetes_carousel']);
        add_shortcode('udpq_paquetes_filter', [__CLASS__, 'shortcode_udpq_paquetes_filter']);

        // Reservas (modal + form)
        add_shortcode('udpq_reserva_modal', [__CLASS__, 'shortcode_udpq_reserva_modal']);
        add_shortcode('udpq_reserva_form', [__CLASS__, 'shortcode_udpq_reserva_form']);
    }

    /**
     * PRO: [udpq_paquetes]
     * Wrapper over the legacy engine with stricter defaults.
     */
    public static function shortcode_udpq_paquetes($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts([
            'view' => 'grid',          // grid | list
            'columns' => 3,
            'limit' => 9,
            'only_active' => 1,
            'only_future' => 0,
            'orderby' => 'date',
            'order' => 'DESC',
            'ids' => '',               // IDs separados por coma
            'destino' => '',
            'hotel' => '',
            'regimen' => '',
            'price_min' => '',
            'price_max' => '',
            'min_noches' => '',
            'max_noches' => '',
        ], $atts, 'udpq_paquetes');

        $atts['type'] = 'grid';
        $atts['layout'] = ($atts['view'] === 'list') ? 'list' : 'grid';
        $atts['columns'] = max(1, min(4, intval($atts['columns'])));
        return self::shortcode_paquetes($atts);
    }

    /**
     * PRO: [udpq_paquetes_carousel]
     */
    public static function shortcode_udpq_paquetes_carousel($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts([
            'limit' => 10,
            'slides' => 3,
            'slides_tablet' => 2,
            'slides_mobile' => 1,
            'space' => 18,
            'loop' => 1,
            'autoplay' => 0,
            'pagination' => 1,
            'nav' => 1,
            // filters
            'only_active' => 1,
            'only_future' => 0,
            'ids' => '',               // IDs separados por coma
            'destino' => '',
            'hotel' => '',
            'regimen' => '',
            'min_noches' => '',
            'max_noches' => '',
            'price_min' => '',
            'price_max' => '',
            'fecha_desde' => '',
            'fecha_hasta' => '',
            'orderby' => 'date',
            'order' => 'DESC',
        ], $atts, 'udpq_paquetes_carousel');

        $atts['type'] = 'carousel';
        return self::shortcode_paquetes($atts);
    }

    /**
     * PRO: [udpq_paquetes_filter]
     * Renders a small filter bar (GET params) + results.
     */
    public static function shortcode_udpq_paquetes_filter($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts([
            'results' => 'grid',       // grid | carousel
            'columns' => 3,
            'limit' => 12,
            'fields' => 'destino,noches,desde,fecha',
            'only_active' => 1,
            'only_future' => 0,
        ], $atts, 'udpq_paquetes_filter');

        $fields = array_filter(array_map('trim', explode(',', (string) $atts['fields'])));
        $show_destino = in_array('destino', $fields, true);
        $show_noches  = in_array('noches', $fields, true);
        $show_desde   = in_array('desde', $fields, true);
        $show_fecha   = in_array('fecha', $fields, true);

        // Current values from GET.
        $v_destino = isset($_GET['destino']) ? sanitize_text_field($_GET['destino']) : '';
        $v_noches_min = isset($_GET['noches_min']) ? sanitize_text_field($_GET['noches_min']) : '';
        $v_noches_max = isset($_GET['noches_max']) ? sanitize_text_field($_GET['noches_max']) : '';
        $v_price_min = isset($_GET['price_min']) ? sanitize_text_field($_GET['price_min']) : '';
        $v_price_max = isset($_GET['price_max']) ? sanitize_text_field($_GET['price_max']) : '';
        $v_fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
        $v_fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';

        ob_start();

        $wrap_class = 'udpq-block';
        $wrap_open = !empty($atts['container']) ? '<div class="' . esc_attr($wrap_class) . '">' : '';
        $wrap_close = !empty($atts['container']) ? '</div>' : '';

        echo $wrap_open;
        ?>
        <form class="udpq-filters" method="get">
            <div class="udpq-filters__row">
                <?php if ($show_destino): ?>
                    <div class="udpq-filters__field">
                        <label>Destino</label>
                        <input type="text" name="destino" value="<?php echo esc_attr($v_destino); ?>" placeholder="Ej: Punta Cana" />
                    </div>
                <?php endif; ?>

                <?php if ($show_noches): ?>
                    <div class="udpq-filters__field">
                        <label>Noches (min)</label>
                        <input type="number" name="noches_min" value="<?php echo esc_attr($v_noches_min); ?>" min="0" />
                    </div>
                    <div class="udpq-filters__field">
                        <label>Noches (max)</label>
                        <input type="number" name="noches_max" value="<?php echo esc_attr($v_noches_max); ?>" min="0" />
                    </div>
                <?php endif; ?>

                <?php if ($show_desde): ?>
                    <div class="udpq-filters__field">
                        <label>Desde (min)</label>
                        <input type="number" name="price_min" value="<?php echo esc_attr($v_price_min); ?>" min="0" />
                    </div>
                    <div class="udpq-filters__field">
                        <label>Desde (max)</label>
                        <input type="number" name="price_max" value="<?php echo esc_attr($v_price_max); ?>" min="0" />
                    </div>
                <?php endif; ?>

                <?php if ($show_fecha): ?>
                    <div class="udpq-filters__field">
                        <label>Salida desde</label>
                        <input type="date" name="fecha_desde" value="<?php echo esc_attr($v_fecha_desde); ?>" />
                    </div>
                    <div class="udpq-filters__field">
                        <label>Salida hasta</label>
                        <input type="date" name="fecha_hasta" value="<?php echo esc_attr($v_fecha_hasta); ?>" />
                    </div>
                <?php endif; ?>
            </div>

            <div class="udpq-filters__actions">
                <button class="udpq-btn" type="submit">Filtrar</button>
                <a class="udpq-btn udpq-btn--ghost" href="<?php echo esc_url(remove_query_arg(['destino','noches_min','noches_max','price_min','price_max','fecha_desde','fecha_hasta'])); ?>">Limpiar</a>
            </div>
        </form>
        <?php

        // Render results using the legacy engine, reading URL params inside.
        $res = [
            'type' => ($atts['results'] === 'carousel') ? 'carousel' : 'grid',
            'limit' => intval($atts['limit']),
            'columns' => intval($atts['columns']),
            'only_active' => intval($atts['only_active']),
            'only_future' => intval($atts['only_future']),
            'show_filters' => 0,
        ];
        echo self::shortcode_paquetes($res);

        return ob_get_clean();
    }

    public static function register_assets() {
        wp_register_style('udpq-public', UDPQ_URL . 'assets/public.css', [], UDPQ_VERSION);
        wp_register_script('udpq-public', UDPQ_URL . 'assets/public.js', [], UDPQ_VERSION, true);

        // Carousel (Swiper) – only enqueued when the carousel shortcode is used.
        wp_register_style(
            'udpq-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            [],
            '11.0.0'
        );
        wp_register_script(
            'udpq-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            [],
            '11.0.0',
            true
        );
        wp_register_script(
            'udpq-carousel',
            UDPQ_URL . 'assets/carousel.js',
            ['udpq-swiper'],
            UDPQ_VERSION,
            true
        );
    }

    /**
     * Alias: grid
     */
    public static function shortcode_paquetes_grid($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts['type'] = 'grid';
        return self::shortcode_paquetes($atts);
    }

    /**
     * Alias: carousel
     */
    public static function shortcode_paquetes_carousel($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts['type'] = 'carousel';
        return self::shortcode_paquetes($atts);
    }

    /**
     * Get price for cards (grid/carousel)
     *
     * Prefer the "Base doble" option when available. Match by:
     *  - row['key'] === 'base_doble'
     *  - OR row['label'] contains 'base' (case-insensitive)
     *
     * If not found, fall back to META_PRICE_BASE_DOBLE, META_VALOR_AEREO,
     * or the minimum active option (excluding infante).
     */
    private static function get_price_from_meta($post_id) {
        $currency = 'USD';

        // Primero, intentar detectar la moneda desde las opciones de precio disponibles
        $opts = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
        if (is_array($opts)) {
            foreach ($opts as $row) {
                if (is_array($row) && !empty($row['currency']) && floatval($row['price'] ?? 0) > 0) {
                    $currency = $row['currency'];
                    break; // Usar la primera moneda que encuentre
                }
            }
        }

        // 1) Buscar "Base doble" en el repeater de opciones (por key o por label)
        if (is_array($opts)) {
            foreach ($opts as $row) {
                $row = wp_parse_args((array)$row, ['key' => '', 'label' => '', 'price' => 0, 'currency' => $currency]);
                $key = isset($row['key']) ? $row['key'] : '';
                $label = isset($row['label']) ? (string)$row['label'] : '';
                $price = isset($row['price']) ? floatval($row['price']) : 0;

                // Match por key OR por label que mencione "base" (p. ej. "Base doble")
                if ($price > 0 && ($key === 'base_doble' || stripos($label, 'base') !== false)) {
                    $currency = !empty($row['currency']) ? $row['currency'] : $currency;
                    return [$price, $currency];
                }
            }
        }

        // 2) Fallback: META_PRICE_BASE_DOBLE (si existe como meta separado).
        $base_doble = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_BASE_DOBLE, true);
        $base_doble = is_string($base_doble) ? str_replace([',', ' '], ['', ''], $base_doble) : $base_doble;
        $quick = floatval($base_doble);
        if ($quick > 0) {
            return [$quick, $currency];
        }

        // 3) Fallback: "Desde" del paquete.
        $desde = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_VALOR_AEREO, true);
        $desde = is_string($desde) ? str_replace([',', ' '], ['', ''], $desde) : $desde;
        $quick = floatval($desde);
        if ($quick > 0) {
            return [$quick, $currency];
        }

        // 4) Fallback final: mínimo de opciones activas (excluye "infante").
        $opts = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
        $min = null;

        if (is_array($opts)) {
            foreach ($opts as $row) {
                $active = !empty($row['active']);
                $price  = isset($row['price']) ? floatval($row['price']) : 0;
                $label  = isset($row['label']) ? sanitize_text_field($row['label']) : '';

                if (!$active || $price <= 0) {
                    continue;
                }
                // Evitar que "Infante" se use como precio principal.
                if ($label && (stripos($label, 'infant') !== false || stripos($label, 'infante') !== false)) {
                    continue;
                }

                if ($min === null || $price < $min) {
                    $min = $price;
                    $currency = !empty($row['currency']) ? $row['currency'] : $currency;
                }
            }
        }

        return [$min, $currency];
    }

    public static function shortcode_paquetes($atts = []) {
        // Merge URL parameters with shortcode attributes for filtering
        $url_filters = [
            'destino' => isset($_GET['destino']) ? sanitize_text_field($_GET['destino']) : '',
            'hotel' => isset($_GET['hotel']) ? sanitize_text_field($_GET['hotel']) : '',
            'regimen' => isset($_GET['regimen']) ? sanitize_text_field($_GET['regimen']) : '',
            'price_min' => isset($_GET['price_min']) ? sanitize_text_field($_GET['price_min']) : '',
            'price_max' => isset($_GET['price_max']) ? sanitize_text_field($_GET['price_max']) : '',
            'fecha_desde' => isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '',
            'fecha_hasta' => isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '',
            'noches_min' => isset($_GET['noches_min']) ? sanitize_text_field($_GET['noches_min']) : '',
            'noches_max' => isset($_GET['noches_max']) ? sanitize_text_field($_GET['noches_max']) : '',
        ];

        $atts = shortcode_atts([
            'type' => 'grid',          // grid | carousel (carousel later)
            'limit' => 9,
            'ids' => '',               // IDs/SKU separados por coma
            'destino' => '',           // taxonomy slug or comma-separated slugs
            'only_active' => 1,
            'only_future' => 0,
            // WP_Query expects order = ASC|DESC. Using an invalid value can trigger
            // "doing_it_wrong" notices (and break REST/JSON responses when WP_DEBUG_DISPLAY is on).
            'order' => 'DESC',
            'orderby' => 'date',
            'price_min' => '',         // minimum price filter
            'price_max' => '',         // maximum price filter
            'currency' => '',          // filter by currency
            'fecha_desde' => '',       // departure date from (YYYY-MM-DD)
            'fecha_hasta' => '',       // departure date to (YYYY-MM-DD)
            'noches_min' => '',        // minimum nights
            'noches_max' => '',        // maximum nights
            'hotel' => '',             // filter by hotel (contains)
            'regimen' => '',           // filter by regimen (contains)
            'layout' => 'grid',        // grid | list (legacy engine)
            'columns' => 0,            // 0 => auto responsive (public.css)
            'container' => 1,          // wrap in a centered container (recommended)
            'show_filters' => 0,       // show filter UI
            'filter_position' => 'top', // top | bottom | both
        ], $atts, 'ud_paquetes');

        // Hard-validate query ordering to prevent warnings/notices in REST requests.
        $atts['order'] = strtoupper((string) $atts['order']);
        if (!in_array($atts['order'], ['ASC', 'DESC'], true)) {
            $atts['order'] = 'DESC';
        }

        // Extend allowed orderby:
        // - desde => order by META_VALOR_AEREO numeric (the "Precio desde" field)
        // - fecha_salida => order by META_SALIDA date
        // - noches => order by META_NOCHES numeric
        $allowed_orderby = ['date', 'title', 'modified', 'rand', 'menu_order', 'desde', 'fecha_salida', 'noches'];
        $atts['orderby'] = (string) $atts['orderby'];
        if (!in_array($atts['orderby'], $allowed_orderby, true)) {
            $atts['orderby'] = 'date';
        }

        // Override shortcode attributes with URL parameters if they exist
        foreach ($url_filters as $key => $value) {
            if (!empty($value)) {
                $atts[$key] = $value;
            }
        }

        wp_enqueue_style('udpq-public');
        wp_enqueue_script('udpq-public');

        if ($atts['type'] === 'carousel') {
            wp_enqueue_style('udpq-swiper');
            wp_enqueue_script('udpq-swiper');
            wp_enqueue_script('udpq-carousel');
        }

        // "Destino" is managed as a taxonomy (ud_destino). Fallback to meta only if taxonomy is missing.
        $tax_query = [];

        $meta_query = [];
        $post__in = [];

        // Si se especifican IDs (SKU), buscar esos posts primero
        if (!empty($atts['ids'])) {
            $skus = array_filter(array_map('trim', explode(',', (string) $atts['ids'])));
            
            if (!empty($skus)) {
                // Crear meta_query con OR para buscar los SKUs
                $sku_meta_queries = [];
                foreach ($skus as $sku) {
                    $sku_meta_queries[] = [
                        'key' => UDPAQUETES_Metaboxes::META_SKU,
                        'value' => $sku,
                        'compare' => '='
                    ];
                }
                
                // Usar array_merge en lugar del spread operator para mejor compatibilidad
                $meta_query_sku = ['relation' => 'OR'];
                $meta_query_sku = array_merge($meta_query_sku, $sku_meta_queries);
                
                // Usar get_posts para encontrar los post IDs
                $sku_posts = get_posts([
                    'post_type' => UDPAQUETES_CPT::POST_TYPE,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => $meta_query_sku
                ]);
                
                if (!empty($sku_posts)) {
                    $post__in = array_map('intval', $sku_posts);
                } else {
                    return '<div class="udpq-empty">No hay paquetes para mostrar.</div>';
                }
            }
        }

        // Si se especifican IDs, no aplicar filtro de activo (permite mostrar paquetes específicos aunque no estén activos)
        if (!empty($atts['only_active']) && empty($atts['ids'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_ACTIVE,
                'value' => '1',
                'compare' => '=',
            ];
        }

        // Text filters (contains)
        // Destino puede ser una lista separada por comas: "mexico,punta-cana,cancun"
        if (!empty($atts['destino'])) {
            $destinos = array_filter(array_map('trim', explode(',', (string) $atts['destino'])));
            $destinos = array_map('sanitize_title', $destinos);

            if (!empty($destinos) && taxonomy_exists(UDPAQUETES_CPT::TAX_DESTINO)) {
                $tax_query[] = [
                    'taxonomy' => UDPAQUETES_CPT::TAX_DESTINO,
                    'field' => 'slug',
                    'terms' => $destinos,
                    'operator' => 'IN',
                ];
            } elseif (!empty($destinos)) {
                // Fallback: meta query when taxonomy isn't available.
                if (count($destinos) > 1) {
                    $destino_queries = [];
                    foreach ($destinos as $dest) {
                        $destino_queries[] = [
                            'key' => UDPAQUETES_Metaboxes::META_DESTINO,
                            'value' => $dest,
                            'compare' => 'LIKE',
                        ];
                    }
                    $destino_query = ['relation' => 'OR'];
                    $destino_query = array_merge($destino_query, $destino_queries);
                    $meta_query[] = $destino_query;
                } else {
                    $meta_query[] = [
                        'key' => UDPAQUETES_Metaboxes::META_DESTINO,
                        'value' => $destinos[0],
                        'compare' => 'LIKE',
                    ];
                }
            }
        }
        if (!empty($atts['hotel'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_HOTEL,
                'value' => sanitize_text_field($atts['hotel']),
                'compare' => 'LIKE',
            ];
        }
        if (!empty($atts['regimen'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_REGIMEN,
                'value' => sanitize_text_field($atts['regimen']),
                'compare' => 'LIKE',
            ];
        }

        // Price filters
        if (!empty($atts['price_min']) || !empty($atts['price_max'])) {
            $price_query = ['relation' => 'AND'];

            if (!empty($atts['price_min'])) {
                $price_query[] = [
                    // Filter by "Desde" value (valor_aereo) in this plugin branch.
                    'key' => UDPAQUETES_Metaboxes::META_VALOR_AEREO,
                    'value' => floatval($atts['price_min']),
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ];
            }

            if (!empty($atts['price_max'])) {
                $price_query[] = [
                    'key' => UDPAQUETES_Metaboxes::META_VALOR_AEREO,
                    'value' => floatval($atts['price_max']),
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ];
            }

            $meta_query[] = $price_query;
        }

        // Currency filter
        if (!empty($atts['currency'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_PRICE_OPTIONS,
                'value' => sanitize_text_field($atts['currency']),
                'compare' => 'LIKE',
            ];
        }

        // Date filters
        if (!empty($atts['fecha_desde'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_SALIDA,
                'value' => sanitize_text_field($atts['fecha_desde']),
                'compare' => '>=',
                'type' => 'DATE',
            ];
        }

        if (!empty($atts['fecha_hasta'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_SALIDA,
                'value' => sanitize_text_field($atts['fecha_hasta']),
                'compare' => '<=',
                'type' => 'DATE',
            ];
        }

        // Only future packages: SALIDA date >= today (site timezone).
        if (!empty($atts['only_future'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_SALIDA,
                'value' => current_time('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE',
            ];
        }

        // Nights filters
        if (!empty($atts['noches_min'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_NOCHES,
                'value' => intval($atts['noches_min']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }

        if (!empty($atts['noches_max'])) {
            $meta_query[] = [
                'key' => UDPAQUETES_Metaboxes::META_NOCHES,
                'value' => intval($atts['noches_max']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        // Build orderby for custom fields
        $orderby = $atts['orderby'];
        $q_args = [
            'post_type' => UDPAQUETES_CPT::POST_TYPE,
            'posts_per_page' => intval($atts['limit']),
            'order' => $atts['order'],
            'tax_query' => $tax_query,
            'meta_query' => $meta_query,
        ];

        // Agregar post__in si se especificaron IDs
        if (!empty($post__in)) {
            $q_args['post__in'] = $post__in;
            if ($atts['orderby'] === 'date') {
                $q_args['orderby'] = 'post__in';
            }
        }

        if ($orderby === 'desde') {
            $q_args['meta_key'] = UDPAQUETES_Metaboxes::META_VALOR_AEREO;
            $q_args['orderby'] = 'meta_value_num';
        } elseif ($orderby === 'fecha_salida') {
            $q_args['meta_key'] = UDPAQUETES_Metaboxes::META_SALIDA;
            $q_args['orderby'] = 'meta_value';
        } elseif ($orderby === 'noches') {
            $q_args['meta_key'] = UDPAQUETES_Metaboxes::META_NOCHES;
            $q_args['orderby'] = 'meta_value_num';
        } else {
            $q_args['orderby'] = $orderby;
        }

        $q = new WP_Query($q_args);

        if (!$q->have_posts()) {
            return '<div class="udpq-empty">No hay paquetes para mostrar.</div>';
        }

        ob_start();

        // Optional centered container (keeps the layout aligned with the single template).
        $wrap_open = !empty($atts['container']) ? '<div class="udpq-block">' : '';
        $wrap_close = !empty($atts['container']) ? '</div>' : '';

        echo $wrap_open;

        // Render filters if enabled
        $filter_output = '';
        if (!empty($atts['show_filters'])) {
            $filter_output = self::render_filters_ui($atts);
        }
        ?>

        <?php if (!empty($atts['show_filters']) && in_array($atts['filter_position'], ['top', 'both'])): ?>
            <?php echo $filter_output; ?>
        <?php endif; ?>

        <?php
        // Layout classes
        $wrap_classes = ['udpq-wrap'];
        if ($atts['type'] === 'carousel') {
            $wrap_classes[] = 'udpq-wrap--carousel';
        } else {
            $wrap_classes[] = ($atts['layout'] === 'list') ? 'udpq-wrap--list' : 'udpq-wrap--grid';
            if (!empty($atts['columns'])) {
                $wrap_classes[] = 'udpq-wrap--cols-' . max(1, min(6, intval($atts['columns'])));
            }
        }
        $wrap_classes_str = implode(' ', array_map('sanitize_html_class', $wrap_classes));
        ?>

        <?php if ($atts['type'] === 'carousel'): ?>
            <div class="<?php echo esc_attr($wrap_classes_str); ?>" data-udpq-filters="<?php echo esc_attr(wp_json_encode($atts)); ?>">
                <div class="udpq-carousel swiper" data-udpq-carousel>
                    <div class="swiper-wrapper">
        <?php else: ?>
            <div class="<?php echo esc_attr($wrap_classes_str); ?>" data-udpq-filters="<?php echo esc_attr(wp_json_encode($atts)); ?>">
        <?php endif; ?>

            <?php while ($q->have_posts()): $q->the_post(); ?>
                <?php
                $id = get_the_ID();
                [$min_price, $currency] = self::get_price_from_meta($id);
                $destino = get_post_meta($id, UDPAQUETES_Metaboxes::META_DESTINO, true);
                $noches = get_post_meta($id, UDPAQUETES_Metaboxes::META_NOCHES, true);
                $thumb_url = has_post_thumbnail()
                    ? get_the_post_thumbnail_url($id, 'large')
                    : trim((string) get_post_meta($id, '_udpq_external_image_url', true));

                // Compatibilidad / hardening:
                // En builds anteriores existían metadatos como "servicios"/"beneficios".
                // En esta rama no están definidos como constantes, así que evitamos
                // referenciarlos directamente para no romper (Fatal: undefined constant).
                $meta_servicios_key = defined('UDPAQUETES_Metaboxes::META_SERVICIOS') ? UDPAQUETES_Metaboxes::META_SERVICIOS : '';
                $meta_beneficios_key = defined('UDPAQUETES_Metaboxes::META_BENEFICIOS') ? UDPAQUETES_Metaboxes::META_BENEFICIOS : '';
                ?>
                <?php if ($atts['type'] === 'carousel'): ?><div class="swiper-slide udpq-slide"><?php endif; ?>
                <article class="udpq-card" data-udpq="<?php
                    $payload = [
                        'title' => get_the_title(),
                        'permalink' => get_permalink(),
                        'thumb' => $thumb_url ?: '',
                        'destino' => $destino ?: '',
                        'fecha' => trim((string)get_post_meta($id, UDPAQUETES_Metaboxes::META_SALIDA, true)),
                        'regreso' => trim((string)get_post_meta($id, UDPAQUETES_Metaboxes::META_REGRESO, true)),
                        'noches' => (string)$noches,
                        'compania' => (string)get_post_meta($id, UDPAQUETES_Metaboxes::META_COMPANIA, true),
                        'salida_vuelo' => (string)get_post_meta($id, UDPAQUETES_Metaboxes::META_SALIDA_VUELO, true),
                        'equipaje' => (string)get_post_meta($id, UDPAQUETES_Metaboxes::META_EQUIPAJE, true),
                        'hotel' => (string)get_post_meta($id, UDPAQUETES_Metaboxes::META_HOTEL, true),
                        'regimen' => (string)get_post_meta($id, UDPAQUETES_Metaboxes::META_REGIMEN, true),
                        'seguro_traslados' => (string)get_post_meta($id, UDPAQUETES_Metaboxes::META_SEGURO_TRASLADOS, true),
                        // Estos campos quedan como opcionales (compatibilidad con versiones previas)
                        'servicios' => $meta_servicios_key ? (string)get_post_meta($id, $meta_servicios_key, true) : '',
                        'beneficios' => $meta_beneficios_key ? (string)get_post_meta($id, $meta_beneficios_key, true) : '',
                        'price_from' => $min_price ? (float)$min_price : 0,
                        'currency' => $currency ?: 'USD',
                        'options' => [],
                    ];
                    $opts = get_post_meta($id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
                    if (is_array($opts)) {
                        foreach ($opts as $row) {
                            $row = wp_parse_args($row, ['active'=>0,'label'=>'','price'=>0,'currency'=>'USD','mp_url'=>'','note'=>'']);
                            if (floatval($row['price']) <= 0) continue;
                            $payload['options'][] = [
                                'active' => !empty($row['active']) ? 1 : 0,
                                'label' => (string)$row['label'],
                                'price' => (float)$row['price'],
                                'currency' => (string)($row['currency'] ?: 'USD'),
                                'note' => (string)$row['note'],
                            ];
                        }
                    }
                    echo esc_attr(wp_json_encode($payload));
                ?>">
                    <a class="udpq-card__img" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('large'); ?>
                        <?php elseif (!empty($thumb_url)): ?>
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php else: ?>
                            <div class="udpq-card__ph">Paquete</div>
                        <?php endif; ?>
                    </a>

                    <div class="udpq-card__body">
                        <h3 class="udpq-card__title">
                            <a href="<?php the_permalink(); ?>">
                                <?php
                                $card_title_parts = [];
                                if (!empty($destino)) {
                                    $card_title_parts[] = esc_html($destino);
                                }
                                $fecha = trim((string)get_post_meta($id, UDPAQUETES_Metaboxes::META_SALIDA, true));
                                if (!empty($fecha)) {
                                    $card_title_parts[] = date_i18n('d/m/Y', strtotime($fecha));
                                }
                                echo implode(' – ', $card_title_parts) ?: esc_html(get_the_title());
                                ?>
                            </a>
                        </h3>

                        <div class="udpq-card__meta">
                            <?php if (!empty($destino)): ?>
                                <span class="udpq-badge udpq-badge--destino">
                                    <?php echo self::icon_svg('pin'); ?>
                                    <?php echo esc_html($destino); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($noches)): ?>
                                <span class="udpq-badge udpq-badge--noches">
                                    <?php echo self::icon_svg('moon'); ?>
                                    <?php echo esc_html($noches); ?> noches
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($min_price)): ?>
                            <div class="udpq-card__price"><span>Desde</span> <strong><?php echo esc_html(udpq_format_money_locale($min_price, $currency)); ?></strong><small>Precio total</small></div>
                        <?php endif; ?>

                        <div class="udpq-card__actions">
                            <a class="udpq-btn udpq-btn--primary" href="<?php the_permalink(); ?>">Ver</a>
                        </div>
                    </div>
                </article>
                <?php if ($atts['type'] === 'carousel'): ?></div><?php endif; ?>
            <?php endwhile; wp_reset_postdata(); ?>

        <?php if ($atts['type'] === 'carousel'): ?>
                    </div>
                    <div class="udpq-swiper-pagination"></div>
                </div>
                <div class="udpq-swiper-nav">
                    <div class="udpq-swiper-prev" aria-label="Anterior"></div>
                    <div class="udpq-swiper-next" aria-label="Siguiente"></div>
                </div>
            </div>
        <?php else: ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($atts['show_filters']) && in_array($atts['filter_position'], ['bottom', 'both'])): ?>
            <?php echo $filter_output; ?>
        <?php endif; ?>

        <?php echo $wrap_close; ?>

        <div class="udpq-modal" id="udpq-modal" aria-hidden="true">
            <div class="udpq-modal__overlay" data-udpq-close></div>
            <div class="udpq-modal__panel" role="dialog" aria-modal="true" aria-label="Detalle del paquete">
                <button class="udpq-modal__close" type="button" data-udpq-close aria-label="Cerrar">×</button>

                <div class="udpq-modal__media">
                    <img class="udpq-modal__img" alt="" />
                    <div class="udpq-modal__hero">
                        <div class="udpq-modal__title"></div>
                        <div class="udpq-modal__subtitle"></div>
                        <div class="udpq-modal__from"></div>
                    </div>
                </div>

                <div class="udpq-modal__content">
                    <div class="udpq-section">
                        <h4>Servicios incluidos en el viaje</h4>
                        <ul class="udpq-list" data-udpq-servicios></ul>
                    </div>

                    <div class="udpq-section">
                        <h4>Alojamiento</h4>
                        <div class="udpq-text" data-udpq-alojamiento></div>
                    </div>

                    <div class="udpq-section">
                        <h4>Beneficios adicionales</h4>
                        <ul class="udpq-list" data-udpq-beneficios></ul>
                    </div>

                    <div class="udpq-section">
                        <h4>Precio final</h4>
                        <div class="udpq-pricebox">
                            <label class="udpq-selectlabel">Elegí opción</label>
                            <select class="udpq-select" data-udpq-select></select>
                            <div class="udpq-pricebox__rows">
                                <div><span>Total para dos pasajeros:</span> <strong data-udpq-total></strong></div>
                                <div><span>Precio por persona:</span> <strong data-udpq-pp></strong></div>
                                <div class="udpq-muted" data-udpq-note></div>
                            </div>
                        </div>
                    </div>

                    <div class="udpq-cta">
                        <a class="udpq-btn udpq-btn--big" data-udpq-reservar href="#">RESERVA AHORA!</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render filters UI
     */
    private static function render_filters_ui($atts) {
        // Get available destinations for filter
        $destinos = get_terms([
            'taxonomy' => UDPAQUETES_CPT::TAX_DESTINO,
            'hide_empty' => true,
        ]);

        ob_start();
        ?>
        <div class="udpq-filters">
            <div class="udpq-filters__header">
                <h3><?php _e('Filtrar Paquetes', 'ud-paquetes'); ?></h3>
                <button type="button" class="udpq-filters__toggle" aria-expanded="false">
                    <?php _e('Mostrar filtros', 'ud-paquetes'); ?>
                </button>
            </div>

            <div class="udpq-filters__content" style="display: none;">
                <form class="udpq-filters__form" method="get" action="">
                    <div class="udpq-filters__grid">
                        <?php if (!empty($destinos) && !is_wp_error($destinos)): ?>
                            <div class="udpq-filter-group">
                                <label><?php _e('Destino', 'ud-paquetes'); ?></label>
                                <select name="destino" class="udpq-filter-select">
                                    <option value=""><?php _e('Todos los destinos', 'ud-paquetes'); ?></option>
                                    <?php foreach ($destinos as $destino): ?>
                                        <option value="<?php echo esc_attr($destino->slug); ?>" <?php selected(isset($_GET['destino']) ? $_GET['destino'] : '', $destino->slug); ?>>
                                            <?php echo esc_html($destino->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="udpq-filter-group">
                            <label><?php _e('Precio mínimo', 'ud-paquetes'); ?></label>
                            <input type="number" name="price_min" class="udpq-filter-input"
                                   value="<?php echo esc_attr(isset($_GET['price_min']) ? $_GET['price_min'] : ''); ?>"
                                   placeholder="0" min="0" />
                        </div>

                        <div class="udpq-filter-group">
                            <label><?php _e('Precio máximo', 'ud-paquetes'); ?></label>
                            <input type="number" name="price_max" class="udpq-filter-input"
                                   value="<?php echo esc_attr(isset($_GET['price_max']) ? $_GET['price_max'] : ''); ?>"
                                   placeholder="Sin límite" min="0" />
                        </div>

                        <div class="udpq-filter-group">
                            <label><?php _e('Fecha de salida desde', 'ud-paquetes'); ?></label>
                            <input type="date" name="fecha_desde" class="udpq-filter-input"
                                   value="<?php echo esc_attr(isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : ''); ?>" />
                        </div>

                        <div class="udpq-filter-group">
                            <label><?php _e('Fecha de salida hasta', 'ud-paquetes'); ?></label>
                            <input type="date" name="fecha_hasta" class="udpq-filter-input"
                                   value="<?php echo esc_attr(isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : ''); ?>" />
                        </div>

                        <div class="udpq-filter-group">
                            <label><?php _e('Noches mínimas', 'ud-paquetes'); ?></label>
                            <input type="number" name="noches_min" class="udpq-filter-input"
                                   value="<?php echo esc_attr(isset($_GET['noches_min']) ? $_GET['noches_min'] : ''); ?>"
                                   placeholder="0" min="0" />
                        </div>
                    </div>

                    <div class="udpq-filters__actions">
                        <button type="submit" class="udpq-btn udpq-btn--primary">
                            <?php _e('Aplicar filtros', 'ud-paquetes'); ?>
                        </button>
                        <a href="<?php echo esc_url(remove_query_arg(['destino', 'price_min', 'price_max', 'fecha_desde', 'fecha_hasta', 'noches_min'])); ?>"
                           class="udpq-btn udpq-btn--ghost">
                            <?php _e('Limpiar filtros', 'ud-paquetes'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Inline icons for badges (avoid depending on external icon libraries).
     * They inherit currentColor.
     */
    private static function icon_svg(string $name): string {
        switch ($name) {
            case 'pin':
                return '<svg class="udpq-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22s7-5.1 7-12a7 7 0 1 0-14 0c0 6.9 7 12 7 12zm0-9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>';
            case 'moon':
                return '<svg class="udpq-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 14.5A8.5 8.5 0 0 1 9.5 3a7 7 0 1 0 11.5 11.5z"/></svg>';
            default:
                return '';
        }
    }


    /* ==========================================================
     * Reservas (shortcodes)
     * ========================================================== */

    /**
     * Encola assets del modal de reserva (CSS/JS) cuando se usa fuera del template single.
     */
    private static function enqueue_reserva_assets(): void {
        // Font Awesome (icons)
        if (!wp_style_is('udpq-fontawesome', 'registered')) {
            wp_register_style(
                'udpq-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                [],
                '6.5.1'
            );
        }
        wp_enqueue_style('udpq-fontawesome');

        // Single CSS/JS (modal + estilos base)
        wp_enqueue_style('udpq-single', UDPQ_URL . 'assets/single.css', [], UDPQ_VERSION);
        wp_enqueue_script('udpq-single', UDPQ_URL . 'assets/single.js', ['jquery'], UDPQ_VERSION, true);

        wp_localize_script('udpq-single', 'udpqSingle', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('udpq_reserva_nonce'),
        ]);
    }

    /**
     * Shortcode: [udpq_reserva_form]
     * - Si no se pasa paquete_id, intenta usar el post actual.
     */
    public static function shortcode_udpq_reserva_form($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts([
            'paquete_id' => 0,
        ], $atts, 'udpq_reserva_form');

        $paquete_id = intval($atts['paquete_id']);
        if (!$paquete_id) {
            $paquete_id = get_the_ID();
        }
        if (!$paquete_id || get_post_type($paquete_id) !== UDPAQUETES_CPT::POST_TYPE) {
            return '';
        }

        self::enqueue_reserva_assets();

        // Opciones de precio del repeater
        $opts = get_post_meta($paquete_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
        if (!is_array($opts)) $opts = [];

        ob_start();
        ?>
        <form class="udpq-reserva-form" data-paquete-id="<?php echo esc_attr($paquete_id); ?>">
            <input type="hidden" name="udpq_action" value="reserva" />
            <input type="hidden" name="paquete_id" value="<?php echo esc_attr($paquete_id); ?>" />

            <div class="udpq-reserva-form__section">
                <label class="udpq-reserva-form__label"><?php echo esc_html__('Seleccioná una opción', 'ud-paquetes'); ?></label>
                <div class="udpq-options-stack">
                    <?php
                    $has_any = false;
                    foreach ($opts as $i => $row) {
                        if (!is_array($row)) continue;
                        $active = !empty($row['active']);
                        $price  = isset($row['price']) ? floatval($row['price']) : 0;
                        $label  = isset($row['label']) ? sanitize_text_field($row['label']) : '';
                        if (!$label) $label = !empty($row['key']) ? sanitize_text_field($row['key']) : ('Opción ' . ($i+1));

                        if (!$active || $price <= 0) continue;
                        $has_any = true;
                        ?>
                        <label class="udpq-option-row" style="cursor:pointer;">
                            <span class="udpq-option-main">
                                <span class="udpq-option-label"><?php echo esc_html($label); ?></span>
                            </span>
                            <span class="udpq-option-actions">
                                <span class="udpq-option-price"><?php echo esc_html('$ ' . number_format($price, 0, ',', '.')); ?></span>
                                <input type="radio" name="price_idx" value="<?php echo esc_attr($i); ?>" style="margin-left:12px;" />
                            </span>
                        </label>
                        <?php
                    }
                    if (!$has_any) {
                        echo '<p>' . esc_html__('No hay opciones de precio activas para este paquete.', 'ud-paquetes') . '</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="udpq-reserva-form__section">
                <label class="udpq-reserva-form__label"><?php echo esc_html__('Tus datos', 'ud-paquetes'); ?></label>
                <div class="udpq-reserva-form__grid">
                    <div class="udpq-reserva-form__field">
                        <label><?php echo esc_html__('Nombre y apellido', 'ud-paquetes'); ?></label>
                        <input type="text" name="nombre" required />
                    </div>
                    <div class="udpq-reserva-form__field">
                        <label><?php echo esc_html__('Email', 'ud-paquetes'); ?></label>
                        <input type="email" name="email" required />
                    </div>
                    <div class="udpq-reserva-form__field">
                        <label><?php echo esc_html__('Teléfono', 'ud-paquetes'); ?></label>
                        <input type="text" name="telefono" required />
                    </div>
                    <div class="udpq-reserva-form__field">
                        <label><?php echo esc_html__('Documento (opcional)', 'ud-paquetes'); ?></label>
                        <input type="text" name="documento" />
                    </div>
                </div>
            </div>

            <div class="udpq-reserva-form__section">
                <label style="display:flex;align-items:center;gap:10px;">
                    <input type="checkbox" name="acepta" value="1" required />
                    <span><?php echo esc_html__('Acepto las políticas y condiciones.', 'ud-paquetes'); ?></span>
                </label>
            </div>

            <div class="udpq-reserva-form__section">
                <button type="submit" class="udpq-btn udpq-btn--reserve"><?php echo esc_html__('Enviar reserva', 'ud-paquetes'); ?></button>
                <div class="udpq-reserva-form__msg" style="margin-top:12px;"></div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [udpq_reserva_modal]
     * Renderiza el modal completo (panel) para que puedas insertarlo en cualquier página.
     */
    public static function shortcode_udpq_reserva_modal($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts([
            'paquete_id' => 0,
            'title' => '',
        ], $atts, 'udpq_reserva_modal');

        $paquete_id = intval($atts['paquete_id']);
        if (!$paquete_id) {
            $paquete_id = get_the_ID();
        }
        if (!$paquete_id || get_post_type($paquete_id) !== UDPAQUETES_CPT::POST_TYPE) {
            return '';
        }

        self::enqueue_reserva_assets();

        $title = $atts['title'] ? sanitize_text_field($atts['title']) : __('Reservar paquete', 'ud-paquetes');

        ob_start();
        ?>
        <div class="udpq-reserva-modal" id="udpq-reserva-modal" aria-hidden="true">
            <div class="udpq-reserva-modal__overlay" data-udpq-close></div>
            <div class="udpq-reserva-modal__panel udpq-reserva-modal__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($title); ?>">
                <button type="button" class="udpq-reserva-modal__close" data-udpq-close aria-label="<?php echo esc_attr__('Cerrar', 'ud-paquetes'); ?>">&times;</button>
                <div class="udpq-reserva-modal__header">
                    <h2><?php echo esc_html($title); ?></h2>
                    <p><?php echo esc_html(get_the_title($paquete_id)); ?></p>
                </div>
                <div class="udpq-reserva-modal__content">
                    <?php echo do_shortcode('[udpq_reserva_form paquete_id="' . intval($paquete_id) . '"]'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}
