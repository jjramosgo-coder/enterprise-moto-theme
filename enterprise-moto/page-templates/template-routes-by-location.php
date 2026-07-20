<?php
/**
 * Template Name: Mapa de rutas por localización
 *
 * PÁGINA-DESTINO del bloque «Mapa de rutas por localización» (#17; rediseño
 * definitivo #18, sustituye a la provisional).
 *
 * Lee el filtro compuesto de los parámetros de URL (rbl_cat / rbl_tag: IDs de
 * término separados por comas) y, opcionalmente, rbl_src (id de la página que
 * hospeda el bloque-mapa, para «← Volver al mapa»). Presenta las entradas
 * agrupadas en UNA SECCIÓN POR CATEGORÍA del marcador: cada sección es un
 * carrusel horizontal de .post-card, resuelto con enterprise_stage_query()
 * —categoría IN + etiquetas del marcador IN, relación AND entre grupos— y
 * reutilizando la librería de carrusel del tema (carousel.js / carousel.css +
 * andamiaje .ent-stages, SIN modificarla; assets encolados por
 * enterprise_carousel_assets()). La tarjeta .post-card se reutiliza tal cual.
 *
 * Cada tarjeta propaga el contexto de navegación from_loc / loc_cat / loc_tag
 * para el prev/next del destino (contrato de navegación §6/§13.13, resuelto en
 * single.php).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* Filtro compuesto desde la URL (IDs de término, saneados a enteros positivos)
   y origen del mapa (rbl_src) para el enlace «← Volver al mapa». */
$rbl_cat = isset( $_GET['rbl_cat'] ) ? wp_parse_id_list( wp_unslash( $_GET['rbl_cat'] ) ) : array();
$rbl_tag = isset( $_GET['rbl_tag'] ) ? wp_parse_id_list( wp_unslash( $_GET['rbl_tag'] ) ) : array();
$rbl_src = isset( $_GET['rbl_src'] ) ? intval( wp_unslash( $_GET['rbl_src'] ) ) : 0;

/* Id de esta página-destino: se estampa en las tarjetas como from_loc. Se captura
   una vez, antes de cualquier bucle de query (a imagen de trip-collection). */
$dest_page_id = get_queried_object_id();

/* Destino de «← Volver al mapa»: permalink de la página que hospeda el mapa
   (rbl_src) si es una página publicada; si no, el referer; si tampoco, se oculta. */
$back_map_url = '';
if ( $rbl_src > 0 && 'publish' === get_post_status( $rbl_src ) ) {
    $link = get_permalink( $rbl_src );
    if ( $link ) $back_map_url = $link;
}
if ( '' === $back_map_url ) {
    $ref = wp_get_referer();
    if ( $ref ) $back_map_url = $ref;
}

get_header();
?>

<!-- ══ CABECERA ══ -->
<div class="archive-header">
  <div class="container">
    <?php if ( '' !== $back_map_url ) : ?>
      <a href="<?php echo esc_url( $back_map_url ); ?>" class="post-back"><?php esc_html_e( '← Volver al mapa', 'enterprise-moto' ); ?></a>
    <?php endif; ?>
    <div class="archive-label"><?php esc_html_e( 'Localización', 'enterprise-moto' ); ?></div>
    <h1 class="archive-title"><?php echo esc_html( get_the_title() ); ?></h1>
    <?php
    /* Línea "relacionadas con" con los nombres de los términos activos. */
    $rbl_names = array();
    foreach ( $rbl_cat as $cid ) { $t = get_term( $cid, 'category' ); if ( $t && ! is_wp_error( $t ) ) $rbl_names[] = $t->name; }
    foreach ( $rbl_tag as $tid ) { $t = get_term( $tid, 'post_tag' ); if ( $t && ! is_wp_error( $t ) ) $rbl_names[] = $t->name; }
    if ( ! empty( $rbl_names ) ) {
        echo '<p class="archive-desc">'
           . esc_html__( 'Entradas relacionadas con:', 'enterprise-moto' ) . ' '
           . esc_html( implode( ', ', $rbl_names ) ) . '</p>';
    }
    ?>
  </div>
</div>

<!-- ══ SECCIONES: UN CARRUSEL DE .post-card POR CATEGORÍA (§3.1/§3.2) ══ -->
<div class="archive-posts">
  <?php if ( empty( $rbl_cat ) && empty( $rbl_tag ) ) : ?>
    <p style="padding:40px 0;color:var(--mid);"><?php
      esc_html_e( 'Esta página muestra las entradas de una localización. Accede a ella desde un marcador del mapa.', 'enterprise-moto' );
    ?></p>
  <?php else :
    $tag_str     = implode( ',', $rbl_tag );
    $any_section = false;

    /* Una sección (carrusel) por cada categoría del marcador, en el orden de
       rbl_cat. La categoría sin entradas coincidentes se omite. La unión de las
       secciones equivale al resultado del filtro compuesto; una entrada en dos
       categorías del marcador aparece en ambas (intencionado, §3.1). */
    foreach ( $rbl_cat as $cat_i ) :
        $sec_query = enterprise_stage_query( array(
            'categoryIds'  => array( $cat_i ),
            'tagIds'       => $rbl_tag,
            'tagRelation'  => 'IN',   // OR entre etiquetas; enterprise_stage_query hace AND entre los grupos
            'postsPerPage' => -1,
            'orderBy'      => 'date',
            'order'        => 'DESC',
        ) );

        if ( ! $sec_query->have_posts() ) { wp_reset_postdata(); continue; }
        $any_section = true;

        $cat_term = get_term( $cat_i, 'category' );
        $cat_name = ( $cat_term && ! is_wp_error( $cat_term ) ) ? $cat_term->name : '';
        $total    = $sec_query->post_count;
        $uid      = 'ent-rbl-' . intval( $cat_i ) . '-' . wp_rand( 1000, 9999 );
        $has_nav  = ( $total > 1 );
        ?>

        <!-- ── Carrusel de la categoría: reutiliza el andamiaje .ent-stages (carousel.js/carousel.css) ── -->
        <div class="ent-stages ent-stages--carousel" id="<?php echo esc_attr( $uid ); ?>" data-layout="carousel">

            <div class="ent-stages__head">
                <h2 class="ent-stages__heading"><?php echo esc_html( $cat_name ); ?></h2>
                <?php if ( $has_nav ) : ?>
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

            <div class="ent-stages__track" role="list">
            <?php
            $n = 1;
            while ( $sec_query->have_posts() ) : $sec_query->the_post();
                $route     = enterprise_get_route_data();
                $card_cat  = enterprise_first_category();
                /* Contexto de navegación del destino: categoría del carrusel + tags
                   del marcador (§3.5). Se estampa en TODOS los enlaces de la tarjeta
                   para que el prev/next se conserve sea cual sea el enlace pulsado. */
                $card_args = array(
                    'from_loc' => $dest_page_id,
                    'loc_cat'  => $cat_i,
                    'loc_tag'  => $tag_str,
                );
                if ( $rbl_src > 0 ) { $card_args['loc_src'] = $rbl_src; }
                $card_href = add_query_arg( $card_args, get_permalink() );
            ?>
                <div class="ent-stages__slide" role="listitem" data-index="<?php echo intval( $n - 1 ); ?>">
                    <article <?php post_class( 'post-card' ); ?> id="post-<?php the_ID(); ?>">
                        <a href="<?php echo esc_url( $card_href ); ?>" tabindex="-1" aria-hidden="true">
                            <div class="post-card-thumb">
                                <div class="post-card-thumb-inner">
                                    <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'enterprise-card', array( 'loading' => 'lazy' ) );
                                    else : ?><div class="post-card-thumb-fallback">🏍️</div><?php endif; ?>
                                </div>
                                <span class="post-card-num" aria-hidden="true"><?php echo str_pad( $n, 2, '0', STR_PAD_LEFT ); ?></span>
                            </div>
                        </a>
                        <div class="post-card-body">
                            <div class="entry-tags">
                                <span class="entry-tag entry-tag--cat"><?php echo esc_html( $card_cat ); ?></span>
                                <span class="entry-tag entry-tag--date"><?php the_date( 'Y' ); ?></span>
                            </div>
                            <h2 class="post-card-title"><a href="<?php echo esc_url( $card_href ); ?>"><?php the_title(); ?></a></h2>
                            <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                            <div class="post-card-footer">
                              <div class="post-card-km">
                                <?php if ( $route['km'] ) echo esc_html( $route['km'] ) . ' <span>km</span>';
                                else echo '<span>' . esc_html__( 'Ruta', 'enterprise-moto' ) . '</span>'; ?>
                              </div>
                              <a href="<?php echo esc_url( $card_href ); ?>" class="post-card-arrow" aria-label="<?php echo esc_attr( get_the_title() ); ?>">→</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php $n++; endwhile; ?>
            </div>

            <?php if ( $has_nav ) : ?>
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
        wp_reset_postdata();
    endforeach;

    /* Hay filtro pero ninguna categoría del marcador tiene entradas. */
    if ( ! $any_section ) : ?>
    <p style="padding:40px 0;color:var(--mid);"><?php
      esc_html_e( 'No hay entradas que coincidan con esta localización todavía.', 'enterprise-moto' );
    ?></p>
    <?php endif;
  endif; ?>
</div>

<?php get_footer();
