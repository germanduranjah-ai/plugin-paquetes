<?php
/**
 * Plugin Name: Heaven Travel – Paquetes
 * Description: Gestión de paquetes de viaje (CPT) con opciones de precio y visualización en cards. Incluye flujo básico para redirección a Mercado Pago por opción elegida.
 * Version: 0.1.5
 * Author: German Durán
 * Text Domain: ud-paquetes
 */

if ( ! defined('ABSPATH') ) exit;

define('UDPQ_VERSION', '0.1.5');
define('UDPQ_SLUG', 'ud-paquetes');
define('UDPQ_PATH', plugin_dir_path(__FILE__));
define('UDPQ_URL', plugin_dir_url(__FILE__));

require_once UDPQ_PATH . 'includes/helpers.php';
require_once UDPQ_PATH . 'includes/class-udpaquetes-plugin.php';

function udpq_run() {
    $plugin = new UDPAQUETES_Plugin();
    $plugin->init();
}
add_action('plugins_loaded', 'udpq_run');

