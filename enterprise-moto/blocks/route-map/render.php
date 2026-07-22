<?php
/**
 * Bitácora Enterprise — blocks/route-map/render.php v1.6.0
 * Doble GPX. Sin <script> inline. Todo via data-* attributes.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_route_map_block( $attributes ) {

    $gpx_url1      = isset( $attributes['gpxUrl'] )        ? esc_url_raw( $attributes['gpxUrl'] )              : '';
    $gpx_url2      = isset( $attributes['gpxUrl2'] )       ? esc_url_raw( $attributes['gpxUrl2'] )             : '';
    $gpx_label1    = isset( $attributes['gpxLabel1'] )     ? sanitize_text_field( $attributes['gpxLabel1'] )    : __( 'Ruta planificada', 'enterprise-moto' );
    $gpx_label2    = isset( $attributes['gpxLabel2'] )     ? sanitize_text_field( $attributes['gpxLabel2'] )    : __( 'Ruta GPS', 'enterprise-moto' );
    $heading       = isset( $attributes['heading'] )       ? sanitize_text_field( $attributes['heading'] )      : '';
    $map_height    = isset( $attributes['mapHeight'] )     ? sanitize_key( $attributes['mapHeight'] )           : 'md';
    $route_color1  = isset( $attributes['routeColor'] )    ? sanitize_hex_color( $attributes['routeColor'] )    : '#001f5c';
    $route_color2  = isset( $attributes['routeColor2'] )   ? sanitize_hex_color( $attributes['routeColor2'] )   : '#c0392b';
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

    $uid = 'ent-rmap-' . wp_rand( 1000, 9999 );

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
             data-map-type="route"
             data-gpx-url="<?php echo esc_attr( $gpx_url1 ); ?>"
             data-gpx-url2="<?php echo esc_attr( $gpx_url2 ); ?>"
             data-gpx-label1="<?php echo esc_attr( $gpx_label1 ); ?>"
             data-gpx-label2="<?php echo esc_attr( $gpx_label2 ); ?>"
             data-route-color="<?php echo esc_attr( $route_color1 ); ?>"
             data-route-color2="<?php echo esc_attr( $route_color2 ); ?>"
             data-route-weight="<?php echo intval( $route_weight ); ?>"
             data-start-label="<?php echo esc_attr( $start_label ); ?>"
             data-end-label="<?php echo esc_attr( $end_label ); ?>"
             data-show-elevation="<?php echo $show_elev ? 'true' : 'false'; ?>"
             role="img"
             aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Mapa de ruta', 'enterprise-moto' ); ?>">
        </div>

        <?php if ( $show_stats ) : ?>
        <div class="ent-map-route-info">
            <?php if ( $gpx_url2 ) : /* Doble ruta: dos distancias coloreadas */ ?>
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km1"
                     style="color:<?php echo esc_attr( $route_color1 ); ?>">
                    <?php echo esc_html( $stat_km ); ?>
                </div>
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km2"
                     style="color:<?php echo esc_attr( $route_color2 ); ?>;display:none;">—</div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Distancia', 'enterprise-moto' ); ?></div>
            </div>
            <?php else : /* Ruta única */ ?>
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km">
                    <?php echo esc_html( $stat_km ); ?>
                </div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Distancia', 'enterprise-moto' ); ?></div>
            </div>
            <?php endif; ?>
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
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Desnivel +', 'enterprise-moto' ); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $show_elev && $gpx_url1 ) : ?>
        <div class="ent-map-elevation" id="<?php echo esc_attr( $uid ); ?>-elev-wrap">
            <span class="ent-map-elevation__label"><?php esc_html_e( 'Perfil de elevación', 'enterprise-moto' ); ?></span>
            <canvas id="<?php echo esc_attr( $uid ); ?>-canvas"></canvas>
        </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
