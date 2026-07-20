<?php
/**
 * Template Name: Mapa de rutas por localización (provisional)
 *
 * PÁGINA-DESTINO PROVISIONAL del bloque «Mapa de rutas por localización» (#17).
 * Lee el filtro compuesto de los parámetros de URL (rbl_cat / rbl_tag: IDs de
 * término separados por comas), lo traduce a enterprise_stage_query()
 * —categorías IN, etiquetas IN, relación AND entre grupos— y pinta un grid simple
 * reutilizando el patrón de tarjeta de archive.php.
 *
 * DELIBERADAMENTE PROVISIONAL: existe solo para validar el bloque #17. Su rediseño
 * definitivo (estética según mockups, URL/enrutado limpio, contexto de navegación
 * prev/next) es el TO-DO #18 y SUSTITUIRÁ esta plantilla.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* Filtro compuesto desde la URL (IDs de término, saneados a enteros positivos). */
$rbl_cat = isset( $_GET['rbl_cat'] ) ? wp_parse_id_list( wp_unslash( $_GET['rbl_cat'] ) ) : array();
$rbl_tag = isset( $_GET['rbl_tag'] ) ? wp_parse_id_list( wp_unslash( $_GET['rbl_tag'] ) ) : array();

get_header();
?>

<!-- ══ CABECERA (provisional) ══ -->
<div class="archive-header">
  <div class="container">
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

<!-- ══ GRID DE POSTS (patrón de archive.php) ══ -->
<div class="archive-posts">
  <?php if ( empty( $rbl_cat ) && empty( $rbl_tag ) ) : ?>
    <p style="padding:40px 0;color:var(--mid);"><?php
      esc_html_e( 'Esta página muestra las entradas de una localización. Accede a ella desde un marcador del mapa.', 'enterprise-moto' );
    ?></p>
  <?php else :
    $rbl_query = enterprise_stage_query( array(
        'categoryIds'  => $rbl_cat,
        'tagIds'       => $rbl_tag,
        'tagRelation'  => 'IN',   // OR entre etiquetas; enterprise_stage_query hace AND entre los grupos
        'postsPerPage' => -1,
        'orderBy'      => 'date',
        'order'        => 'DESC',
    ) );

    if ( $rbl_query->have_posts() ) : ?>
    <div class="posts-grid">
      <?php $n = 1; while ( $rbl_query->have_posts() ) : $rbl_query->the_post();
        $route          = enterprise_get_route_data();
        $cat_name       = enterprise_first_category();
        $card_permalink = get_permalink();
      ?>
        <article <?php post_class( 'post-card' ); ?> id="post-<?php the_ID(); ?>">
          <a href="<?php echo esc_url( $card_permalink ); ?>" tabindex="-1" aria-hidden="true">
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
              <span class="entry-tag entry-tag--cat"><?php echo esc_html( $cat_name ); ?></span>
              <span class="entry-tag entry-tag--date"><?php the_date( 'Y' ); ?></span>
            </div>
            <h2 class="post-card-title"><a href="<?php echo esc_url( $card_permalink ); ?>"><?php the_title(); ?></a></h2>
            <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <div class="post-card-footer">
              <div class="post-card-km">
                <?php if ( $route['km'] ) echo esc_html( $route['km'] ) . ' <span>km</span>';
                else echo '<span>' . esc_html__( 'Ruta', 'enterprise-moto' ) . '</span>'; ?>
              </div>
              <a href="<?php echo esc_url( $card_permalink ); ?>" class="post-card-arrow" aria-label="<?php echo esc_attr( get_the_title() ); ?>">→</a>
            </div>
          </div>
        </article>
      <?php $n++; endwhile; ?>
    </div>
    <?php wp_reset_postdata(); else : ?>
    <p style="padding:40px 0;color:var(--mid);"><?php
      esc_html_e( 'No hay entradas que coincidan con esta localización todavía.', 'enterprise-moto' );
    ?></p>
    <?php endif;
  endif; ?>
</div>

<?php get_footer();
