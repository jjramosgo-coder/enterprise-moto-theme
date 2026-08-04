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
 *     (nombre de la región) + subtítulo + fila de cifras (nº etapas + nº viajes) +
 *     ticker (campo «Nombre en el ticker», _post_ticker_name, de las etapas del término;
 *     si está vacío cae al título de la entrada).
 *   · Carrusel 1 — ETAPAS del término `regiones` (Tipo B/C), reutilizando la
 *     .trip-card (enterprise_trip_card_data) y el andamiaje .ent-stages del
 *     carrusel compartido (carousel.js/carousel.css), SIN modificarlos.
 *   · Carrusel 2 — entradas TIPO D («viaje») que CONTIENEN alguna de esas etapas
 *     (por el filtro propio del viaje). NUNCA páginas «Colección de viajes»
 *     (modelo A+B/colecciones anulado, ver §0 del requisito y §13.19). Estampa el
 *     contexto de navegación from_region, igual que el carrusel de etapas (#65
 *     reabre el salto plano de §3.5).
 *
 * Estados vacíos (§3.4 del requisito): la taxonomía `regiones` es public=false, así
 * que el único estado alcanzable por navegación es etapas>0 (el globo con count=0
 * NO enlaza). El acceso por URL directa a un region_code inválido o a una región
 * con 0 etapas muestra el mensaje de fallback.
 *
 * El contexto de navegación from_region lo estampan AMBOS carruseles: las tarjetas de
 * ETAPA desde #46 (Commit 4) y las de VIAJE desde #65 (que reabre el salto plano de §3.5).
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
/* Nombre a mostrar. El nombre del término puede venir en la forma «Nativo [Español]»
   (mismo convenio bilingüe que parsea el globo del mapa, parseName en region-map-frontend.js):
   si hay texto entre corchetes, se usa el español; si no, el nombre tal cual. */
$region_name = trim( (string) $region_term->name );
if ( preg_match( '/^(.*?)\s*\[(.*?)\]\s*$/', $region_name, $mname ) ) {
    $native  = trim( $mname[1] );
    $spanish = trim( $mname[2] );
    $region_name = ( '' !== $spanish ) ? $spanish : $native;
}

/* Carrusel 2 (#46 Commit 2): entradas TIPO D que contienen alguna etapa de la región.
   Derivado al vuelo y cacheado por region_code (transient invalidado al guardar etapa/viaje).
   Se usa la cifra en el hero y la lista en el carrusel de abajo. */
$region_code_stored = (string) get_term_meta( $region_term->term_id, 'region_code', true );
$trip_ids   = function_exists( 'enterprise_region_trip_ids' )
                ? enterprise_region_trip_ids( $region_code_stored )
                : array();
$trip_count = count( $trip_ids );

/* Título con la última palabra en dorado (mismo patrón que col-hero). */
$parts = preg_split( '/\s+/', trim( $region_name ) );
if ( is_array( $parts ) && count( $parts ) > 1 ) {
    $last       = array_pop( $parts );
    $title_html = esc_html( implode( ' ', $parts ) ) . ' <em>' . esc_html( $last ) . '</em>';
} else {
    $title_html = '<em>' . esc_html( $region_name ) . '</em>';
}

/* Ticker: campo «Nombre en el ticker» (_post_ticker_name) de las etapas del término, en
   mayúsculas, deduplicado, en orden de aparición del carrusel. Semántica del campo (igual
   que el col-hero): si está vacío, se usa el título de la entrada. */
$ticker_names = array();
foreach ( $stage_q->posts as $p ) {
    $name = trim( (string) get_post_meta( $p->ID, '_post_ticker_name', true ) );
    if ( '' === $name ) {
        $name = get_the_title( $p->ID );
    }
    $name = trim( (string) $name );
    if ( '' === $name ) {
        continue;
    }
    $name = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $name, 'UTF-8' ) : strtoupper( $name );
    if ( ! in_array( $name, $ticker_names, true ) ) {
        $ticker_names[] = $name;
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

    <p class="col-subtitle"><?php esc_html_e( 'Rutas que cruzaron esta región', 'enterprise-moto' ); ?></p>

    <div class="col-stats">
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $stage_count ); ?></div><div class="col-stat-l"><?php echo esc_html( _n( 'Etapa', 'Etapas', $stage_count, 'enterprise-moto' ) ); ?></div></div>
      <?php if ( $trip_count > 0 ) : ?>
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $trip_count ); ?></div><div class="col-stat-l"><?php echo esc_html( _n( 'Viaje', 'Viajes', $trip_count, 'enterprise-moto' ) ); ?></div></div>
      <?php endif; ?>
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

          /* #46 Commit 4: contexto de navegación from_region en la card de ETAPA (y, desde
             #65, también en las de viaje del carrusel 2). from_region = esta página;
             region_code = código de la región; region_src = origen del mapa (para «← Volver
             al mapa»). single.php reconstruye prev/next desde la secuencia del término
             `regiones` y «Volver» aquí. */
          $card_args = array(
              'from_region' => $dest_page_id,
              'region_code' => $region_code_stored,
          );
          if ( $region_src > 0 ) { $card_args['region_src'] = $region_src; }
          $card_href = add_query_arg( $card_args, get_permalink() );
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

    <!-- ── Carrusel 2: entradas TIPO D que contienen alguna etapa de la región (estampa contexto from_region, #65) ── -->
    <?php if ( $trip_count > 0 ) :
      $bg2      = array( 'bg1', 'bg2', 'bg3', 'bg4', 'bg5' );
      $uid2     = 'ent-region-trips-' . intval( $region_term->term_id ) . '-' . wp_rand( 1000, 9999 );
      $has_nav2 = ( $trip_count > 1 );
    ?>
    <div class="ent-stages ent-stages--carousel ent-trip-collection" id="<?php echo esc_attr( $uid2 ); ?>" data-layout="carousel">

      <div class="ent-stages__head">
        <h2 class="ent-stages__heading"><?php esc_html_e( 'Viajes', 'enterprise-moto' ); ?></h2>
        <?php if ( $has_nav2 ) : ?>
        <div class="ent-stages__nav">
          <button class="ent-stages__nav-btn ent-stages__nav-btn--prev"
                  data-target="<?php echo esc_attr( $uid2 ); ?>"
                  aria-label="<?php esc_attr_e( 'Anterior', 'enterprise-moto' ); ?>"
                  type="button">←</button>
          <span class="ent-stages__nav-count">
            <span class="ent-stages__nav-current">1</span> / <?php echo intval( $trip_count ); ?>
          </span>
          <button class="ent-stages__nav-btn ent-stages__nav-btn--next"
                  data-target="<?php echo esc_attr( $uid2 ); ?>"
                  aria-label="<?php esc_attr_e( 'Siguiente', 'enterprise-moto' ); ?>"
                  type="button">→</button>
        </div>
        <?php endif; ?>
      </div>

      <div class="ent-stages__track" role="list">
      <?php
      /* #65: cada ítem es una entrada Tipo D («viaje»). Se estampa el MISMO contexto de
         navegación `from_region` que la card de etapa (l.231-236), reabriendo §3.5 de #46
         (el carrusel de viajes deja de ser salto plano). Así el «Volver» del viaje resuelve
         por la rama from_region de single.php a la página de región, en vez de caer al referer
         (que producía el bucle viaje↔etapa). Tarjeta .trip-card poblada con
         enterprise_trip_card_data (branching «Viaje»). */
      $m = 0;
      foreach ( $trip_ids as $viaje_id ) :
          $viaje_id = (int) $viaje_id;
          if ( 'publish' !== get_post_status( $viaje_id ) ) { continue; }

          $data      = enterprise_trip_card_data( $viaje_id );
          $t_title   = get_the_title( $viaje_id );
          $t_thumb   = get_the_post_thumbnail_url( $viaje_id, 'enterprise-card' );
          $t_excerpt = get_the_excerpt( $viaje_id );

          /* #65: contexto from_region en la card de VIAJE, idéntico al de la card de etapa
             (from_region = esta página; region_code = código de la región; region_src = origen
             del mapa, si lo hay). single.php bifurca prev/next por tipo de entrada. */
          $t_card_args = array(
              'from_region' => $dest_page_id,
              'region_code' => $region_code_stored,
          );
          if ( $region_src > 0 ) { $t_card_args['region_src'] = $region_src; }
          $t_href    = add_query_arg( $t_card_args, get_permalink( $viaje_id ) );

          $t_km_str  = enterprise_km_display( $data['km'] );
          if ( '' !== $t_km_str && $data['km_inc'] ) { $t_km_str = '≈' . $t_km_str; }

          $bg_class2 = $bg2[ $m % count( $bg2 ) ];
      ?>
        <div class="ent-stages__slide" role="listitem" data-index="<?php echo intval( $m ); ?>">
          <a href="<?php echo esc_url( $t_href ); ?>" class="trip-card">
            <div class="trip-thumb <?php echo esc_attr( $bg_class2 ); ?>">
              <?php if ( $t_thumb ) : ?>
                <img src="<?php echo esc_url( $t_thumb ); ?>" alt="<?php echo esc_attr( $t_title ); ?>" loading="lazy">
              <?php else : ?>
                <span class="trip-thumb-fallback" aria-hidden="true">🏍️</span>
              <?php endif; ?>
              <span class="type-badge"><?php echo esc_html( $data['tipo_label'] ); ?></span>
              <?php if ( $data['year'] ) : ?>
                <span class="year-badge"><?php echo esc_html( $data['year'] ); ?></span>
              <?php endif; ?>
            </div>
            <div class="trip-body">
              <div class="trip-title"><?php echo esc_html( $t_title ); ?></div>
              <?php if ( $t_excerpt ) : ?>
                <div class="trip-desc"><?php echo esc_html( $t_excerpt ); ?></div>
              <?php endif; ?>
              <div class="trip-meta">
                <div class="trip-meta-i">
                  <div class="trip-meta-n"><?php echo '' !== $t_km_str ? esc_html( $t_km_str ) : '—'; ?></div>
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
      <?php $m++; endforeach; ?>
      </div>

      <?php if ( $has_nav2 ) : ?>
      <div class="ent-stages__dots" aria-hidden="true">
        <?php for ( $i = 0; $i < $trip_count; $i++ ) : ?>
          <button class="ent-stages__dot <?php echo $i === 0 ? 'is-active' : ''; ?>"
                  data-target="<?php echo esc_attr( $uid2 ); ?>"
                  data-index="<?php echo intval( $i ); ?>"
                  type="button"></button>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
    <?php endif; /* $trip_count > 0 */ ?>

  </div>
</div>

<?php get_footer(); ?>
