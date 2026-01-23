<?php
/**
 * La clase principal del plugin
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/includes
 */

class Plugin_Paquetes {

    /**
     * El cargador que mantiene todos los hooks del plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      Plugin_Paquetes_Loader    $loader
     */
    protected $loader;

    /**
     * El identificador único de este plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name
     */
    protected $plugin_name;

    /**
     * La versión actual del plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version
     */
    protected $version;

    /**
     * Define la funcionalidad principal del plugin
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = PLUGIN_PAQUETES_VERSION;
        $this->plugin_name = 'plugin-paquetes';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carga las dependencias requeridas para este plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once PLUGIN_PAQUETES_PATH . 'includes/class-plugin-paquetes-loader.php';
        require_once PLUGIN_PAQUETES_PATH . 'includes/class-plugin-paquetes-i18n.php';
        require_once PLUGIN_PAQUETES_PATH . 'admin/class-plugin-paquetes-admin.php';
        require_once PLUGIN_PAQUETES_PATH . 'public/class-plugin-paquetes-public.php';

        $this->loader = new Plugin_Paquetes_Loader();
    }

    /**
     * Define el locale para internacionalización
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Plugin_Paquetes_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Registra todos los hooks relacionados con el área de administración
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Plugin_Paquetes_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
    }

    /**
     * Registra todos los hooks relacionados con el área pública
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Plugin_Paquetes_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_shortcode( 'paquetes_seguimiento', $plugin_public, 'shortcode_seguimiento' );
    }

    /**
     * Ejecuta el loader para ejecutar todos los hooks
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * El nombre del plugin
     *
     * @since     1.0.0
     * @return    string    El nombre del plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Referencia al loader
     *
     * @since     1.0.0
     * @return    Plugin_Paquetes_Loader    El loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Obtiene el número de versión del plugin
     *
     * @since     1.0.0
     * @return    string    El número de versión
     */
    public function get_version() {
        return $this->version;
    }
}
