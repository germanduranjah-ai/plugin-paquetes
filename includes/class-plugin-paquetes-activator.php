<?php
/**
 * Disparado durante la activación del plugin
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/includes
 */

class Plugin_Paquetes_Activator {

    /**
     * Activación del plugin
     *
     * Crea las tablas necesarias en la base de datos
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        
        $tabla_paquetes = $wpdb->prefix . 'paquetes';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $tabla_paquetes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            numero_seguimiento varchar(255) NOT NULL,
            descripcion text,
            destinatario varchar(255),
            origen varchar(255),
            destino varchar(255),
            estado varchar(100),
            fecha_envio datetime,
            fecha_entrega datetime,
            notas text,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY numero_seguimiento (numero_seguimiento)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        // Agregar versión del plugin
        add_option( 'plugin_paquetes_version', PLUGIN_PAQUETES_VERSION );
    }
}
