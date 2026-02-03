<?php
if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_Metaboxes {

    const NONCE = 'udpq_meta_nonce';

    // Meta keys (simple fields)
    const META_ACTIVE = '_udpq_active';

    // Identificador interno (tipo SKU) para import/export
    const META_SKU = '_udpq_sku';

    // Datos del paquete
    // (Compatibilidad) antes era "SALIDA vuelo"; ahora la usamos como "SALIDA" (ciudad/aeropuerto)
    const META_SALIDA_VUELO = '_udpq_salida_vuelo';
    const META_DESTINO = '_udpq_destino';
    const META_LINK_PAQUETE = '_udpq_link_paquete'; // LINK MP (opcional)
    const META_COMPANIA = '_udpq_compania_aerea';
    const META_NOCHES = '_udpq_noches';
    const META_VALOR_AEREO = '_udpq_valor_aereo';
    const META_SALIDA_AEREO = '_udpq_salida_aereo';
    const META_REGRESO_AEREO = '_udpq_regreso_aereo';

    // Fechas
    const META_SALIDA = '_udpq_salida';  // fecha
    const META_REGRESO = '_udpq_regreso'; // fecha

    // Otros
    const META_EQUIPAJE = '_udpq_equipaje';
    const META_HOTEL = '_udpq_hotel';
    const META_REGIMEN = '_udpq_regimen';

    // Checklist (siempre "si")
    const META_SEGURO_TRASLADOS = '_udpq_seguro_traslados';

    // Texto libre
    const META_INFO_EXTRA = '_udpq_info_extra';

    // Opciones de reserva (precios)
    const META_PRICE_OPTIONS = '_udpq_price_options';

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_metaboxes']);
        add_action('save_post', [__CLASS__, 'save_metaboxes']);
    }

    public static function register_metaboxes() {
        add_meta_box(
            'udpq_general',
            'Información del paquete',
            [__CLASS__, 'render_general_box'],
            UDPAQUETES_CPT::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'udpq_prices',
            'Opciones de reserva (precios)',
            [__CLASS__, 'render_prices_box'],
            UDPAQUETES_CPT::POST_TYPE,
            'normal',
            'default'
        );
    }

    private static function admin_styles() {
        ?>
        <style>
            .udpq-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
            .udpq-field label{display:block;font-weight:600;margin-bottom:6px}
            .udpq-field input[type="text"], .udpq-field input[type="number"], .udpq-field input[type="date"], .udpq-field textarea{width:100%}
            .udpq-field textarea{min-height:90px}
            .udpq-wide{grid-column:1 / -1}
            .udpq-note{opacity:.8;font-size:12px;margin-top:4px}
            .udpq-table{width:100%;border-collapse:collapse}
            .udpq-table th,.udpq-table td{border:1px solid #dcdcde;padding:8px;vertical-align:middle}
            .udpq-table th{background:#f6f7f7;text-align:left}
            .udpq-muted{opacity:.8}
            @media (max-width: 900px){.udpq-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }

    public static function render_general_box($post) {
        wp_nonce_field(self::NONCE, self::NONCE);

        $active = get_post_meta($post->ID, self::META_ACTIVE, true);

        // SKU (ID PAQUETE)
        $sku = get_post_meta($post->ID, self::META_SKU, true);

        // Datos
        $salida_txt   = get_post_meta($post->ID, self::META_SALIDA_VUELO, true); // salida (ciudad/aeropuerto)
        $destino      = get_post_meta($post->ID, self::META_DESTINO, true);
        $mp_link      = get_post_meta($post->ID, self::META_LINK_PAQUETE, true);
        $compania     = get_post_meta($post->ID, self::META_COMPANIA, true);
        $noches       = get_post_meta($post->ID, self::META_NOCHES, true);
        $valor_aereo  = get_post_meta($post->ID, self::META_VALOR_AEREO, true);
        $salida_aereo = get_post_meta($post->ID, self::META_SALIDA_AEREO, true);
        $regreso_aereo = get_post_meta($post->ID, self::META_REGRESO_AEREO, true);
        $salida_date  = get_post_meta($post->ID, self::META_SALIDA, true);
        $regreso_date = get_post_meta($post->ID, self::META_REGRESO, true);
        $equipaje     = get_post_meta($post->ID, self::META_EQUIPAJE, true);
        $hotel        = get_post_meta($post->ID, self::META_HOTEL, true);
        $regimen      = get_post_meta($post->ID, self::META_REGIMEN, true);
        $seguro       = get_post_meta($post->ID, self::META_SEGURO_TRASLADOS, true);
        $info_extra   = get_post_meta($post->ID, self::META_INFO_EXTRA, true);
        $destino_terms = [];
        if (taxonomy_exists(UDPAQUETES_CPT::TAX_DESTINO)) {
            $destino_terms = get_terms([
                'taxonomy' => UDPAQUETES_CPT::TAX_DESTINO,
                'hide_empty' => false,
            ]);
        }

        ?>
        <style>
            .udpq-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
            .udpq-field label{display:block;font-weight:600;margin-bottom:6px}
            .udpq-field input[type="text"], .udpq-field input[type="number"], .udpq-field input[type="date"], .udpq-field textarea{width:100%}
            .udpq-field textarea{min-height:70px}
            .udpq-wide{grid-column:1 / -1}
            .udpq-note{opacity:.8;font-size:12px;margin-top:4px}
            @media (max-width: 900px){.udpq-grid{grid-template-columns:1fr}}
        </style>

        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr(self::META_ACTIVE); ?>" value="1" <?php checked($active, '1'); ?> />
                Paquete activo (se muestra en el sitio)
            </label>
        </p>

        <div class="udpq-grid" style="margin: 10px 0 14px;">
            <div class="udpq-field udpq-wide">
                <label for="<?php echo esc_attr(self::META_SKU); ?>">ID PAQUETE (SKU)</label>
                <input type="text" id="<?php echo esc_attr(self::META_SKU); ?>" name="<?php echo esc_attr(self::META_SKU); ?>" value="<?php echo esc_attr($sku); ?>" placeholder="Ej: HT-000123" />
                <div class="udpq-note">Identificador interno para importación/exportación. Si se deja vacío, se puede autogenerar en la importación.</div>
            </div>
        </div>

        <div class="udpq-grid">
            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_SALIDA_VUELO); ?>">SALIDA</label>
                <input type="text" id="<?php echo esc_attr(self::META_SALIDA_VUELO); ?>" name="<?php echo esc_attr(self::META_SALIDA_VUELO); ?>" value="<?php echo esc_attr($salida_txt); ?>" placeholder="Ej: Córdoba / Ezeiza" />
            </div>
            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_DESTINO); ?>">DESTINO</label>
                <input type="text" id="<?php echo esc_attr(self::META_DESTINO); ?>" name="<?php echo esc_attr(self::META_DESTINO); ?>" value="<?php echo esc_attr($destino); ?>" placeholder="Ej: Punta Cana" list="udpq-destino-list" />
                <?php if (!empty($destino_terms) && !is_wp_error($destino_terms)): ?>
                    <datalist id="udpq-destino-list">
                        <?php foreach ($destino_terms as $term): ?>
                            <option value="<?php echo esc_attr($term->name); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                <?php endif; ?>
            </div>

            <div class="udpq-field udpq-wide">
                <label for="<?php echo esc_attr(self::META_LINK_PAQUETE); ?>">LINK MP (opcional)</label>
                <input type="text" id="<?php echo esc_attr(self::META_LINK_PAQUETE); ?>" name="<?php echo esc_attr(self::META_LINK_PAQUETE); ?>" value="<?php echo esc_attr($mp_link); ?>" placeholder="https://..." />
                <div class="udpq-note">Se usa para redireccionar a Mercado Pago al finalizar el formulario.</div>
            </div>

            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_COMPANIA); ?>">COMPAÑÍA AÉREA</label>
                <input type="text" id="<?php echo esc_attr(self::META_COMPANIA); ?>" name="<?php echo esc_attr(self::META_COMPANIA); ?>" value="<?php echo esc_attr($compania); ?>" placeholder="Ej: Aerolíneas Argentinas" />
            </div>
            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_NOCHES); ?>">NOCHES EN DESTINO</label>
                <input type="number" min="0" step="1" id="<?php echo esc_attr(self::META_NOCHES); ?>" name="<?php echo esc_attr(self::META_NOCHES); ?>" value="<?php echo esc_attr($noches); ?>" />
            </div>

            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_VALOR_AEREO); ?>">VALOR AÉREO</label>
                <input type="number" min="0" step="1" id="<?php echo esc_attr(self::META_VALOR_AEREO); ?>" name="<?php echo esc_attr(self::META_VALOR_AEREO); ?>" value="<?php echo esc_attr($valor_aereo); ?>" />
            </div>
            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_SALIDA_AEREO); ?>">SALIDA AÉREO</label>
                <input type="text" id="<?php echo esc_attr(self::META_SALIDA_AEREO); ?>" name="<?php echo esc_attr(self::META_SALIDA_AEREO); ?>" value="<?php echo esc_attr($salida_aereo); ?>" placeholder="Ej: 10:30 HS" />
            </div>

            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_REGRESO_AEREO); ?>">REGRESO AÉREO</label>
                <input type="text" id="<?php echo esc_attr(self::META_REGRESO_AEREO); ?>" name="<?php echo esc_attr(self::META_REGRESO_AEREO); ?>" value="<?php echo esc_attr($regreso_aereo); ?>" placeholder="Ej: 18:45 HS" />
            </div>

            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_SALIDA); ?>">SALIDA (fecha)</label>
                <input type="date" id="<?php echo esc_attr(self::META_SALIDA); ?>" name="<?php echo esc_attr(self::META_SALIDA); ?>" value="<?php echo esc_attr($salida_date); ?>" />
            </div>
            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_REGRESO); ?>">REGRESO (fecha)</label>
                <input type="date" id="<?php echo esc_attr(self::META_REGRESO); ?>" name="<?php echo esc_attr(self::META_REGRESO); ?>" value="<?php echo esc_attr($regreso_date); ?>" />
            </div>

            <div class="udpq-field udpq-wide">
                <label for="<?php echo esc_attr(self::META_EQUIPAJE); ?>">EQUIPAJE</label>
                <textarea id="<?php echo esc_attr(self::META_EQUIPAJE); ?>" name="<?php echo esc_attr(self::META_EQUIPAJE); ?>" placeholder="Ej: en bodega - carry on"><?php echo esc_textarea($equipaje); ?></textarea>
            </div>

            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_HOTEL); ?>">Nombre del hotel</label>
                <input type="text" id="<?php echo esc_attr(self::META_HOTEL); ?>" name="<?php echo esc_attr(self::META_HOTEL); ?>" value="<?php echo esc_attr($hotel); ?>" />
            </div>
            <div class="udpq-field">
                <label for="<?php echo esc_attr(self::META_REGIMEN); ?>">Régimen</label>
                <input type="text" id="<?php echo esc_attr(self::META_REGIMEN); ?>" name="<?php echo esc_attr(self::META_REGIMEN); ?>" value="<?php echo esc_attr($regimen); ?>" placeholder="Ej: desayuno - all inclusive" />
            </div>

            <div class="udpq-field udpq-wide">
                <label>Seguro y traslados</label>
                <label style="font-weight:400;display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" name="<?php echo esc_attr(self::META_SEGURO_TRASLADOS); ?>" value="1" <?php checked($seguro, '1'); ?> />
                    <span>Incluye seguros y traslados</span>
                </label>
                <div class="udpq-note">Por defecto lo dejamos activado (SI).</div>
            </div>

            <div class="udpq-field udpq-wide">
                <label for="<?php echo esc_attr(self::META_INFO_EXTRA); ?>">Información (texto libre)</label>
                <textarea id="<?php echo esc_attr(self::META_INFO_EXTRA); ?>" name="<?php echo esc_attr(self::META_INFO_EXTRA); ?>" placeholder="Agregá la información que quieras mostrar en la plantilla..."><?php echo esc_textarea($info_extra); ?></textarea>
            </div>
        </div>
        <?php
    }

    public static function render_prices_box($post) {
        // Repeater simplificado: opciones fijas
        $options = get_post_meta($post->ID, self::META_PRICE_OPTIONS, true);
        if (!is_array($options)) $options = [];
        ?>
        <style>
            .udpq-table{width:100%;border-collapse:collapse}
            .udpq-table th,.udpq-table td{border:1px solid #dcdcde;padding:8px;vertical-align:top}
            .udpq-table th{background:#f6f7f7;text-align:left}
            .udpq-table input[type="number"]{width:100%}
            .udpq-muted{opacity:.8}
        </style>

        <p class="udpq-muted">Cargá los importes por tipo de base. Se muestran en el front como "Opciones de reserva".</p>

        <table class="udpq-table">
            <thead>
                <tr>
                    <th style="width:90px">Activo</th>
                    <th>Opción</th>
                    <th style="width:220px">Precio</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $defaults = [
                    ['key'=>'base_doble',  'label'=>'Base doble',  'active'=>1, 'price'=>''],
                    ['key'=>'base_triple', 'label'=>'Base triple', 'active'=>1, 'price'=>''],
                    ['key'=>'base_single', 'label'=>'Base single', 'active'=>1, 'price'=>''],
                    ['key'=>'base_family', 'label'=>'Base family', 'active'=>1, 'price'=>''],
                    ['key'=>'infante',     'label'=>'Infante',     'active'=>1, 'price'=>''],
                ];

                // Indexar por key
                $indexed = [];
                foreach ($options as $row) {
                    if (!is_array($row)) continue;
                    $k = $row['key'] ?? '';
                    if (!$k) continue;
                    $indexed[$k] = $row;
                }

                foreach ($defaults as $i => $def) {
                    $row = isset($indexed[$def['key']]) ? wp_parse_args($indexed[$def['key']], $def) : $def;
                    $row['price'] = isset($row['price']) ? $row['price'] : '';
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="<?php echo esc_attr(self::META_PRICE_OPTIONS); ?>[<?php echo esc_attr($i); ?>][active]" value="1" <?php checked(!empty($row['active']), true); ?> />
                            <input type="hidden" name="<?php echo esc_attr(self::META_PRICE_OPTIONS); ?>[<?php echo esc_attr($i); ?>][key]" value="<?php echo esc_attr($row['key']); ?>" />
                            <input type="hidden" name="<?php echo esc_attr(self::META_PRICE_OPTIONS); ?>[<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($row['label']); ?>" />
                        </td>
                        <td><strong><?php echo esc_html($row['label']); ?></strong></td>
                        <td>
                            <input type="number" min="0" step="1" name="<?php echo esc_attr(self::META_PRICE_OPTIONS); ?>[<?php echo esc_attr($i); ?>][price]" value="<?php echo esc_attr($row['price']); ?>" />
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <?php
    }

    public static function save_metaboxes($post_id) {
        if (get_post_type($post_id) !== UDPAQUETES_CPT::POST_TYPE) return;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], self::NONCE)) return;

        // Active
        update_post_meta($post_id, self::META_ACTIVE, !empty($_POST[self::META_ACTIVE]) ? '1' : '0');

        // Simple fields
        $simple_keys = [
            self::META_SKU,
            self::META_SALIDA_VUELO,
            self::META_DESTINO,
            self::META_LINK_PAQUETE,
            self::META_COMPANIA,
            self::META_NOCHES,
            self::META_VALOR_AEREO,
            self::META_SALIDA_AEREO,
            self::META_REGRESO_AEREO,
            self::META_SALIDA,
            self::META_REGRESO,
            self::META_EQUIPAJE,
            self::META_HOTEL,
            self::META_REGIMEN,
            self::META_INFO_EXTRA,
        ];

        $destino_value = '';

        foreach ($simple_keys as $key) {
            $val = isset($_POST[$key]) ? $_POST[$key] : '';

            if ($key === self::META_NOCHES || $key === self::META_VALOR_AEREO) {
                $val = udpq_sanitize_int($val);
            } elseif ($key === self::META_LINK_PAQUETE) {
                $val = udpq_sanitize_url($val);
            } elseif ($key === self::META_SALIDA || $key === self::META_REGRESO) {
                $val = is_string($val) ? preg_replace('/[^0-9\-]/', '', $val) : '';
            } else {
                $is_textarea = in_array($key, [self::META_EQUIPAJE, self::META_INFO_EXTRA], true);
                $val = $is_textarea ? sanitize_textarea_field($val) : udpq_sanitize_text($val);
            }

            update_post_meta($post_id, $key, $val);

            if ($key === self::META_DESTINO) {
                $destino_value = $val;
            }
        }

        self::sync_destino_taxonomy($post_id, $destino_value);

        // Seguro y traslados (checklist)
        update_post_meta($post_id, self::META_SEGURO_TRASLADOS, !empty($_POST[self::META_SEGURO_TRASLADOS]) ? '1' : '0');

        // Opciones de reserva (repeater simplificado)
        $raw = $_POST[self::META_PRICE_OPTIONS] ?? [];
        $options = udpq_sanitize_price_options($raw);
        update_post_meta($post_id, self::META_PRICE_OPTIONS, $options);
    }

    public static function sync_destino_taxonomy($post_id, $destino) {
        if (!taxonomy_exists(UDPAQUETES_CPT::TAX_DESTINO)) {
            return;
        }

        $destino = is_string($destino) ? trim($destino) : '';
        if ($destino === '') {
            wp_set_object_terms($post_id, [], UDPAQUETES_CPT::TAX_DESTINO, false);
            return;
        }

        $term = term_exists($destino, UDPAQUETES_CPT::TAX_DESTINO);
        if (!$term) {
            $term = wp_insert_term($destino, UDPAQUETES_CPT::TAX_DESTINO);
        }

        if (is_wp_error($term)) {
            return;
        }

        $term_id = is_array($term) ? $term['term_id'] : $term;
        wp_set_object_terms($post_id, [intval($term_id)], UDPAQUETES_CPT::TAX_DESTINO, false);
    }

}
