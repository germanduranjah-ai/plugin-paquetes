<?php
/**
 * Funcionalidad del área de administración
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/admin
 */

class Plugin_Paquetes_Admin {

    /**
     * El ID de este plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name
     */
    private $plugin_name;

    /**
     * La versión de este plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version
     */
    private $version;

    /**
     * Inicializa la clase y define sus propiedades
     *
     * @since    1.0.0
     * @param    string    $plugin_name
     * @param    string    $version
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Registra los estilos del área de administración
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, PLUGIN_PAQUETES_URL . 'assets/css/plugin-paquetes-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Registra los scripts del área de administración
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, PLUGIN_PAQUETES_URL . 'assets/js/plugin-paquetes-admin.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Agrega el menú de administración del plugin
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __( 'Gestión de Paquetes', 'plugin-paquetes' ),
            __( 'Paquetes', 'plugin-paquetes' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-archive',
            26
        );

        add_submenu_page(
            $this->plugin_name,
            __( 'Todos los Paquetes', 'plugin-paquetes' ),
            __( 'Todos los Paquetes', 'plugin-paquetes' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_plugin_admin_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            __( 'Agregar Nuevo', 'plugin-paquetes' ),
            __( 'Agregar Nuevo', 'plugin-paquetes' ),
            'manage_options',
            $this->plugin_name . '-new',
            array( $this, 'display_plugin_admin_new_page' )
        );
    }

    /**
     * Muestra la página principal de administración
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
        global $wpdb;
        
        // Procesar eliminación
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            $id = intval( $_GET['id'] );
            $wpdb->delete( $wpdb->prefix . 'paquetes', array( 'id' => $id ) );
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Paquete eliminado correctamente.', 'plugin-paquetes' ) . '</p></div>';
        }

        // Obtener todos los paquetes
        $tabla_paquetes = $wpdb->prefix . 'paquetes';
        $paquetes = $wpdb->get_results( "SELECT * FROM $tabla_paquetes ORDER BY fecha_creacion DESC" );

        require_once PLUGIN_PAQUETES_PATH . 'admin/partials/plugin-paquetes-admin-display.php';
    }

    /**
     * Muestra la página para agregar nuevo paquete
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_new_page() {
        global $wpdb;

        // Procesar formulario
        if ( isset( $_POST['plugin_paquetes_submit'] ) ) {
            $numero_seguimiento = sanitize_text_field( $_POST['numero_seguimiento'] );
            $descripcion = sanitize_textarea_field( $_POST['descripcion'] );
            $destinatario = sanitize_text_field( $_POST['destinatario'] );
            $origen = sanitize_text_field( $_POST['origen'] );
            $destino = sanitize_text_field( $_POST['destino'] );
            $estado = sanitize_text_field( $_POST['estado'] );
            $fecha_envio = sanitize_text_field( $_POST['fecha_envio'] );
            $notas = sanitize_textarea_field( $_POST['notas'] );

            $wpdb->insert(
                $wpdb->prefix . 'paquetes',
                array(
                    'numero_seguimiento' => $numero_seguimiento,
                    'descripcion' => $descripcion,
                    'destinatario' => $destinatario,
                    'origen' => $origen,
                    'destino' => $destino,
                    'estado' => $estado,
                    'fecha_envio' => $fecha_envio,
                    'notas' => $notas
                )
            );

            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Paquete agregado correctamente.', 'plugin-paquetes' ) . '</p></div>';
        }

        require_once PLUGIN_PAQUETES_PATH . 'admin/partials/plugin-paquetes-admin-new.php';
    }
}
