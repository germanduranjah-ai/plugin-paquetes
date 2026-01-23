<?php
/**
 * Plugin Name: Plugin Paquetes
 * Plugin URI: https://github.com/germanduranjah-ai/plugin-paquetes
 * Description: Plugin para gestionar el seguimiento y control de paquetes en WordPress
 * Version: 1.0.0
 * Author: German Duranjah
 * Author URI: https://github.com/germanduranjah-ai
 * Text Domain: plugin-paquetes
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Versión actual del plugin
 */
define( 'PLUGIN_PAQUETES_VERSION', '1.0.0' );

/**
 * Ruta del plugin
 */
define( 'PLUGIN_PAQUETES_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL del plugin
 */
define( 'PLUGIN_PAQUETES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Código que se ejecuta durante la activación del plugin
 */
function activate_plugin_paquetes() {
    require_once PLUGIN_PAQUETES_PATH . 'includes/class-plugin-paquetes-activator.php';
    Plugin_Paquetes_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin
 */
function deactivate_plugin_paquetes() {
    require_once PLUGIN_PAQUETES_PATH . 'includes/class-plugin-paquetes-deactivator.php';
    Plugin_Paquetes_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_plugin_paquetes' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_paquetes' );

/**
 * La clase principal del plugin
 */
require PLUGIN_PAQUETES_PATH . 'includes/class-plugin-paquetes.php';

/**
 * Comienza la ejecución del plugin
 */
function run_plugin_paquetes() {
    $plugin = new Plugin_Paquetes();
    $plugin->run();
}
run_plugin_paquetes();
