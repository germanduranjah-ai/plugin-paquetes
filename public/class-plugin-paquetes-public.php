<?php
/**
 * Funcionalidad del área pública
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/public
 */

class Plugin_Paquetes_Public {

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
     * Registra los estilos del área pública
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, PLUGIN_PAQUETES_URL . 'assets/css/plugin-paquetes-public.css', array(), $this->version, 'all' );
    }

    /**
     * Registra los scripts del área pública
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, PLUGIN_PAQUETES_URL . 'assets/js/plugin-paquetes-public.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Shortcode para mostrar seguimiento de paquetes
     *
     * @since    1.0.0
     * @param    array    $atts
     * @return   string
     */
    public function shortcode_seguimiento( $atts ) {
        global $wpdb;

        $atts = shortcode_atts( array(
            'numero' => '',
        ), $atts, 'paquetes_seguimiento' );

        ob_start();

        if ( ! empty( $atts['numero'] ) ) {
            // Buscar paquete específico
            $tabla_paquetes = $wpdb->prefix . 'paquetes';
            $paquete = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $tabla_paquetes WHERE numero_seguimiento = %s",
                $atts['numero']
            ) );

            if ( $paquete ) {
                ?>
                <div class="paquete-seguimiento">
                    <div class="paquete-info">
                        <h3><?php _e( 'Información del Paquete', 'plugin-paquetes' ); ?></h3>
                        <p><strong><?php _e( 'Número de Seguimiento:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->numero_seguimiento ); ?></p>
                        <p><strong><?php _e( 'Estado:', 'plugin-paquetes' ); ?></strong> <span class="paquete-estado"><?php echo esc_html( $paquete->estado ); ?></span></p>
                        <?php if ( $paquete->descripcion ) : ?>
                            <p><strong><?php _e( 'Descripción:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->descripcion ); ?></p>
                        <?php endif; ?>
                        <?php if ( $paquete->destinatario ) : ?>
                            <p><strong><?php _e( 'Destinatario:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->destinatario ); ?></p>
                        <?php endif; ?>
                        <?php if ( $paquete->origen ) : ?>
                            <p><strong><?php _e( 'Origen:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->origen ); ?></p>
                        <?php endif; ?>
                        <?php if ( $paquete->destino ) : ?>
                            <p><strong><?php _e( 'Destino:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->destino ); ?></p>
                        <?php endif; ?>
                        <?php if ( $paquete->fecha_envio ) : ?>
                            <p><strong><?php _e( 'Fecha de Envío:', 'plugin-paquetes' ); ?></strong> <?php echo date_i18n( get_option( 'date_format' ), strtotime( $paquete->fecha_envio ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( $paquete->notas ) : ?>
                            <p><strong><?php _e( 'Notas:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->notas ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            } else {
                echo '<p class="paquete-no-encontrado">' . __( 'No se encontró el paquete con ese número de seguimiento.', 'plugin-paquetes' ) . '</p>';
            }
        } else {
            // Formulario de búsqueda
            ?>
            <div class="paquete-seguimiento-form">
                <form method="get" action="">
                    <label for="numero_seguimiento"><?php _e( 'Ingrese su número de seguimiento:', 'plugin-paquetes' ); ?></label>
                    <input type="text" id="numero_seguimiento" name="numero_seguimiento" placeholder="<?php _e( 'Ej: PKG123456', 'plugin-paquetes' ); ?>" required>
                    <button type="submit" class="button"><?php _e( 'Buscar', 'plugin-paquetes' ); ?></button>
                </form>
            </div>
            <?php

            // Mostrar resultado si se buscó
            if ( isset( $_GET['numero_seguimiento'] ) && ! empty( $_GET['numero_seguimiento'] ) ) {
                $numero = sanitize_text_field( $_GET['numero_seguimiento'] );
                $tabla_paquetes = $wpdb->prefix . 'paquetes';
                $paquete = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM $tabla_paquetes WHERE numero_seguimiento = %s",
                    $numero
                ) );

                if ( $paquete ) {
                    ?>
                    <div class="paquete-seguimiento">
                        <div class="paquete-info">
                            <h3><?php _e( 'Información del Paquete', 'plugin-paquetes' ); ?></h3>
                            <p><strong><?php _e( 'Número de Seguimiento:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->numero_seguimiento ); ?></p>
                            <p><strong><?php _e( 'Estado:', 'plugin-paquetes' ); ?></strong> <span class="paquete-estado"><?php echo esc_html( $paquete->estado ); ?></span></p>
                            <?php if ( $paquete->descripcion ) : ?>
                                <p><strong><?php _e( 'Descripción:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->descripcion ); ?></p>
                            <?php endif; ?>
                            <?php if ( $paquete->destinatario ) : ?>
                                <p><strong><?php _e( 'Destinatario:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->destinatario ); ?></p>
                            <?php endif; ?>
                            <?php if ( $paquete->origen ) : ?>
                                <p><strong><?php _e( 'Origen:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->origen ); ?></p>
                            <?php endif; ?>
                            <?php if ( $paquete->destino ) : ?>
                                <p><strong><?php _e( 'Destino:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->destino ); ?></p>
                            <?php endif; ?>
                            <?php if ( $paquete->fecha_envio ) : ?>
                                <p><strong><?php _e( 'Fecha de Envío:', 'plugin-paquetes' ); ?></strong> <?php echo date_i18n( get_option( 'date_format' ), strtotime( $paquete->fecha_envio ) ); ?></p>
                            <?php endif; ?>
                            <?php if ( $paquete->notas ) : ?>
                                <p><strong><?php _e( 'Notas:', 'plugin-paquetes' ); ?></strong> <?php echo esc_html( $paquete->notas ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                } else {
                    echo '<p class="paquete-no-encontrado">' . __( 'No se encontró el paquete con ese número de seguimiento.', 'plugin-paquetes' ) . '</p>';
                }
            }
        }

        return ob_get_clean();
    }
}
