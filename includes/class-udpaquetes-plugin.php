<?php
if ( ! defined('ABSPATH') ) exit;

require_once UDPQ_PATH . 'includes/class-udpaquetes-cpt.php';
require_once UDPQ_PATH . 'includes/class-udpaquetes-metaboxes.php';
require_once UDPQ_PATH . 'includes/class-udpaquetes-shortcodes.php';
require_once UDPQ_PATH . 'includes/class-udpaquetes-reserva.php';
require_once UDPQ_PATH . 'includes/class-udpaquetes-template.php';
require_once UDPQ_PATH . 'includes/class-udpaquetes-importer.php';

final class UDPAQUETES_Plugin {

    public function init() {
        // CPT + taxonomies
        add_action('init', ['UDPAQUETES_CPT', 'register']);

        // Admin metaboxes
        if (is_admin()) {
            UDPAQUETES_Metaboxes::init();
            UDPAQUETES_CPT::register_admin_filters();
        }

        // Front shortcodes + assets
        UDPAQUETES_Shortcodes::init();

        // Single template
        UDPAQUETES_Template::init();

        // Reserva handler (POST)
        UDPAQUETES_Reserva::init();

        // Admin settings
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
            add_action('admin_init', [__CLASS__, 'register_settings']);

            // Importador masivo (CSV/XLSX)
            UDPAQUETES_Importer::init();
        }
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . UDPAQUETES_CPT::POST_TYPE,
            __('Configuración de Paquetes', 'ud-paquetes'),
            __('Configuración', 'ud-paquetes'),
            'manage_options',
            'udpq-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public static function register_settings() {
        register_setting('udpq_settings', 'udpq_base_currency', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'USD'
        ]);

        register_setting('udpq_settings', 'udpq_currency_rates', [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_currency_rates'],
            'default' => []
        ]);

        add_settings_section(
            'udpq_currency_section',
            __('Configuración de Monedas', 'ud-paquetes'),
            [__CLASS__, 'render_currency_section'],
            'udpq-settings'
        );

        add_settings_field(
            'udpq_base_currency',
            __('Moneda Base', 'ud-paquetes'),
            [__CLASS__, 'render_base_currency_field'],
            'udpq-settings',
            'udpq_currency_section'
        );

        add_settings_field(
            'udpq_currency_rates',
            __('Tasas de Cambio', 'ud-paquetes'),
            [__CLASS__, 'render_currency_rates_field'],
            'udpq-settings',
            'udpq_currency_section'
        );

        // Form integration settings
        add_settings_section(
            'udpq_form_section',
            __('Integración de Formularios', 'ud-paquetes'),
            [__CLASS__, 'render_form_section'],
            'udpq-settings'
        );

        register_setting('udpq_settings', 'udpq_cf7_form_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('udpq_settings', 'udpq_elementor_form_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        add_settings_field(
            'udpq_cf7_form_id',
            __('Contact Form 7 - ID del Formulario', 'ud-paquetes'),
            [__CLASS__, 'render_cf7_form_field'],
            'udpq-settings',
            'udpq_form_section'
        );

        add_settings_field(
            'udpq_elementor_form_id',
            __('Elementor - ID del Formulario', 'ud-paquetes'),
            [__CLASS__, 'render_elementor_form_field'],
            'udpq-settings',
            'udpq_form_section'
        );

        /* ==============================
         * Reservas (emails + estado)
         * ============================== */
        add_settings_section(
            'udpq_reserva_section',
            __('Reservas', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_section'],
            'udpq-settings'
        );

        register_setting('udpq_settings', 'udpq_reserva_notify_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('udpq_settings', 'udpq_reserva_email_client_enabled', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ]);

        register_setting('udpq_settings', 'udpq_reserva_email_admin_enabled', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ]);

        // Remitente de emails (evita que aparezca "WordPress" como remitente)
        register_setting('udpq_settings', 'udpq_reserva_from_name', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('udpq_settings', 'udpq_reserva_from_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => ''
        ]);

        add_settings_field(
            'udpq_reserva_notify_email',
            __('Email de notificación (admin)', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_notify_email_field'],
            'udpq-settings',
            'udpq_reserva_section'
        );

        add_settings_field(
            'udpq_reserva_email_client_enabled',
            __('Enviar email al cliente', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_email_client_field'],
            'udpq-settings',
            'udpq_reserva_section'
        );

        add_settings_field(
            'udpq_reserva_email_admin_enabled',
            __('Enviar email al admin', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_email_admin_field'],
            'udpq-settings',
            'udpq_reserva_section'
        );

        add_settings_field(
            'udpq_reserva_from_name',
            __('Remitente (Nombre)', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_from_name_field'],
            'udpq-settings',
            'udpq_reserva_section'
        );

        add_settings_field(
            'udpq_reserva_from_email',
            __('Remitente (Email)', 'ud-paquetes'),
            [__CLASS__, 'render_reserva_from_email_field'],
            'udpq-settings',
            'udpq_reserva_section'
        );
    }

    /**
     * Sanitize currency rates
     */
    public static function sanitize_currency_rates($rates) {
        if (!is_array($rates)) return [];

        $sanitized = [];
        foreach ($rates as $currency => $rate) {
            $currency = sanitize_text_field($currency);
            $rate = floatval($rate);
            if ($rate > 0) {
                $sanitized[$currency] = $rate;
            }
        }
        return $sanitized;
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Heaven Travel - Paquetes', 'ud-paquetes'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('udpq_settings');
                do_settings_sections('udpq-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render currency section description
     */
    public static function render_currency_section() {
        echo '<p>' . __('Configure las monedas y tasas de cambio para los precios de los paquetes.', 'ud-paquetes') . '</p>';
    }

    /**
     * Render base currency field
     */
    public static function render_base_currency_field() {
        $base_currency = get_option('udpq_base_currency', 'USD');
        $currencies = udpq_get_currencies();

        echo '<select name="udpq_base_currency" id="udpq_base_currency">';
        foreach ($currencies as $code => $info) {
            printf(
                '<option value="%s" %s>%s (%s)</option>',
                esc_attr($code),
                selected($base_currency, $code, false),
                esc_html($info['name']),
                esc_html($info['symbol'])
            );
        }
        echo '</select>';
        echo '<p class="description">' . __('Moneda principal para cálculos internos.', 'ud-paquetes') . '</p>';
    }

    /**
     * Render currency rates field
     */
    public static function render_currency_rates_field() {
        $rates = get_option('udpq_currency_rates', []);
        $currencies = udpq_get_currencies();
        $base_currency = get_option('udpq_base_currency', 'USD');

        echo '<table class="form-table">';
        echo '<thead><tr><th>' . __('Moneda', 'ud-paquetes') . '</th><th>' . __('Tasa (1 ' . $base_currency . ' = X unidades)', 'ud-paquetes') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($currencies as $code => $info) {
            if ($code === $base_currency) continue;

            $rate = isset($rates[$code]) ? $rates[$code] : '';
            printf(
                '<tr><td>%s (%s)</td><td><input type="number" name="udpq_currency_rates[%s]" value="%s" step="0.0001" min="0.0001" /></td></tr>',
                esc_html($info['name']),
                esc_html($info['symbol']),
                esc_attr($code),
                esc_attr($rate)
            );
        }

        echo '</tbody></table>';
        echo '<p class="description">' . __('Las tasas de cambio se usan para conversiones automáticas. Deje vacío para usar valores por defecto.', 'ud-paquetes') . '</p>';
    }

    /**
     * Render form section description
     */
    public static function render_form_section() {
        echo '<p>' . __('Configure la integración con Contact Form 7 o Elementor para usar formularios personalizados en el modal de reserva.', 'ud-paquetes') . '</p>';
    }

    /**
     * Render CF7 form field
     */
    public static function render_cf7_form_field() {
        $cf7_form_id = get_option('udpq_cf7_form_id', '');
        
        if (!function_exists('wpcf7_contact_form')) {
            echo '<p class="description" style="color: #d63638;">' . __('Contact Form 7 no está instalado o activo.', 'ud-paquetes') . '</p>';
            return;
        }

        echo '<input type="number" name="udpq_cf7_form_id" id="udpq_cf7_form_id" value="' . esc_attr($cf7_form_id) . '" min="1" />';
        echo '<p class="description">' . __('Ingrese el ID del formulario de Contact Form 7 que desea usar en el modal de reserva. Puede encontrar el ID en la lista de formularios de CF7.', 'ud-paquetes') . '</p>';
        
        // Show available forms
        $forms = get_posts(['post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1]);
        if (!empty($forms)) {
            echo '<p><strong>' . __('Formularios disponibles:', 'ud-paquetes') . '</strong></p>';
            echo '<ul style="list-style: disc; margin-left: 20px;">';
            foreach ($forms as $form) {
                echo '<li>ID: ' . $form->ID . ' - ' . esc_html($form->post_title) . '</li>';
            }
            echo '</ul>';
        }
    }

    /**
     * Render Elementor form field
     */
    public static function render_elementor_form_field() {
        $elementor_form_id = get_option('udpq_elementor_form_id', '');
        
        if (!class_exists('\Elementor\Plugin')) {
            echo '<p class="description" style="color: #d63638;">' . __('Elementor no está instalado o activo.', 'ud-paquetes') . '</p>';
            return;
        }

        echo '<input type="number" name="udpq_elementor_form_id" id="udpq_elementor_form_id" value="' . esc_attr($elementor_form_id) . '" min="1" />';
        echo '<p class="description">' . __('Ingrese el ID de la página/template de Elementor que contiene el formulario de reserva.', 'ud-paquetes') . '</p>';
    }

    /* =====================================================
     * Reservas: Settings fields
     * ===================================================== */

    public static function render_reserva_section() {
        echo '<p>' . __('Configura a quién se le envían los avisos de reserva y si se envían emails automáticos.', 'ud-paquetes') . '</p>';
        echo '<p class="description">' . __('Los emails se envían con wp_mail(). Si el sitio tiene un plugin SMTP (o el hosting lo provee), se usará automáticamente.', 'ud-paquetes') . '</p>';
    }

    public static function render_reserva_notify_email_field() {
        $val = get_option('udpq_reserva_notify_email', '');
        echo '<input type="text" name="udpq_reserva_notify_email" value="' . esc_attr($val) . '" class="regular-text" placeholder="reservas@tu-dominio.com" />';
        echo '<p class="description">' . __('Si se dejan vacío, se usa el email de administración de WordPress. Podés usar varios separados por coma o punto y coma.', 'ud-paquetes') . '</p>';
    }

    public static function render_reserva_email_client_field() {
        $val = (string) get_option('udpq_reserva_email_client_enabled', '1');
        echo '<input type="hidden" name="udpq_reserva_email_client_enabled" value="0" />';
        echo '<label><input type="checkbox" name="udpq_reserva_email_client_enabled" value="1" ' . checked($val, '1', false) . ' /> ' . __('Enviar email automático al cliente', 'ud-paquetes') . '</label>';
    }

    public static function render_reserva_email_admin_field() {
        $val = (string) get_option('udpq_reserva_email_admin_enabled', '1');
        echo '<input type="hidden" name="udpq_reserva_email_admin_enabled" value="0" />';
        echo '<label><input type="checkbox" name="udpq_reserva_email_admin_enabled" value="1" ' . checked($val, '1', false) . ' /> ' . __('Enviar email automático a la agencia/admin', 'ud-paquetes') . '</label>';
    }

    public static function render_reserva_from_name_field() {
        $val = (string) get_option('udpq_reserva_from_name', '');
        echo '<input type="text" name="udpq_reserva_from_name" value="' . esc_attr($val) . '" class="regular-text" placeholder="On Heaven Travel" />';
        echo '<p class="description">' . __('Nombre que aparecerá como remitente (From). Si se deja vacío, se usa el dominio del sitio.', 'ud-paquetes') . '</p>';
    }

    public static function render_reserva_from_email_field() {
        $val = (string) get_option('udpq_reserva_from_email', '');
        echo '<input type="email" name="udpq_reserva_from_email" value="' . esc_attr($val) . '" class="regular-text" placeholder="no-reply@on-heaventravel.com" />';
        echo '<p class="description">' . __('Email que aparecerá como remitente (From). Debe ser un email válido del dominio para evitar SPAM. Si se deja vacío, se usa no-reply@tu-dominio.', 'ud-paquetes') . '</p>';
    }

}
