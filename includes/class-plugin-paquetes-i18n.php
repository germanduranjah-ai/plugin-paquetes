<?php
/**
 * Define la funcionalidad de internacionalización
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/includes
 */

class Plugin_Paquetes_i18n {

    /**
     * Carga el dominio de texto del plugin para traducción
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'plugin-paquetes',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}
