<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Admin list table UX for reservas (ud_reserva).
 *
 * Meta keys used (defined in UDPAQUETES_Reserva):
 * - Nombre: UDPAQUETES_Reserva::META_RESERVA_NOMBRE
 * - Email: UDPAQUETES_Reserva::META_RESERVA_EMAIL
 * - Teléfono: UDPAQUETES_Reserva::META_RESERVA_TELEFONO
 * - Opción: UDPAQUETES_Reserva::META_RESERVA_PRECIO_OPCION
 * - Precio: UDPAQUETES_Reserva::META_RESERVA_PRECIO_TOTAL
 * - Moneda: UDPAQUETES_Reserva::META_RESERVA_MONEDA
 * - Estado: UDPAQUETES_Reserva::META_RESERVA_STATUS
 * - Fecha (viaje/reserva): UDPAQUETES_Reserva::META_RESERVA_FECHA
 */
final class UDPAQUETES_Reserva_Admin {

    public static function init(): void {
        add_filter('manage_edit-' . UDPAQUETES_CPT::POST_TYPE_RESERVA . '_columns', [__CLASS__, 'admin_columns']);
        add_action('manage_' . UDPAQUETES_CPT::POST_TYPE_RESERVA . '_posts_custom_column', [__CLASS__, 'admin_column_content'], 10, 2);
        add_filter('manage_edit-' . UDPAQUETES_CPT::POST_TYPE_RESERVA . '_sortable_columns', [__CLASS__, 'sortable_columns']);
        add_action('pre_get_posts', [__CLASS__, 'handle_sorting']);
        add_filter('post_row_actions', [__CLASS__, 'admin_row_actions'], 10, 2);
        add_action('admin_post_udpq_reserva_set_status', [__CLASS__, 'handle_admin_set_status']);
        add_filter('the_title', [__CLASS__, 'filter_admin_title'], 10, 2);
        add_action('save_post_' . UDPAQUETES_CPT::POST_TYPE_RESERVA, [__CLASS__, 'sync_travel_date_meta'], 20, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }

    public static function enqueue_admin_assets($hook_suffix): void {
        if ($hook_suffix !== 'edit.php') {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== UDPAQUETES_CPT::POST_TYPE_RESERVA) {
            return;
        }

        wp_enqueue_style(
            'udpq-admin-reservas',
            UDPQ_URL . 'assets/admin-reservas.css',
            [],
            UDPQ_VERSION
        );
    }

    public static function admin_columns(array $columns): array {
        $new = [];
        $new['cb'] = $columns['cb'] ?? '';
        $new['title'] = __('Reserva', 'ud-paquetes');
        $new['udpq_viaje'] = __('Viaje', 'ud-paquetes');
        $new['udpq_cliente'] = __('Cliente', 'ud-paquetes');
        $new['udpq_email'] = __('Email', 'ud-paquetes');
        $new['udpq_telefono'] = __('Teléfono', 'ud-paquetes');
        $new['udpq_opcion'] = __('Opción', 'ud-paquetes');
        $new['udpq_precio'] = __('Precio', 'ud-paquetes');
        $new['udpq_status'] = __('Estado', 'ud-paquetes');
        $new['date'] = $columns['date'] ?? __('Fecha', 'ud-paquetes');
        return $new;
    }

    public static function admin_column_content(string $column, int $post_id): void {
        switch ($column) {
            case 'udpq_viaje':
                echo esc_html(self::format_travel_date($post_id));
                break;
            case 'udpq_cliente':
                echo esc_html(get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_NOMBRE, true));
                break;
            case 'udpq_email':
                $email = sanitize_email(get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_EMAIL, true));
                echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
                break;
            case 'udpq_telefono':
                echo esc_html(get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_TELEFONO, true));
                break;
            case 'udpq_opcion':
                $label = (string) get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_PRECIO_OPCION, true);
                echo $label ? self::render_badge($label, 'option') : '—';
                break;
            case 'udpq_precio':
                $cur = (string) get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_MONEDA, true);
                $tot = floatval(get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_PRECIO_TOTAL, true));
                if ($tot > 0) {
                    $currency = $cur ? $cur : 'USD';
                    echo '<span class="udpq-admin-price">';
                    echo self::render_badge($currency, 'currency');
                    echo esc_html(number_format($tot, 0, ',', '.'));
                    echo '</span>';
                } else {
                    echo '—';
                }
                break;
            case 'udpq_status':
                $status = (string) get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_STATUS, true);
                $label = UDPAQUETES_Reserva::get_status_label($status);
                echo self::render_badge($label, 'status-' . sanitize_key($status ?: UDPAQUETES_Reserva::STATUS_PENDING));
                break;
        }
    }

    public static function sortable_columns(array $columns): array {
        $columns['udpq_viaje'] = 'udpq_viaje';
        return $columns;
    }

    public static function handle_sorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== UDPAQUETES_CPT::POST_TYPE_RESERVA) {
            return;
        }

        $orderby = $query->get('orderby');
        if ($orderby !== 'udpq_viaje') {
            return;
        }

        $query->set('meta_key', UDPAQUETES_Reserva::META_RESERVA_FECHA_ORD);
        $query->set('orderby', 'meta_value_num');
    }

    public static function admin_row_actions(array $actions, \WP_Post $post): array {
        if ($post->post_type !== UDPAQUETES_CPT::POST_TYPE_RESERVA) {
            return $actions;
        }
        if (!current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $status = (string) get_post_meta($post->ID, UDPAQUETES_Reserva::META_RESERVA_STATUS, true);
        $base_url = admin_url('admin-post.php');

        $make = function(string $new_status, string $label) use ($base_url, $post) {
            $url = add_query_arg([
                'action' => 'udpq_reserva_set_status',
                'reserva_id' => $post->ID,
                'status' => $new_status,
                '_wpnonce' => wp_create_nonce('udpq_reserva_set_status_' . $post->ID),
            ], $base_url);
            return '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        };

        $quick = [];
        if ($status !== UDPAQUETES_Reserva::STATUS_CONFIRMED) {
            $quick['udpq_confirm'] = $make(UDPAQUETES_Reserva::STATUS_CONFIRMED, __('Confirmar', 'ud-paquetes'));
        }
        if ($status !== UDPAQUETES_Reserva::STATUS_CANCELLED) {
            $quick['udpq_cancel']  = $make(UDPAQUETES_Reserva::STATUS_CANCELLED, __('Cancelar', 'ud-paquetes'));
        }

        $phone = (string) get_post_meta($post->ID, UDPAQUETES_Reserva::META_RESERVA_TELEFONO, true);
        $wa = self::format_whatsapp_url($phone);
        if ($wa) {
            $quick['udpq_whatsapp'] = '<a href="' . esc_url($wa) . '" target="_blank" rel="noopener">' . esc_html__('WhatsApp', 'ud-paquetes') . '</a>';
        }

        return $quick + $actions;
    }

    public static function handle_admin_set_status(): void {
        $reserva_id = isset($_GET['reserva_id']) ? intval($_GET['reserva_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field((string) $_GET['status']) : '';

        if (!$reserva_id || get_post_type($reserva_id) !== UDPAQUETES_CPT::POST_TYPE_RESERVA) {
            wp_die(__('Reserva inválida.', 'ud-paquetes'));
        }
        if (!current_user_can('edit_post', $reserva_id)) {
            wp_die(__('No tenés permisos para editar esta reserva.', 'ud-paquetes'));
        }
        check_admin_referer('udpq_reserva_set_status_' . $reserva_id);

        $allowed = array_keys(UDPAQUETES_Reserva::get_status_options());
        if (!in_array($status, $allowed, true)) {
            wp_die(__('Estado inválido.', 'ud-paquetes'));
        }

        update_post_meta($reserva_id, UDPAQUETES_Reserva::META_RESERVA_STATUS, $status);

        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = admin_url('edit.php?post_type=' . UDPAQUETES_CPT::POST_TYPE_RESERVA);
        }
        wp_safe_redirect($redirect);
        exit;
    }

    public static function filter_admin_title(string $title, int $post_id): string {
        if (!is_admin()) {
            return $title;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->base !== 'edit' || $screen->post_type !== UDPAQUETES_CPT::POST_TYPE_RESERVA) {
            return $title;
        }

        if (get_post_type($post_id) !== UDPAQUETES_CPT::POST_TYPE_RESERVA) {
            return $title;
        }

        $name = (string) get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_NOMBRE, true);
        $suffix = $name ? $name : '#' . $post_id;
        return sprintf(__('Reserva %s', 'ud-paquetes'), $suffix);
    }

    public static function sync_travel_date_meta(int $post_id, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        UDPAQUETES_Reserva::sync_travel_date_order_meta($post_id);
    }

    private static function format_travel_date(int $post_id): string {
        $date = (string) get_post_meta($post_id, UDPAQUETES_Reserva::META_RESERVA_FECHA, true);
        if (!$date) {
            return '—';
        }

        $timestamp = strtotime($date);
        if (!$timestamp) {
            return '—';
        }

        return date_i18n(get_option('date_format'), $timestamp);
    }

    private static function format_whatsapp_url(string $phone): string {
        $digits = preg_replace('/\\D+/', '', $phone);
        if (!$digits) {
            return '';
        }

        return 'https://wa.me/' . $digits;
    }

    private static function render_badge(string $label, string $variant): string {
        $class = 'udpq-badge udpq-badge--' . sanitize_html_class($variant);
        return '<span class="' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    }
}
