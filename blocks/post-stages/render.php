<?php
/**
 * Enterprise Moto — blocks/post-stages/render.php
 * Filtro por múltiples categorías (tax_query OR) y/o etiquetas.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_post_stages_block( $attributes ) {

    /* ── Atributos ── */
    $category_ids   = isset( $attributes['categoryIds'] )  && is_array( $attributes['categoryIds'] )
                        ? array_map( 'intval', $attributes['categoryIds'] ) : array();
    $tag_ids        = isset( $attributes['tagIds'] )       && is_array( $attributes['tagIds'] )
                        ? array_map( 'intval', $attributes['tagIds'] ) : array();
    $filter_date_from = isset( $attributes['filterDateFrom'] ) ? sanitize_text_field( $attributes['filterDateFrom'] ) : '';
    $filter_date_to   = isset( $attributes['filterDateTo'] )   ? sanitize_text_field( $attributes['filterDateTo'] )   : '';
    $tag_relation   = isset( $attributes['tagRelation'] ) && $attributes['tagRelation'] === 'AND' ? 'AND' : 'IN';
    $posts_per_page = isset( $attributes['postsPerPage'] ) ? intval( $attributes['postsPerPage'] )        : 6;
    $order_by       = isset( $attributes['orderBy'] )      ? sanitize_key( $attributes['orderBy'] )       : 'date';
    $order          = isset( $attributes['order'] )        ? sanitize_key( $attributes['order'] )         : 'DESC';
    $layout         = isset( $attributes['layout'] )       ? sanitize_key( $attributes['layout'] )        : 'carousel';
    $card_size      = isset( $attributes['cardSize'] )     ? sanitize_key( $attributes['cardSize'] )      : 'normal';
    $heading        = isset( $attributes['heading'] )      ? sanitize_text_field( $attributes['heading'] ): '';
    $show_excerpt   = isset( $attributes['showExcerpt'] )  ? (bool) $attributes['showExcerpt']            : true;
    $show_km        = isset( $attributes['showKm'] )       ? (bool) $attributes['showKm']                 : true;
    $show_date      = isset( $attributes['showDate'] )     ? (bool) $attributes['showDate']               : true;

    $is_carousel = ( 'carousel' === $layout );
    $is_large    = ( 'large'    === $card_size );

    /* ── Contexto de navegación: detectar si el bloque está en un post tipo D ── */
    $from_post_id = 0;
    $current_id   = get_the_ID();
    if ( $current_id && get_post_meta( $current_id, '_post_tipo', true ) === 'viaje' ) {
        $from_post_id = $current_id;
    }
    $nav_suffix = $from_post_id ? array( 'from_post' => $from_post_id ) : array();

    /* ── Construir la query ── */
    $query_args = array(
        'post_type'      => 'post',
        'posts_per_page' => $posts_per_page,
        'orderby'        => $order_by,
        'order'          => strtoupper( $order ),
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    );

    /*
     * tax_query con relación AND entre categorías y etiquetas:
     * - Si hay categorías Y etiquetas → post debe cumplir ambas condiciones
     * - Si solo hay categorías → OR entre ellas (posts de cualquiera)
     * - Si solo hay etiquetas  → OR entre ellas
     */
    $tax_query = array();

    if ( ! empty( $category_ids ) ) {
        $tax_query[] = array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $category_ids,
            'operator' => 'IN',   // OR entre categorías seleccionadas
        );
    }

    if ( ! empty( $tag_ids ) ) {
        $tax_query[] = array(
            'taxonomy' => 'post_tag',
            'field'    => 'term_id',
            'terms'    => $tag_ids,
            'operator' => $tag_relation, // AND = todas las etiquetas | IN = cualquiera (OR)
        );
    }

    if ( ! empty( $tax_query ) ) {
        $tax_query['relation'] = count( $tax_query ) > 1 ? 'AND' : 'AND';
        $query_args['tax_query'] = $tax_query;
    }

    // Filtro de fecha absoluta (desde / hasta)
    if ( $filter_date_from || $filter_date_to ) {
        $dq = array( 'relation' => 'AND' );
        if ( $filter_date_from ) $dq[] = array( 'after'  => $filter_date_from . ' 00:00:00', 'inclusive' => true );
        if ( $filter_date_to )   $dq[] = array( 'before' => $filter_date_to   . ' 23:59:59', 'inclusive' => true );
        $query_args['date_query'] = $dq;
    }

    $query = new WP_Query( $query_args );

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
            $filter_str = ! empty( $filter_desc )
                ? implode( ' + ', $filter_desc )
                : 'todos los posts';

            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . sprintf( esc_html__( 'Sin posts con el filtro: %s', 'enterprise-moto' ), esc_html( $filter_str ) )
                 . '</p>';
        }
        return '';
    }

    $total = $query->post_count;
    $uid   = 'ent-stages-' . wp_rand( 1000, 9999 );

    ob_start(); ?>
    <div class="ent-stages ent-stages--<?php echo esc_attr( $layout ); ?><?php echo $is_large ? ' ent-stages--large' : ''; ?>"
         id="<?php echo esc_attr( $uid ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>">

        <?php if ( $heading || ( $is_carousel && $total > 1 ) ) : ?>
        <div class="ent-stages__head">
            <?php if ( $heading ) : ?>
                <h2 class="ent-stages__heading"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>
            <?php if ( $is_carousel && $total > 1 ) : ?>
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
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="ent-stages__track" role="list">
        <?php
        $n = 1;
        while ( $query->have_posts() ) : $query->the_post();
            $route    = enterprise_get_route_data();
            $cat_name = enterprise_first_category();
            $thumb    = get_the_post_thumbnail_url( null, 'enterprise-card' );
            $date_str = get_the_date( 'd M Y' );
            $excerpt  = get_the_excerpt();

            if ( $is_carousel ) : ?>
            <div class="ent-stages__slide" role="listitem" data-index="<?php echo intval( $n - 1 ); ?>">
                <a href="<?php echo esc_url( $nav_suffix ? add_query_arg( $nav_suffix, get_permalink() ) : get_permalink() ); ?>" class="ent-card ent-card--<?php echo $is_large ? 'large' : 'normal'; ?>">
                    <div class="ent-card__img">
                        <?php if ( $thumb ) : ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="ent-card__img-fallback">🏍️</div>
                        <?php endif; ?>
                        <span class="ent-card__num" aria-hidden="true"><?php echo str_pad( $n, 2, '0', STR_PAD_LEFT ); ?></span>
                        <?php if ( $cat_name ) : ?>
                            <span class="ent-card__cat"><?php echo esc_html( $cat_name ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ent-card__body">
                        <?php if ( $show_date ) : ?>
                            <div class="ent-card__date"><?php echo esc_html( $date_str ); ?></div>
                        <?php endif; ?>
                        <h3 class="ent-card__title"><?php the_title(); ?></h3>
                        <?php if ( $show_excerpt && $excerpt ) : ?>
                            <p class="ent-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
                        <?php endif; ?>
                        <div class="ent-card__footer">
                            <?php if ( $show_km && $route['km'] ) : ?>
                                <span class="ent-card__km"><?php echo esc_html( $route['km'] ); ?></span>
                            <?php else : ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="ent-card__arrow" aria-hidden="true">→</span>
                        </div>
                    </div>
                </a>
            </div>

            <?php else : /* timeline */ ?>
            <div class="ent-tl-item" role="listitem">
                <div class="ent-tl-dot-col" aria-hidden="true">
                    <div class="ent-tl-dot is-done"><?php echo str_pad( $n, 2, '0', STR_PAD_LEFT ); ?></div>
                    <?php if ( $n < $total ) : ?><div class="ent-tl-connector is-done"></div><?php endif; ?>
                </div>
                <a href="<?php echo esc_url( $nav_suffix ? add_query_arg( $nav_suffix, get_permalink() ) : get_permalink() ); ?>" class="ent-tl-card">
                    <div class="ent-tl-thumb">
                        <?php if ( $thumb ) : ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="ent-tl-thumb-fallback">🏍️</div>
                        <?php endif; ?>
                    </div>
                    <div class="ent-tl-body">
                        <?php if ( $show_date ) : ?>
                            <div class="ent-tl-date">
                                <?php echo esc_html( $date_str ); ?>
                                <?php if ( $cat_name ) echo ' · ' . esc_html( $cat_name ); ?>
                            </div>
                        <?php endif; ?>
                        <div class="ent-tl-title"><?php the_title(); ?></div>
                        <?php if ( $show_excerpt && $excerpt ) : ?>
                            <div class="ent-tl-excerpt"><?php echo esc_html( $excerpt ); ?></div>
                        <?php endif; ?>
                        <div class="ent-tl-footer">
                            <?php if ( $show_km && $route['km'] ) : ?>
                                <span class="ent-tl-km"><?php echo esc_html( $route['km'] ); ?></span>
                            <?php else : ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="ent-tl-arrow" aria-hidden="true">→</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; $n++; endwhile; wp_reset_postdata(); ?>
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
