<?php
/**
 * Disparado durante la desactivación del plugin
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/includes
 */

class Plugin_Paquetes_Deactivator {

    /**
     * Desactivación del plugin
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Limpiar tareas programadas si las hay
        wp_clear_scheduled_hook( 'plugin_paquetes_cron_hook' );
    }
}
