<?php
if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_Reserva {

    // Reservation status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    // Meta keys for reservation data
    const META_RESERVA_PAQUETE_ID = '_udpq_reserva_paquete_id';
    const META_RESERVA_NOMBRE = '_udpq_reserva_nombre';
    const META_RESERVA_EMAIL = '_udpq_reserva_email';
    const META_RESERVA_TELEFONO = '_udpq_reserva_telefono';
    const META_RESERVA_DOCUMENTO = '_udpq_reserva_documento';
    const META_RESERVA_PRECIO_OPCION = '_udpq_reserva_precio_opcion';
    const META_RESERVA_MONEDA = '_udpq_reserva_moneda';
    const META_RESERVA_PRECIO_TOTAL = '_udpq_reserva_precio_total';
    const META_RESERVA_FECHA = '_udpq_reserva_fecha';
    const META_RESERVA_STATUS = '_udpq_reserva_status';
    const META_RESERVA_MP_URL = '_udpq_reserva_mp_url';

    public static function init() {
        // Reserva vía AJAX (front). Evitamos handlers legacy por POST/redirect para no romper REST/headers.
        add_action('wp_ajax_udpq_reserva', [__CLASS__, 'handle_ajax_reserva']);
        add_action('wp_ajax_nopriv_udpq_reserva', [__CLASS__, 'handle_ajax_reserva']);

        // Admin UX (Reservas)
        if (is_admin()) {
            add_filter('manage_edit-' . UDPAQUETES_CPT::POST_TYPE_RESERVA . '_columns', [__CLASS__, 'admin_columns']);
            add_action('manage_' . UDPAQUETES_CPT::POST_TYPE_RESERVA . '_posts_custom_column', [__CLASS__, 'admin_column_content'], 10, 2);
            add_filter('post_row_actions', [__CLASS__, 'admin_row_actions'], 10, 2);
            add_action('admin_post_udpq_reserva_set_status', [__CLASS__, 'handle_admin_set_status']);
            add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
            add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
            add_action('save_post_' . UDPAQUETES_CPT::POST_TYPE_RESERVA, [__CLASS__, 'save_reserva_metabox'], 10, 2);
        }
    }

    public static function enqueue_admin_assets($hook_suffix): void {
        if (!in_array($hook_suffix, ['edit.php', 'post.php', 'post-new.php'], true)) {
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

    /**
     * Handle AJAX reservation request
     */
    public static function handle_ajax_reserva() {
        check_ajax_referer('udpq_reserva_nonce', 'nonce');

        try {
            if (empty($_POST['udpq_action']) || $_POST['udpq_action'] !== 'reserva') {
                throw new Exception(__('Acción inválida.', 'ud-paquetes'));
            }

            $paquete_id = intval($_POST['paquete_id'] ?? 0);
            if (!$paquete_id || get_post_type($paquete_id) !== UDPAQUETES_CPT::POST_TYPE) {
                throw new Exception(__('Paquete inválido.', 'ud-paquetes'));
            }

            $active = get_post_meta($paquete_id, UDPAQUETES_Metaboxes::META_ACTIVE, true);
            if ($active !== '1') {
                throw new Exception(__('Este paquete no está disponible.', 'ud-paquetes'));
            }

            // Validate and sanitize form data
            $nombre = udpq_sanitize_text($_POST['nombre'] ?? '');
            udpq_validate_required($nombre, __('Nombre y apellido', 'ud-paquetes'));
            udpq_validate_min_length($nombre, 2, __('Nombre y apellido', 'ud-paquetes'));

            $email = udpq_sanitize_email($_POST['email'] ?? '');
            udpq_validate_required($email, __('Email', 'ud-paquetes'));
            if (!udpq_validate_email($email)) {
                throw new Exception(__('El email proporcionado no es válido.', 'ud-paquetes'));
            }

            $telefono = udpq_sanitize_phone($_POST['telefono'] ?? '');
            udpq_validate_required($telefono, __('Teléfono', 'ud-paquetes'));
            udpq_validate_min_length($telefono, 7, __('Teléfono', 'ud-paquetes'));

            $documento = udpq_sanitize_text($_POST['documento'] ?? '');
            $acepta = !empty($_POST['acepta']);

            if (!$acepta) {
                throw new Exception(__('Debe aceptar las políticas para continuar.', 'ud-paquetes'));
            }

            $idx = isset($_POST['price_idx']) ? intval($_POST['price_idx']) : -1;
            if ($idx < 0) {
                throw new Exception(__('Debe seleccionar una opción de precio.', 'ud-paquetes'));
            }

            $opts = get_post_meta($paquete_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
            if (!is_array($opts)) $opts = [];

            $selected = null;
            $currency = function_exists('udpq_get_base_currency') ? udpq_get_base_currency() : 'USD';
            $mp_link = get_post_meta($paquete_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, true);

            if (isset($opts[$idx])) {
                $row = is_array($opts[$idx]) ? $opts[$idx] : [];
                $active = !empty($row['active']);
                $price = isset($row['price']) ? floatval($row['price']) : 0;
                $label = $row['label'] ?? '';
                if (!$label) $label = !empty($row['key']) ? $row['key'] : ('Opción ' . ($idx+1));

                if ($active && $price > 0) {
                    $selected = [
                        'label' => $label,
                        'price' => $price,
                        'currency' => $currency,
                        'mp_url' => $mp_link,
                    ];
                }
            }

            if (!$selected) {
                throw new Exception(__('La opción elegida no es válida.', 'ud-paquetes'));
            }

            // Create reservation record
            $form_data = [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'documento' => $documento,
            ];

            $reserva_id = self::create_reserva($paquete_id, $form_data, $selected);

            // Responder JSON (evita "headers already sent" y permite redirigir desde JS)
            $redirect = !empty($selected['mp_url']) ? esc_url_raw($selected['mp_url']) : '';
            $payload = [
                'reserva_id' => intval($reserva_id),
                'redirect'   => $redirect,
                'message'    => $redirect
                    ? __('Solicitud enviada. Te redirigimos a Mercado Pago...', 'ud-paquetes')
                    : __('Solicitud enviada. En breve te contactaremos para finalizar la reserva.', 'ud-paquetes'),
            ];

            wp_send_json_success($payload);

        } catch (Exception $e) {
            // Log the error for debugging
            error_log('UDPQ Reserva Error: ' . $e->getMessage());

            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Crea un registro de reserva (CPT ud_reserva) y dispara emails.
     *
     * - Guarda toda la data del formulario en meta.
     * - Envía 2 emails: al cliente (confirmación de recepción) y al admin (aviso).
     *
     * Nota SMTP:
     * Este flujo usa wp_mail(). Si el sitio tiene configurado SMTP (p.ej. WP Mail SMTP
     * o el SMTP del hosting), se va a “colgar” de esa configuración automáticamente.
     */
    private static function create_reserva(int $paquete_id, array $form_data, array $selected): int {
        $paquete = get_post($paquete_id);
        $paquete_title = $paquete ? $paquete->post_title : ('Paquete #' . $paquete_id);

                $post_title = 'Reserva - ' . $paquete_title . ' - ' . (isset($form_data['nombre']) ? sanitize_text_field($form_data['nombre']) : '');

        $reserva_id = wp_insert_post([
            'post_type'   => UDPAQUETES_CPT::POST_TYPE_RESERVA,
            'post_status' => 'publish',
            'post_title'  => $post_title,
            'post_content'=> '',
        ], true);

        if (is_wp_error($reserva_id)) {
            throw new Exception($reserva_id->get_error_message());
        }

        $reserva_id = intval($reserva_id);

        // Meta
        update_post_meta($reserva_id, self::META_RESERVA_PAQUETE_ID, $paquete_id);
        update_post_meta($reserva_id, self::META_RESERVA_NOMBRE, sanitize_text_field($form_data['nombre'] ?? ''));
        update_post_meta($reserva_id, self::META_RESERVA_EMAIL, sanitize_email($form_data['email'] ?? ''));
        update_post_meta($reserva_id, self::META_RESERVA_TELEFONO, sanitize_text_field($form_data['telefono'] ?? ''));
        update_post_meta($reserva_id, self::META_RESERVA_DOCUMENTO, sanitize_text_field($form_data['documento'] ?? ''));
        update_post_meta($reserva_id, self::META_RESERVA_PRECIO_OPCION, sanitize_text_field($selected['label'] ?? ''));
        update_post_meta($reserva_id, self::META_RESERVA_MONEDA, sanitize_text_field($selected['currency'] ?? 'USD'));
        update_post_meta($reserva_id, self::META_RESERVA_PRECIO_TOTAL, floatval($selected['price'] ?? 0));
        update_post_meta($reserva_id, self::META_RESERVA_FECHA, current_time('mysql'));
        update_post_meta($reserva_id, self::META_RESERVA_STATUS, self::STATUS_PENDING);
        update_post_meta($reserva_id, self::META_RESERVA_MP_URL, esc_url_raw($selected['mp_url'] ?? ''));

        // Emails
        self::send_emails($reserva_id, $paquete_id, $form_data, $selected);

        return $reserva_id;
    }

    /**
     * Envía emails (cliente + admin).
     */
    private static function send_emails(int $reserva_id, int $paquete_id, array $form_data, array $selected): void {
        // Email de notificación (si está vacío usa admin_email). Puede ser una lista separada por comas.
        $admin_email = sanitize_text_field(get_option('udpq_reserva_notify_email', ''));
        if (!$admin_email) {
            $admin_email = get_option('admin_email');
        }

        $send_client = (string) get_option('udpq_reserva_email_client_enabled', '1') === '1';
        $send_admin  = (string) get_option('udpq_reserva_email_admin_enabled', '1') === '1';
        $to_client = sanitize_email($form_data['email'] ?? '');

        // Armar tabla HTML (planilla) + plantilla de email.
        $paquete_title = get_the_title($paquete_id);
        $paquete_link  = get_permalink($paquete_id);

        $rows = [
            ['Reserva ID', '#' . $reserva_id],
            ['Paquete', $paquete_title],
            ['Link', '<a href="' . esc_url($paquete_link) . '">' . esc_html($paquete_link) . '</a>'],
            ['Opción', esc_html($selected['label'] ?? '')],
            ['Precio', esc_html(($selected['currency'] ?? 'USD') . ' ' . number_format(floatval($selected['price'] ?? 0), 0, ',', '.'))],
            ['Nombre', esc_html($form_data['nombre'] ?? '')],
            ['Email', esc_html($to_client)],
            ['Teléfono', esc_html($form_data['telefono'] ?? '')],
        ];

        // Link legal (mostrar también en emails)
        $terms_url = apply_filters(
            'udpq_reserva_terms_url',
            'https://on-heaventravel.com/wp-content/uploads/2026/01/TERMINOS-Y-CONDICIONES.pdf',
            $reserva_id,
            $paquete_id
        );
        if (!empty($terms_url)) {
            $rows[] = [
                __('Términos y condiciones', 'ud-paquetes'),
                '<a href="' . esc_url($terms_url) . '">' . esc_html($terms_url) . '</a>',
            ];
        }

        if (!empty($form_data['documento'])) {
            $rows[] = ['Documento', esc_html($form_data['documento'])];
        }

        $table = '<table cellpadding="10" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:640px">';
        foreach ($rows as $r) {
            $table .= '<tr>'
                . '<td style="border:1px solid #e7e7e7;background:#f8fafc;width:200px"><strong>' . esc_html($r[0]) . '</strong></td>'
                . '<td style="border:1px solid #e7e7e7">' . $r[1] . '</td>'
                . '</tr>';
        }
        $table .= '</table>';

        // ---- Email headers ----
        // Ajuste solicitado: evitar que el remitente diga "WordPress".
        // Usamos el host del sitio como nombre y un no-reply@{host} como email.
        $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (!$host) {
            $host = 'on-heaventravel.com';
        }
        // Remitente ajustable desde Settings (Reservas)
        // - Si están vacíos, cae al dominio del sitio.
        $opt_from_name  = (string) get_option('udpq_reserva_from_name', '');
        $opt_from_email = (string) get_option('udpq_reserva_from_email', '');

        $default_from_name  = $host;
        $default_from_email = 'no-reply@' . $host;

        $from_name  = apply_filters('udpq_reserva_mail_from_name', $opt_from_name ?: $default_from_name, $reserva_id, $paquete_id);
        $from_email = apply_filters('udpq_reserva_mail_from_email', $opt_from_email ?: $default_from_email, $reserva_id, $paquete_id);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        // Plantilla base (logo + contenedor + footer). Se puede overridear con un filtro.
        $logo_url = apply_filters(
            'udpq_reserva_email_logo_url',
            'https://on-heaventravel.com/wp-content/uploads/2025/01/logo_horizontal.png',
            $reserva_id,
            $paquete_id
        );

        $wrap = function(string $title, string $intro_html, string $content_html) use ($logo_url): string {
            $brand  = '#008183';
            $accent = '#EE9C23';
            $safe_logo = $logo_url ? esc_url($logo_url) : '';

            $header_logo = $safe_logo
                ? '<div style="padding:18px 24px;border-bottom:1px solid #eef2f7;text-align:center">'
                    . '<img src="' . $safe_logo . '" alt="On Heaven Travel" style="max-width:220px;height:auto;display:inline-block">'
                  . '</div>'
                : '';

            return '<!doctype html><html><body style="margin:0;padding:0;background:#f4f7f7;font-family:Arial,Helvetica,sans-serif;color:#111827">'
                . '<div style="width:100%;padding:22px 12px">'
                . '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e6edf2;border-radius:16px;overflow:hidden">'
                . $header_logo
                . '<div style="padding:24px 24px 8px 24px">'
                . '<h1 style="margin:0 0 10px 0;font-size:22px;line-height:1.25;color:' . $brand . '">' . esc_html($title) . '</h1>'
                . '<div style="font-size:14px;line-height:1.6;color:#334155">' . $intro_html . '</div>'
                . '</div>'
                . '<div style="padding:0 24px 22px 24px">'
                . $content_html
                . '</div>'
                . '<div style="padding:16px 24px;background:#0b2e2c;color:#ffffff;font-size:12px;line-height:1.5">'
                . '<div style="opacity:.95">On Heaven Travel • <span style="color:' . $accent . '">Reservas</span></div>'
                . '<div style="opacity:.75;margin-top:6px">Este email fue generado automáticamente. Si necesitás ayuda, respondé este mensaje.</div>'
                . '</div>'
                . '</div>'
                . '</div>'
                . '</body></html>';
        };

        // Email al cliente
        if ($send_client && $to_client) {
            // Ajuste solicitado: asunto "Solicitud de reserva" (más profesional)
            $subject_client = sprintf(__('Solicitud de reserva recibida: %s', 'ud-paquetes'), $paquete_title);
            $intro = '<p style="margin:0 0 12px 0">¡Gracias! Recibimos tu solicitud de reserva. En breve nos comunicaremos para confirmarla.</p>'
                   . '<p style="margin:0 0 14px 0">A continuación, te dejamos el resumen:</p>';
            $body_client = $wrap(__('Reserva recibida', 'ud-paquetes'), $intro, $table);
            // Reply-To: al email del admin para que el cliente pueda responder.
            $headers_client = array_merge($headers, ['Reply-To: ' . sanitize_email(get_option('admin_email'))]);
            @wp_mail($to_client, $subject_client, $body_client, $headers_client);
        }

        // Email al admin
        if ($send_admin && $admin_email) {
            // Ajuste solicitado: asunto "Solicitud de reserva"
            $subject_admin = sprintf(__('Solicitud de reserva: %s', 'ud-paquetes'), $paquete_title);
            $intro_admin = '<p style="margin:0 0 12px 0">Se recibió una nueva reserva desde el sitio.</p>'
                        . '<p style="margin:0 0 14px 0">Detalle:</p>';
            $body_admin = $wrap(__('Nueva reserva', 'ud-paquetes'), $intro_admin, $table);

            $recipients = array_filter(array_map('trim', preg_split('/[;,]+/', (string) $admin_email)));
            if (empty($recipients)) {
                $recipients = [get_option('admin_email')];
            }
            foreach ($recipients as $to_admin) {
                if ($to_admin) {
                    // Reply-To: al email del cliente para que el admin pueda responderle directo.
                    $headers_admin = array_merge($headers, $to_client ? ['Reply-To: ' . $to_client] : []);
                    @wp_mail($to_admin, $subject_admin, $body_admin, $headers_admin);
                }
            }
        }
    }


    /* =====================================================
     * Admin UI (ud_reserva)
     * ===================================================== */

    public static function admin_columns(array $columns): array {
        // Orden de columnas
        $new = [];
        $new['cb'] = $columns['cb'] ?? '';
        $new['title'] = __('Reserva', 'ud-paquetes');
        $new['udpq_paquete'] = __('Paquete', 'ud-paquetes');
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
            case 'udpq_paquete':
                $pid = intval(get_post_meta($post_id, self::META_RESERVA_PAQUETE_ID, true));
                if ($pid) {
                    echo '<a href="' . esc_url(get_edit_post_link($pid)) . '">' . esc_html(get_the_title($pid)) . '</a>';
                } else {
                    echo '—';
                }
                break;
            case 'udpq_cliente':
                echo esc_html(get_post_meta($post_id, self::META_RESERVA_NOMBRE, true));
                break;
            case 'udpq_email':
                $email = sanitize_email(get_post_meta($post_id, self::META_RESERVA_EMAIL, true));
                echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
                break;
            case 'udpq_telefono':
                echo esc_html(get_post_meta($post_id, self::META_RESERVA_TELEFONO, true));
                break;
            case 'udpq_opcion':
                echo esc_html(get_post_meta($post_id, self::META_RESERVA_PRECIO_OPCION, true));
                break;
            case 'udpq_precio':
                $cur = get_post_meta($post_id, self::META_RESERVA_MONEDA, true);
                $tot = floatval(get_post_meta($post_id, self::META_RESERVA_PRECIO_TOTAL, true));
                if ($tot > 0) {
                    echo esc_html(($cur ? $cur : 'USD') . ' ' . number_format($tot, 0, ',', '.'));
                } else {
                    echo '—';
                }
                break;
            case 'udpq_status':
                $status = (string) get_post_meta($post_id, self::META_RESERVA_STATUS, true);
                echo esc_html(self::get_status_label($status));
                break;
        }
    }

    public static function admin_row_actions(array $actions, \WP_Post $post): array {
        if ($post->post_type !== UDPAQUETES_CPT::POST_TYPE_RESERVA) return $actions;
        if (!current_user_can('edit_post', $post->ID)) return $actions;

        $status = (string) get_post_meta($post->ID, self::META_RESERVA_STATUS, true);
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

        // Acciones rápidas (simples)
        $quick = [];
        if ($status !== self::STATUS_CONFIRMED) {
            $quick['udpq_confirm'] = $make(self::STATUS_CONFIRMED, __('Confirmar', 'ud-paquetes'));
        }
        if ($status !== self::STATUS_CANCELLED) {
            $quick['udpq_cancel']  = $make(self::STATUS_CANCELLED, __('Cancelar', 'ud-paquetes'));
        }

        // Insertarlas al principio
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

        $allowed = array_keys(self::get_status_options());
        if (!in_array($status, $allowed, true)) {
            wp_die(__('Estado inválido.', 'ud-paquetes'));
        }

        update_post_meta($reserva_id, self::META_RESERVA_STATUS, $status);

        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = admin_url('edit.php?post_type=' . UDPAQUETES_CPT::POST_TYPE_RESERVA);
        }
        wp_safe_redirect($redirect);
        exit;
    }

    public static function add_meta_boxes(): void {
        add_meta_box(
            'udpq_reserva_box',
            __('Datos de la reserva', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_metabox'],
            UDPAQUETES_CPT::POST_TYPE_RESERVA,
            'normal',
            'high'
        );
    }

    public static function render_reserva_metabox(\WP_Post $post): void {
        $paquete_id = intval(get_post_meta($post->ID, self::META_RESERVA_PAQUETE_ID, true));
        $nombre = get_post_meta($post->ID, self::META_RESERVA_NOMBRE, true);
        $email = get_post_meta($post->ID, self::META_RESERVA_EMAIL, true);
        $tel = get_post_meta($post->ID, self::META_RESERVA_TELEFONO, true);
        $doc = get_post_meta($post->ID, self::META_RESERVA_DOCUMENTO, true);
        $opcion = get_post_meta($post->ID, self::META_RESERVA_PRECIO_OPCION, true);
        $cur = get_post_meta($post->ID, self::META_RESERVA_MONEDA, true);
        $tot = floatval(get_post_meta($post->ID, self::META_RESERVA_PRECIO_TOTAL, true));
        $mp = get_post_meta($post->ID, self::META_RESERVA_MP_URL, true);
        $status = (string) get_post_meta($post->ID, self::META_RESERVA_STATUS, true);

        wp_nonce_field('udpq_reserva_metabox', 'udpq_reserva_metabox_nonce');

        echo '<style>.udpq-admin-table{width:100%;border-collapse:collapse}.udpq-admin-table td{padding:8px;border-bottom:1px solid #eee}.udpq-admin-table td:first-child{width:180px;color:#555}</style>';
        echo '<table class="udpq-admin-table">';
        echo '<tr><td><strong>' . esc_html__('Paquete', 'ud-paquetes') . '</strong></td><td>';
        if ($paquete_id) {
            echo '<a href="' . esc_url(get_edit_post_link($paquete_id)) . '">' . esc_html(get_the_title($paquete_id)) . '</a>';
        } else {
            echo '—';
        }
        echo '</td></tr>';

        echo '<tr><td><strong>' . esc_html__('Cliente', 'ud-paquetes') . '</strong></td><td>' . esc_html($nombre) . '</td></tr>';
        echo '<tr><td><strong>' . esc_html__('Email', 'ud-paquetes') . '</strong></td><td>' . ($email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—') . '</td></tr>';
        echo '<tr><td><strong>' . esc_html__('Teléfono', 'ud-paquetes') . '</strong></td><td>' . esc_html($tel) . '</td></tr>';
        if ($doc) {
            echo '<tr><td><strong>' . esc_html__('Documento', 'ud-paquetes') . '</strong></td><td>' . esc_html($doc) . '</td></tr>';
        }
        echo '<tr><td><strong>' . esc_html__('Opción', 'ud-paquetes') . '</strong></td><td>' . esc_html($opcion) . '</td></tr>';
        echo '<tr><td><strong>' . esc_html__('Precio', 'ud-paquetes') . '</strong></td><td>' . esc_html(($cur ? $cur : 'USD') . ' ' . number_format($tot, 0, ',', '.')) . '</td></tr>';

        if ($mp) {
            echo '<tr><td><strong>' . esc_html__('Mercado Pago', 'ud-paquetes') . '</strong></td><td><a href="' . esc_url($mp) . '" target="_blank" rel="noopener">' . esc_html($mp) . '</a></td></tr>';
        }

        echo '<tr><td><strong>' . esc_html__('Estado', 'ud-paquetes') . '</strong></td><td>';
        echo '<select name="udpq_reserva_status">';
        foreach (self::get_status_options() as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';
        echo '</table>';
    }

    public static function save_reserva_metabox(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['udpq_reserva_metabox_nonce']) || !wp_verify_nonce((string) $_POST['udpq_reserva_metabox_nonce'], 'udpq_reserva_metabox')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['udpq_reserva_status'])) {
            $status = sanitize_text_field((string) $_POST['udpq_reserva_status']);
            $allowed = array_keys(self::get_status_options());
            if (in_array($status, $allowed, true)) {
                update_post_meta($post_id, self::META_RESERVA_STATUS, $status);
            }
        }
    }

    private static function get_status_options(): array {
        return [
            self::STATUS_PENDING   => __('Pendiente', 'ud-paquetes'),
            self::STATUS_CONFIRMED => __('Confirmada', 'ud-paquetes'),
            self::STATUS_CANCELLED => __('Cancelada', 'ud-paquetes'),
            self::STATUS_COMPLETED => __('Completada', 'ud-paquetes'),
        ];
    }

    private static function get_status_label(string $status): string {
        $opts = self::get_status_options();
        return $opts[$status] ?? __('Pendiente', 'ud-paquetes');
    }

}
