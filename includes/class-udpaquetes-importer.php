<?php
/**
 * Importador masivo de Paquetes (ud_paquete)
 *
 * - Acepta CSV y XLSX
 * - Mapea columnas del Excel "Documento.xlsx" a los meta keys actuales
 * - Crea o actualiza paquetes por "hash" (destino + fechas + hotel)
 * - Mantiene el plugin simple: sin librerías pesadas
 *
 * Seguridad:
 * - Solo usuarios con manage_options
 * - Nonce en el formulario
 */

if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_Importer {

    const PAGE_SLUG = 'udpq-import';
    const NONCE_ACTION = 'udpq_import_action';
    const NONCE_NAME = 'udpq_import_nonce';

    // Guardamos un hash para poder actualizar sin duplicar
    const META_IMPORT_HASH = '_udpq_import_hash';

    // Transient para confirmar importación luego de la vista previa
    const TRANSIENT_PREFIX = 'udpq_import_';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);

        // Descarga de plantilla (CSV/XLSX)
        add_action('admin_post_udpq_export_template', [__CLASS__, 'handle_export_template']);

        // Exportación de paquetes existentes (CSV) para edición masiva
        add_action('admin_post_udpq_export_packages', [__CLASS__, 'handle_export_packages']);
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=' . UDPAQUETES_CPT::POST_TYPE,
            __('Importar paquetes', 'ud-paquetes'),
            __('Importar', 'ud-paquetes'),
            'manage_options',
            self::PAGE_SLUG,
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page() {
        if ( ! current_user_can('manage_options') ) {
            wp_die(__('No tenés permisos para acceder a esta sección.', 'ud-paquetes'));
        }

        $result = null;
        $preview = null;

        // 1) Vista previa (sube el archivo y muestra cómo se va a importar)
        if ( isset($_POST['udpq_preview_submit']) ) {
            check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);
            $preview = self::handle_preview();
        }

        // 2) Confirmación (importa usando el token guardado en transient)
        if ( isset($_POST['udpq_import_confirm']) ) {
            check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);
            $result = self::handle_token_import();
        }

        // 3) Modo legacy (importar directo desde upload)
        if ( isset($_POST['udpq_import_submit']) ) {
            check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);
            $result = self::handle_upload_and_import();
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Importar paquetes (CSV / XLSX)', 'ud-paquetes'); ?></h1>

            <p style="max-width: 980px;">
                <?php esc_html_e('Subí un archivo CSV o XLSX con tus paquetes. Incluye una columna "ID PAQUETE" para asignar un SKU (si se deja vacío, el sistema puede autogenerarlo). También podés incluir "URL IMAGEN" para asignar imagen destacada.', 'ud-paquetes'); ?>
            </p>

            <p>
                <a class="button" href="<?php echo esc_url( admin_url('admin-post.php?action=udpq_export_template&format=csv') ); ?>"><?php esc_html_e('Descargar plantilla CSV', 'ud-paquetes'); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url('admin-post.php?action=udpq_export_template&format=xlsx') ); ?>"><?php esc_html_e('Descargar plantilla XLSX', 'ud-paquetes'); ?></a>
            </p>

            <h2 style="margin-top: 18px;"><?php esc_html_e('Exportar para edición masiva', 'ud-paquetes'); ?></h2>
            <form method="get" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:14px;max-width:980px;">
                <input type="hidden" name="action" value="udpq_export_packages">
                <input type="hidden" name="format" value="csv">

                <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Estado', 'ud-paquetes'); ?></label>
                        <select name="status">
                            <option value="any"><?php esc_html_e('Todos', 'ud-paquetes'); ?></option>
                            <option value="publish"><?php esc_html_e('Publicado', 'ud-paquetes'); ?></option>
                            <option value="draft"><?php esc_html_e('Borrador', 'ud-paquetes'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Filtrar por DESTINO (opcional)', 'ud-paquetes'); ?></label>
                        <input type="text" name="destino" value="" placeholder="Ej: Punta Cana" style="min-width:280px;">
                    </div>

                    <div>
                        <button class="button button-primary" type="submit"><?php esc_html_e('Exportar CSV', 'ud-paquetes'); ?></button>
                    </div>
                </div>
                <p class="description" style="margin:10px 0 0;"><?php esc_html_e('Tip: exportá, editá en Excel/Sheets y reimportá en modo “Crear o actualizar”.', 'ud-paquetes'); ?></p>
            </form>

            <p class="description" style="max-width: 980px;">
                <?php esc_html_e('Tip: exportá tus paquetes, editá el CSV en Excel/Sheets y volvé a importarlo en modo “Crear o actualizar”. Si “ID PAQUETE” coincide, el sistema actualiza sin duplicar.', 'ud-paquetes'); ?>
            </p>

            <?php if ( is_array($result) ) : ?>
                <div class="notice notice-<?php echo esc_attr($result['ok'] ? 'success' : 'error'); ?>">
                    <p>
                        <strong><?php echo esc_html($result['message']); ?></strong>
                        <?php if (!empty($result['details'])): ?>
                            <br><?php echo wp_kses_post($result['details']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <hr>

            <?php if ( is_array($preview) && ! empty($preview['token']) ) : ?>
                <h2><?php esc_html_e('Vista previa', 'ud-paquetes'); ?></h2>

                <p>
                    <?php
                        echo esc_html( sprintf(
                            'Se van a crear: %d / actualizar: %d / omitir: %d / errores: %d',
                            intval($preview['counts']['create']),
                            intval($preview['counts']['update']),
                            intval($preview['counts']['skip']),
                            intval($preview['counts']['error'])
                        ) );
                    ?>
                </p>

                <?php if ( ! empty($preview['sample']) ) : ?>
                    <div style="max-width: 1100px; overflow:auto; background:#fff; border:1px solid #ddd; border-radius:8px;">
                        <table class="widefat striped" style="min-width: 980px;">
                            <thead>
                                <tr>
                                    <?php foreach ($preview['sample_headers'] as $h): ?>
                                        <th><?php echo esc_html($h); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preview['sample'] as $r): ?>
                                    <tr>
                                        <?php foreach ($preview['sample_headers'] as $h): ?>
                                            <td><?php echo esc_html( isset($r[$h]) ? (string)$r[$h] : '' ); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                    <input type="hidden" name="udpq_import_token" value="<?php echo esc_attr($preview['token']); ?>">
                    <input type="hidden" name="udpq_import_mode" value="<?php echo esc_attr($preview['mode']); ?>">
                    <input type="hidden" name="udpq_import_status" value="<?php echo esc_attr($preview['status']); ?>">
                    <input type="hidden" name="udpq_import_overwrite_image" value="<?php echo !empty($preview['overwrite_image']) ? '1' : '0'; ?>">
                    <?php submit_button(__('Importar ahora', 'ud-paquetes'), 'primary', 'udpq_import_confirm'); ?>
                </form>

                <hr>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="udpq_import_file"><?php esc_html_e('Archivo', 'ud-paquetes'); ?></label></th>
                        <td>
                            <input type="file" id="udpq_import_file" name="udpq_import_file" accept=".csv,.xlsx" required>
                            <p class="description"><?php esc_html_e('Formatos soportados: .csv, .xlsx', 'ud-paquetes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Modo', 'ud-paquetes'); ?></th>
                        <td>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" name="udpq_import_mode" value="upsert" checked>
                                <?php esc_html_e('Crear o actualizar (recomendado)', 'ud-paquetes'); ?>
                            </label>
                            <label style="display:block;">
                                <input type="radio" name="udpq_import_mode" value="create">
                                <?php esc_html_e('Crear siempre (puede duplicar)', 'ud-paquetes'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('En modo recomendado, si un paquete ya existe (por hash), se actualiza en lugar de crear otro.', 'ud-paquetes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Estado', 'ud-paquetes'); ?></th>
                        <td>
                            <select name="udpq_import_status">
                                <option value="draft" selected><?php esc_html_e('Borrador', 'ud-paquetes'); ?></option>
                                <option value="publish"><?php esc_html_e('Publicado', 'ud-paquetes'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Recomendado: importar como Borrador y revisar.', 'ud-paquetes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Imágenes', 'ud-paquetes'); ?></th>
                        <td>
                            <label style="display:flex;gap:8px;align-items:center;">
                                <input type="checkbox" name="udpq_import_overwrite_image" value="1">
                                <span><?php esc_html_e('Pisar imagen destacada si viene URL IMAGEN (aunque ya tenga una)', 'ud-paquetes'); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e('Por defecto NO se pisa para evitar reemplazos accidentales.', 'ud-paquetes'); ?></p>
                        </td>
                    </tr>
                </table>

                <div style="display:flex; gap:10px; align-items:center;">
                    <?php submit_button(__('Vista previa', 'ud-paquetes'), 'secondary', 'udpq_preview_submit', false); ?>
                    <?php submit_button(__('Importar directo', 'ud-paquetes'), 'primary', 'udpq_import_submit', false); ?>
                </div>
            </form>

            <hr>
            <h2><?php esc_html_e('Sugerencia de formato', 'ud-paquetes'); ?></h2>
            <p style="max-width:980px;">
                <?php esc_html_e('Encabezados recomendados: ID PAQUETE, SALIDA vuelo, DESTINO, MP, COMPANIA AEREA, NOCHES EN DESTINO, VALOR AEREO (desde), SALIDA AEREO, REGRESO AEREO, SALIDA, REGRESO, EQUIPAJE, nombre del hotel, regimen, seguro y traslados, ACTIVO, base doble, base triple, base single, base family, INFANTE, URL IMAGEN, INFORMACION (texto libre).', 'ud-paquetes'); ?>
            </p>
        </div>
        <?php
    }

    private static function handle_upload_and_import() {
        if ( empty($_FILES['udpq_import_file']) || ! isset($_FILES['udpq_import_file']['tmp_name']) ) {
            return [
                'ok' => false,
                'message' => __('No se recibió el archivo.', 'ud-paquetes'),
                'details' => ''
            ];
        }

        $file = $_FILES['udpq_import_file'];
        if ( ! empty($file['error']) ) {
            return [
                'ok' => false,
                'message' => __('Error al subir el archivo.', 'ud-paquetes'),
                'details' => 'PHP upload error: ' . intval($file['error'])
            ];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tmp = $file['tmp_name'];

        $rows = [];
        if ($ext === 'csv') {
            $rows = self::parse_csv($tmp);
        } elseif ($ext === 'xlsx') {
            $rows = self::parse_xlsx($tmp);
        } else {
            return [
                'ok' => false,
                'message' => __('Formato no soportado. Usá CSV o XLSX.', 'ud-paquetes'),
                'details' => ''
            ];
        }

        if ( empty($rows) || count($rows) < 2 ) {
            return [
                'ok' => false,
                'message' => __('El archivo no tiene datos suficientes.', 'ud-paquetes'),
                'details' => __('Asegurate de que tenga encabezados y al menos 1 fila de datos.', 'ud-paquetes')
            ];
        }

        $mode = isset($_POST['udpq_import_mode']) ? sanitize_text_field($_POST['udpq_import_mode']) : 'upsert';
        $status = isset($_POST['udpq_import_status']) ? sanitize_key($_POST['udpq_import_status']) : 'draft';
        if ( ! in_array($status, ['draft','publish'], true) ) $status = 'draft';
        $overwrite_image = !empty($_POST['udpq_import_overwrite_image']);

        // Procesar filas usando función reutilizable
        return self::process_import_rows($rows, $mode, $status, $overwrite_image);
    }

    /**
     * Vista previa: sube el archivo a uploads y deja un token por 1 hora.
     * Muestra un muestreo (primeras filas) y conteo create/update.
     */
    private static function handle_preview() {
        if ( empty($_FILES['udpq_import_file']) || ! isset($_FILES['udpq_import_file']['tmp_name']) ) {
            return [
                'token' => '',
                'counts' => ['create'=>0,'update'=>0,'skip'=>0,'error'=>1],
                'sample_headers' => [],
                'sample' => [],
                'mode' => 'upsert',
                'status' => 'draft',
            ];
        }

        $mode = isset($_POST['udpq_import_mode']) ? sanitize_text_field($_POST['udpq_import_mode']) : 'upsert';
        $status = isset($_POST['udpq_import_status']) ? sanitize_key($_POST['udpq_import_status']) : 'draft';
        if ( ! in_array($status, ['draft','publish'], true) ) $status = 'draft';

        // Guardamos el archivo en uploads (carpeta udpq-import)
        $file = $_FILES['udpq_import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, ['csv','xlsx'], true) ) {
            return [
                'token' => '',
                'counts' => ['create'=>0,'update'=>0,'skip'=>0,'error'=>1],
                'sample_headers' => [],
                'sample' => [],
                'mode' => $mode,
                'status' => $status,
            ];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $overrides = ['test_form' => false, 'mimes' => [
            'csv'  => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]];

        // Forzamos subdir
        add_filter('upload_dir', [__CLASS__, 'filter_upload_dir']);
        $uploaded = wp_handle_upload($file, $overrides);
        remove_filter('upload_dir', [__CLASS__, 'filter_upload_dir']);

        if ( empty($uploaded['file']) || ! file_exists($uploaded['file']) ) {
            return [
                'token' => '',
                'counts' => ['create'=>0,'update'=>0,'skip'=>0,'error'=>1],
                'sample_headers' => [],
                'sample' => [],
                'mode' => $mode,
                'status' => $status,
            ];
        }

        $path = $uploaded['file'];
        $rows = ($ext === 'csv') ? self::parse_csv($path) : self::parse_xlsx($path);
        if ( empty($rows) || count($rows) < 2 ) {
            @unlink($path);
            return [
                'token' => '',
                'counts' => ['create'=>0,'update'=>0,'skip'=>0,'error'=>1],
                'sample_headers' => [],
                'sample' => [],
                'mode' => $mode,
                'status' => $status,
            ];
        }

        $headers = array_shift($rows);
        $map = self::build_header_map($headers);

        $counts = ['create'=>0,'update'=>0,'skip'=>0,'error'=>0];
        $sample = [];

        foreach ($rows as $i => $row) {
            $destino = trim((string) self::get_cell($row, $map, ['destino','DESTINO']));
            if ($destino === '') { $counts['skip']++; continue; }

            $hotel = trim((string) self::get_cell($row, $map, ['hotel','nombre del hotel','NOMBRE DEL HOTEL']));
            $fecha_salida = self::normalize_date(self::get_cell($row, $map, ['fecha_salida','SALIDA']));
            $fecha_regreso = self::normalize_date(self::get_cell($row, $map, ['fecha_regreso','REGRESO']));
            $hash = md5(strtolower($destino . '|' . $fecha_salida . '|' . $fecha_regreso . '|' . $hotel));

            $sku = trim((string) self::get_cell($row, $map, ['id paquete','ID PAQUETE','sku','SKU','id_paquete']));
            $sku = udpq_sanitize_text($sku);

            $existing = 0;
            if ($mode === 'upsert') {
                $existing = self::find_existing_post($sku, $hash);
            }

            if ($existing) $counts['update']++; else $counts['create']++;

            // Sample: limitamos a 5 filas
            if (count($sample) < 5) {
                $sample[] = [
                    'Acción' => $existing ? 'Actualizar' : 'Crear',
                    'ID PAQUETE' => $sku,
                    'DESTINO' => $destino,
                    'SALIDA' => $fecha_salida,
                    'REGRESO' => $fecha_regreso,
                    'Hotel' => $hotel,
                ];
            }
        }

        $token = wp_generate_uuid4();
        set_transient(self::TRANSIENT_PREFIX . $token, [
            'path' => $path,
            'ext' => $ext,
            'created_at' => time(),
        ], HOUR_IN_SECONDS);

        $sample_headers = ['Acción','ID PAQUETE','DESTINO','SALIDA','REGRESO','Hotel'];

        return [
            'token' => $token,
            'counts' => $counts,
            'sample_headers' => $sample_headers,
            'sample' => $sample,
            'mode' => $mode,
            'status' => $status,
        ];
    }

    /** Importa usando el token de la vista previa. */
    private static function handle_token_import() {
        $token = isset($_POST['udpq_import_token']) ? sanitize_text_field($_POST['udpq_import_token']) : '';
        if ($token === '') {
            return [ 'ok'=>false, 'message'=>__('Token inválido.', 'ud-paquetes'), 'details'=>'' ];
        }
        $data = get_transient(self::TRANSIENT_PREFIX . $token);
        if ( empty($data['path']) || ! file_exists($data['path']) ) {
            return [ 'ok'=>false, 'message'=>__('No se encontró el archivo del token (puede haber expirado).', 'ud-paquetes'), 'details'=>'' ];
        }

        // Recuperamos modo y estado del POST (ya fueron validados en preview)
        $mode = isset($_POST['udpq_import_mode']) ? sanitize_text_field($_POST['udpq_import_mode']) : 'upsert';
        $status = isset($_POST['udpq_import_status']) ? sanitize_key($_POST['udpq_import_status']) : 'draft';
        if ( ! in_array($status, ['draft','publish'], true) ) $status = 'draft';
        $overwrite_image = isset($_POST['udpq_import_overwrite_image']) ? !empty($_POST['udpq_import_overwrite_image']) : false;

        // Parseamos el archivo del token
        $ext = !empty($data['ext']) ? $data['ext'] : strtolower(pathinfo($data['path'], PATHINFO_EXTENSION));
        $rows = ($ext === 'csv') ? self::parse_csv($data['path']) : self::parse_xlsx($data['path']);
        if ( empty($rows) || count($rows) < 2 ) {
            delete_transient(self::TRANSIENT_PREFIX . $token);
            return [ 'ok'=>false, 'message'=>__('El archivo del token no tiene datos suficientes.', 'ud-paquetes'), 'details'=>'' ];
        }

        // Usamos la función reutilizable de procesamiento
        $result = self::process_import_rows($rows, $mode, $status, $overwrite_image);

        // Limpieza
        delete_transient(self::TRANSIENT_PREFIX . $token);

        return $result;
    }

    /**
     * Procesa filas de importación y guarda los posts con metadatos.
     * Esta es la función central que garantiza que los datos se guarden correctamente.
     * 
     * @param array $rows Array de filas (primera debe ser headers)
     * @param string $mode 'upsert' o 'create'
     * @param string $status 'draft' o 'publish'
     * @param bool $overwrite_image Si pisar imagen existente
     * @return array Resultado con 'ok', 'message', 'details'
     */
    private static function process_import_rows($rows, $mode = 'upsert', $status = 'draft', $overwrite_image = false) {
        if ( empty($rows) || count($rows) < 2 ) {
            return [
                'ok' => false,
                'message' => __('No hay datos para procesar.', 'ud-paquetes'),
                'details' => ''
            ];
        }

        // Primera fila = headers
        $headers = array_shift($rows);
        $map = self::build_header_map($headers);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $i => $row) {
            // Skip filas vacías (sin DESTINO)
            $destino = trim((string) self::get_cell($row, $map, ['destino','DESTINO']));
            if ($destino === '') {
                $skipped++;
                continue;
            }

            // SKU / ID PAQUETE (opcional pero recomendado)
            $sku = trim((string) self::get_cell($row, $map, ['id paquete','ID PAQUETE','sku','SKU','id_paquete']));
            $sku = udpq_sanitize_text($sku);

            $salida_txt = trim((string) self::get_cell($row, $map, ['salida_vuelo','SALIDA vuelo','SALIDA']));
            $salida_aereo = trim((string) self::get_cell($row, $map, ['salida_aereo','SALIDA AEREO','SALIDA AÉREO']));
            $regreso_aereo = trim((string) self::get_cell($row, $map, ['regreso_aereo','REGRESO AEREO','REGRESO AÉREO']));
            $hotel = trim((string) self::get_cell($row, $map, ['hotel','nombre del hotel','NOMBRE DEL HOTEL']));
            $fecha_salida = self::normalize_date(self::get_cell($row, $map, ['fecha_salida','SALIDA']));
            $fecha_regreso = self::normalize_date(self::get_cell($row, $map, ['fecha_regreso','REGRESO']));

            // Título sugerido (editable luego)
            $title_parts = array_filter([
                $destino,
                $hotel,
                $fecha_salida ? date_i18n('d/m/Y', strtotime($fecha_salida)) : null,
            ]);
            $post_title = implode(' - ', $title_parts);
            if ($post_title === '') $post_title = $destino;

            // Hash para evitar duplicados (modo upsert)
            $hash = md5(strtolower($destino . '|' . $fecha_salida . '|' . $fecha_regreso . '|' . $hotel));
            $post_id = 0;

            if ($mode === 'upsert') {
                $existing = self::find_existing_post($sku, $hash);
                if ($existing) {
                    $post_id = $existing;
                    $updated++;
                }
            }

            if (!$post_id) {
                $post_id = wp_insert_post([
                    'post_type' => UDPAQUETES_CPT::POST_TYPE,
                    'post_status' => $status,
                    'post_title' => $post_title,
                ], true);

                if ( is_wp_error($post_id) ) {
                    $errors++;
                    continue;
                }
                $created++;
            }

            // SKU: si no vino, autogeneramos uno simple
            if ($sku === '') {
                $sku = self::generate_sku($post_id);
            }
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_SKU, $sku);

            // Guardamos hash
            update_post_meta($post_id, self::META_IMPORT_HASH, $hash);

            $active_raw = trim((string) self::get_cell($row, $map, ['activo', 'ACTIVO', 'estado', 'ESTADO']));
            $active_value = null;
            if ($active_raw !== '') {
                $active_norm = strtolower($active_raw);
                $active_value = in_array($active_norm, ['1', 'si', 'sí', 'activo', 'active', 'yes'], true) ? '1' : '0';
            }

            // Seteamos activo
            if ($active_value === null) {
                $current_active = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, true);
                $active_value = ($current_active !== '') ? $current_active : '1';
            }
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, $active_value);

            // Mapeo de campos
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_VUELO, $salida_txt);
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_DESTINO, $destino);
            UDPAQUETES_Metaboxes::sync_destino_taxonomy($post_id, $destino);
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_AEREO, $salida_aereo);
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO_AEREO, $regreso_aereo);

            $mp = trim((string) self::get_cell($row, $map, ['mp','MP','LINK MP']));
            if ($mp !== '') {
                update_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, esc_url_raw($mp));
            } else {
                delete_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE);
            }

            $compania = trim((string) self::get_cell($row, $map, ['compania','COMPANIA AEREA','COMPAÑIA AEREA']));
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_COMPANIA, $compania);

            $noches = self::normalize_number(self::get_cell($row, $map, ['noches','NOCHES EN DESTINO']));
            if ($noches !== '') update_post_meta($post_id, UDPAQUETES_Metaboxes::META_NOCHES, $noches);

            $valor_aereo = self::normalize_number(self::get_cell($row, $map, ['valor_aereo','VALOR AEREO']));
            if ($valor_aereo !== '') update_post_meta($post_id, UDPAQUETES_Metaboxes::META_VALOR_AEREO, $valor_aereo);

            if ($fecha_salida) update_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA, $fecha_salida);
            if ($fecha_regreso) update_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO, $fecha_regreso);

            $equipaje = trim((string) self::get_cell($row, $map, ['equipaje','EQUIPAJE']));
            if ($equipaje !== '') update_post_meta($post_id, UDPAQUETES_Metaboxes::META_EQUIPAJE, $equipaje);

            if ($hotel !== '') update_post_meta($post_id, UDPAQUETES_Metaboxes::META_HOTEL, $hotel);

            $regimen = trim((string) self::get_cell($row, $map, ['regimen','régimen','REGIMEN']));
            if ($regimen !== '') update_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGIMEN, $regimen);

            $seguro = trim((string) self::get_cell($row, $map, ['seguro','seguro y traslados','SEGURO Y TRASLADOS']));
            $seguro_bool = (strtolower($seguro) === 'si' || strtolower($seguro) === 'sí' || strtolower($seguro) === '1');
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_SEGURO_TRASLADOS, $seguro_bool ? '1' : '');

            // Información (texto libre)
            $info_extra = trim((string) self::get_cell($row, $map, [
                'informacion',
                'INFORMACION',
                'información',
                'INFORMACIÓN',
                'informacion (texto libre)',
                'INFORMACION (texto libre)',
                'info',
                'INFO',
            ]));
            if ($info_extra !== '') {
                update_post_meta($post_id, UDPAQUETES_Metaboxes::META_INFO_EXTRA, sanitize_textarea_field($info_extra));
            }

            // Opciones de reserva (PRECIOS)
            // Lógica: si no viene base_doble pero hay otros bases, calcular automáticamente
            $opts = [];
            $price_map = [
                'base_doble'  => ['base doble','BASE DOBLE'],
                'base_triple' => ['base triple','BASE TRIPLE'],
                'base_single' => ['base single','BASE SINGLE'],
                'base_family' => ['base family','BASE FAMILY'],
                'infante'     => ['INFANTE','infante'],
            ];

            $defaults = [
                ['key'=>'base_doble',  'label'=>'Base doble'],
                ['key'=>'base_triple', 'label'=>'Base triple'],
                ['key'=>'base_single', 'label'=>'Base single'],
                ['key'=>'base_family', 'label'=>'Base family'],
                ['key'=>'infante',     'label'=>'Infante'],
            ];

            // Primero, recopilamos todos los precios
            $prices_raw = [];
            foreach ($defaults as $d) {
                $k = $d['key'];
                $price_raw = self::get_cell($row, $map, $price_map[$k]);
                $price = self::normalize_number($price_raw);
                $prices_raw[$k] = $price;
            }

            // Si no hay base_doble pero hay base_triple, calcular: base_doble = 2 × base_triple
            if (($prices_raw['base_doble'] === '' || $prices_raw['base_doble'] === null) && 
                ($prices_raw['base_triple'] !== '' && $prices_raw['base_triple'] !== null)) {
                $prices_raw['base_doble'] = (string) (floatval($prices_raw['base_triple']) * 2);
            }

            // Construir opciones finales
            foreach ($defaults as $d) {
                $k = $d['key'];
                $price = $prices_raw[$k];

                // Activo si hay precio
                $opts[] = [
                    'key' => $k,
                    'label' => $d['label'],
                    'active' => ($price !== '' ? 1 : 0),
                    'price' => $price,
                ];
            }
            update_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, $opts);

            // Imagen destacada (opcional): URL IMAGEN
            $img_url = trim((string) self::get_cell($row, $map, ['url imagen','URL IMAGEN','imagen','IMAGEN','image','URL_IMAGEN']));
            if ($img_url !== '') {
                self::maybe_set_featured_image($post_id, $img_url, $overwrite_image);
            }
        }

        $msg = sprintf(
            __('Importación finalizada. Creados: %d. Actualizados: %d. Omitidos: %d. Errores: %d.', 'ud-paquetes'),
            $created, $updated, $skipped, $errors
        );

        return [
            'ok' => true,
            'message' => $msg,
            'details' => ''
        ];
    }

    /**
     * Subdirectorio de uploads para archivos de importación.
     */
    public static function filter_upload_dir($dirs) {
        $subdir = '/udpq-import';
        $dirs['subdir'] = $subdir;
        $dirs['path'] = $dirs['basedir'] . $subdir;
        $dirs['url']  = $dirs['baseurl'] . $subdir;
        if ( ! file_exists($dirs['path']) ) {
            wp_mkdir_p($dirs['path']);
        }
        return $dirs;
    }

    /** Genera un SKU básico a partir del ID del post. */
    private static function generate_sku($post_id) {
        $num = intval($post_id);
        if ($num <= 0) return '';
        return 'PAQ-' . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }

    /** Busca paquete por SKU (meta _udpq_sku). */
    private static function find_post_by_sku($sku) {
        $sku = udpq_sanitize_text($sku);
        if ($sku === '') return 0;
        $q = new WP_Query([
            'post_type' => UDPAQUETES_CPT::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_key' => UDPAQUETES_Metaboxes::META_SKU,
            'meta_value' => $sku,
            'fields' => 'ids',
        ]);
        if (!empty($q->posts)) return intval($q->posts[0]);
        return 0;
    }

    private static function find_existing_post($sku, $hash) {
        $existing = 0;
        $sku = udpq_sanitize_text($sku);

        if ($sku !== '') {
            $existing = self::find_post_by_sku($sku);

            if (!$existing && ctype_digit($sku)) {
                $post_id = intval($sku);
                $post = get_post($post_id);
                if ($post && $post->post_type === UDPAQUETES_CPT::POST_TYPE) {
                    $existing = $post_id;
                }
            }
        }

        if (!$existing && $hash !== '') {
            $existing = self::find_post_by_hash($hash);
        }

        return $existing;
    }

    /**
     * Descarga plantilla de importación.
     * - CSV: generado al vuelo
     * - XLSX: si existe en /samples, se envía tal cual (para evitar librerías PHP)
     */
    public static function handle_export_template() {
        if ( ! current_user_can('manage_options') ) {
            wp_die(__('No tenés permisos.', 'ud-paquetes'));
        }

        $format = isset($_GET['format']) ? sanitize_key($_GET['format']) : 'csv';

        $headers = [
            'ID PAQUETE',
            'SALIDA vuelo',
            'DESTINO',
            'MP',
            'COMPANIA AEREA',
            'NOCHES EN DESTINO',
            'VALOR AEREO (desde)',
            'SALIDA AEREO',
            'REGRESO AEREO',
            'SALIDA',
            'REGRESO',
            'EQUIPAJE',
            'nombre del hotel',
            'regimen',
            'seguro y traslados',
            'ACTIVO',
            'base doble',
            'base triple',
            'base single',
            'base family',
            'INFANTE',
            'URL IMAGEN',
            'INFORMACION (texto libre)',
        ];

        if ($format === 'xlsx') {
            $sample_path = UDPQ_PATH . 'samples/udpq-import-template.xlsx';
            if (file_exists($sample_path)) {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="udpq-import-template.xlsx"');
                header('Content-Length: ' . filesize($sample_path));
                readfile($sample_path);
                exit;
            }
            // fallback a CSV si no existe
            $format = 'csv';
        }

        // CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="udpq-import-template.csv"');
        $out = fopen('php://output', 'w');
        // Excel friendly UTF-8 BOM
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');
        // Fila ejemplo (vacía) para que Excel mantenga columnas
        $example = array_fill(0, count($headers), '');
        fputcsv($out, $example, ';');
        fclose($out);
        exit;
    }

    /**
     * Exporta paquetes existentes a CSV para edición masiva.
     *
     * Columnas: las mismas que la plantilla de importación.
     *
     * Nota: usamos CSV con delimitador ";" + BOM UTF-8 para compatibilidad con Excel.
     */
    public static function handle_export_packages() {
        if ( ! current_user_can('manage_options') ) {
            wp_die(__('No tenés permisos.', 'ud-paquetes'));
        }

        $format = isset($_GET['format']) ? sanitize_key($_GET['format']) : 'csv';
        if ($format !== 'csv') {
            // Por simplicidad (sin dependencias), exportamos siempre CSV.
            $format = 'csv';
        }

        // Filtros opcionales (UI en la página)
        $post_status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'any';
        if ( ! in_array($post_status, ['any','publish','draft'], true) ) {
            $post_status = 'any';
        }
        $destino_filter = isset($_GET['destino']) ? sanitize_text_field(wp_unslash($_GET['destino'])) : '';
        $destino_filter = trim($destino_filter);

        $headers = [
            'ID PAQUETE',
            'SALIDA vuelo',
            'DESTINO',
            'MP',
            'COMPANIA AEREA',
            'NOCHES EN DESTINO',
            'VALOR AEREO (desde)',
            'SALIDA AEREO',
            'REGRESO AEREO',
            'SALIDA',
            'REGRESO',
            'EQUIPAJE',
            'nombre del hotel',
            'regimen',
            'seguro y traslados',
            'ACTIVO',
            'base doble',
            'base triple',
            'base single',
            'base family',
            'INFANTE',
            'URL IMAGEN',
            'INFORMACION (texto libre)',
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="udpq-export-paquetes.csv"');

        $out = fopen('php://output', 'w');
        // Excel friendly UTF-8 BOM
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');

        $paged = 1;
        $per_page = 200;

        do {
            $q = new WP_Query([
                'post_type' => UDPAQUETES_CPT::POST_TYPE,
                'post_status' => $post_status,
                'posts_per_page' => $per_page,
                'paged' => $paged,
                'fields' => 'ids',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);

            if (empty($q->posts)) {
                break;
            }

            foreach ($q->posts as $post_id) {
                $post_id = intval($post_id);

                // Meta principales
                $sku          = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SKU, true);
                $salida_vuelo = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_VUELO, true);
                $destino      = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_DESTINO, true);

                // Filtrar por destino (match parcial, case-insensitive)
                if ($destino_filter !== '' && stripos($destino, $destino_filter) === false) {
                    continue;
                }
                $mp           = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_LINK_PAQUETE, true);
                $compania     = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_COMPANIA, true);
                $noches       = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_NOCHES, true);
                $valor_aereo  = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_VALOR_AEREO, true);
                $salida_aereo = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA_AEREO, true);
                $regreso_aereo = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO_AEREO, true);
                $salida       = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SALIDA, true);
                $regreso      = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGRESO, true);
                $equipaje     = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_EQUIPAJE, true);
                $hotel        = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_HOTEL, true);
                $regimen      = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_REGIMEN, true);
                $seguro       = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_SEGURO_TRASLADOS, true);
                $info_extra   = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_INFO_EXTRA, true);
                $activo       = (string) get_post_meta($post_id, UDPAQUETES_Metaboxes::META_ACTIVE, true);

                // Opciones de precio
                $prices = [
                    'base_doble'  => '',
                    'base_triple' => '',
                    'base_single' => '',
                    'base_family' => '',
                    'infante'     => '',
                ];

                $opts = get_post_meta($post_id, UDPAQUETES_Metaboxes::META_PRICE_OPTIONS, true);
                if (is_array($opts)) {
                    foreach ($opts as $row) {
                        if (!is_array($row)) continue;
                        $k = isset($row['key']) ? (string)$row['key'] : '';
                        if ($k === '' || !array_key_exists($k, $prices)) continue;
                        $price = isset($row['price']) ? (string)$row['price'] : '';
                        // Exportamos el precio aunque no esté activo, para que sea editable.
                        $prices[$k] = $price;
                    }
                }

                $img_url = '';
                $thumb = get_the_post_thumbnail_url($post_id, 'full');
                if (is_string($thumb) && $thumb !== '') {
                    $img_url = $thumb;
                }

                // Si no tiene SKU guardado, exportamos uno estable basado en el ID
                if (trim($sku) === '') {
                    $sku = self::generate_sku($post_id);
                }

                $row = [
                    $sku,
                    $salida_vuelo,
                    $destino,
                    $mp,
                    $compania,
                    $noches,
                    $valor_aereo,
                    $salida_aereo,
                    $regreso_aereo,
                    $salida,
                    $regreso,
                    $equipaje,
                    $hotel,
                    $regimen,
                    (!empty($seguro) ? 'SI' : ''),
                    (!empty($activo) ? 'SI' : 'NO'),
                    $prices['base_doble'],
                    $prices['base_triple'],
                    $prices['base_single'],
                    $prices['base_family'],
                    $prices['infante'],
                    $img_url,
                    $info_extra,
                ];

                fputcsv($out, $row, ';');
            }

            $paged++;
            wp_reset_postdata();
        } while (true);

        fclose($out);
        exit;
    }

    /** Asigna imagen destacada usando URL externa (sin duplicar). */
    private static function maybe_set_featured_image($post_id, $url, $overwrite = false) {
        $url = esc_url_raw($url);
        if ($url === '') return;

        // Si ya tiene thumbnail, no pisamos salvo que el usuario lo pida
        if (has_post_thumbnail($post_id) && !$overwrite) {
            return;
        }

        // Usar URL externa directamente sin descargar (evita duplicación)
        // Guardamos la URL como meta _external_image_url para referencia
        update_post_meta($post_id, '_udpq_external_image_url', $url);
    }

    // ----------------------
    // Parsing helpers
    // ----------------------

    private static function parse_csv($path) {
        $rows = [];
        if ( ! file_exists($path) ) return $rows;

        $fh = fopen($path, 'r');
        if (!$fh) return $rows;

        // Autodetect delimitador (coma o punto y coma)
        $first = fgets($fh);
        if ($first === false) { fclose($fh); return $rows; }
        $delim = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
        rewind($fh);

        while (($data = fgetcsv($fh, 0, $delim)) !== false) {
            // Limpiar BOM
            if (!empty($data) && is_string($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            }
            $rows[] = $data;
        }
        fclose($fh);
        return $rows;
    }

    /**
     * XLSX minimal parser (sin dependencias).
     * Lee sharedStrings y sheet1.
     */
    private static function parse_xlsx($path) {
        $rows = [];
        if ( ! class_exists('ZipArchive') ) return $rows;

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return $rows;

        // sharedStrings
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $sx = simplexml_load_string($sharedXml);
            if ($sx && isset($sx->si)) {
                foreach ($sx->si as $si) {
                    // Puede venir en <t> o múltiples <r><t>
                    if (isset($si->t)) {
                        $shared[] = (string) $si->t;
                    } else {
                        $text = '';
                        if (isset($si->r)) {
                            foreach ($si->r as $r) {
                                $text .= (string) $r->t;
                            }
                        }
                        $shared[] = $text;
                    }
                }
            }
        }

        // sheet1 (si hay más de una sheet, igual tomamos la primera)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            // fallback: buscar alguna sheet
            for ($i=1; $i<=10; $i++) {
                $sheetXml = $zip->getFromName('xl/worksheets/sheet'.$i.'.xml');
                if ($sheetXml) break;
            }
        }

        if (!$sheetXml) {
            $zip->close();
            return $rows;
        }

        $sx = simplexml_load_string($sheetXml);
        if (!$sx || !isset($sx->sheetData)) {
            $zip->close();
            return $rows;
        }

        $maxCol = 0;
        foreach ($sx->sheetData->row as $row) {
            $rowArr = [];
            foreach ($row->c as $c) {
                $r = (string) $c['r']; // e.g. A1
                $colIndex = self::xlsx_col_to_index($r);
                $maxCol = max($maxCol, $colIndex);

                $v = isset($c->v) ? (string) $c->v : '';
                $t = isset($c['t']) ? (string) $c['t'] : '';

                $value = '';
                if ($t === 's') {
                    $idx = intval($v);
                    $value = isset($shared[$idx]) ? $shared[$idx] : '';
                } else {
                    $value = $v;
                }
                $rowArr[$colIndex] = $value;
            }

            // Normalizar longitud
            $out = [];
            for ($i=0; $i<=$maxCol; $i++) {
                $out[] = isset($rowArr[$i]) ? $rowArr[$i] : '';
            }
            $rows[] = $out;
        }

        $zip->close();
        return $rows;
    }

    private static function xlsx_col_to_index($cellRef) {
        // A1 -> 0, B1 -> 1, AA1 -> 26
        if (!preg_match('/^([A-Z]+)(\d+)$/', strtoupper($cellRef), $m)) return 0;
        $letters = $m[1];
        $num = 0;
        for ($i=0; $i<strlen($letters); $i++) {
            $num = $num * 26 + (ord($letters[$i]) - 64);
        }
        return $num - 1;
    }

    private static function build_header_map($headers) {
        $map = [];
        foreach ((array)$headers as $idx => $h) {
            $key = self::norm_header($h);
            if ($key !== '') {
                $map[$key] = $idx;
            }
        }

        // Alias útiles
        // Documento.xlsx usa "SALIDA vuelo" y "MP"
        if (isset($map['salida vuelo']) && !isset($map['salida_vuelo'])) $map['salida_vuelo'] = $map['salida vuelo'];
        if (isset($map['mp']) && !isset($map['link mp'])) $map['link mp'] = $map['mp'];
        if (isset($map['compania aerea']) && !isset($map['compañia aerea'])) $map['compañia aerea'] = $map['compania aerea'];
        if (isset($map['valor aereo']) && !isset($map['valor aéreo'])) $map['valor aéreo'] = $map['valor aereo'];
        if (isset($map['regimen']) && !isset($map['régimen'])) $map['régimen'] = $map['regimen'];
        if (isset($map['salida aereo']) && !isset($map['salida aéreo'])) $map['salida aéreo'] = $map['salida aereo'];
        if (isset($map['regreso aereo']) && !isset($map['regreso aéreo'])) $map['regreso aéreo'] = $map['regreso aereo'];

        return $map;
    }

    /**
     * Devuelve el valor de una celda por lista de posibles encabezados.
     * $row = fila numérica (array)
     * $map = header_normalizado => indice
     */
    private static function get_cell($row, $map, $candidates) {
        foreach ((array)$candidates as $c) {
            $k = self::norm_header($c);
            if (isset($map[$k])) {
                $idx = intval($map[$k]);
                return isset($row[$idx]) ? $row[$idx] : '';
            }
        }
        return '';
    }

    private static function norm_header($h) {
        $h = (string) $h;
        $h = trim($h);
        $h = str_replace(["\n","\r","\t"], ' ', $h);
        $h = preg_replace('/\s+/', ' ', $h);
        $h = strtolower($h);
        return $h;
    }

    private static function normalize_number($v) {
        if ($v === null) return '';
        $s = trim((string)$v);
        if ($s === '') return '';

        // Quitar símbolos comunes
        $s = str_replace(['$', 'USD', 'ARS', '€'], '', $s);
        $s = trim($s);

        // 2.504,32 -> 2504.32
        if (preg_match('/\d+\.\d{3},\d{2}/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        // 2,504.32 -> 2504.32
        if (preg_match('/\d+,\d{3}\.\d{2}/', $s)) {
            $s = str_replace(',', '', $s);
        }
        // 2.230 -> 2230 (miles)
        if (preg_match('/^\d+\.\d{3}$/', $s)) {
            $s = str_replace('.', '', $s);
        }

        // Decimal simple con coma
        if (preg_match('/^\d+,\d+$/', $s)) {
            $s = str_replace(',', '.', $s);
        }

        // Si quedó algo no numérico, intentar extraer
        if (!is_numeric($s)) {
            if (preg_match('/([0-9]+(\.[0-9]+)?)/', $s, $m)) {
                $s = $m[1];
            } else {
                return '';
            }
        }

        // Guardamos sin decimales si es entero
        $f = floatval($s);
        if (abs($f - round($f)) < 0.00001) return (string) intval(round($f));
        return (string) $f;
    }

    private static function normalize_date($v) {
        if ($v === null) return '';
        $s = trim((string)$v);
        if ($s === '') return '';

        // Si viene como número (Excel date)
        if (is_numeric($s)) {
            $ts = self::excel_date_to_timestamp(floatval($s));
            if ($ts) return gmdate('Y-m-d', $ts);
        }

        // Si viene como YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
        // Si viene como DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', intval($m[3]), intval($m[2]), intval($m[1]));
        }

        $t = strtotime($s);
        if ($t) return gmdate('Y-m-d', $t);
        return '';
    }

    private static function excel_date_to_timestamp($excel) {
        // Excel epoch: 1899-12-30
        $unix = ($excel - 25569) * 86400;
        return (int) round($unix);
    }

    private static function find_post_by_hash($hash) {
        $q = new WP_Query([
            'post_type' => UDPAQUETES_CPT::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_key' => self::META_IMPORT_HASH,
            'meta_value' => $hash,
            'fields' => 'ids',
        ]);
        if (!empty($q->posts)) return intval($q->posts[0]);
        return 0;
    }
}
