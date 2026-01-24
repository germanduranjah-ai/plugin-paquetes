# Shortcodes (UD Paquetes)

> Nota: además de los shortcodes “legacy” (`ud_*`), esta versión incluye shortcodes **PRO** (`udpq_*`) con defaults más estrictos, wrapper centrado y opciones de layout.

## 1) Listado de paquetes (grid)

```text
[ud_paquetes]
```

Atributos principales:

- `type`: `grid` (default) o `carousel`
- `destino`: texto (coincide contra el meta "Destino" del paquete; búsqueda tipo contains/LIKE)
- `limit`: cantidad de paquetes (default: 12)
- `orderby`: `date`, `title`, `rand`, `desde`, `fecha_salida`, `noches` (default: `date`)
- `order`: `DESC` / `ASC` (default: `DESC`)
- `show_filters`: `1`/`0` (default: 1)
- `filter_position`: `top` | `bottom` | `both` (default: `top`)

Ejemplos:

```text
[ud_paquetes destino="Mexico" limit="8" orderby="desde" order="ASC"]
[ud_paquetes show_filters="0" orderby="rand" limit="6"]
```

Filtros extra (por atributos):

```text
[ud_paquetes destino="Punta Cana" hotel="Wyndham" regimen="all inclusive" noches_min="7" noches_max="10" price_max="2500000" orderby="desde" order="ASC"]
```

Filtros vía URL (cuando `show_filters="1"` o manual):

```text
?destino=punta%20cana&hotel=wyndham&regimen=all%20inclusive&noches_min=7&noches_max=10&price_min=1500000&price_max=2500000&fecha_desde=2026-04-01&fecha_hasta=2026-12-31
```

## 2) Listado de paquetes (carousel)

```text
[ud_paquetes type="carousel" limit="12"]
```

Alias:

```text
[ud_paquetes_carousel]
```

> El carousel usa Swiper vía CDN (se encola **solo** cuando se usa el shortcode).

## 3) Alias grid

```text
[ud_paquetes_grid]
```

## 4) Compatibilidad

Si en el pasado usabas solo `[ud_paquetes]`, sigue funcionando igual.

---

# Shortcodes PRO (recomendados)

## A) Listado PRO

```text
[udpq_paquetes]
```

Atributos:

- `view`: `grid` (default) o `list`
- `columns`: 1..4 (default: 3)
- `limit`: default 9
- `ids`: ID PAQUETE (SKU) separados por coma (ej: `"PAQ-001858,PAQ-001847,PAQ-001844"`) - muestra solo esos paquetes
- `destino`: uno o múltiples destinos separados por coma (ej: `"Mexico,Punta Cana,Cancun"`)
- `hotel`: texto (búsqueda parcial)
- `regimen`: texto (búsqueda parcial)
- `price_min`, `price_max`
- `min_noches`, `max_noches`
 - `fecha_desde`, `fecha_hasta`
 - `only_future`: `1`/`0` (default: 0)
 - `orderby`: `date`, `title`, `rand`, `desde`, `fecha_salida`, `noches`
 - `order`: `ASC` / `DESC`

Ejemplos:

```text
[udpq_paquetes columns="3" limit="12" destino="Punta Cana" orderby="desde" order="ASC"]
[udpq_paquetes view="list" limit="6" ids="PAQ-001858,PAQ-001847"]
[udpq_paquetes columns="4" limit="16" destino="Mexico,Playa del Carmen,Cancun" regimen="all inclusive" fecha_desde="2026-04-01" fecha_hasta="2026-12-31"]
[udpq_paquetes destino="Punta Cana,Playa del Carmen" hotel="Wyndham" limit="8"]
[udpq_paquetes ids="PAQ-001844,PAQ-001858" columns="3"]
```

## B) Carousel PRO

```text
[udpq_paquetes_carousel]
```

Atributos principales:
- `limit`: cantidad de paquetes (default: 10)
- `ids`: ID PAQUETE (SKU) separados por coma (ej: `"PAQ-001858,PAQ-001847,PAQ-001844"`) - muestra solo esos paquetes
- `slides`: slides a mostrar en desktop (default: 3)
- `slides_tablet`: slides en tablet (default: 2)
- `slides_mobile`: slides en mobile (default: 1)
- `autoplay`: `1`/`0` (default: 0)
- `destino`: uno o múltiples destinos separados por coma (ej: `"Mexico,Punta Cana,Cancun"`)
- `orderby`: `date`, `title`, `rand`, `desde`, `fecha_salida`, `noches`
- `order`: `ASC` / `DESC`

Ejemplos:

```text
[udpq_paquetes_carousel limit="12" slides="3" slides_tablet="2" slides_mobile="1" autoplay="1" orderby="desde" order="ASC"]
[udpq_paquetes_carousel limit="12" slides="3" slides_tablet="2" slides_mobile="1" autoplay="1" destino="Mexico,Playa del Carmen,Cancun" orderby="desde" order="ASC"]
[udpq_paquetes_carousel limit="8" slides="4" destino="Punta Cana"]
[udpq_paquetes_carousel ids="PAQ-001858,PAQ-001847,PAQ-001844" slides="3" autoplay="1"]
[udpq_paquetes_carousel limit="6" slides="2" ids="PAQ-001844,PAQ-001847" destino="Mexico"]
```

Notas de diseño:
- Las cards usan overlay + botón único **Ver** (va al post del paquete).

## C) Filtros + resultados

```text
[udpq_paquetes_filter]
```

- `results`: `grid` (default) o `carousel`
- `fields`: qué campos mostrar en el form (default: `destino,noches,desde,fecha`)

Ejemplos:

```text
[udpq_paquetes_filter results="grid" limit="12" fields="destino,desde,noches"]
[udpq_paquetes_filter results="carousel" limit="10"]
```
