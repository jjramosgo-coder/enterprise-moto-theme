<?php
/**
 * Enterprise Moto — blocks/animated-route-map/render.php
 * Mapa de ruta animado: sincronización elevación ↔ marcador en mapa.
 * data-map-type="animated-route"
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_animated_route_map_block( $attributes ) {

    $gpx_url      = isset( $attributes['gpxUrl'] )        ? esc_url_raw( $attributes['gpxUrl'] )            : '';
    $heading      = isset( $attributes['heading'] )       ? sanitize_text_field( $attributes['heading'] )    : '';
    $map_height   = isset( $attributes['mapHeight'] )     ? sanitize_key( $attributes['mapHeight'] )         : 'md';
    $route_color  = isset( $attributes['routeColor'] )    ? sanitize_hex_color( $attributes['routeColor'] )  : '#001f5c';
    $marker_color = isset( $attributes['markerColor'] )   ? sanitize_hex_color( $attributes['markerColor'] ) : '#f2c118';
    $route_weight = isset( $attributes['routeWeight'] )   ? intval( $attributes['routeWeight'] )             : 4;
    $show_elev    = isset( $attributes['showElevation'] ) ? (bool) $attributes['showElevation']              : true;
    $show_stats   = isset( $attributes['showStats'] )     ? (bool) $attributes['showStats']                  : true;
    $start_label  = isset( $attributes['startLabel'] )    ? sanitize_text_field( $attributes['startLabel'] ) : '';
    $end_label    = isset( $attributes['endLabel'] )      ? sanitize_text_field( $attributes['endLabel'] )   : '';
    $stat_km      = isset( $attributes['statKm'] )        ? sanitize_text_field( $attributes['statKm'] )     : '';
    $stat_dur     = isset( $attributes['statDuration'] )  ? sanitize_text_field( $attributes['statDuration'] ): '';
    $stat_elev    = isset( $attributes['statElevGain'] )  ? sanitize_text_field( $attributes['statElevGain'] ): '';
    $description  = isset( $attributes['description'] )   ? sanitize_textarea_field( $attributes['description'] ) : '';

    if ( ! $route_color  ) $route_color  = '#001f5c';
    if ( ! $marker_color ) $marker_color = '#f2c118';

    $uid = 'ent-armap-' . wp_rand( 1000, 9999 );

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

        <!-- Mapa -->
        <div class="ent-map ent-map--<?php echo esc_attr( $map_height ); ?>"
             id="<?php echo esc_attr( $uid ); ?>"
             data-map-type="animated-route"
             data-gpx-url="<?php echo esc_attr( $gpx_url ); ?>"
             data-route-color="<?php echo esc_attr( $route_color ); ?>"
             data-marker-color="<?php echo esc_attr( $marker_color ); ?>"
             data-route-weight="<?php echo intval( $route_weight ); ?>"
             data-start-label="<?php echo esc_attr( $start_label ); ?>"
             data-end-label="<?php echo esc_attr( $end_label ); ?>"
             data-show-elevation="<?php echo $show_elev ? 'true' : 'false'; ?>"
             role="img"
             aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Mapa de ruta animado', 'enterprise-moto' ); ?>">
        </div>

        <?php if ( $show_stats ) : ?>
        <div class="ent-map-route-info">
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-km"><?php echo esc_html( $stat_km ); ?></div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Distancia', 'enterprise-moto' ); ?></div>
            </div>
            <?php if ( $stat_dur ) : ?>
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n"><?php echo esc_html( $stat_dur ); ?></div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></div>
            </div>
            <?php endif; ?>
            <div class="ent-map-route-stat">
                <div class="ent-map-route-stat__n" id="<?php echo esc_attr( $uid ); ?>-stat-elev"><?php echo esc_html( $stat_elev ); ?></div>
                <div class="ent-map-route-stat__l"><?php esc_html_e( 'Desnivel +', 'enterprise-moto' ); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $show_elev && $gpx_url ) : ?>
        <div class="ent-map-elevation ent-map-elevation--interactive"
             id="<?php echo esc_attr( $uid ); ?>-elev-wrap"
             style="cursor:crosshair;"
             title="<?php esc_attr_e( 'Mueve el ratón para ver la posición en el mapa', 'enterprise-moto' ); ?>">
            <span class="ent-map-elevation__label">
                <?php esc_html_e( 'Perfil de elevación', 'enterprise-moto' ); ?>
                <em style="font-style:normal;font-weight:300;opacity:.6;margin-left:6px;">
                    — <?php esc_html_e( 'mueve el ratón para animar', 'enterprise-moto' ); ?>
                </em>
            </span>
            <canvas id="<?php echo esc_attr( $uid ); ?>-canvas"
                    data-map-uid="<?php echo esc_attr( $uid ); ?>">
            </canvas>
            <!-- Línea vertical de posición -->
            <div class="ent-map-elev-cursor" id="<?php echo esc_attr( $uid ); ?>-elev-cursor"
                 style="display:none;position:absolute;top:24px;bottom:0;width:2px;background:<?php echo esc_attr( $route_color ); ?>;pointer-events:none;opacity:.8;"></div>
        </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
