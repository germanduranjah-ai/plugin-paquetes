<?php
/**
 * Template for single paquete post
 * Based on onheaventravel.com design
 */

// This template is loaded via filter, so we need to handle the output
if (!function_exists('get_header')) {
    // If headers not loaded, we're in a different context
    return;
}

get_header();

// =====================================================
// Fetch paquete data (this template is loaded directly by WP via single_template filter).
// So we MUST resolve meta here (the previous version relied on variables that were never defined).
// =====================================================
$post_id = get_the_ID();
$meta = function_exists('udpq_get_paquete_meta') ? udpq_get_paquete_meta($post_id) : [];

$active = $meta['active'] ?? get_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, true);
$destino = $meta['destino'] ?? '';
$salida_vuelo = $meta['salida_vuelo'] ?? '';
$compania = $meta['compania'] ?? '';
$valor_aereo = $meta['valor_aereo'] ?? '';
$salida_aereo = $meta['salida_aereo'] ?? '';
$regreso_aereo = $meta['regreso_aereo'] ?? '';
$noches = $meta['noches'] ?? '';
$salida = $meta['salida'] ?? '';
$regreso = $meta['regreso'] ?? '';
$equipaje = $meta['equipaje'] ?? '';
$hotel = $meta['hotel'] ?? '';
$regimen = $meta['regimen'] ?? '';
$seguro_traslados = $meta['seguro_traslados'] ?? '';
$seguro_traslados = ($seguro_traslados === '1') ? 'SI' : $seguro_traslados;
$servicios = $meta['servicios'] ?? '';
$beneficios = $meta['beneficios'] ?? '';
$info_extra = $meta['info_extra'] ?? '';

$featured_image = has_post_thumbnail() ? get_the_post_thumbnail_url($post_id, 'full') : '';

// Si no hay imagen destacada, intenta usar URL externa guardada en importación
if (empty($featured_image)) {
    $external_image = get_post_meta($post_id, '_udpq_external_image_url', true);
    if (!empty($external_image)) {
        $featured_image = esc_url($external_image);
    }
}

// Price options + "desde"
$opts = $meta['price_options'] ?? get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
if (!is_array($opts)) $opts = [];

$currency = udpq_get_base_currency();

// "Desde" price: usa base_doble directamente
$desde_price = null;

if (!empty($opts)) {
    foreach ($opts as $opt) {
        if (is_array($opt) && ($opt['key'] ?? '') === 'base_doble') {
            $price = isset($opt['price']) ? (string)$opt['price'] : '';
            if ($price !== '' && is_numeric($price)) {
                $desde_price = (float) $price;
            }
            break;
        }
    }
}

// Parse servicios and beneficios (one per line)
$servicios_list = !empty($servicios) ? array_filter(array_map('trim', explode("\n", $servicios))) : [];
$beneficios_list = !empty($beneficios) ? array_filter(array_map('trim', explode("\n", $beneficios))) : [];

if (!function_exists('udpq_price_option_note')) {
    function udpq_price_option_note(string $label): string {
        $map = [
            'base doble' => 'Precio por persona si viajan dos.',
            'base triple' => 'Precio por persona si viajan tres.',
            'base single' => 'Precio por persona si viajas solo.',
            'base family' => 'Precio por persona si viajan dos adultos y dos menores de hasta 12 años.',
            'infante' => 'En caso de viajar con un infante, el adicional por el bebé.',
        ];

        $key = strtolower(trim($label));
        return $map[$key] ?? '';
    }
}
?>

<div class="udpq-single">
    <?php if ($active !== '1'): ?>
        <div class="udpq-notice udpq-notice--error">
            <p><?php _e('Este paquete no está disponible actualmente.', 'ud-paquetes'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="udpq-single__hero">
        <?php if ($featured_image): ?>
            <div class="udpq-single__hero-image" style="background-image: url('<?php echo esc_url($featured_image); ?>');">
                <div class="udpq-single__hero-overlay"></div>
            </div>
        <?php endif; ?>
        
        <div class="udpq-single__hero-content">
            <div class="udpq-container">
            <div class="udpq-single__hero-text">
                    <h1 class="udpq-single__title">
                        <?php 
                        $title_parts = [];
                        if (!empty($destino)) {
                            $title_parts[] = esc_html($destino);
                        }
                        if (!empty($salida)) {
                            $title_parts[] = date_i18n('d/m/Y', strtotime($salida));
                        }
                        echo implode(' – ', $title_parts) ?: esc_html(get_the_title());
                        ?>
                    </h1>
                </div>
                
                <div class="udpq-single__hero-price">
                    <?php if (!empty($desde_price)): ?>
                        <div class="udpq-single__price-label"><?php _e('Desde', 'ud-paquetes'); ?></div>
                        <div class="udpq-single__price-amount"><?php echo esc_html(udpq_format_money_locale($desde_price, $currency)); ?></div>
                        <div class="udpq-single__price-note"><?php _e('Precio total', 'ud-paquetes'); ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($active === '1'): ?>
                    <button type="button" class="udpq-btn udpq-btn--big udpq-btn--reserve" data-udpq-open-reserva>
                        <?php _e('Opciones de reserva', 'ud-paquetes'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="udpq-container udpq-single__main">
        <div class="udpq-single__content">
            <!-- Package Info -->
            <div class="udpq-single__section">
                <div class="udpq-single__meta">
                    <span class="udpq-single__meta-item"><?php _e('Paquete de vacaciones', 'ud-paquetes'); ?></span>
                    <span class="udpq-single__meta-item"><?php printf(__('Creado: %s', 'ud-paquetes'), get_the_date()); ?></span>
                    <?php
                    // ID PAQUETE (SKU). Si no existe, fallback al ID numérico.
                    $sku = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SKU, true);
                    $sku = $sku ? $sku : $post_id;
                    ?>
                    <span class="udpq-single__meta-item"><?php printf(__('ID Paquete: %s', 'ud-paquetes'), esc_html($sku)); ?></span>
                </div>
            </div>

            <!-- About Destination -->
            <?php if (get_the_content()): ?>
                <div class="udpq-single__section">
                    <h2 class="udpq-single__section-title"><?php _e('Sobre el destino', 'ud-paquetes'); ?></h2>
                    <div class="udpq-single__content-text">
                        <?php the_content(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detalles del paquete (campos del backend) -->
            <?php if (!empty($noches) || !empty($salida) || !empty($regreso) || !empty($salida_aereo) || !empty($regreso_aereo) || !empty($equipaje) || !empty($regimen) || !empty($seguro_traslados)): ?>
                <div class="udpq-single__section">
                    <h2 class="udpq-single__section-title"><i class="udpq-ico fa-solid fa-circle-info" aria-hidden="true"></i> <?php _e('Detalles del paquete', 'ud-paquetes'); ?></h2>

                    <div class="udpq-single__details">
                        <?php if (!empty($noches)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-moon"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Noches en destino', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html($noches); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($salida)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-plane-departure"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Salida', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html(date_i18n('d/m/Y', strtotime($salida))); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($regreso)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-plane-arrival"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Regreso', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html(date_i18n('d/m/Y', strtotime($regreso))); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($salida_aereo)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-plane-departure"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Salida aéreo', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html($salida_aereo); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($regreso_aereo)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-plane-arrival"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Regreso aéreo', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html($regreso_aereo); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($equipaje)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-suitcase-rolling"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Equipaje', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html($equipaje); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($regimen)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-utensils"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Régimen', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html($regimen); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($seguro_traslados)): ?>
                            <div class="udpq-single__detail">
                                <div class="udpq-single__detail-icon"><i class="fa-solid fa-plane-circle-exclamation"></i></div>
                                <div class="udpq-single__detail-label"><?php _e('Seguros y traslados', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__detail-value"><?php echo esc_html($seguro_traslados); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            

            <!-- Servicios incluidos -->
            <div class="udpq-single__section">
                <h2 class="udpq-single__section-title"><i class="udpq-ico fa-solid fa-circle-check" aria-hidden="true"></i> <?php _e('Servicios incluidos', 'ud-paquetes'); ?></h2>
                
                <div class="udpq-single__services udpq-cards-grid">
                    <?php if (!empty($salida)): ?>
                        <div class="udpq-single__service-item">
                            <div class="udpq-single__service-date">
                                <span class="udpq-single__service-day"><?php echo esc_html(date('d', strtotime($salida))); ?></span>
                                <span class="udpq-single__service-month"><?php echo esc_html(date_i18n('M', strtotime($salida))); ?></span>
                            </div>
                            <div class="udpq-single__service-content">
                                <div class="udpq-card__title"><?php _e('Salida', 'ud-paquetes'); ?></div>
                                <?php if (!empty($compania) && !empty($salida_vuelo)): ?>
                                    <div class="udpq-single__service-detail">
                                        <div class="udpq-single__service-airline">
                                            <strong><?php echo esc_html($compania); ?></strong>
                                        </div>
                                        <div class="udpq-single__service-info">
                                            <?php echo esc_html($salida_vuelo); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($salida_aereo)): ?>
                                    <div class="udpq-single__service-info">
                                        <?php echo esc_html($salida_aereo); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($hotel)): ?>
                        <div class="udpq-single__service-item">
                            <div class="udpq-single__service-date">
                                <?php if (!empty($noches)): ?>
                                    <span class="udpq-single__service-nights"><?php echo esc_html($noches); ?></span>
                                    <span class="udpq-single__service-label"><?php _e('Noches', 'ud-paquetes'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="udpq-single__service-content">
                                <div class="udpq-card__title"><?php _e('Alojamiento', 'ud-paquetes'); ?></div>
                                <div class="udpq-single__service-detail">
                                    <strong><?php echo esc_html($hotel); ?></strong>
                                    <?php if (!empty($regimen)): ?>
                                        <span class="udpq-single__service-regimen"><?php echo esc_html($regimen); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($regreso)): ?>
                        <div class="udpq-single__service-item">
                            <div class="udpq-single__service-date">
                                <span class="udpq-single__service-day"><?php echo esc_html(date('d', strtotime($regreso))); ?></span>
                                <span class="udpq-single__service-month"><?php echo esc_html(date_i18n('M', strtotime($regreso))); ?></span>
                            </div>
                            <div class="udpq-single__service-content">
                                <div class="udpq-card__title"><?php _e('Regreso', 'ud-paquetes'); ?></div>
                                <?php if (!empty($regreso_aereo)): ?>
                                    <div class="udpq-single__service-info">
                                        <?php echo esc_html($regreso_aereo); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($servicios_list) || !empty($beneficios_list)): ?>
                    <ul class="udpq-single__list">
                        <?php foreach ($servicios_list as $servicio): ?>
                            <li><i class="fa-solid fa-circle-check"></i> <i class="udpq-ico udpq-ico--list fa-solid fa-check" aria-hidden="true"></i><?php echo esc_html($servicio); ?></li>
                        <?php endforeach; ?>
                        <?php foreach ($beneficios_list as $beneficio): ?>
                            <li><i class="fa-solid fa-circle-check"></i> <i class="udpq-ico udpq-ico--list fa-regular fa-star" aria-hidden="true"></i><?php echo esc_html($beneficio); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Información del paquete (texto libre) -->
            <?php if (!empty($info_extra)): ?>
                <div class="udpq-single__section">
                    <h2 class="udpq-single__section-title"><i class="udpq-ico fa-solid fa-file-lines" aria-hidden="true"></i> <?php _e('Información del paquete', 'ud-paquetes'); ?></h2>
                    <div class="udpq-single__content-text">
                        <?php echo wpautop(esc_html($info_extra)); ?>
                    </div>
                </div>
            <?php endif; ?>


            <!-- Opciones de reserva (al final) -->
            <?php if (!empty($opts) && is_array($opts)): ?>
                <?php
                    // Build display rows (active + price) for template
                    $options_rows = [];
                    foreach ((array)$opts as $i => $row) {
                        $row = is_array($row) ? $row : [];
                        $active = !empty($row['active']);
                        $price = isset($row['price']) ? floatval($row['price']) : 0;
                        if ($price <= 0) continue;

                        $label = $row['label'] ?? '';
                        if (!$label) {
                            $label = !empty($row['key']) ? $row['key'] : ('Opción ' . ($i+1));
                        }
                        $options_rows[] = [
                            'label' => $label,
                            'price' => $price,
                            'active' => $active,
                        ];
                    }
                ?>
                <?php if (!empty($options_rows)): ?>
                    <div class="udpq-single__section">
                        <h2 class="udpq-single__section-title"><i class="udpq-ico fa-solid fa-tags" aria-hidden="true"></i> <?php _e('Opciones de reserva', 'ud-paquetes'); ?></h2>
                        <!--
                          Opciones de reserva
                          Nota: evitamos clases "udpq-card" dentro de labels/precios para que no se aniden cajas
                          y no se rompa el layout (altura excesiva / columnas raras).
                        -->
                        <div class="udpq-single__options udpq-options-stack">
                            <?php foreach ($options_rows as $row): ?>
                                <?php $desc = function_exists('udpq_price_option_note') ? udpq_price_option_note($row['label']) : ''; ?>
                                <div class="udpq-single__option <?php echo empty($row['active']) ? 'is-disabled' : ''; ?>">
                                    <span class="udpq-single__option-label"><?php echo esc_html($row['label']); ?></span>
                                    <?php if ($desc): ?><span class="udpq-radio__desc"><?php echo esc_html($desc); ?></span><?php endif; ?>
                                    <span class="udpq-single__option-price"><?php echo esc_html(udpq_format_money_locale($row['price'], $currency)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($active === '1'): ?>
                            <div class="udpq-card udpq-card--option udpq-single__options-cta">
                                <button type="button" class="udpq-btn udpq-btn--big udpq-btn--primary udpq-btn--reserve" data-udpq-open-reserva>
                                    <?php _e('Reservar ahora', 'ud-paquetes'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar with Summary -->
        <aside class="udpq-single__sidebar">
            <div class="udpq-single__summary">
                <h3 class="udpq-single__summary-title"><?php _e('Resumen del viaje', 'ud-paquetes'); ?></h3>
                
                <div class="udpq-single__summary-item">
                    <span><?php _e('Destinos', 'ud-paquetes'); ?></span>
                    <strong>1</strong>
                </div>
                
                <?php if (!empty($noches)): ?>
                    <div class="udpq-single__summary-item">
                        <span><?php _e('Noches', 'ud-paquetes'); ?></span>
                        <strong><?php echo esc_html($noches); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($desde_price)): ?>
                    <div class="udpq-single__summary-price">
                        <span><?php _e('Precio total Desde', 'ud-paquetes'); ?></span>
                        <strong><?php echo esc_html(udpq_format_money_locale($desde_price, $currency)); ?></strong>
                    </div>
                <?php endif; ?>
                    <button type="button" class="udpq-btn udpq-btn--big udpq-btn--primary udpq-btn--reserve" data-udpq-open-reserva>
                                    <?php _e('Reservar ahora', 'ud-paquetes'); ?>
                    </button>
            </div>

            <?php if ($active === '1'): ?>
                <button type="button" class="udpq-btn udpq-btn--big udpq-btn--primary udpq-btn--reserve" data-udpq-open-reserva>
                    <?php _e('Reservar ahora', 'ud-paquetes'); ?>
                </button>
            <?php endif; ?>
        </aside>
    </div>
</div>

<!-- Reservation Modal -->
<div class="udpq-reserva-modal" id="udpq-reserva-modal" aria-hidden="true">
    <div class="udpq-reserva-modal__overlay" data-udpq-close-reserva></div>
    <div class="udpq-reserva-modal__panel" role="dialog" aria-modal="true" aria-label="<?php _e('Formulario de reserva', 'ud-paquetes'); ?>">
        <button class="udpq-reserva-modal__close" type="button" data-udpq-close-reserva aria-label="<?php _e('Cerrar', 'ud-paquetes'); ?>">×</button>

        <div class="udpq-reserva-modal__header">
            <h2><?php _e('Reservar Paquete', 'ud-paquetes'); ?></h2>
            <p><?php echo esc_html(get_the_title()); ?></p>
        </div>

        <div class="udpq-reserva-modal__content">
            <?php
            // Check if Contact Form 7 is available
            $cf7_form_id = get_option('udpq_cf7_form_id', '');
            $elementor_form_id = get_option('udpq_elementor_form_id', '');

            $has_cf7 = !empty($cf7_form_id) && function_exists('wpcf7_contact_form');
            $has_elementor = !empty($elementor_form_id) && class_exists('\\Elementor\\Plugin');

            // If user uses CF7 / Elementor, we still render our price selector so the redirect can go to the
            // Mercado Pago link of the chosen option.
            if ($has_cf7 || $has_elementor) {
                udpq_render_price_selector($post_id, $opts);

                echo '<div class="udpq-reserva-form__section" style="margin-top:16px">';
                echo '<div class="udpq-reserva-form__label">' . esc_html__('Tus datos de contacto', 'ud-paquetes') . '</div>';
                echo '<div class="udpq-muted" style="font-size:13px; margin-top:6px">' . esc_html__('Al enviar el formulario te redirigimos a Mercado Pago con la opción elegida.', 'ud-paquetes') . '</div>';
                echo '</div>';

                if ($has_cf7) {
                    echo do_shortcode('[contact-form-7 id="' . intval($cf7_form_id) . '"]');
                } else {
                    echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($elementor_form_id);
                }
            } else {
                // Fallback to default form (includes price selector + redirect via AJAX)
                render_default_reserva_form($post_id, $opts);
            }
            ?>
        </div>
    </div>
</div>

<?php
get_footer();

/**
 * Render price selector (used both by default form and CF7/Elementor integrations).
 */
function udpq_render_price_selector($post_id, $opts) {
    $display = [];
    $currency = function_exists('udpq_get_base_currency') ? udpq_get_base_currency() : 'USD';
    $mp_link = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, true);

    foreach ((array)$opts as $i => $row) {
        $row = is_array($row) ? $row : [];
        $active = !empty($row['active']);
        $price = isset($row['price']) ? floatval($row['price']) : 0;
        if ($price <= 0) continue;

        $label = $row['label'] ?? '';
        if (!$label) {
            $label = !empty($row['key']) ? $row['key'] : ('Opción ' . ($i+1));
        }
        $display[] = [
            '_idx' => $i,
            'label' => $label,
            'price' => $price,
            'currency' => $currency,
            'active' => $active,
            'mp_url' => $mp_link,
        ];
    }

    if (empty($display)) {
        echo '<p>' . esc_html__('Este paquete no tiene opciones de precio disponibles.', 'ud-paquetes') . '</p>';
        return;
    }

    echo '<div class="udpq-reserva-form__section udpq-price-selector" data-udpq-price-selector>';
    echo '<div class="udpq-reserva-form__label">' . esc_html__('Elegí una opción', 'ud-paquetes') . '</div>';

    foreach ($display as $k => $row) {
        $label_lower = strtolower(trim($row['label']));
        $is_infante = strpos($label_lower, 'infante') !== false;
        $disabled = empty($row['active']) || $is_infante;
        $css_class = $is_infante ? 'is-infante' : ($disabled ? 'is-disabled' : '');
        $desc = function_exists('udpq_price_option_note') ? udpq_price_option_note($row['label']) : '';
        $desc_html = $desc ? '<span class="udpq-radio__desc">' . esc_html($desc) . '</span>' : '';
        $disabled_note = ($disabled && !$is_infante) ? '<span class="udpq-radio__note">' . esc_html__('No disponible', 'ud-paquetes') . '</span>' : '';

        printf(
            '<label class="udpq-radio %s">'
                . '<input type="radio" name="udpq_price_choice" value="%s" %s %s data-mp-url="%s" data-price="%s" data-currency="%s">'
                . '<span class="udpq-radio__label">%s</span>'
                . '%s'
                . '<span class="udpq-radio__price">%s</span>'
                . '%s'
            . '</label>',
            $css_class,
            esc_attr($row['_idx']),
            $disabled ? 'disabled' : '',
            checked($k === 0 && !$disabled, true, false),
            esc_url($row['mp_url']),
            esc_attr($row['price']),
            esc_attr($row['currency']),
            esc_html($row['label']),
            $desc_html,
            esc_html(udpq_format_money_locale($row['price'], $row['currency'])),
            $disabled_note
        );
    }

    echo '<input type="hidden" data-udpq-paquete-id value="' . esc_attr($post_id) . '">';
    echo '</div>';
}

/**
 * Render default reservation form
 */
function render_default_reserva_form($post_id, $opts) {
    $display = [];
    $currency = function_exists('udpq_get_base_currency') ? udpq_get_base_currency() : 'USD';
    $mp_link = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, true);

    foreach ((array)$opts as $i => $row) {
        $row = is_array($row) ? $row : [];
        $active = !empty($row['active']);
        $price = isset($row['price']) ? floatval($row['price']) : 0;
        if ($price <= 0) continue;
        $label = $row['label'] ?? '';
        if (!$label) $label = !empty($row['key']) ? $row['key'] : ('Opción ' . ($i+1));
        $display[] = [ '_idx'=>$i, 'label'=>$label, 'price'=>$price, 'currency'=>$currency, 'active'=>$active, 'mp_url'=>$mp_link ];
    }

    if (empty($display)) {
        echo '<p>' . __('Este paquete no tiene opciones de precio disponibles.', 'ud-paquetes') . '</p>';
        return;
    }
    ?>
    <form method="post" class="udpq-reserva-form" id="udpq-reserva-form">
        <?php wp_nonce_field('udpq_reserva', 'udpq_reserva_nonce'); ?>
        <input type="hidden" name="udpq_action" value="reserva" />
        <input type="hidden" name="paquete_id" value="<?php echo esc_attr($post_id); ?>" />

        <div class="udpq-reserva-form__section">
            <label class="udpq-reserva-form__label"><?php _e('Elegí una opción', 'ud-paquetes'); ?></label>
            <?php foreach ($display as $k => $row): ?>
                <?php 
                $desc = function_exists('udpq_price_option_note') ? udpq_price_option_note($row['label']) : '';
                $label_lower = strtolower(trim($row['label']));
                $is_infante = strpos($label_lower, 'infante') !== false;
                $is_disabled = empty($row['active']) || $is_infante;
                $css_class = $is_infante ? 'is-infante' : ($is_disabled ? 'is-disabled' : '');
                ?>
                <label class="udpq-radio <?php echo $css_class; ?>">
                    <input type="radio" name="price_idx" value="<?php echo esc_attr($row['_idx']); ?>" 
                           <?php echo $is_disabled ? 'disabled' : ''; ?> 
                           <?php checked($k===0 && !$is_disabled, true); ?>
                           data-price="<?php echo esc_attr($row['price']); ?>"
                           data-currency="<?php echo esc_attr($row['currency']); ?>"
                           data-mp-url="<?php echo esc_url($row['mp_url']); ?>">
                    <span class="udpq-radio__label"><?php echo esc_html($row['label']); ?></span>
                    <?php if ($desc): ?>
                        <span class="udpq-radio__desc"><?php echo esc_html($desc); ?></span>
                    <?php endif; ?>
                    <span class="udpq-radio__price"><?php echo esc_html(udpq_format_money_locale($row['price'], $row['currency'])); ?></span>
                    <?php if (!$is_infante && empty($row['active'])): ?>
                        <span class="udpq-radio__note"><?php _e('No disponible', 'ud-paquetes'); ?></span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="udpq-reserva-form__section">
            <label class="udpq-reserva-form__label"><?php _e('Datos del titular de la reserva', 'ud-paquetes'); ?></label>
            <p class="udpq-reserva-form__note"><?php _e('Por favor, ingresá los datos de la persona responsable. Una vez confirmada la reserva, nos pondremos en contacto para solicitar la información del resto de los pasajeros', 'ud-paquetes'); ?></p>
            <div class="udpq-reserva-form__grid">
                <div class="udpq-reserva-form__field">
                    <label><?php _e('Nombre y apellido', 'ud-paquetes'); ?> *</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="udpq-reserva-form__field">
                    <label><?php _e('Email', 'ud-paquetes'); ?> *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="udpq-reserva-form__field">
                    <label><?php _e('Teléfono', 'ud-paquetes'); ?> *</label>
                    <input type="text" name="telefono" required>
                </div>
                <div class="udpq-reserva-form__field">
                    <label><?php _e('DNI / Pasaporte', 'ud-paquetes'); ?></label>
                    <input type="text" name="documento">
                </div>
            </div>
        </div>

<div class="udpq-reserva-form__section">
    <label class="udpq-check">
        <input type="checkbox" name="acepta" value="1" required="">
        <span>
            Acepto los 
            <a href="https://on-heaventravel.com/wp-content/uploads/2026/01/TERMINOS-Y-CONDICIONES.pdf"
               target="_blank"
               rel="noopener noreferrer">
               términos y condiciones
            </a>
            *
        </span>
    </label>
</div>


        <button class="udpq-btn udpq-btn--big udpq-btn--primary" type="submit">
            <?php _e('Ir a pagar', 'ud-paquetes'); ?>
        </button>
    </form>
    <?php
}
?>
    <!-- Sticky CTA (mobile) -->
    <?php if ($active === '1'): ?>
    <div class="udpq-sticky-cta" aria-hidden="false">
        <button type="button" class="udpq-btn udpq-btn--sticky" data-udpq-open-reserva>
            <i class="fa-solid fa-calendar-check"></i> <?php _e('Reservar', 'ud-paquetes'); ?>
        </button>
    </div>
    <?php endif; ?>

