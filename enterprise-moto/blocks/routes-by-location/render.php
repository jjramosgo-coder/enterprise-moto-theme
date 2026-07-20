<?php
/**
 * Enterprise Moto — blocks/routes-by-location/render.php
 * Bloque «Mapa de rutas por localización» (enterprise/routes-by-location).
 * Solo HTML + data-* attributes. Sin <script> inline.
 * La lógica de mapa (OpenLayers 9.2.4) está en assets/js/map-frontend.js.
 *
 * A diferencia de location-map, cada marcador NO guarda una URL fija: guarda un
 * filtro compuesto sobre las taxonomías existentes —(cat_1 OR … OR cat_n) AND
 * (tag_1 AND … AND tag_m)— mediante IDs de término (filterCatIds / filterTagIds).
 *
 * NOTA de fases: en el Commit 1 el render emite el contrato de mapa con
 * data-map-type="routes-by-location" y los marcadores saneados. La derivación de
 * la URL de destino por marcador (enlace «→ Entradas relacionadas») se añade en el
 * Commit 3, y la página-destino provisional en el Commit 4.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Deriva la URL de destino de una localización a partir de su filtro compuesto.
 *
 * Base = permalink de la Página-destino elegida en el Customizer (theme mod
 * `enterprise_rbl_dest_page`, registrado en el Commit 4). Mientras no esté
 * configurada —o no esté publicada— la base cae a home_url('/'); el enlace sigue
 * llevando el filtro en los parámetros rbl_cat / rbl_tag (IDs de término separados
 * por comas), que la página-destino (Commit 4) mapea a enterprise_stage_query().
 *
 * El tercer parámetro $src_page_id (id de la página que hospeda el bloque-mapa) se
 * estampa como rbl_src cuando es un entero positivo, para el enlace «← Volver al
 * mapa» de la página-destino (#18); si es 0 se omite.
 */
if ( ! function_exists( 'enterprise_rbl_destination_url' ) ) :
function enterprise_rbl_destination_url( $cat_ids, $tag_ids, $src_page_id = 0 ) {
    $cat_ids     = is_array( $cat_ids ) ? array_values( array_filter( array_map( 'intval', $cat_ids ) ) ) : array();
    $tag_ids     = is_array( $tag_ids ) ? array_values( array_filter( array_map( 'intval', $tag_ids ) ) ) : array();
    $src_page_id = intval( $src_page_id );

    $page_id = (int) get_theme_mod( 'enterprise_rbl_dest_page', 0 );
    $base    = ( $page_id && 'publish' === get_post_status( $page_id ) )
                 ? get_permalink( $page_id )
                 : home_url( '/' );

    $args = array();
    if ( ! empty( $cat_ids ) ) $args['rbl_cat'] = implode( ',', $cat_ids );
    if ( ! empty( $tag_ids ) ) $args['rbl_tag'] = implode( ',', $tag_ids );
    if ( $src_page_id > 0 )    $args['rbl_src'] = $src_page_id;

    return empty( $args ) ? esc_url_raw( $base ) : esc_url_raw( add_query_arg( $args, $base ) );
}
endif;

function enterprise_render_routes_by_location_block( $attributes ) {

    $markers      = isset( $attributes['markers'] )     && is_array( $attributes['markers'] ) ? $attributes['markers'] : array();
    $map_height   = isset( $attributes['mapHeight'] )   ? sanitize_key( $attributes['mapHeight'] )      : 'md';
    $map_zoom     = isset( $attributes['mapZoom'] )     ? intval( $attributes['mapZoom'] )              : 6;
    $heading      = isset( $attributes['heading'] )     ? sanitize_text_field( $attributes['heading'] ) : '';
    $show_legend  = isset( $attributes['showLegend'] )  ? (bool) $attributes['showLegend']              : true;
    $show_numbers = isset( $attributes['showNumbers'] ) ? (bool) $attributes['showNumbers']             : true;

    if ( empty( $markers ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . esc_html__( 'Mapa de rutas por localización: sin localizaciones. Añádelas en el editor.', 'enterprise-moto' )
                 . '</p>';
        }
        return '';
    }

    $uid = 'ent-rbl-' . wp_rand( 1000, 9999 );

    /* Página que hospeda el bloque-mapa: se estampa como rbl_src en la URL de cada
       marcador para el enlace «← Volver al mapa» de la página-destino (#18). En el
       editor u otros contextos sin objeto consultado vale 0 y se omite. */
    $src_page_id = (int) get_queried_object_id();

    /* Sanitizar y serializar marcadores. Cada localización lleva su filtro
       compuesto como listas de IDs de término (site-global), no una URL. */
    $clean_markers = array_map( function( $m ) use ( $src_page_id ) {
        $cat_ids = ( isset( $m['filterCatIds'] ) && is_array( $m['filterCatIds'] ) )
                     ? array_values( array_map( 'intval', $m['filterCatIds'] ) ) : array();
        $tag_ids = ( isset( $m['filterTagIds'] ) && is_array( $m['filterTagIds'] ) )
                     ? array_values( array_map( 'intval', $m['filterTagIds'] ) ) : array();
        return array(
            'lat'          => isset( $m['lat'] )         ? floatval( $m['lat'] )                    : 0,
            'lng'          => isset( $m['lng'] )         ? floatval( $m['lng'] )                    : 0,
            'name'         => isset( $m['name'] )        ? sanitize_text_field( $m['name'] )        : '',
            'description'  => isset( $m['description'] ) ? sanitize_text_field( $m['description'] ) : '',
            'filterCatIds' => $cat_ids,
            'filterTagIds' => $tag_ids,
            'url'          => enterprise_rbl_destination_url( $cat_ids, $tag_ids, $src_page_id ),
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
             data-map-type="routes-by-location"
             data-zoom="<?php echo intval( $map_zoom ); ?>"
             data-show-numbers="<?php echo $show_numbers ? '1' : '0'; ?>"
             data-markers="<?php echo esc_attr( wp_json_encode( $clean_markers ) ); ?>"
             role="img"
             aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Mapa de rutas por localización', 'enterprise-moto' ); ?>">
        </div>

        <?php if ( $show_legend ) : ?>
        <ul class="ent-map-legend">
            <?php foreach ( $clean_markers as $i => $m ) : ?>
            <li class="ent-map-legend__item" data-legend-index="<?php echo intval( $i ); ?>">
                <?php if ( $show_numbers ) : ?>
                    <span class="ent-map-legend__num"><?php echo str_pad( $i + 1, 2, '0', STR_PAD_LEFT ); ?></span>
                <?php endif; ?>
                <span class="ent-map-legend__name"><?php echo esc_html( $m['name'] ); ?></span>
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
