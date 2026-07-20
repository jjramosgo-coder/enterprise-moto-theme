<?php
/**
 * Enterprise Moto — blocks/location-map/render.php
 * Solo HTML + data-* attributes. Sin <script> inline.
 * La lógica de mapa (OpenLayers 9.2.4) está en assets/js/map-frontend.js
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_location_map_block( $attributes ) {

    $markers     = isset( $attributes['markers'] )     && is_array( $attributes['markers'] ) ? $attributes['markers'] : array();
    $map_height  = isset( $attributes['mapHeight'] )   ? sanitize_key( $attributes['mapHeight'] )          : 'md';
    $map_zoom    = isset( $attributes['mapZoom'] )     ? intval( $attributes['mapZoom'] )                  : 6;
    $heading     = isset( $attributes['heading'] )     ? sanitize_text_field( $attributes['heading'] )      : '';
    $show_legend = isset( $attributes['showLegend'] )  ? (bool) $attributes['showLegend']                  : true;
    $show_numbers= isset( $attributes['showNumbers'] ) ? (bool) $attributes['showNumbers']                 : true;

    if ( empty( $markers ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . esc_html__( 'Mapa de localizaciones: sin marcadores. Añade lugares en el editor.', 'enterprise-moto' )
                 . '</p>';
        }
        return '';
    }

    $uid = 'ent-lmap-' . wp_rand( 1000, 9999 );

    /* Sanitizar y serializar marcadores */
    $clean_markers = array_map( function( $m ) {
        return array(
            'lat'         => isset( $m['lat'] )         ? floatval( $m['lat'] )                    : 0,
            'lng'         => isset( $m['lng'] )         ? floatval( $m['lng'] )                    : 0,
            'name'        => isset( $m['name'] )        ? sanitize_text_field( $m['name'] )        : '',
            'description' => isset( $m['description'] ) ? sanitize_text_field( $m['description'] ) : '',
            'postUrl'     => isset( $m['postUrl'] )     ? esc_url_raw( $m['postUrl'] )             : '',
        );
    }, $markers );

    ob_start();
    ?>
    <div class="ent-map-block" id="<?php echo esc_attr( $uid ); ?>-wrap">

        <?php if ( $heading ) : ?>
            <h2 class="ent-map-block__heading"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>

        <div class="ent-map ent-map--<?php echo esc_attr( $map_height ); ?>"
             id="<?php echo esc_attr( $uid ); ?>"
             data-map-type="location"
             data-zoom="<?php echo intval( $map_zoom ); ?>"
             data-show-numbers="<?php echo $show_numbers ? '1' : '0'; ?>"
             data-markers="<?php echo esc_attr( wp_json_encode( $clean_markers ) ); ?>"
             role="img"
             aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Mapa de localizaciones', 'enterprise-moto' ); ?>">
        </div>

        <?php if ( $show_legend ) : ?>
        <ul class="ent-map-legend">
            <?php foreach ( $clean_markers as $i => $m ) : ?>
            <li class="ent-map-legend__item" data-legend-index="<?php echo intval( $i ); ?>">
                <?php if ( $show_numbers ) : ?>
                    <span class="ent-map-legend__num"><?php echo str_pad( $i + 1, 2, '0', STR_PAD_LEFT ); ?></span>
                <?php endif; ?>
                <span class="ent-map-legend__name">
                    <?php if ( ! empty( $m['postUrl'] ) ) : ?>
                        <a href="<?php echo esc_url( $m['postUrl'] ); ?>"><?php echo esc_html( $m['name'] ); ?></a>
                    <?php else : ?>
                        <?php echo esc_html( $m['name'] ); ?>
                    <?php endif; ?>
                </span>
                <?php if ( ! empty( $m['description'] ) ) : ?>
                    <span class="ent-map-legend__desc"><?php echo esc_html( $m['description'] ); ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
