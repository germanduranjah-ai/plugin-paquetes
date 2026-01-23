# Guía de Instalación Rápida - Plugin Paquetes

## Instalación en WordPress

### Método 1: Instalación Manual

1. **Descarga el plugin**
   - Descarga todos los archivos de este repositorio

2. **Sube el plugin a WordPress**
   - Conecta a tu servidor por FTP o usa el administrador de archivos
   - Navega a `wp-content/plugins/`
   - Crea una carpeta llamada `plugin-paquetes`
   - Sube todos los archivos dentro de esta carpeta

3. **Activa el plugin**
   - Ve al panel de WordPress
   - Menú "Plugins" → "Plugins instalados"
   - Busca "Plugin Paquetes"
   - Haz clic en "Activar"

4. **¡Listo!**
   - Verás un nuevo menú "Paquetes" en el panel lateral

### Método 2: Desde ZIP

1. **Crea un archivo ZIP**
   - Comprime toda la carpeta `plugin-paquetes` en un archivo .zip

2. **Instala desde WordPress**
   - Ve a "Plugins" → "Añadir nuevo"
   - Haz clic en "Subir plugin"
   - Selecciona el archivo ZIP
   - Haz clic en "Instalar ahora"
   - Activa el plugin

## Primeros Pasos

### 1. Agregar tu Primer Paquete

1. Ve a "Paquetes" → "Agregar Nuevo"
2. Completa el formulario:
   - **Número de Seguimiento**: PKG001 (o el formato que uses)
   - **Descripción**: Describe el contenido
   - **Destinatario**: Nombre del cliente
   - **Origen**: Ciudad de origen
   - **Destino**: Ciudad de destino
   - **Estado**: Selecciona el estado actual
   - **Fecha de Envío**: Selecciona fecha y hora
   - **Notas**: Información adicional (opcional)
3. Haz clic en "Guardar Paquete"

### 2. Ver Todos los Paquetes

- Ve a "Paquetes" → "Todos los Paquetes"
- Verás una tabla con todos los paquetes registrados
- Puedes eliminar paquetes desde aquí

### 3. Agregar el Seguimiento al Sitio

1. Crea o edita una página (ej: "Rastrear mi paquete")
2. Agrega el siguiente shortcode en el contenido:
   ```
   [paquetes_seguimiento]
   ```
3. Publica la página
4. Tus clientes podrán buscar sus paquetes ingresando el número de seguimiento

### 4. Mostrar un Paquete Específico

Si quieres mostrar información de un paquete específico directamente:

```
[paquetes_seguimiento numero="PKG001"]
```

## Ejemplo de Uso

### Escenario: Tienda en Línea

1. **Cliente hace un pedido**
   - Generas un número de seguimiento: PKG20260123001

2. **Registras el paquete**
   - "Paquetes" → "Agregar Nuevo"
   - Número: PKG20260123001
   - Descripción: "2x Camisetas + 1x Pantalón"
   - Destinatario: "Juan Pérez"
   - Origen: "Buenos Aires"
   - Destino: "Córdoba"
   - Estado: "Pendiente"

3. **Envías el número al cliente**
   - Por email o mensaje
   - Les indicas que visiten tu página de seguimiento

4. **Cliente rastrea su paquete**
   - Entra a tu página con el shortcode
   - Ingresa PKG20260123001
   - Ve toda la información del envío

5. **Actualizas el estado**
   - Cuando el paquete está en tránsito, editas el registro
   - Cambias estado a "En tránsito"
   - El cliente verá la actualización al buscar nuevamente

## Base de Datos

El plugin crea automáticamente una tabla `wp_paquetes` al activarse. No necesitas hacer nada manualmente.

## Personalización

### Colores de Estado (CSS)

Los estados tienen colores predefinidos en `assets/css/plugin-paquetes-admin.css`:

- **Pendiente**: Gris (#f0f0f1)
- **En tránsito**: Azul (#72aee6)
- **Entregado**: Verde (#00a32a)

Puedes personalizarlos editando el archivo CSS.

### Agregar Más Estados

Edita el archivo `admin/partials/plugin-paquetes-admin-new.php` y agrega más opciones en el select de estado.

## Solución de Problemas

### El menú no aparece
- Verifica que el plugin esté activado
- Verifica que tu usuario tenga permisos de administrador

### La tabla no se muestra
- Ve a "Plugins" → Desactiva y vuelve a activar el plugin
- Esto recreará la tabla en la base de datos

### El shortcode no funciona
- Verifica que escribiste correctamente: `[paquetes_seguimiento]`
- Asegúrate de estar en una página o entrada, no en un widget de texto antiguo

## Soporte

Si tienes problemas o sugerencias:
- Abre un issue en: https://github.com/germanduranjah-ai/plugin-paquetes/issues

## Siguientes Pasos

Posibles mejoras futuras:
- Editar paquetes existentes
- Estados personalizados desde admin
- Notificaciones por email
- Integración con API de transportistas
- Historial de cambios de estado
- Exportar a CSV/PDF
- Widget para el sidebar

¡Disfruta usando Plugin Paquetes! 📦
