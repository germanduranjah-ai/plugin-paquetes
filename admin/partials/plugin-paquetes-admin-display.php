<?php
/**
 * Vista de administración - Listado de paquetes
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/admin/partials
 */
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php if ( empty( $paquetes ) ) : ?>
        <p><?php _e( 'No hay paquetes registrados aún.', 'plugin-paquetes' ); ?></p>
        <p><a href="<?php echo admin_url( 'admin.php?page=' . $this->plugin_name . '-new' ); ?>" class="button button-primary"><?php _e( 'Agregar el primer paquete', 'plugin-paquetes' ); ?></a></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Número de Seguimiento', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Descripción', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Destinatario', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Origen', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Destino', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Estado', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Fecha de Envío', 'plugin-paquetes' ); ?></th>
                    <th><?php _e( 'Acciones', 'plugin-paquetes' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $paquetes as $paquete ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $paquete->numero_seguimiento ); ?></strong></td>
                        <td><?php echo esc_html( $paquete->descripcion ); ?></td>
                        <td><?php echo esc_html( $paquete->destinatario ); ?></td>
                        <td><?php echo esc_html( $paquete->origen ); ?></td>
                        <td><?php echo esc_html( $paquete->destino ); ?></td>
                        <td>
                            <?php
                            $estado_class = '';
                            switch ( $paquete->estado ) {
                                case 'En tránsito':
                                    $estado_class = 'status-transit';
                                    break;
                                case 'Entregado':
                                    $estado_class = 'status-delivered';
                                    break;
                                case 'Pendiente':
                                    $estado_class = 'status-pending';
                                    break;
                            }
                            ?>
                            <span class="paquete-estado <?php echo $estado_class; ?>"><?php echo esc_html( $paquete->estado ); ?></span>
                        </td>
                        <td><?php echo $paquete->fecha_envio ? date_i18n( get_option( 'date_format' ), strtotime( $paquete->fecha_envio ) ) : '-'; ?></td>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=' . $this->plugin_name . '&action=delete&id=' . $paquete->id ); ?>" 
                               class="button button-small button-link-delete" 
                               onclick="return confirm('<?php _e( '¿Estás seguro de que quieres eliminar este paquete?', 'plugin-paquetes' ); ?>');">
                                <?php _e( 'Eliminar', 'plugin-paquetes' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
