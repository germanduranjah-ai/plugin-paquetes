# Importación / Exportación (XLSX)

Ruta en admin: **Paquetes → Importar/Exportar**.

El flujo está pensado para 2 casos:

1. **Importar (crear)** paquetes nuevos.
2. **Upsert (editar/actualizar)** paquetes ya cargados (por SKU / ID PAQUETE o por ID WP).

## Columnas soportadas

> Los nombres de columna no son case-sensitive. Recomendado usar exactamente estos encabezados:

| Columna | Descripción |
|---|---|
| `ID PAQUETE` | SKU / identificador interno (meta `_udpq_sku`). Si viene vacío, el sistema autogenera uno estable. |
| `TITULO` | Título del paquete (si no se envía, se mantiene). |
| `DESTINO` | Destino (taxonomía) por nombre o slug. |
| `SALIDA vuelo` | Texto libre (ej: “AR...”). |
| `DESTINO` | Nombre/slug. |
| `MP` | URL de Mercado Pago (opcional). |
| `COMPANIA AEREA` | Aerolínea. |
| `NOCHES EN DESTINO` | Número de noches. |
| `VALOR AEREO (desde)` | Precio desde (float). |
| `SALIDA` | Fecha de salida (YYYY-MM-DD o similar). |
| `REGRESO` | Fecha de regreso (YYYY-MM-DD o similar). |
| `EQUIPAJE` | Texto (ej: “en bodega”). |
| `nombre del hotel` | Hotel. |
| `regimen` | Régimen (desayuno, all inclusive...). |
| `seguro y traslados` | “si/sí/1” = activo. |
| `base doble` | Precio opción reserva. |
| `base triple` | Precio opción reserva. |
| `base single` | Precio opción reserva. |
| `base family` | Precio opción reserva. |
| `INFANTE` | Precio opción reserva. |
| `URL IMAGEN` | URL absoluta de la imagen destacada. |
| `INFORMACION (texto libre)` | Texto libre que se muestra en el front como “Información del paquete”. |

## Exportar

El botón **Exportar** genera un XLSX con los encabezados esperados (incluye SKU).

## Upsert (edición masiva)

- Si en el XLSX viene **`ID PAQUETE`**, se busca el paquete por SKU.
- Si no viene SKU, el importador puede usar el ID WP (si se exportó y se mantiene).
- Si no encuentra coincidencia, crea un nuevo paquete.

## Tips

- Para editar masivamente, el flujo más seguro es:
  1) **Exportar**
  2) Editar el XLSX
  3) Importar usando **Upsert**

