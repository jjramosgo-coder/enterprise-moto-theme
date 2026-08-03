<?php
/**
 * Template Name: Página de destino por región
 *
 * PÁGINA-DESTINO del bloque «Mapa interactivo de regiones» (#41). El globo de una
 * unidad terminal del mapa (región terminal o provincia) enlaza aquí transportando
 * el region_code en la URL (?region=<code>). Cabeza del Bloque III (#46).
 *
 * Lee ?region=<code> (código ISO 3166-2 sin guion = id del <path> del SVG = clave
 * de join del término de la taxonomía `regiones`) y, opcionalmente, region_src (id
 * de la página que hospeda el mapa, para «← Volver al mapa»). Presenta:
 *   · Hero decorado (patrón col-hero, coleccion.css): marca de agua + título
 *     (nombre de la región) + subtítulo + fila de cifras + ticker (campo TRAMO
 *     «origen → destino», _post_tramo, de las etapas del término).
 *   · Carrusel 1 — ETAPAS del término `regiones` (Tipo B/C), reutilizando la
 *     .trip-card (enterprise_trip_card_data) y el andamiaje .ent-stages del
 *     carrusel compartido (carousel.js/carousel.css), SIN modificarlos.
 *   · Carrusel 2 — VIAJES «Colección de viajes» que incluyen alguna de esas etapas
 *     (se añade en el Commit 2 de #46; junto con la cifra «Viajes» del hero).
 *
 * Estados vacíos (§3.4 del requisito): la taxonomía `regiones` es public=false, así
 * que el único estado alcanzable por navegación es etapas>0 (el globo con count=0
 * NO enlaza). El acceso por URL directa a un region_code inválido o a una región
 * con 0 etapas muestra el mensaje de fallback.
 *
 * El contexto de navegación from_region se estampa en las tarjetas de ETAPA en el
 * Commit 4 de #46 (aquí las tarjetas enlazan con permalink plano).
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* region_code de la URL, saneado a alfanumérico y mayúsculas (ISO 3166-2 sin
   guion). region_src: id de la página que hospeda el mapa, para «← Volver al mapa». */
$region_code = isset( $_GET['region'] )     ? strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['region'] ) ) ) : '';
$region_src  = isset( $_GET['region_src'] ) ? intval( wp_unslash( $_GET['region_src'] ) ) : 0;

/* Término de `regiones` cuyo region_code coincide (fuente única de la resolución,
   compartida con el contexto from_region de single.php, #46 Commit 4). */
$region_term = ( '' !== $region_code && function_exists( 'enterprise_region_term_by_code' ) )
                 ? enterprise_region_term_by_code( $region_code )
                 : null;

/* Etapas del término. MISMA FUENTE que el prev/next del contexto from_region
   (#46 Commit 4): enterprise_region_stage_query() es el único origen de la
   secuencia, para que la plantilla y single.php no diverjan. */
$stage_q     = ( $region_term && function_exists( 'enterprise_region_stage_query' ) )
                 ? enterprise_region_stage_query( $region_term->term_id )
                 : null;
$stage_count = ( $stage_q instanceof WP_Query ) ? (int) $stage_q->post_count : 0;

$dest_page_id = get_queried_object_id();

get_header();

/* ══ ESTADO DE FALLBACK: region_code inválido/ausente o región con 0 etapas ══ */
if ( ! $region_term || $stage_count < 1 ) :
    if ( $stage_q instanceof WP_Query ) { wp_reset_postdata(); }
    ?>
    <div class="archive-header">
      <div class="container">
        <div class="archive-label"><?php esc_html_e( 'Región', 'enterprise-moto' ); ?></div>
        <h1 class="archive-title"><?php echo esc_html( get_the_title() ); ?></h1>
      </div>
    </div>
    <div class="archive-posts">
      <div class="container">
        <p style="padding:40px 0;color:var(--mid);"><?php
          esc_html_e( '¡Te pasaste de frenada! Esta página muestra las rutas organizadas geográficamente. Regresa al track y accede a ella desde el mapa interactivo.', 'enterprise-moto' );
        ?></p>
      </div>
    </div>
    <?php
    get_footer();
    return;
endif;

/* ══ DATOS DEL HERO ══ */
$region_name = $region_term->name;

/* Título con la última palabra en dorado (mismo patrón que col-hero). */
$parts = preg_split( '/\s+/', trim( $region_name ) );
if ( is_array( $parts ) && count( $parts ) > 1 ) {
    $last       = array_pop( $parts );
    $title_html = esc_html( implode( ' ', $parts ) ) . ' <em>' . esc_html( $last ) . '</em>';
} else {
    $title_html = '<em>' . esc_html( $region_name ) . '</em>';
}

/* Ticker: campo TRAMO (origen → destino, _post_tramo) de las etapas del término,
   en mayúsculas, deduplicado, en orden de aparición del carrusel. */
$ticker_names = array();
foreach ( $stage_q->posts as $p ) {
    $tramo = trim( (string) get_post_meta( $p->ID, '_post_tramo', true ) );
    if ( '' === $tramo ) {
        continue;
    }
    $tramo = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $tramo, 'UTF-8' ) : strtoupper( $tramo );
    if ( ! in_array( $tramo, $ticker_names, true ) ) {
        $ticker_names[] = $tramo;
    }
}
$ticker_loop = array_merge( $ticker_names, $ticker_names ); // duplicado para el bucle infinito

/* Destino de «← Volver al mapa»: permalink de la página que hospeda el mapa
   (region_src) si está publicada; si no, el referer; si tampoco, se oculta.
   (Mismo criterio que template-routes-by-location.php.) */
$back_map_url = '';
if ( $region_src > 0 && 'publish' === get_post_status( $region_src ) ) {
    $link = get_permalink( $region_src );
    if ( $link ) $back_map_url = $link;
}
if ( '' === $back_map_url ) {
    $ref = wp_get_referer();
    if ( $ref ) $back_map_url = $ref;
}
?>

<!-- ══ HERO REGIÓN (patrón col-hero, coleccion.css) ══ -->
<section class="col-hero">
  <div class="col-hero-watermark" aria-hidden="true"><?php
    echo esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $region_name, 'UTF-8' ) : strtoupper( $region_name ) );
  ?></div>

  <div class="col-hero-inner">
    <?php if ( '' !== $back_map_url ) : ?>
      <a href="<?php echo esc_url( $back_map_url ); ?>" class="post-back"><?php esc_html_e( '← Volver al mapa', 'enterprise-moto' ); ?></a>
    <?php endif; ?>

    <h1 class="col-title"><?php echo $title_html; // ya escapado por partes ?></h1>

    <p class="col-subtitle"><?php esc_html_e( 'Rutas y etapas que cruzaron esta región', 'enterprise-moto' ); ?></p>

    <div class="col-stats">
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $stage_count ); ?></div><div class="col-stat-l"><?php echo esc_html( _n( 'Etapa', 'Etapas', $stage_count, 'enterprise-moto' ) ); ?></div></div>
      <?php /* #46 Commit 2: la cifra «Viajes» se añade aquí junto al carrusel 2. */ ?>
    </div>
  </div>

  <?php if ( ! empty( $ticker_names ) ) : ?>
  <div class="ticker" aria-hidden="true">
    <div class="ticker-track">
      <?php foreach ( $ticker_loop as $item ) : ?>
        <span class="ticker-item"><?php echo esc_html( $item ); ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- ══ CUERPO: carruseles ══ -->
<div class="col-body">
  <div class="col-content">

    <!-- ── Carrusel 1: ETAPAS del término `regiones` (reutiliza .ent-stages + .trip-card) ── -->
    <?php
    $bg      = array( 'bg1', 'bg2', 'bg3', 'bg4', 'bg5' );
    $uid     = 'ent-region-stages-' . intval( $region_term->term_id ) . '-' . wp_rand( 1000, 9999 );
    $has_nav = ( $stage_count > 1 );
    ?>
    <div class="ent-stages ent-stages--carousel ent-trip-collection" id="<?php echo esc_attr( $uid ); ?>" data-layout="carousel">

      <div class="ent-stages__head">
        <h2 class="ent-stages__heading"><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></h2>
        <?php if ( $has_nav ) : ?>
        <div class="ent-stages__nav">
          <button class="ent-stages__nav-btn ent-stages__nav-btn--prev"
                  data-target="<?php echo esc_attr( $uid ); ?>"
                  aria-label="<?php esc_attr_e( 'Anterior', 'enterprise-moto' ); ?>"
                  type="button">←</button>
          <span class="ent-stages__nav-count">
            <span class="ent-stages__nav-current">1</span> / <?php echo intval( $stage_count ); ?>
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
      $n = 0;
      while ( $stage_q->have_posts() ) : $stage_q->the_post();
          $data    = enterprise_trip_card_data( get_the_ID() );
          $thumb   = get_the_post_thumbnail_url( null, 'enterprise-card' );
          $excerpt = get_the_excerpt();

          $km_str = enterprise_km_display( $data['km'] );
          if ( '' !== $km_str && $data['km_inc'] ) {
              $km_str = '≈' . $km_str;
          }

          $bg_class = $bg[ $n % count( $bg ) ];

          /* #46 Commit 4: aquí se estampará el contexto from_region en $card_href
             (from_region = $dest_page_id, region_code, region_src). Por ahora,
             enlace plano al relato. */
          $card_href = get_permalink();
      ?>
        <div class="ent-stages__slide" role="listitem" data-index="<?php echo intval( $n ); ?>">
          <a href="<?php echo esc_url( $card_href ); ?>" class="trip-card">
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
              <div class="trip-foot">
                <span class="trip-arrow" aria-hidden="true">→</span>
              </div>
            </div>
          </a>
        </div>
      <?php $n++; endwhile; wp_reset_postdata(); ?>
      </div>

      <?php if ( $has_nav ) : ?>
      <div class="ent-stages__dots" aria-hidden="true">
        <?php for ( $i = 0; $i < $stage_count; $i++ ) : ?>
          <button class="ent-stages__dot <?php echo $i === 0 ? 'is-active' : ''; ?>"
                  data-target="<?php echo esc_attr( $uid ); ?>"
                  data-index="<?php echo intval( $i ); ?>"
                  type="button"></button>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>

    <?php /* #46 Commit 2: aquí se inserta el carrusel 2 (VIAJES «Colección de viajes»). */ ?>

  </div>
</div>

<?php get_footer(); ?>
