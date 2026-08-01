<?php
/**
 * Bitácora Enterprise — blocks/route-metadata-map/render.php
 * Bloque «Mapa de ruta con metadatos» (enterprise/route-metadata-map).
 * Dibuja la ruta planificada (opcional) y la registrada (track) como route-comparison
 * (reutiliza map-frontend.js vía el mismo contrato data-*), y emite el espejo completo
 * de las métricas leídas del fichero de metadatos almacenado.
 *
 * Commit 4 (#56 · Fase 2 del plan #45): render de front (mapa + estadísticas + info).
 * Los ficheros se localizan por year/month/day + assetSuffix (sufijo ANTES de la
 * extensión). El título «Perfil de elevación…» se omite deliberadamente (Commit 5).
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Utilidades de formato ─────────────────────────────────────────── */

/* Número en formato español: miles con «.», decimales con «,» (hasta 2, sin ceros
   finales). Entero → sin decimales. No numérico → ''. */
function enterprise_rmm_num( $v ) {
    if ( $v === null || $v === '' || ! is_numeric( $v ) ) return '';
    $v = (float) $v;
    if ( floor( $v ) == $v ) return number_format( $v, 0, ',', '.' );
    $s = number_format( $v, 2, ',', '.' );
    $s = rtrim( $s, '0' );
    $s = rtrim( $s, ',' );
    return $s;
}

/* Segundos → «HH h MM m SS s» (dos dígitos). No numérico → ''. */
function enterprise_rmm_hms( $sec ) {
    if ( $sec === null || $sec === '' || ! is_numeric( $sec ) ) return '';
    $sec = (int) round( (float) $sec );
    if ( $sec < 0 ) $sec = 0;
    $h = intdiv( $sec, 3600 );
    $m = intdiv( $sec % 3600, 60 );
    $s = $sec % 60;
    return sprintf( '%02dh %02dm %02ds', $h, $m, $s );
}

/* Botón «info» + tooltip para una etiqueta, SOLO si hay entrada no vacía en el mapa
   localizado (§3.10). Accesible: <button> + aria-describedby; el JS de front lo activa
   por hover, foco y tap. */
function enterprise_rmm_info_btn( $key, $info, $uid ) {
    if ( empty( $info[ $key ] ) ) return '';
    $id = $uid . '-info-' . str_replace( '.', '-', $key );
    return ' <span class="ent-rmm-info-wrap">'
        . '<button type="button" class="ent-rmm-info" aria-describedby="' . esc_attr( $id ) . '" '
        . 'aria-label="' . esc_attr__( 'Más información', 'enterprise-moto' ) . '">i</button>'
        . '<span role="tooltip" id="' . esc_attr( $id ) . '" class="ent-rmm-tip" hidden>'
        . esc_html( $info[ $key ] ) . '</span>'
        . '</span>';
}

/* Fila de tabla clave/valor, omitida si el valor está vacío. */
function enterprise_rmm_row( $label, $value, $key, $info, $uid ) {
    if ( $value === '' || $value === null ) return '';
    return '<tr><td class="k">' . esc_html( $label ) . enterprise_rmm_info_btn( $key, $info, $uid )
        . '</td><td class="v">' . esc_html( $value ) . '</td></tr>';
}

/* ── Render ────────────────────────────────────────────────────────── */

function enterprise_render_route_metadata_map_block( $attributes ) {

    if ( empty( $attributes['validated'] ) ) return ''; // sin datos → nada en front.

    /* Fecha + sufijo (para localizar los ficheros). */
    $year   = preg_replace( '/\D/', '', (string) ( isset( $attributes['year'] )  ? $attributes['year']  : '' ) );
    $month  = preg_replace( '/\D/', '', (string) ( isset( $attributes['month'] ) ? $attributes['month'] : '' ) );
    $day    = preg_replace( '/\D/', '', (string) ( isset( $attributes['day'] )   ? $attributes['day']   : '' ) );
    $month  = substr( str_pad( $month, 2, '0', STR_PAD_LEFT ), -2 );
    $day    = substr( str_pad( $day,   2, '0', STR_PAD_LEFT ), -2 );
    $suffix = (string) ( isset( $attributes['assetSuffix'] ) ? $attributes['assetSuffix'] : '' );
    if ( '' !== $suffix && ! preg_match( '/^\(\d+\)$/', $suffix ) ) $suffix = '';
    if ( ! preg_match( '/^\d{4}$/', $year ) || ! preg_match( '/^\d{2}$/', $month ) || ! preg_match( '/^\d{2}$/', $day ) ) {
        return '';
    }
    $md = $month . $day;

    $upload  = wp_upload_dir();
    if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) return '';
    $rel     = 'routes/recorded/' . $year . '/' . $month . '/';
    $basedir = trailingslashit( $upload['basedir'] ) . $rel;
    $baseurl = trailingslashit( $upload['baseurl'] ) . $rel;

    $recorded_file = $md . '_track' . $suffix . '.gpx';
    $planned_file  = $md . $suffix . '.gpx';
    $meta_file     = $md . '_metadata' . $suffix . '.json';

    $gpx_url2 = file_exists( $basedir . $recorded_file ) ? $baseurl . $recorded_file : '';
    $gpx_url1 = file_exists( $basedir . $planned_file )  ? $baseurl . $planned_file  : '';

    /* Presentación (heredada de route-comparison). */
    $gpx_label1   = isset( $attributes['gpxLabel1'] )     ? sanitize_text_field( $attributes['gpxLabel1'] )    : __( 'GPX1 — Ruta planificada', 'enterprise-moto' );
    $gpx_label2   = isset( $attributes['gpxLabel2'] )     ? sanitize_text_field( $attributes['gpxLabel2'] )    : __( 'GPX2 — Ruta realizada',   'enterprise-moto' );
    $heading      = isset( $attributes['heading'] )       ? sanitize_text_field( $attributes['heading'] )      : '';
    $map_height   = isset( $attributes['mapHeight'] )     ? sanitize_key( $attributes['mapHeight'] )           : 'md';
    $route_color1 = isset( $attributes['routeColor'] )    ? sanitize_hex_color( $attributes['routeColor'] )    : '#001f5c';
    $route_color2 = isset( $attributes['routeColor2'] )   ? sanitize_hex_color( $attributes['routeColor2'] )   : '#c0392b';
    $marker_color = isset( $attributes['markerColor'] )   ? sanitize_hex_color( $attributes['markerColor'] )   : '#f2c118';
    $route_weight = isset( $attributes['routeWeight'] )   ? intval( $attributes['routeWeight'] )               : 4;
    $show_elev    = isset( $attributes['showElevation'] ) ? (bool) $attributes['showElevation']                : true;
    $show_stats   = isset( $attributes['showStats'] )     ? (bool) $attributes['showStats']                    : true;
    $start_label  = isset( $attributes['startLabel'] )    ? sanitize_text_field( $attributes['startLabel'] )   : '';
    $end_label    = isset( $attributes['endLabel'] )      ? sanitize_text_field( $attributes['endLabel'] )     : '';
    $description  = isset( $attributes['description'] )    ? sanitize_textarea_field( $attributes['description'] ) : '';
    if ( ! $route_color1 ) $route_color1 = '#001f5c';
    if ( ! $route_color2 ) $route_color2 = '#c0392b';
    if ( ! $marker_color ) $marker_color = '#f2c118';

    /* Metadatos (lectura en servidor). */
    $meta = null;
    if ( file_exists( $basedir . $meta_file ) ) {
        $decoded = json_decode( (string) file_get_contents( $basedir . $meta_file ), true );
        if ( is_array( $decoded ) ) $meta = $decoded;
    }

    $uid = 'ent-rmm-' . wp_rand( 1000, 9999 );

    ob_start(); ?>
    <div class="ent-map-block ent-route-metadata-map" id="<?php echo esc_attr( $uid ); ?>-wrap">

        <?php if ( $heading ) : ?>
            <h2 class="ent-map-block__heading"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>

        <?php if ( $description ) : ?>
            <p style="font-size:15px;font-weight:300;color:var(--mid,#5a5a5a);line-height:1.75;margin-bottom:16px;">
                <?php echo esc_html( $description ); ?>
            </p>
        <?php endif; ?>

        <div class="ent-map ent-map--<?php echo esc_attr( $map_height ); ?>"
             id="<?php echo esc_attr( $uid ); ?>"
             data-map-type="route-comparison"
             data-gpx-url="<?php echo esc_attr( $gpx_url1 ); ?>"
             data-gpx-url2="<?php echo esc_attr( $gpx_url2 ); ?>"
             data-gpx-label1="<?php echo esc_attr( $gpx_label1 ); ?>"
             data-gpx-label2="<?php echo esc_attr( $gpx_label2 ); ?>"
             data-route-color="<?php echo esc_attr( $route_color1 ); ?>"
             data-route-color2="<?php echo esc_attr( $route_color2 ); ?>"
             data-marker-color="<?php echo esc_attr( $marker_color ); ?>"
             data-route-weight="<?php echo intval( $route_weight ); ?>"
             data-start-label="<?php echo esc_attr( $start_label ); ?>"
             data-end-label="<?php echo esc_attr( $end_label ); ?>"
             data-show-elevation="<?php echo $show_elev ? 'true' : 'false'; ?>"
             role="img"
             aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Mapa de ruta con metadatos', 'enterprise-moto' ); ?>">
        </div>

        <?php
        /* ── Perfil de elevación (interactivo; SIN título — Commit 5): pegado al mapa. ── */
        if ( $show_elev && $gpx_url2 ) : ?>
        <div class="ent-map-elevation ent-map-elevation--interactive"
             id="<?php echo esc_attr( $uid ); ?>-elev-wrap"
             style="cursor:crosshair;"
             title="<?php esc_attr_e( 'Mueve el ratón para ver la posición en el mapa', 'enterprise-moto' ); ?>">
            <canvas id="<?php echo esc_attr( $uid ); ?>-canvas"
                    data-map-uid="<?php echo esc_attr( $uid ); ?>">
            </canvas>
            <div class="ent-map-elev-cursor" id="<?php echo esc_attr( $uid ); ?>-elev-cursor"
                 style="display:none;position:absolute;top:8px;bottom:0;width:2px;background:<?php echo esc_attr( $route_color2 ); ?>;pointer-events:none;opacity:.8;"></div>
        </div>
        <?php endif; ?>

        <?php
        /* ── Espejo de estadísticas de los metadatos (debajo del mapa + perfil) ── */
        if ( $show_stats && is_array( $meta ) ) :
            $info     = enterprise_route_metadata_stat_info();
            $summary  = ( isset( $meta['summary'] )          && is_array( $meta['summary'] ) )          ? $meta['summary']          : array();
            $kin      = ( isset( $meta['kinematics'] )       && is_array( $meta['kinematics'] ) )       ? $meta['kinematics']       : array();
            $elev     = ( isset( $meta['elevation'] )        && is_array( $meta['elevation'] ) )        ? $meta['elevation']        : array();
            $slope    = ( isset( $elev['slope_distribution_km'] ) && is_array( $elev['slope_distribution_km'] ) ) ? $elev['slope_distribution_km'] : array();
            $curves   = ( isset( $meta['technical_curves'] ) && is_array( $meta['technical_curves'] ) ) ? $meta['technical_curves'] : array();
            $clusters = ( isset( $curves['curve_clusters'] ) && is_array( $curves['curve_clusters'] ) ) ? $curves['curve_clusters'] : array();
            $scoring  = ( isset( $meta['scoring'] )          && is_array( $meta['scoring'] ) )          ? $meta['scoring']          : array();

            $g = function ( $arr, $k ) { return isset( $arr[ $k ] ) ? $arr[ $k ] : null; };
        ?>
        <div class="ent-rmm">

            <?php
            /* Cabecera: distancia · duración en movimiento · desnivel + */
            $c_dist = enterprise_rmm_num( $g( $summary, 'distance_km' ) );
            $c_dur  = $g( $summary, 'duration_moving_formatted' );
            $c_gain = enterprise_rmm_num( $g( $summary, 'elevation_gain_m' ) );
            if ( $c_dist !== '' || $c_dur || $c_gain !== '' ) : ?>
            <div class="ent-rmm__cards">
                <?php if ( $c_dist !== '' ) : ?>
                <div class="ent-rmm__card"><div class="l"><?php esc_html_e( 'Distancia', 'enterprise-moto' ); ?></div><div class="n"><?php echo esc_html( $c_dist ); ?> km</div></div>
                <?php endif; ?>
                <?php if ( $c_dur ) : ?>
                <div class="ent-rmm__card"><div class="l"><?php esc_html_e( 'Duración en movimiento', 'enterprise-moto' ); ?></div><div class="n"><?php echo esc_html( $c_dur ); ?></div></div>
                <?php endif; ?>
                <?php if ( $c_gain !== '' ) : ?>
                <div class="ent-rmm__card"><div class="l"><?php esc_html_e( 'Desnivel +', 'enterprise-moto' ); ?></div><div class="n"><?php echo esc_html( $c_gain ); ?> m</div></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php
            /* Tira de dificultad. */
            $d_score = $g( $summary, 'overall_difficulty_score' );
            $d_cat   = $g( $summary, 'difficulty_category' );
            $d_phys  = $g( $summary, 'physical_level' );
            $d_tech  = $g( $summary, 'technical_level' );
            if ( $d_score !== null || $d_cat ) : ?>
            <div class="ent-rmm__diff">
                <span class="l"><?php esc_html_e( 'Dificultad global', 'enterprise-moto' ); ?><?php echo enterprise_rmm_info_btn( 'summary.overall_difficulty_score', $info, $uid ); ?></span>
                <span class="pill">
                    <?php
                    $pill = array();
                    if ( $d_score !== null ) $pill[] = enterprise_rmm_num( $d_score ) . ' / 100';
                    if ( $d_cat )            $pill[] = $d_cat;
                    echo esc_html( implode( ' · ', $pill ) );
                    ?>
                </span>
                <?php if ( $d_phys || $d_tech ) : ?>
                <span class="r">
                    <?php if ( $d_phys ) : ?><?php esc_html_e( 'Físico', 'enterprise-moto' ); ?> <b><?php echo esc_html( $d_phys ); ?></b><?php endif; ?>
                    <?php if ( $d_phys && $d_tech ) echo ' · '; ?>
                    <?php if ( $d_tech ) : ?><?php esc_html_e( 'Técnico', 'enterprise-moto' ); ?> <b><?php echo esc_html( $d_tech ); ?></b><?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php
            /* Cinemática + Elevación (dos columnas). */
            $kin_rows = ''
                . enterprise_rmm_row( __( 'Duración total', 'enterprise-moto' ), enterprise_rmm_hms( $g( $kin, 'duration_total_seconds' ) ), 'kinematics.duration_total_seconds', $info, $uid )
                . enterprise_rmm_row( __( 'En movimiento', 'enterprise-moto' ),  enterprise_rmm_hms( $g( $kin, 'duration_moving_seconds' ) ), 'kinematics.duration_moving_seconds', $info, $uid )
                . enterprise_rmm_row( __( 'En pausa', 'enterprise-moto' ),       enterprise_rmm_hms( $g( $kin, 'duration_paused_seconds' ) ), 'kinematics.duration_paused_seconds', $info, $uid )
                . enterprise_rmm_row( __( 'Velocidad media', 'enterprise-moto' ), ( ( $x = enterprise_rmm_num( $g( $kin, 'avg_speed_kmh' ) ) ) !== '' ? $x . ' km/h' : '' ), 'kinematics.avg_speed_kmh', $info, $uid )
                . enterprise_rmm_row( __( 'Velocidad máxima', 'enterprise-moto' ), ( ( $x = enterprise_rmm_num( $g( $kin, 'max_speed_kmh' ) ) ) !== '' ? $x . ' km/h' : '' ), 'kinematics.max_speed_kmh', $info, $uid );

            $elev_rows = ''
                . enterprise_rmm_row( __( 'Cota máxima', 'enterprise-moto' ), ( ( $x = enterprise_rmm_num( $g( $elev, 'max_elevation_m' ) ) ) !== '' ? $x . ' m' : '' ), 'elevation.max_elevation_m', $info, $uid )
                . enterprise_rmm_row( __( 'Cota mínima', 'enterprise-moto' ), ( ( $x = enterprise_rmm_num( $g( $elev, 'min_elevation_m' ) ) ) !== '' ? $x . ' m' : '' ), 'elevation.min_elevation_m', $info, $uid )
                . enterprise_rmm_row( __( 'Desnivel −', 'enterprise-moto' ),  ( ( $x = enterprise_rmm_num( $g( $elev, 'elevation_loss_m' ) ) ) !== '' ? $x . ' m' : '' ), 'elevation.elevation_loss_m', $info, $uid )
                . enterprise_rmm_row( __( 'Pendiente máxima', 'enterprise-moto' ), ( ( $x = enterprise_rmm_num( $g( $elev, 'max_gradient_pct' ) ) ) !== '' ? $x . ' %' : '' ), 'elevation.max_gradient_pct', $info, $uid );

            if ( $kin_rows || $elev_rows ) : ?>
            <div class="ent-rmm__cols">
                <?php if ( $kin_rows ) : ?>
                <div><div class="ent-rmm__sec"><?php esc_html_e( 'Cinemática', 'enterprise-moto' ); ?></div><table><?php echo $kin_rows; // ya escapado en enterprise_rmm_row ?></table></div>
                <?php endif; ?>
                <?php if ( $elev_rows ) : ?>
                <div><div class="ent-rmm__sec"><?php esc_html_e( 'Elevación', 'enterprise-moto' ); ?></div><table><?php echo $elev_rows; ?></table></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php
            /* Reparto por pendiente (barras). */
            $slope_defs = array(
                array( __( 'Llano 0–3 %', 'enterprise-moto' ),    'flat_0_3_pct',     1 ),
                array( __( 'Moderada 3–7 %', 'enterprise-moto' ), 'moderate_3_7_pct', 2 ),
                array( __( 'Fuerte 7–12 %', 'enterprise-moto' ),  'steep_7_12_pct',   3 ),
                array( __( 'Extrema >12 %', 'enterprise-moto' ),  'extreme_gt_12_pct',4 ),
            );
            $slope_max = 0.0;
            foreach ( $slope_defs as $sd ) { $v = $g( $slope, $sd[1] ); if ( is_numeric( $v ) && (float) $v > $slope_max ) $slope_max = (float) $v; }
            if ( $slope_max > 0 ) : ?>
            <div class="ent-rmm__block">
                <div class="ent-rmm__sec"><?php esc_html_e( 'Reparto por pendiente (km)', 'enterprise-moto' ); ?></div>
                <div class="ent-rmm__dist">
                    <?php foreach ( $slope_defs as $sd ) :
                        $v = $g( $slope, $sd[1] );
                        if ( ! is_numeric( $v ) ) continue;
                        $w = max( 2, round( (float) $v / $slope_max * 100 ) );
                    ?>
                    <span class="k"><?php echo esc_html( $sd[0] ); ?></span>
                    <span class="bar ent-rmm__bar--<?php echo intval( $sd[2] ); ?>" style="width:<?php echo intval( $w ); ?>%"></span>
                    <span class="val"><?php echo esc_html( enterprise_rmm_num( $v ) ); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php
            /* Curvas técnicas + Racimos. */
            $wind_km  = enterprise_rmm_num( $g( $curves, 'winding_distance_km' ) );
            $wind_pct = enterprise_rmm_num( $g( $curves, 'winding_distance_pct' ) );
            $wind_val = '';
            if ( $wind_km !== '' && $wind_pct !== '' ) $wind_val = $wind_km . ' km (' . $wind_pct . ' %)';
            elseif ( $wind_km !== '' )                 $wind_val = $wind_km . ' km';

            $curve_rows = ''
                . enterprise_rmm_row( __( 'Curvas totales', 'enterprise-moto' ), enterprise_rmm_num( $g( $curves, 'total_curves_count' ) ), 'technical_curves.total_curves_count', $info, $uid )
                . enterprise_rmm_row( __( 'Curvas por km', 'enterprise-moto' ), enterprise_rmm_num( $g( $curves, 'curves_per_km' ) ), 'technical_curves.curves_per_km', $info, $uid )
                . enterprise_rmm_row( __( 'Distancia sinuosa', 'enterprise-moto' ), $wind_val, 'technical_curves.winding_distance_km', $info, $uid )
                . enterprise_rmm_row( __( 'Índice de sinuosidad', 'enterprise-moto' ), enterprise_rmm_num( $g( $curves, 'sinuosity_index' ) ), 'technical_curves.sinuosity_index', $info, $uid )
                . enterprise_rmm_row( __( 'Curvas de fuerte pendiente', 'enterprise-moto' ), enterprise_rmm_num( $g( $curves, 'steep_curves_count' ) ), 'technical_curves.steep_curves_count', $info, $uid );

            $clus_rows = ''
                . enterprise_rmm_row( __( 'Abiertas >40 m', 'enterprise-moto' ), enterprise_rmm_num( $g( $clusters, 'open_gt_40m' ) ), 'technical_curves.curve_clusters.open_gt_40m', $info, $uid )
                . enterprise_rmm_row( __( 'Medias 15–40 m', 'enterprise-moto' ), enterprise_rmm_num( $g( $clusters, 'medium_15_40m' ) ), 'technical_curves.curve_clusters.medium_15_40m', $info, $uid )
                . enterprise_rmm_row( __( 'Cerradas 7–15 m', 'enterprise-moto' ), enterprise_rmm_num( $g( $clusters, 'tight_7_15m' ) ), 'technical_curves.curve_clusters.tight_7_15m', $info, $uid )
                . enterprise_rmm_row( __( 'Horquillas <7 m', 'enterprise-moto' ), enterprise_rmm_num( $g( $clusters, 'hairpins_lt_7m' ) ), 'technical_curves.curve_clusters.hairpins_lt_7m', $info, $uid );

            if ( $curve_rows || $clus_rows ) : ?>
            <div class="ent-rmm__block ent-rmm__cols">
                <?php if ( $curve_rows ) : ?>
                <div><div class="ent-rmm__sec"><?php esc_html_e( 'Curvas técnicas', 'enterprise-moto' ); ?></div><table><?php echo $curve_rows; ?></table></div>
                <?php endif; ?>
                <?php if ( $clus_rows ) : ?>
                <div><div class="ent-rmm__sec"><?php esc_html_e( 'Racimos de curvas', 'enterprise-moto' ); ?></div><table><?php echo $clus_rows; ?></table></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php
            /* Puntuación. */
            $sc_phys = enterprise_rmm_num( $g( $scoring, 'physical_score' ) );
            $sc_tech = enterprise_rmm_num( $g( $scoring, 'technical_score' ) );
            $sc_glob = enterprise_rmm_num( $g( $scoring, 'overall_score' ) );
            if ( $sc_phys !== '' || $sc_tech !== '' || $sc_glob !== '' ) : ?>
            <div class="ent-rmm__block">
                <div class="ent-rmm__sec"><?php esc_html_e( 'Puntuación', 'enterprise-moto' ); ?></div>
                <div class="ent-rmm__score">
                    <?php if ( $sc_phys !== '' ) : ?><div class="ent-rmm__card"><div class="l"><?php esc_html_e( 'Físico', 'enterprise-moto' ); ?></div><div class="n"><?php echo esc_html( $sc_phys ); ?><small> / 100</small></div></div><?php endif; ?>
                    <?php if ( $sc_tech !== '' ) : ?><div class="ent-rmm__card"><div class="l"><?php esc_html_e( 'Técnico', 'enterprise-moto' ); ?></div><div class="n"><?php echo esc_html( $sc_tech ); ?><small> / 100</small></div></div><?php endif; ?>
                    <?php if ( $sc_glob !== '' ) : ?><div class="ent-rmm__card"><div class="l"><?php esc_html_e( 'Global', 'enterprise-moto' ); ?></div><div class="n"><?php echo esc_html( $sc_glob ); ?><small> / 100</small></div></div><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; /* espejo */ ?>

    </div>
    <?php
    return ob_get_clean();
}
