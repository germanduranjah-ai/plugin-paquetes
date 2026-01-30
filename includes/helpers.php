<?php
if ( ! defined('ABSPATH') ) exit;

function udpq_sanitize_text($value) {
    return is_string($value) ? sanitize_text_field($value) : '';
}

function udpq_sanitize_url($value) {
    return is_string($value) ? esc_url_raw($value) : '';
}

function udpq_sanitize_int($value) {
    return is_numeric($value) ? intval($value) : 0;
}

function udpq_sanitize_email($value) {
    return is_string($value) ? sanitize_email($value) : '';
}

function udpq_sanitize_phone($value) {
    if (!is_string($value)) return '';
    // Remove all non-numeric characters except + and spaces
    $cleaned = preg_replace('/[^\d\s\+\-\(\)]/', '', $value);
    return sanitize_text_field($cleaned);
}

function udpq_validate_email($email) {
    return is_email($email) !== false;
}

function udpq_validate_required($value, $field_name = '') {
    if (is_string($value)) {
        $value = trim($value);
    }
    if (empty($value)) {
        $message = $field_name ? sprintf(__('El campo %s es obligatorio.', 'ud-paquetes'), $field_name) : __('Este campo es obligatorio.', 'ud-paquetes');
        throw new Exception($message);
    }
    return $value;
}

function udpq_validate_min_length($value, $min_length, $field_name = '') {
    if (is_string($value) && strlen(trim($value)) < $min_length) {
        $message = $field_name
            ? sprintf(__('El campo %s debe tener al menos %d caracteres.', 'ud-paquetes'), $field_name, $min_length)
            : sprintf(__('Este campo debe tener al menos %d caracteres.', 'ud-paquetes'), $min_length);
        throw new Exception($message);
    }
    return $value;
}

function udpq_validate_date($date_string, $field_name = '') {
    if (empty($date_string)) return '';

    $timestamp = strtotime($date_string);
    if ($timestamp === false) {
        $message = $field_name ? sprintf(__('El campo %s contiene una fecha inválida.', 'ud-paquetes'), $field_name) : __('Fecha inválida.', 'ud-paquetes');
        throw new Exception($message);
    }

    // Additional validation: date shouldn't be in the past for departure dates
    if ($timestamp < strtotime('today')) {
        $message = $field_name ? sprintf(__('El campo %s no puede ser una fecha pasada.', 'ud-paquetes'), $field_name) : __('La fecha no puede ser pasada.', 'ud-paquetes');
        throw new Exception($message);
    }

    return date('Y-m-d', $timestamp);
}

function udpq_format_money($amount, $currency = 'USD') {
    $amount = floatval($amount);
    // Simple format (Argentina style)
    $formatted = number_format($amount, 0, ',', '.');
    return ($currency ? $currency . ' ' : '') . $formatted;
}

/**
 * Get available currencies
 */
function udpq_get_currencies() {
    return [
        'ARS' => ['name' => 'Peso Argentino', 'symbol' => '$', 'locale' => 'es-AR'],
        'USD' => ['name' => 'Dólar Estadounidense', 'symbol' => 'USD', 'locale' => 'en-US'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'locale' => 'es-ES'],
        'BRL' => ['name' => 'Real Brasileño', 'symbol' => 'R$', 'locale' => 'pt-BR'],
        'CLP' => ['name' => 'Peso Chileno', 'symbol' => 'CLP$', 'locale' => 'es-CL'],
        'UYU' => ['name' => 'Peso Uruguayo', 'symbol' => '$U', 'locale' => 'es-UY'],
        'COP' => ['name' => 'Peso Colombiano', 'symbol' => 'COP$', 'locale' => 'es-CO'],
        'MXN' => ['name' => 'Peso Mexicano', 'symbol' => 'MX$', 'locale' => 'es-MX'],
    ];
}

/**
 * Get currency info
 */
function udpq_get_currency_info($currency_code) {
    $currencies = udpq_get_currencies();
    return isset($currencies[$currency_code]) ? $currencies[$currency_code] : $currencies['USD'];
}

/**
 * Format money with proper locale
 */
function udpq_format_money_locale($amount, $currency = 'USD') {
    $amount = floatval($amount);
    $currency_info = udpq_get_currency_info($currency);

    // Para USD, usar formato personalizado con "USD" en lugar del símbolo $
    if ($currency === 'USD') {
        return 'USD ' . number_format($amount, 0, ',', '.');
    }

    // Use locale-aware formatting
    $locale = $currency_info['locale'];
    $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0); // No decimals for most LatAm currencies

    try {
        return $formatter->formatCurrency($amount, $currency);
    } catch (Exception $e) {
        // Fallback to simple formatting
        return $currency_info['symbol'] . ' ' . number_format($amount, 0, ',', '.');
    }
}

/**
 * Convert currency using exchange rates from settings or defaults
 * Note: In production, use a real API like fixer.io or currencyapi.com
 */
function udpq_convert_currency($amount, $from_currency, $to_currency) {
    if ($from_currency === $to_currency) {
        return $amount;
    }

    $base_currency = udpq_get_base_currency();
    $saved_rates = get_option('udpq_currency_rates', []);

    // Default exchange rates (1 ARS = X units of other currency)
    $default_rates = [
        'ARS' => 1.0,      // Base currency
        'USD' => 0.0011,   // 1 ARS = ~0.0011 USD (approximate)
        'EUR' => 0.0010,   // 1 ARS = ~0.0010 EUR (approximate)
        'BRL' => 0.0059,   // 1 ARS = ~0.0059 BRL (approximate)
        'CLP' => 0.91,     // 1 ARS = ~0.91 CLP (approximate)
        'UYU' => 0.029,    // 1 ARS = ~0.029 UYU (approximate)
        'COP' => 4.35,     // 1 ARS = ~4.35 COP (approximate)
        'MXN' => 0.019,    // 1 ARS = ~0.019 MXN (approximate)
    ];

    // Merge saved rates with defaults
    $rates = array_merge($default_rates, $saved_rates);

    if (!isset($rates[$from_currency]) || !isset($rates[$to_currency])) {
        return $amount; // Return original amount if currency not supported
    }

    // Convert to base currency first, then to target currency
    $amount_in_base = $amount / $rates[$from_currency];
    $converted_amount = $amount_in_base * $rates[$to_currency];

    return round($converted_amount, 2);
}

/**
 * Get base currency from settings (USD by default)
 */
function udpq_get_base_currency() {
    return get_option('udpq_base_currency', 'USD');
}

/**
 * Set base currency
 */
function udpq_set_base_currency($currency) {
    $currencies = udpq_get_currencies();
    if (isset($currencies[$currency])) {
        update_option('udpq_base_currency', $currency);
        return true;
    }
    return false;
}

/**
 * Options repeater sanitizer
 */
function udpq_sanitize_price_options($raw) {
    $out = [];
    if (!is_array($raw)) return $out;

    foreach ($raw as $row) {
        if (!is_array($row)) continue;

        // New simplified structure: key + label + price + active
        if (isset($row['key'])) {
            $key = sanitize_text_field($row['key']);
            $label = sanitize_text_field($row['label'] ?? '');
            $price = isset($row['price']) ? floatval($row['price']) : 0;
            $active = !empty($row['active']) ? 1 : 0;

            if ($key === '' || ($price <= 0 && !$active)) {
                // keep only meaningful rows
                continue;
            }

            $out[] = [
                'key' => $key,
                'label' => $label ?: $key,
                'price' => $price,
                'active' => $active,
            ];
            continue;
        }

        // Legacy structure (kept for backwards compatibility)
        $label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
        $price = isset($row['price']) ? floatval($row['price']) : 0;
        $currency = isset($row['currency']) ? sanitize_text_field($row['currency']) : 'ARS';
        $active = !empty($row['active']) ? 1 : 0;
        $mp_url = isset($row['mp_url']) ? esc_url_raw($row['mp_url']) : '';
        $note = isset($row['note']) ? sanitize_text_field($row['note']) : '';

        if ($label === '' && $price <= 0 && $mp_url === '') continue;

        $out[] = [
            'label' => $label,
            'price' => $price,
            'currency' => $currency ?: 'USD',
            'active' => $active,
            'mp_url' => $mp_url,
            'note' => $note,
        ];
    }
    return $out;
}

/**
 * Get price from base_doble for a paquete.
 * Returns array [$price, $currency]. $price may be null.
 */
function udpq_get_min_price_and_currency($post_id) {
    $opts = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
    $currency = udpq_get_base_currency();

    // Obtener precio de base_doble
    if (is_array($opts)) {
        foreach ($opts as $row) {
            $row = is_array($row) ? $row : [];
            if (($row['key'] ?? '') === 'base_doble') {
                $price = isset($row['price']) ? floatval($row['price']) : 0;
                if ($price > 0) {
                    return [$price, $currency];
                }
                break;
            }
        }
    }

    return [null, $currency ?: 'USD'];
}

/**
 * Get paquete meta as an associative array (handy for templates).
 */
function udpq_get_paquete_meta($post_id) {
    return [
        'active' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, true),
        'destino' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_DESTINO, true),
        'salida_vuelo' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_VUELO, true),
        'compania' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_COMPANIA, true),
        'valor_aereo' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_VALOR_AEREO, true),
        'salida_aereo' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_AEREO, true),
        'regreso_aereo' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO_AEREO, true),
        'noches' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_NOCHES, true),
        'salida' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA, true),
        'regreso' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO, true),
        'equipaje' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_EQUIPAJE, true),
        'hotel' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_HOTEL, true),
        'regimen' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGIMEN, true),
        'seguro_traslados' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SEGURO_TRASLADOS, true),
        // Legacy (optional) meta keys kept as raw strings for backwards compatibility.
        // If your older content stored these, they will still be available here.
        'servicios' => get_post_meta($post_id, '_udpq_servicios', true),
        'beneficios' => get_post_meta($post_id, '_udpq_beneficios', true),
        'info_extra' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_INFO_EXTRA, true),
        'price_options' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true),
        'link_paquete' => get_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, true),
    ];
}

/**
 * Obtener todos los SKUs disponibles en los paquetes
 * Útil para debuggear la búsqueda por IDs
 */
function udpq_get_all_skus() {
    global $wpdb;
    
    $results = $wpdb->get_results(
        "SELECT post_id, meta_value as sku FROM {$wpdb->postmeta}
         WHERE meta_key = '_udpq_sku' 
         AND post_id IN (
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'ud_paquete'
         )
         ORDER BY post_id"
    );
    
    return $results;
}
