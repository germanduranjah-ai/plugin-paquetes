# Plugin Paquetes - WordPress

Plugin para gestionar el seguimiento y control de paquetes en WordPress.

## Descripción

**Plugin Paquetes** es una solución completa para gestionar el seguimiento de paquetes directamente desde tu sitio WordPress. Perfecto para tiendas en línea, empresas de logística o cualquier negocio que necesite rastrear envíos.

## Características

- ✅ Gestión completa de paquetes desde el panel de administración
- ✅ Sistema de seguimiento con números únicos
- ✅ Estados personalizables (Pendiente, En tránsito, Entregado, etc.)
- ✅ Formulario de búsqueda público mediante shortcode
- ✅ Interfaz intuitiva y fácil de usar
- ✅ Soporte completo en español
- ✅ Diseño responsive y moderno
- ✅ Almacenamiento seguro en base de datos

## Instalación

1. Descarga el plugin y súbelo a la carpeta `/wp-content/plugins/plugin-paquetes/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Aparecerá un nuevo menú "Paquetes" en tu panel de administración

## Uso

### Panel de Administración

Una vez activado el plugin, encontrarás un nuevo menú "Paquetes" en tu panel de WordPress:

- **Todos los Paquetes**: Ver listado completo de todos los paquetes registrados
- **Agregar Nuevo**: Crear un nuevo registro de paquete

#### Campos del Paquete:
- **Número de Seguimiento**: Identificador único del paquete (obligatorio)
- **Descripción**: Descripción del contenido
- **Destinatario**: Nombre del destinatario
- **Origen**: Ciudad o lugar de origen
- **Destino**: Ciudad o lugar de destino
- **Estado**: Estado actual del envío
  - Pendiente
  - En tránsito
  - En almacén
  - En reparto
  - Entregado
  - Devuelto
- **Fecha de Envío**: Fecha y hora del envío
- **Notas**: Información adicional sobre el paquete

### Shortcode para el Frontend

Puedes mostrar un formulario de seguimiento en cualquier página o entrada usando el shortcode:

```
[paquetes_seguimiento]
```

Este shortcode muestra:
- Un formulario de búsqueda donde los usuarios pueden ingresar su número de seguimiento
- Los detalles completos del paquete cuando se encuentra

También puedes mostrar la información de un paquete específico directamente:

```
[paquetes_seguimiento numero="PKG123456"]
```

## Estructura del Proyecto

```
plugin-paquetes/
├── plugin-paquetes.php          # Archivo principal del plugin
├── README.md                     # Documentación
├── includes/                     # Clases principales
│   ├── class-plugin-paquetes.php
│   ├── class-plugin-paquetes-activator.php
│   ├── class-plugin-paquetes-deactivator.php
│   ├── class-plugin-paquetes-loader.php
│   └── class-plugin-paquetes-i18n.php
├── admin/                        # Área de administración
│   ├── class-plugin-paquetes-admin.php
│   └── partials/
│       ├── plugin-paquetes-admin-display.php
│       └── plugin-paquetes-admin-new.php
├── public/                       # Área pública
│   └── class-plugin-paquetes-public.php
├── assets/                       # Recursos estáticos
│   ├── css/
│   │   ├── plugin-paquetes-admin.css
│   │   └── plugin-paquetes-public.css
│   └── js/
│       ├── plugin-paquetes-admin.js
│       └── plugin-paquetes-public.js
└── languages/                    # Archivos de traducción
```

## Base de Datos

El plugin crea una tabla `wp_paquetes` con la siguiente estructura:

- `id`: ID único del registro
- `numero_seguimiento`: Número de seguimiento (único)
- `descripcion`: Descripción del paquete
- `destinatario`: Nombre del destinatario
- `origen`: Lugar de origen
- `destino`: Lugar de destino
- `estado`: Estado actual
- `fecha_envio`: Fecha de envío
- `fecha_entrega`: Fecha de entrega
- `notas`: Notas adicionales
- `fecha_creacion`: Fecha de creación del registro
- `fecha_actualizacion`: Última actualización

## Requisitos

- WordPress 5.0 o superior
- PHP 7.2 o superior
- MySQL 5.6 o superior

## Capturas de Pantalla

(Las capturas se pueden agregar después de la instalación)

## Preguntas Frecuentes

### ¿Cómo agrego un nuevo paquete?

Ve a "Paquetes > Agregar Nuevo" en tu panel de WordPress, completa el formulario y haz clic en "Guardar Paquete".

### ¿Cómo pueden los clientes rastrear sus paquetes?

Crea una página y agrega el shortcode `[paquetes_seguimiento]`. Los clientes podrán ingresar su número de seguimiento para ver el estado de su paquete.

### ¿Puedo personalizar los estados?

Actualmente el plugin incluye estados predefinidos. Las futuras versiones incluirán estados personalizables.

### ¿Es compatible con WooCommerce?

El plugin funciona de forma independiente, pero puede integrarse con WooCommerce mediante desarrollo personalizado.

## Changelog

### 1.0.0 (2026-01-23)
- Versión inicial
- Sistema de gestión de paquetes
- Panel de administración
- Shortcode público de seguimiento
- Soporte completo en español

## Licencia

Este plugin está licenciado bajo GPL-2.0+. Consulta el archivo LICENSE para más detalles.

## Autor

**German Duranjah**
- GitHub: [@germanduranjah-ai](https://github.com/germanduranjah-ai)

## Soporte

Para reportar problemas o sugerir mejoras, por favor abre un issue en el [repositorio de GitHub](https://github.com/germanduranjah-ai/plugin-paquetes).

## Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Haz fork del repositorio
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

¿Te gusta el plugin? ¡Dale una ⭐ en GitHub!