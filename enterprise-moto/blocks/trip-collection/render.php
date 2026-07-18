<?php
/**
 * Enterprise Moto — blocks/trip-collection/render.php
 *
 * Bloque «Colección de viajes» (#5, #11). Pinta como tarjetas de viaje/ruta las
 * entradas que resultan de los MISMOS filtros que enterprise/post-stages
 * (query compartida: enterprise_stage_query()). Presentación configurable
 * (#11): carrusel horizontal o timeline vertical, reutilizando el scaffolding
 * y los assets (carousel.js/carousel.css) de post-stages. Cada tarjeta es un
 * enlace plano al relato; NO se inyecta contexto de navegación (from_*) — la
 * navegación anterior/siguiente entre viajes de la colección queda fuera de
 * alcance (navegación entre viajes = #8).
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

    /* #11 R3: «sin límite» → traer todas las entradas del filtro. No se toca la
       query compartida (ya mapea -1 nativamente); el ajuste es a nivel de bloque. */
    if ( ! empty( $attributes['showAll'] ) ) {
        $attributes['postsPerPage'] = -1;
    }

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

    /* #11 R6: presentación configurable (carrusel | timeline). Se reutiliza el
       scaffolding de enterprise/post-stages (.ent-stages--{layout}, track, slides,
       nav/dots, .ent-tl-item) para aprovechar carousel.js/carousel.css SIN tocarlos.
       El contenedor conserva además .ent-trip-collection para que coleccion.css siga
       estilando la .trip-card, que se preserva intacta como contenido de cada
       slide/fila. Sigue siendo enlace plano (sin from_*; navegación entre viajes = #8). */
    $layout      = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'carousel';
    $is_carousel = ( 'carousel' === $layout );
    $total       = $query->post_count;
    $uid         = 'ent-trips-' . wp_rand( 1000, 9999 );

    ob_start(); ?>
    <div class="ent-stages ent-stages--<?php echo esc_attr( $layout ); ?> ent-trip-collection"
         id="<?php echo esc_attr( $uid ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>">

        <?php if ( $is_carousel && $total > 1 ) : ?>
        <div class="ent-stages__head">
            <div class="ent-stages__nav">
                <button class="ent-stages__nav-btn ent-stages__nav-btn--prev"
                        data-target="<?php echo esc_attr( $uid ); ?>"
                        aria-label="<?php esc_attr_e( 'Anterior', 'enterprise-moto' ); ?>"
                        type="button">←</button>
                <span class="ent-stages__nav-count">
                    <span class="ent-stages__nav-current">1</span> / <?php echo intval( $total ); ?>
                </span>
                <button class="ent-stages__nav-btn ent-stages__nav-btn--next"
                        data-target="<?php echo esc_attr( $uid ); ?>"
                        aria-label="<?php esc_attr_e( 'Siguiente', 'enterprise-moto' ); ?>"
                        type="button">→</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="ent-stages__track" role="list">
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

            /* Tarjeta de viaje: idéntica en ambos modos. Se compone una vez y se
               envuelve según el layout (slide de carrusel o fila de timeline). */
            ob_start(); ?>
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="trip-card">
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
                        <?php if ( $data['ferrys'] > 0 ) : ?>
                        <div class="trip-meta-i">
                            <div class="trip-meta-n"><?php echo intval( $data['ferrys'] ); ?></div>
                            <div class="trip-meta-l"><?php esc_html_e( 'Ferry', 'enterprise-moto' ); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php
            $card = ob_get_clean();

            if ( $is_carousel ) : ?>
                <div class="ent-stages__slide" role="listitem" data-index="<?php echo intval( $n ); ?>"><?php echo $card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <?php else : /* timeline */ ?>
                <div class="ent-tl-item" role="listitem">
                    <div class="ent-tl-dot-col" aria-hidden="true">
                        <div class="ent-tl-dot is-done"><?php echo str_pad( $n + 1, 2, '0', STR_PAD_LEFT ); ?></div>
                        <?php if ( $n + 1 < $total ) : ?><div class="ent-tl-connector is-done"></div><?php endif; ?>
                    </div>
                    <?php echo $card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
            <?php
            $n++;
        endwhile; wp_reset_postdata(); ?>
        </div>

        <?php if ( $is_carousel && $total > 1 ) : ?>
        <div class="ent-stages__dots" aria-hidden="true">
            <?php for ( $i = 0; $i < $total; $i++ ) : ?>
                <button class="ent-stages__dot <?php echo $i === 0 ? 'is-active' : ''; ?>"
                        data-target="<?php echo esc_attr( $uid ); ?>"
                        data-index="<?php echo intval( $i ); ?>"
                        type="button"></button>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
