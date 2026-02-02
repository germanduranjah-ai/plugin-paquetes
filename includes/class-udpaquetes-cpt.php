<?php
if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_CPT {

    const POST_TYPE = 'ud_paquete';
    const POST_TYPE_RESERVA = 'ud_reserva';
    const TAX_DESTINO = 'ud_destino';

    public static function register() {

        // Paquetes CPT
        $labels_paquetes = [
            'name'               => 'Paquetes',
            'singular_name'      => 'Paquete',
            'add_new'            => 'Añadir nuevo',
            'add_new_item'       => 'Añadir nuevo paquete',
            'edit_item'          => 'Editar paquete',
            'new_item'           => 'Nuevo paquete',
            'view_item'          => 'Ver paquete',
            'search_items'       => 'Buscar paquetes',
            'not_found'          => 'No se encontraron paquetes',
            'not_found_in_trash' => 'No hay paquetes en la papelera',
            'menu_name'          => 'Paquetes',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels_paquetes,
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'paquetes'],
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-palmtree',
        ]);

        // Reservas CPT
        $labels_reservas = [
            'name'               => 'Reservas',
            'singular_name'      => 'Reserva',
            'add_new'            => 'Añadir nueva',
            'add_new_item'       => 'Añadir nueva reserva',
            'edit_item'          => 'Editar reserva',
            'new_item'           => 'Nueva reserva',
            'view_item'          => 'Ver reserva',
            'search_items'       => 'Buscar reservas',
            'not_found'          => 'No se encontraron reservas',
            'not_found_in_trash' => 'No hay reservas en la papelera',
            'menu_name'          => 'Reservas',
        ];

        register_post_type(self::POST_TYPE_RESERVA, [
            'labels' => $labels_reservas,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::POST_TYPE,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap' => true,
        ]);

        register_taxonomy(self::TAX_DESTINO, [self::POST_TYPE], [
            'label' => 'Destinos',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'destino'],
        ]);
    }

    public static function register_admin_filters() {
        add_filter('manage_edit-' . self::POST_TYPE . '_columns', [__CLASS__, 'add_admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [__CLASS__, 'render_admin_columns'], 10, 2);
        add_action('restrict_manage_posts', [__CLASS__, 'render_admin_filters']);
        add_action('pre_get_posts', [__CLASS__, 'apply_admin_filters']);
    }

    public static function add_admin_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['udpq_sku'] = __('SKU', 'ud-paquetes');
                $new_columns['udpq_destino'] = __('Destino', 'ud-paquetes');
                $new_columns['udpq_activo'] = __('Activo', 'ud-paquetes');
            }
        }

        if (!isset($new_columns['udpq_sku'])) {
            $new_columns['udpq_sku'] = __('SKU', 'ud-paquetes');
        }
        if (!isset($new_columns['udpq_destino'])) {
            $new_columns['udpq_destino'] = __('Destino', 'ud-paquetes');
        }
        if (!isset($new_columns['udpq_activo'])) {
            $new_columns['udpq_activo'] = __('Activo', 'ud-paquetes');
        }

        return $new_columns;
    }

    public static function render_admin_columns($column, $post_id) {
        if ($column === 'udpq_sku') {
            $sku = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SKU, true);
            echo esc_html($sku ?: '—');
            return;
        }

        if ($column === 'udpq_destino') {
            $terms = get_the_terms($post_id, self::TAX_DESTINO);
            if (!empty($terms) && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', $names));
                return;
            }
            $destino = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_DESTINO, true);
            echo esc_html($destino ?: '—');
            return;
        }

        if ($column === 'udpq_activo') {
            $active = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, true);
            echo $active === '1' ? esc_html__('Sí', 'ud-paquetes') : esc_html__('No', 'ud-paquetes');
        }
    }

    public static function render_admin_filters() {
        global $typenow;
        if ($typenow !== self::POST_TYPE) {
            return;
        }

        wp_dropdown_categories([
            'show_option_all' => __('Todos los destinos', 'ud-paquetes'),
            'taxonomy' => self::TAX_DESTINO,
            'name' => 'udpq_destino',
            'orderby' => 'name',
            'selected' => isset($_GET['udpq_destino']) ? sanitize_text_field(wp_unslash($_GET['udpq_destino'])) : '',
            'hierarchical' => true,
            'show_count' => false,
            'hide_empty' => false,
            'value_field' => 'slug',
        ]);

        $active_value = isset($_GET['udpq_active']) ? sanitize_text_field(wp_unslash($_GET['udpq_active'])) : '';
        ?>
        <select name="udpq_active">
            <option value=""><?php esc_html_e('Activo (todos)', 'ud-paquetes'); ?></option>
            <option value="1" <?php selected($active_value, '1'); ?>><?php esc_html_e('Activo', 'ud-paquetes'); ?></option>
            <option value="0" <?php selected($active_value, '0'); ?>><?php esc_html_e('Inactivo', 'ud-paquetes'); ?></option>
        </select>
        <?php
    }

    public static function apply_admin_filters($query) {
        global $pagenow;
        if ($pagenow !== 'edit.php' || !is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== self::POST_TYPE) {
            return;
        }

        $tax_slug = isset($_GET['udpq_destino']) ? sanitize_text_field(wp_unslash($_GET['udpq_destino'])) : '';
        if ($tax_slug !== '') {
            $query->set('tax_query', [
                [
                    'taxonomy' => self::TAX_DESTINO,
                    'field' => 'slug',
                    'terms' => [$tax_slug],
                ],
            ]);
        }

        $active_value = isset($_GET['udpq_active']) ? sanitize_text_field(wp_unslash($_GET['udpq_active'])) : '';
        if ($active_value !== '') {
            $query->set('meta_query', [
                [
                    'key' => UDPAQUETES_Metaboxes::META_ACTIVE,
                    'value' => $active_value,
                    'compare' => '=',
                ],
            ]);
        }
    }
}
