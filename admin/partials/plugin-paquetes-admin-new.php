<?php
/**
 * Vista de administración - Agregar nuevo paquete
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/admin/partials
 */
?>

<div class="wrap">
    <h1><?php _e( 'Agregar Nuevo Paquete', 'plugin-paquetes' ); ?></h1>
    
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="numero_seguimiento"><?php _e( 'Número de Seguimiento', 'plugin-paquetes' ); ?> *</label>
                </th>
                <td>
                    <input type="text" id="numero_seguimiento" name="numero_seguimiento" class="regular-text" required>
                    <p class="description"><?php _e( 'Número único de seguimiento del paquete', 'plugin-paquetes' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="descripcion"><?php _e( 'Descripción', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <textarea id="descripcion" name="descripcion" rows="3" class="large-text"></textarea>
                    <p class="description"><?php _e( 'Descripción breve del contenido del paquete', 'plugin-paquetes' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="destinatario"><?php _e( 'Destinatario', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <input type="text" id="destinatario" name="destinatario" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="origen"><?php _e( 'Origen', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <input type="text" id="origen" name="origen" class="regular-text">
                    <p class="description"><?php _e( 'Ciudad o lugar de origen del envío', 'plugin-paquetes' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="destino"><?php _e( 'Destino', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <input type="text" id="destino" name="destino" class="regular-text">
                    <p class="description"><?php _e( 'Ciudad o lugar de destino del envío', 'plugin-paquetes' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="estado"><?php _e( 'Estado', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <select id="estado" name="estado">
                        <option value="Pendiente"><?php _e( 'Pendiente', 'plugin-paquetes' ); ?></option>
                        <option value="En tránsito"><?php _e( 'En tránsito', 'plugin-paquetes' ); ?></option>
                        <option value="En almacén"><?php _e( 'En almacén', 'plugin-paquetes' ); ?></option>
                        <option value="En reparto"><?php _e( 'En reparto', 'plugin-paquetes' ); ?></option>
                        <option value="Entregado"><?php _e( 'Entregado', 'plugin-paquetes' ); ?></option>
                        <option value="Devuelto"><?php _e( 'Devuelto', 'plugin-paquetes' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="fecha_envio"><?php _e( 'Fecha de Envío', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <input type="datetime-local" id="fecha_envio" name="fecha_envio">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="notas"><?php _e( 'Notas', 'plugin-paquetes' ); ?></label>
                </th>
                <td>
                    <textarea id="notas" name="notas" rows="4" class="large-text"></textarea>
                    <p class="description"><?php _e( 'Notas adicionales sobre el paquete', 'plugin-paquetes' ); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button( __( 'Guardar Paquete', 'plugin-paquetes' ), 'primary', 'plugin_paquetes_submit' ); ?>
    </form>
</div>
