/**
 * JavaScript del área pública
 * Plugin Paquetes
 */

(function( $ ) {
    'use strict';

    $(function() {
        // Validación del formulario de búsqueda
        $('.paquete-seguimiento-form form').on('submit', function(e) {
            var numeroSeguimiento = $('#numero_seguimiento').val();
            if (!numeroSeguimiento || numeroSeguimiento.trim() === '') {
                alert('Por favor ingrese un número de seguimiento');
                e.preventDefault();
                return false;
            }
        });

        // Animación suave al mostrar resultados
        if ($('.paquete-seguimiento').length) {
            $('.paquete-seguimiento').hide().fadeIn(500);
        }
    });

})( jQuery );
