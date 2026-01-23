# Arquitectura del Plugin Paquetes

## Flujo de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    Plugin Paquetes                           │
│                     (WordPress)                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    ACTIVACIÓN                                │
│                                                              │
│  plugin-paquetes.php                                         │
│         │                                                    │
│         ├─► class-plugin-paquetes-activator.php             │
│         │   └─► Crea tabla wp_paquetes en MySQL             │
│         │                                                    │
│         └─► class-plugin-paquetes.php (inicializa)          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                  ÁREA DE ADMINISTRACIÓN                      │
│                                                              │
│  class-plugin-paquetes-admin.php                            │
│         │                                                    │
│         ├─► add_plugin_admin_menu()                         │
│         │   ├─► Menú: "Paquetes"                            │
│         │   ├─► Submenú: "Todos los Paquetes"               │
│         │   └─► Submenú: "Agregar Nuevo"                    │
│         │                                                    │
│         ├─► display_plugin_admin_page()                     │
│         │   └─► partials/plugin-paquetes-admin-display.php  │
│         │       └─► Muestra tabla con todos los paquetes    │
│         │                                                    │
│         └─► display_plugin_admin_new_page()                 │
│             └─► partials/plugin-paquetes-admin-new.php      │
│                 └─► Formulario para agregar paquete         │
│                                                              │
│  Estilos: assets/css/plugin-paquetes-admin.css             │
│  Scripts: assets/js/plugin-paquetes-admin.js               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                     ÁREA PÚBLICA                             │
│                                                              │
│  class-plugin-paquetes-public.php                           │
│         │                                                    │
│         └─► shortcode_seguimiento()                         │
│             │                                                │
│             ├─► [paquetes_seguimiento]                      │
│             │   └─► Muestra formulario de búsqueda          │
│             │                                                │
│             └─► [paquetes_seguimiento numero="PKG123"]      │
│                 └─► Muestra info del paquete directo        │
│                                                              │
│  Estilos: assets/css/plugin-paquetes-public.css            │
│  Scripts: assets/js/plugin-paquetes-public.js              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   BASE DE DATOS                              │
│                                                              │
│  Tabla: wp_paquetes                                          │
│  ┌──────────────────────────────────────────────────┐       │
│  │ id (primary key)                                  │       │
│  │ numero_seguimiento (unique)                       │       │
│  │ descripcion                                       │       │
│  │ destinatario                                      │       │
│  │ origen                                            │       │
│  │ destino                                           │       │
│  │ estado                                            │       │
│  │ fecha_envio                                       │       │
│  │ fecha_entrega                                     │       │
│  │ notas                                             │       │
│  │ fecha_creacion                                    │       │
│  │ fecha_actualizacion                               │       │
│  └──────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────┘
```

## Casos de Uso

### Caso 1: Administrador Agrega Paquete

```
[Administrador] 
     │
     ├─► Accede a WordPress Admin
     │
     ├─► Clic en "Paquetes" → "Agregar Nuevo"
     │
     ├─► Completa formulario:
     │   ├─ Número: PKG20260123001
     │   ├─ Descripción: "2x Zapatos"
     │   ├─ Destinatario: "María García"
     │   ├─ Origen: "Madrid"
     │   ├─ Destino: "Barcelona"
     │   ├─ Estado: "En tránsito"
     │   └─ Fecha: 2026-01-23 10:00
     │
     ├─► Clic en "Guardar Paquete"
     │
     └─► ✓ Registro guardado en wp_paquetes
```

### Caso 2: Cliente Rastrea Paquete

```
[Cliente]
     │
     ├─► Visita página: ejemplo.com/rastrear-pedido
     │   (Contiene shortcode [paquetes_seguimiento])
     │
     ├─► Ve formulario de búsqueda
     │
     ├─► Ingresa: "PKG20260123001"
     │
     ├─► Clic en "Buscar"
     │
     ├─► Plugin consulta wp_paquetes
     │
     └─► ✓ Muestra información:
         ├─ Estado: "En tránsito"
         ├─ Origen: Madrid
         ├─ Destino: Barcelona
         └─ Fecha de envío: 23/01/2026
```

### Caso 3: Mostrar Paquete Específico en Página

```
[Administrador]
     │
     ├─► Crea página: "Estado de tu pedido"
     │
     ├─► Agrega shortcode: 
     │   [paquetes_seguimiento numero="PKG20260123001"]
     │
     ├─► Publica página
     │
     └─► [Cliente visita URL]
         └─► Ve información del paquete directamente
             (sin necesidad de buscar)
```

## Estructura de Archivos

```
plugin-paquetes/
│
├── plugin-paquetes.php              # Archivo principal
│   └─► Define constantes
│   └─► Registra hooks de activación/desactivación
│   └─► Inicia la clase principal
│
├── includes/                        # Clases principales
│   ├── class-plugin-paquetes.php           # Clase principal
│   ├── class-plugin-paquetes-loader.php    # Gestor de hooks
│   ├── class-plugin-paquetes-activator.php # Activación
│   ├── class-plugin-paquetes-deactivator.php # Desactivación
│   └── class-plugin-paquetes-i18n.php      # Internacionalización
│
├── admin/                           # Backend (Panel Admin)
│   ├── class-plugin-paquetes-admin.php
│   └── partials/
│       ├── plugin-paquetes-admin-display.php  # Lista paquetes
│       └── plugin-paquetes-admin-new.php      # Agregar paquete
│
├── public/                          # Frontend (Cara pública)
│   └── class-plugin-paquetes-public.php
│
├── assets/                          # Recursos estáticos
│   ├── css/
│   │   ├── plugin-paquetes-admin.css   # Estilos admin
│   │   └── plugin-paquetes-public.css  # Estilos públicos
│   └── js/
│       ├── plugin-paquetes-admin.js    # Scripts admin
│       └── plugin-paquetes-public.js   # Scripts públicos
│
├── languages/                       # Traducciones (preparado)
│
├── README.md                        # Documentación completa
├── INSTALACION.md                   # Guía de instalación
└── .gitignore                       # Archivos ignorados
```

## Tecnologías Utilizadas

- **PHP**: 7.2+
- **WordPress**: 5.0+
- **MySQL**: 5.6+
- **JavaScript**: ES5 + jQuery
- **CSS**: CSS3

## Seguridad Implementada

1. ✓ Verificación `WPINC` para acceso directo
2. ✓ Sanitización de inputs (`sanitize_text_field`, `sanitize_textarea_field`)
3. ✓ Prepared statements para consultas SQL
4. ✓ Escape de outputs (`esc_html`, `esc_attr`)
5. ✓ Verificación de permisos (`manage_options`)
6. ✓ Confirmación antes de eliminar
7. ✓ Nonces recomendados (para implementar en v1.1)

## Próximas Mejoras Sugeridas

1. **Edición de paquetes**: Permitir modificar paquetes existentes
2. **Historial de estados**: Registrar cada cambio de estado
3. **Notificaciones por email**: Avisar al destinatario
4. **API REST**: Integración con apps móviles
5. **Importar/Exportar**: CSV, Excel
6. **Múltiples idiomas**: .po/.mo files
7. **Reportes**: Estadísticas y gráficos
8. **Integración con transportistas**: API de Correos, etc.
9. **Códigos QR**: Para seguimiento rápido
10. **Widget**: Búsqueda en sidebar

---

**Versión**: 1.0.0  
**Autor**: German Duranjah  
**Licencia**: GPL-2.0+
