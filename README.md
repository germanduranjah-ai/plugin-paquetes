# Heaven Travel – Paquetes (UD Paquetes)

Plugin de WordPress para gestionar **Paquetes de viaje** (CPT) con:

- Metaboxes con campos del paquete + **opciones de precios** (repeater)
- Template single premium (front)
- Shortcode de listado con modal
- Importación / exportación masiva (XLSX)

## 1) Post Type

- **Post Type:** `ud_paquete`
- **Taxonomy Destinos:** `ud_destino`

## 2) Shortcodes

> Ver documentación completa en `docs/SHORTCODES.md`.

- `[ud_paquetes]` (por defecto tipo grid)
- `[ud_paquetes type="carousel"]`
- `[ud_paquetes_grid]`
- `[ud_paquetes_carousel]`

## 3) Importación y exportación masiva

- Menú: **Paquetes → Importar/Exportar**
- Formato soportado: **XLSX**

> Ver documentación completa en `docs/IMPORT_EXPORT.md`.

## 4) Desarrollo

- Carpeta `templates/` contiene el template `single-ud_paquete.php`.
- Estilos:
  - `assets/public.css` (cards + modal + carousel)
  - `assets/single.css` (single template)
  
