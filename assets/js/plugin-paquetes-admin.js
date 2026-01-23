/**
 * JavaScript del área de administración
 * Plugin Paquetes
 */

(function( $ ) {
    'use strict';

    $(function() {
        // Confirmación al eliminar
        $('.button-link-delete').on('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres eliminar este paquete?')) {
                e.preventDefault();
                return false;
            }
        });

        // Validación del formulario
        $('form').on('submit', function(e) {
            var numeroSeguimiento = $('#numero_seguimiento').val();
            if (numeroSeguimiento && numeroSeguimiento.trim() === '') {
                alert('El número de seguimiento es obligatorio');
                e.preventDefault();
                return false;
            }
        });
    });

})( jQuery );
