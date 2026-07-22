<?php
/**
 * Bitácora Enterprise — blocks/route-comparison/render.php
 * Mapa comparativa ruta planificada (GPX1, azul) vs realizada (GPX2, rojo).
 * Perfil de altitud y sincronización posición ↔ perfil exclusivamente de GPX2.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_route_comparison_block( $attributes ) {

    $gpx_url1      = isset( $attributes['gpxUrl'] )        ? esc_url_raw( $attributes['gpxUrl'] )               : '';
    $gpx_url2      = isset( $attributes['gpxUrl2'] )       ? esc_url_raw( $attributes['gpxUrl2'] )              : '';
    $gpx_label1    = isset( $attributes['gpxLabel1'] )     ? sanitize_text_field( $attributes['gpxLabel1'] )    : __( 'GPX1 — Ruta planificada', 'enterprise-moto' );
    $gpx_label2    = isset( $attributes['gpxLabel2'] )     ? sanitize_text_field( $attributes['gpxLabel2'] )    : __( 'GPX2 — Ruta realizada',   'enterprise-moto' );
    $heading       = isset( $attributes['heading'] )       ? sanitize_text_field( $attributes['heading'] )      : '';
    $map_height    = isset( $attributes['mapHeight'] )     ? sanitize_key( $attributes['mapHeight'] )           : 'md';
    $route_color1  = isset( $attributes['routeColor'] )    ? sanitize_hex_color( $attributes['routeColor'] )    : '#001f5c';
    $route_color2  = isset( $attributes['routeColor2'] )   ? sanitize_hex_color( $attributes['routeColor2'] )   : '#c0392b';
    $marker_color  = isset( $attributes['markerColor'] )   ? sanitize_hex_color( $attributes['markerColor'] )   : '#f2c118';
    $route_weight  = isset( $attributes['routeWeight'] )   ? intval( $attributes['routeWeight'] )               : 4;
    $show_elev     = isset( $attributes['showElevation'] ) ? (bool) $attributes['showElevation']                : true;
    $show_stats    = isset( $attributes['showStats'] )     ? (bool) $attributes['showStats']                    : true;
    $start_label   = isset( $attributes['startLabel'] )    ? sanitize_text_field( $attributes['startLabel'] )   : '';
    $end_label     = isset( $attributes['endLabel'] )      ? sanitize_text_field( $attributes['endLabel'] )     : '';
    $stat_km       = isset( $attributes['statKm'] )        ? sanitize_text_field( $attributes['statKm'] )       : '';
    $stat_duration = isset( $attributes['statDuration'] )  ? sanitize_text_field( $attributes['statDuration'] ) : '';
    $stat_elev     = isset( $attributes['statElevGain'] )  ? sanitize_text_field( $attributes['statElevGain'] ) : '';
    $description   = isset( $attributes['description'] )   ? sanitize_textarea_field( $attributes['description'] ) : '';

    if ( ! $route_color1 ) $route_color1 = '#001f5c';
    if ( ! $route_color2 ) $route_color2 = '#c0392b';
    if ( ! $marker_color ) $marker_color = '#f2c118';

    $uid = 'ent-rcmp-' . wp_rand( 1000, 9999 );

    ob_start(); ?>
    <div class="ent-map-block" id="<?php echo esc_attr( $uid ); ?>-wrap">

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
             aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Comparativa de ruta planificada vs realizada', 'enterprise-moto' ); ?>">
        </div>

        <?php if ( $show_stats ) : ?>
        <div class="ent-map-route-info">
            <div class="ent-map-route-stat">
                <?php if ( $gpx_url2 ) : ?>
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km1"
                     style="color:<?php echo esc_attr( $route_color1 ); ?>">
                    <?php echo esc_html( $stat_km ); ?>
                </div>
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km2"
                     style="color:<?php echo esc_attr( $route_color2 ); ?>;display:none;">—</div>
                <?php else : ?>
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km">
                    <?php echo esc_html( $stat_km ); ?>
                </div>
                <?php endif; ?>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Distancia', 'enterprise-moto' ); ?></div>
            </div>
            <?php if ( $stat_duration ) : ?>
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n"><?php echo esc_html( $stat_duration ); ?></div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></div>
            </div>
            <?php endif; ?>
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-elev">
                    <?php echo esc_html( $stat_elev ); ?>
                </div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Desnivel + (realizada)', 'enterprise-moto' ); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $show_elev && $gpx_url2 ) : ?>
        <div class="ent-map-elevation ent-map-elevation--interactive"
             id="<?php echo esc_attr( $uid ); ?>-elev-wrap"
             style="cursor:crosshair;"
             title="<?php esc_attr_e( 'Mueve el ratón para ver la posición en el mapa', 'enterprise-moto' ); ?>">
            <span class="ent-map-elevation__label">
                <?php esc_html_e( 'Perfil de elevación — Ruta realizada', 'enterprise-moto' ); ?>
                <em style="font-style:normal;font-weight:300;opacity:.6;margin-left:6px;">
                    — <?php esc_html_e( 'mueve el ratón para animar', 'enterprise-moto' ); ?>
                </em>
            </span>
            <canvas id="<?php echo esc_attr( $uid ); ?>-canvas"
                    data-map-uid="<?php echo esc_attr( $uid ); ?>">
            </canvas>
            <div class="ent-map-elev-cursor" id="<?php echo esc_attr( $uid ); ?>-elev-cursor"
                 style="display:none;position:absolute;top:24px;bottom:0;width:2px;background:<?php echo esc_attr( $route_color2 ); ?>;pointer-events:none;opacity:.8;"></div>
        </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
