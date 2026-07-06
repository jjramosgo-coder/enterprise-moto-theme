<?php
/**
 * Enterprise Moto — blocks/trip-collection/render.php
 *
 * Bloque «Colección de viajes» (#5). Pinta como tarjetas de viaje/ruta las
 * entradas que resultan de los MISMOS filtros que enterprise/post-stages
 * (query compartida: enterprise_stage_query()). Cada tarjeta es un enlace
 * plano al relato; NO se inyecta contexto de navegación (from_*) — la
 * navegación anterior/siguiente entre viajes de la colección queda fuera de
 * alcance (spec #5, §5).
 *
 * Datos por entrada (badge de tipo, año, km/etapas/ferrys): enterprise_trip_card_data().
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_trip_collection_block( $attributes ) {

    /* Atributos de filtro que también necesita el mensaje «sin entradas». */
    $category_ids = isset( $attributes['categoryIds'] ) && is_array( $attributes['categoryIds'] )
                        ? array_map( 'intval', $attributes['categoryIds'] ) : array();
    $tag_ids      = isset( $attributes['tagIds'] ) && is_array( $attributes['tagIds'] )
                        ? array_map( 'intval', $attributes['tagIds'] ) : array();

    /* Query compartida con post-stages (misma resolución de filtros → entradas). */
    $query = enterprise_stage_query( $attributes );

    if ( ! $query->have_posts() ) {
        if ( current_user_can( 'edit_posts' ) ) {
            $filter_desc = array();
            if ( ! empty( $category_ids ) ) {
                $cat_names = array();
                foreach ( $category_ids as $cid ) {
                    $cat = get_category( $cid );
                    if ( $cat ) $cat_names[] = $cat->name;
                }
                $filter_desc[] = 'categorías: ' . implode( ', ', $cat_names );
            }
            if ( ! empty( $tag_ids ) ) {
                $tag_names = array();
                foreach ( $tag_ids as $tid ) {
                    $tag = get_tag( $tid );
                    if ( $tag ) $tag_names[] = $tag->name;
                }
                $filter_desc[] = 'etiquetas: ' . implode( ', ', $tag_names );
            }
            $filter_str = ! empty( $filter_desc ) ? implode( ' + ', $filter_desc ) : 'todos los posts';

            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . sprintf( esc_html__( 'Colección de viajes — sin entradas con el filtro: %s', 'enterprise-moto' ), esc_html( $filter_str ) )
                 . '</p>';
        }
        return '';
    }

    /* Gradientes de reserva rotativos cuando la entrada no tiene imagen destacada. */
    $bg = array( 'bg1', 'bg2', 'bg3', 'bg4', 'bg5' );

    ob_start(); ?>
    <div class="ent-trip-collection">
        <div class="trip-grid" role="list">
        <?php
        $n = 0;
        while ( $query->have_posts() ) : $query->the_post();
            $data    = enterprise_trip_card_data( get_the_ID() );
            $thumb   = get_the_post_thumbnail_url( null, 'enterprise-card' );
            $excerpt = get_the_excerpt();

            /* Km: unidad defensiva + prefijo ≈ si el viaje tiene km incompletos. */
            $km_str = enterprise_km_display( $data['km'] );
            if ( '' !== $km_str && $data['km_inc'] ) {
                $km_str = '≈' . $km_str;
            }

            $bg_class = $bg[ $n % count( $bg ) ];
            ?>
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="trip-card" role="listitem">
                <div class="trip-thumb <?php echo esc_attr( $bg_class ); ?>">
                    <?php if ( $thumb ) : ?>
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                    <?php else : ?>
                        <span class="trip-thumb-fallback" aria-hidden="true">🏍️</span>
                    <?php endif; ?>
                    <span class="type-badge"><?php echo esc_html( $data['tipo_label'] ); ?></span>
                    <?php if ( $data['year'] ) : ?>
                        <span class="year-badge"><?php echo esc_html( $data['year'] ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="trip-body">
                    <div class="trip-title"><?php the_title(); ?></div>
                    <?php if ( $excerpt ) : ?>
                        <div class="trip-desc"><?php echo esc_html( $excerpt ); ?></div>
                    <?php endif; ?>
                    <div class="trip-meta">
                        <div class="trip-meta-i">
                            <div class="trip-meta-n"><?php echo '' !== $km_str ? esc_html( $km_str ) : '—'; ?></div>
                            <div class="trip-meta-l"><?php esc_html_e( 'Distancia', 'enterprise-moto' ); ?></div>
                        </div>
                        <div class="trip-meta-i">
                            <div class="trip-meta-n"><?php echo intval( $data['etapas'] ); ?></div>
                            <div class="trip-meta-l"><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></div>
                        </div>
                        <div class="trip-meta-i">
                            <div class="trip-meta-n"><?php echo intval( $data['ferrys'] ); ?></div>
                            <div class="trip-meta-l"><?php esc_html_e( 'Ferrys', 'enterprise-moto' ); ?></div>
                        </div>
                    </div>
                </div>
            </a>
            <?php
            $n++;
        endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
