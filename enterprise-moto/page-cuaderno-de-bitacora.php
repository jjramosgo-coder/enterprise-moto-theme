<?php
/**
 * Template Name: Cuaderno de bitácora
 *
 * Dos modos según el tipo de página:
 *
 * PORTAL  (/cuaderno-de-bitacora/)
 *   Sin _exp_estado propio y sin página padre.
 *   → Si hay hija activa: redirige a ella.
 *   → Si no: muestra estado "Fuera de ruta".
 *
 * CUADERNO INDIVIDUAL  (/cuaderno-de-bitacora/sicilia-2026/)
 *   Tiene _exp_estado propio O tiene página padre.
 *   → Renderiza el cuaderno directamente.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/* ── DETECCIÓN DEL MODO — ANTES de get_header() para permitir redirección ── */
$portal_id     = get_the_ID();
$exp_estado_p  = get_post_meta( $portal_id, '_exp_estado', true );
$has_parent    = (bool) wp_get_post_parent_id( $portal_id );
$es_individual = ! empty( $exp_estado_p ) || $has_parent;

if ( ! $es_individual ) {
    /* ── MODO PORTAL: buscar cuaderno activo entre las páginas hijas ── */
    $cuaderno_activo = get_posts( array(
        'post_type'   => 'page',
        'post_parent' => $portal_id,
        'post_status' => 'publish',
        'numberposts' => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_query'  => array( array(
            'key'   => '_exp_estado',
            'value' => 'activo',
        ) ),
    ) );

    if ( ! empty( $cuaderno_activo ) ) {
        /* Redirigir a la URL permanente del cuaderno activo */
        wp_redirect( get_permalink( $cuaderno_activo[0]->ID ), 302 );
        exit;
    }

    /* Sin cuaderno activo → modo off-route */
    get_header();
    enterprise_render_off_route( $portal_id, get_the_title( $portal_id ) );
    get_footer();
    return;
}

/* ── MODO CUADERNO INDIVIDUAL — el page_id es esta misma página ── */
get_header();

/* ── CONFIGURACIÓN ─────────────────────────────────────────────── */

$page_id       = $portal_id; /* = get_the_ID() del cuaderno individual */

// Datos del viaje — rellenar en los campos personalizados de la página,
// o editar los valores por defecto aquí:
$exp_nombre    = get_post_meta( $page_id, '_exp_nombre',    true ) ?: get_the_title();
$exp_subtitulo = get_post_meta( $page_id, '_exp_subtitulo', true ) ?: get_the_excerpt();
$exp_km        = get_post_meta( $page_id, '_exp_km',        true ) ?: '';
$exp_paises    = get_post_meta( $page_id, '_exp_paises',    true ) ?: '';
$exp_en_ruta   = get_post_meta( $page_id, '_exp_en_ruta',   true ); // backward compat
$exp_estado    = get_post_meta( $page_id, '_exp_estado',    true ) ?:
                 ( $exp_en_ruta === '1' ? 'activo' : 'finalizado' );
// #4 R3/R4: el progreso ya NO se lee del meta manual _exp_progreso; se computa
// más abajo por estado + fechas (el campo del metabox se retira en el commit 4).

/* Fechas → calcular salida y duración automáticamente */
$exp_fecha_inicio = get_post_meta( $page_id, '_exp_fecha_inicio', true ) ?: '';
$exp_fecha_fin    = get_post_meta( $page_id, '_exp_fecha_fin',    true ) ?: '';
$exp_salida       = get_post_meta( $page_id, '_exp_salida',       true ) ?: '';

if ( ! $exp_salida && $exp_fecha_inicio ) {
    $exp_salida = date_i18n( 'j M Y', strtotime( $exp_fecha_inicio ) );
}
// #4 R3/R4: la duración ya NO se lee del meta manual _exp_duracion ni se calcula
// aquí con time() (esa semántica «vacío = en curso» se retira). Se computa más
// abajo desde enterprise_cuaderno_stats(), por estado y fechas. Se define ahí.
$exp_duracion = '';

// Slug de la categoría y etiquetas que agrupan las etapas
// DEPRECATED — sustituidos por _filt_* ; se mantienen solo para backward compat del ticker de page-bitacora-bloques
$categoria_slug = get_post_meta( $page_id, '_exp_categoria', true ) ?: '';
$etiquetas_raw  = get_post_meta( $page_id, '_exp_etiquetas', true ) ?: '';

/* ── QUERY DE ETAPAS — filtros del metabox (secciones 3-4) ─────── */
$filt_cat_ids   = get_post_meta( $page_id, '_filt_category_ids',  true ) ?: array();
$filt_tag_ids   = get_post_meta( $page_id, '_filt_tag_ids',       true ) ?: array();
$filt_tag_rel   = get_post_meta( $page_id, '_filt_tag_relation',  true ) ?: 'OR';
$filt_date_from = get_post_meta( $page_id, '_filt_date_from',     true ) ?: '';
$filt_date_to   = get_post_meta( $page_id, '_filt_date_to',       true ) ?: '';
$filt_limit     = get_post_meta( $page_id, '_filt_limit',         true ); // '' = sin límite
$filt_orderby   = get_post_meta( $page_id, '_filt_orderby',       true ) ?: 'date';
$filt_order     = get_post_meta( $page_id, '_filt_order',         true ) ?: 'DESC';
$pres_layout    = get_post_meta( $page_id, '_pres_layout',        true ) ?: 'timeline';
$pres_card_size = get_post_meta( $page_id, '_pres_card_size',     true ) ?: 'normal';
$pres_excerpt   = get_post_meta( $page_id, '_pres_show_excerpt',  true );
$pres_km        = get_post_meta( $page_id, '_pres_show_km',       true );
$pres_date      = get_post_meta( $page_id, '_pres_show_date',     true );
$pres_excerpt   = ( $pres_excerpt === '' ) ? true : (bool) $pres_excerpt;
$pres_km        = ( $pres_km      === '' ) ? true : (bool) $pres_km;
$pres_date      = ( $pres_date    === '' ) ? true : (bool) $pres_date;
$is_carousel    = ( 'carousel' === $pres_layout );
$is_large       = ( 'large'    === $pres_card_size );

$filt_cat_ids = is_array( $filt_cat_ids ) ? array_map( 'intval', $filt_cat_ids ) : array();
$filt_tag_ids = is_array( $filt_tag_ids ) ? array_map( 'intval', $filt_tag_ids ) : array();

$query_args = array(
    'post_type'      => 'post',
    'posts_per_page' => ( $filt_limit !== '' && intval( $filt_limit ) > 0 ) ? intval( $filt_limit ) : -1,
    'orderby'        => $filt_orderby,
    'order'          => strtoupper( $filt_order ),
    'post_status'    => 'publish',
);

/* tax_query idéntica a la del bloque post-stages */
$tax_query = array();
if ( ! empty( $filt_cat_ids ) ) {
    $tax_query[] = array(
        'taxonomy' => 'category',
        'field'    => 'term_id',
        'terms'    => $filt_cat_ids,
        'operator' => 'IN',   // OR entre categorías seleccionadas
    );
}
if ( ! empty( $filt_tag_ids ) ) {
    $tag_operator = ( $filt_tag_rel === 'AND' ) ? 'AND' : 'IN';
    $tax_query[] = array(
        'taxonomy' => 'post_tag',
        'field'    => 'term_id',
        'terms'    => $filt_tag_ids,
        'operator' => $tag_operator,
    );
}
if ( ! empty( $tax_query ) ) {
    $tax_query['relation'] = 'AND'; // categorías AND etiquetas
    $query_args['tax_query'] = $tax_query;
}

/* Filtro de fechas absolutas */
if ( $filt_date_from || $filt_date_to ) {
    $date_q = array( 'relation' => 'AND' );
    if ( $filt_date_from ) {
        $date_q[] = array( 'after' => $filt_date_from . ' 00:00:00', 'inclusive' => true );
    }
    if ( $filt_date_to ) {
        $date_q[] = array( 'before' => $filt_date_to . ' 23:59:59', 'inclusive' => true );
    }
    $query_args['date_query'] = $date_q;
}

$etapas_query = new WP_Query( $query_args );

$total_etapas = $etapas_query->found_posts;
$etapas       = $etapas_query->posts;

// La primera es la más reciente (la "activa")
$ultima_etapa = ! empty( $etapas ) ? $etapas[0] : null;

/* #4 R2/R3: estadísticas en caliente desde la fuente única enterprise_cuaderno_stats().
   La query de arriba se CONSERVA porque el listado (carrusel/timeline) necesita los
   objetos post; de ella sale el conteo de etapas ($total_etapas). El km, la duración y
   el progreso salen de la función, que resuelve la fin heredada (R5) y las fechas. */
$c_stats              = enterprise_cuaderno_stats( $page_id );
$exp_km               = enterprise_km_display( $c_stats['km'] ); // '' si no hay km
$c_dias_totales       = (int) $c_stats['dias_totales'];
$c_dias_transcurridos = (int) $c_stats['dias_transcurridos'];

/* Duración mostrada y modo de progreso, según estado (tabla R3 del análisis).
   El estado manda en los extremos; las fechas solo interpolan en 'activo' con fin. */
$exp_duracion = '';    // se rellena si procede
$prog_show    = false; // ¿mostrar widget de progreso?
$prog_bar     = false; // true = barra %, false = «día N en ruta» (sin %)
$prog_pct     = 0;     // % de la barra
$prog_dia_n   = 0;     // N para «día N en ruta»

if ( $exp_estado === 'preparando' ) {
    // Ni duración ni progreso.
} elseif ( $exp_estado === 'activo' ) {
    if ( $exp_fecha_inicio === '' ) {
        // R5: 'activo' sin fecha de inicio → no se calcula progreso; widget oculto.
    } elseif ( $c_dias_totales > 0 ) {
        // 'activo' con fin: barra de % = transcurridos / totales (clamp 0-100).
        $exp_duracion = $c_dias_totales . ' ' . _n( 'día', 'días', $c_dias_totales, 'enterprise-moto' );
        $prog_show = true;
        $prog_bar  = true;
        $prog_pct  = max( 0, min( 100, (int) round( $c_dias_transcurridos / $c_dias_totales * 100 ) ) );
    } else {
        // 'activo' sin fin: sin %, indicador «día N en ruta».
        $exp_duracion = sprintf(
            _n( '%d día, en curso', '%d días, en curso', $c_dias_transcurridos, 'enterprise-moto' ),
            $c_dias_transcurridos
        );
        $prog_show  = true;
        $prog_bar   = false;
        $prog_dia_n = $c_dias_transcurridos;
    }
} else {
    // 'finalizado': progreso 100 % por definición del estado.
    $prog_show = true;
    $prog_bar  = true;
    $prog_pct  = 100;
    if ( $c_dias_totales > 0 ) {
        // Con fin real o heredada de la última etapa (R5).
        $exp_duracion = $c_dias_totales . ' ' . _n( 'día', 'días', $c_dias_totales, 'enterprise-moto' );
    }
    // 'finalizado' sin fin resoluble (sin etapas) → duración omitida (R5).
}

/* ── VARIABLES DE ESTADO ──────────────────────────────────────── */

if ( $exp_estado === 'activo' ) {
    $estado_label = __( 'En ruta ahora mismo', 'enterprise-moto' );
    $estado_color = '#ff6b6b';
} elseif ( $exp_estado === 'preparando' ) {
    $estado_label = __( 'En preparación', 'enterprise-moto' );
    $estado_color = '#f2c118';
} else {
    $estado_label = __( 'Viaje completado', 'enterprise-moto' );
    $estado_color = '#1a9a4a';
}

?>

<!-- ════════════════════════════════════════════
     HERO DE EXPEDICIÓN
════════════════════════════════════════════ -->
<section class="exp-hero" aria-label="<?php echo esc_attr( $exp_nombre ); ?>">

  <!-- Watermark con el nombre -->
  <div class="exp-hero-watermark" aria-hidden="true">
    <?php echo esc_html( strtoupper( $exp_nombre ) ); ?>
  </div>

  <div class="exp-hero-inner container">
    <div class="exp-hero-left">

      <!-- Breadcrumb -->
      <nav class="exp-breadcrumb" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'enterprise-moto' ); ?>">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'enterprise-moto' ); ?></a>
        <span aria-hidden="true">›</span>
        <span><?php echo esc_html( $exp_nombre ); ?></span>
      </nav>

      <!-- Badge de estado -->
      <div class="exp-status-badge <?php echo $exp_estado === 'activo' ? 'is-live' : ( $exp_estado === 'preparando' ? 'is-prep' : 'is-done' ); ?>">
        <?php if ( $exp_estado === 'activo' ) : ?>
          <span class="live-dot" aria-hidden="true"></span>
        <?php elseif ( $exp_estado === 'preparando' ) : ?>
          <span aria-hidden="true">🔧</span>
        <?php else : ?>
          <span aria-hidden="true">✓</span>
        <?php endif; ?>
        <?php echo esc_html( $estado_label ); ?>
      </div>

      <!-- Título -->
      <h1 class="exp-title"><?php echo esc_html( $exp_nombre ); ?></h1>

      <?php if ( $exp_subtitulo ) : ?>
        <p class="exp-subtitle"><?php echo esc_html( $exp_subtitulo ); ?></p>
      <?php endif; ?>

      <!-- Estadísticas -->
      <div class="exp-stats" role="list" aria-label="<?php esc_attr_e( 'Estadísticas del viaje', 'enterprise-moto' ); ?>">
        <?php if ( $exp_duracion ) : ?>
          <div class="exp-stat" role="listitem">
            <div class="exp-stat-n"><?php echo esc_html( $exp_duracion ); ?></div>
            <div class="exp-stat-l"><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></div>
          </div>
        <?php endif; ?>
        <div class="exp-stat" role="listitem">
          <div class="exp-stat-n">
            <?php echo intval( $total_etapas ); ?>
            <?php if ( $c_dias_totales > 0 ) : ?>
              <span style="font-size:.45em;font-weight:300;color:var(--mid,#5a5a5a);letter-spacing:0;"> / <?php echo intval( $c_dias_totales ); ?> <?php esc_html_e('días','enterprise-moto'); ?></span>
            <?php endif; ?>
          </div>
          <div class="exp-stat-l"><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></div>
        </div>
        <?php if ( $exp_km ) : ?>
          <div class="exp-stat" role="listitem">
            <div class="exp-stat-n"><?php echo esc_html( $exp_km ); ?></div>
            <div class="exp-stat-l"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div>
          </div>
        <?php endif; ?>
        <?php if ( $exp_paises ) : ?>
          <div class="exp-stat" role="listitem">
            <div class="exp-stat-n"><?php echo esc_html( $exp_paises ); ?></div>
            <div class="exp-stat-l"><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></div>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /exp-hero-left -->

    <!-- Barra de progreso — se muestra según el estado (R3): activo y finalizado; nunca en preparando -->
    <?php if ( $prog_show ) : ?>
      <div class="exp-hero-right">
        <div class="exp-progress-widget">
          <div class="exp-progress-label">
            <?php esc_html_e( 'Progreso del viaje', 'enterprise-moto' ); ?>
            <?php if ( $prog_bar ) : ?>
              <span><?php echo intval( $prog_pct ); ?>%</span>
            <?php else : ?>
              <span><?php printf( esc_html__( 'Día %d en ruta', 'enterprise-moto' ), intval( $prog_dia_n ) ); ?></span>
            <?php endif; ?>
          </div>
          <?php if ( $prog_bar ) : ?>
            <div class="exp-progress-track" role="progressbar"
                 aria-valuenow="<?php echo intval( $prog_pct ); ?>"
                 aria-valuemin="0" aria-valuemax="100">
              <div class="exp-progress-fill" style="width:<?php echo intval( $prog_pct ); ?>%"></div>
            </div>
          <?php endif; ?>
          <p class="exp-progress-sub">
            <?php printf(
              esc_html( _n( '%d etapa publicada', '%d etapas publicadas', intval( $total_etapas ), 'enterprise-moto' ) ),
              intval( $total_etapas )
            ); ?>
          </p>
        </div>
      </div>
    <?php endif; ?>

  </div><!-- /exp-hero-inner -->
</section>

<!-- ════════════════════════════════════════════
     TICKER — nombres de etapas
════════════════════════════════════════════ -->
<?php if ( ! empty( $etapas ) ) : ?>
<div class="site-ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php
    // Doble pasada para el bucle infinito
    for ( $pass = 0; $pass < 2; $pass++ ) :
      foreach ( $etapas as $etapa ) :
        echo '<span class="ticker-item">' . esc_html( strtoupper( $etapa->post_title ) ) . '</span>';
      endforeach;
    endfor;
    ?>
  </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════
     ÚLTIMA ETAPA DESTACADA
════════════════════════════════════════════ -->
<?php if ( $ultima_etapa ) :
  $route      = enterprise_get_route_data( $ultima_etapa->ID );
  $excerpt    = get_the_excerpt( $ultima_etapa->ID );
  $permalink  = get_permalink( $ultima_etapa->ID );
  $date_label = get_the_date( 'd M Y', $ultima_etapa->ID );
  $cats       = get_the_category( $ultima_etapa->ID );
?>
<section class="exp-latest-section">
  <div class="container">

    <div class="section-eyebrow">
      <?php esc_html_e( 'Última etapa publicada', 'enterprise-moto' ); ?>
      <?php if ( $exp_estado === 'activo' ) : ?>
        <span class="eyebrow-live" style="margin-left:auto;display:flex;align-items:center;gap:6px;flex-shrink:0;">
          <span class="live-dot" style="background:#e03030;" aria-hidden="true"></span>
          <?php
          /* translators: %s: human time diff */
          printf( esc_html__( 'Actualizado hace %s', 'enterprise-moto' ), human_time_diff( get_post_time( 'U', false, $ultima_etapa->ID ), current_time( 'timestamp' ) ) );
          ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="exp-latest-card">

      <!-- Imagen -->
      <div class="exp-latest-img">
        <?php if ( has_post_thumbnail( $ultima_etapa->ID ) ) : ?>
          <a href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
            <?php echo get_the_post_thumbnail( $ultima_etapa->ID, 'enterprise-wide', array( 'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;' ) ); ?>
          </a>
        <?php else : ?>
          <div class="exp-latest-img-fallback">🏍️</div>
        <?php endif; ?>
        <div class="exp-latest-img-overlay" aria-hidden="true"></div>
        <?php if ( $exp_estado === 'activo' ) : ?>
          <div class="exp-latest-badge">
            <?php esc_html_e( 'Última etapa', 'enterprise-moto' ); ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Cuerpo -->
      <div class="exp-latest-body">
        <div class="exp-latest-date"><?php echo esc_html( $date_label ); ?></div>
        <h2 class="exp-latest-title">
          <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $ultima_etapa->ID ) ); ?></a>
        </h2>

        <?php if ( $excerpt ) : ?>
          <p class="exp-latest-excerpt"><?php echo esc_html( $excerpt ); ?></p>
        <?php endif; ?>

        <!-- Stats de la etapa -->
        <?php if ( ! empty( array_filter( $route ) ) ) : ?>
        <div class="exp-latest-meta">
          <?php if ( $route['km'] ) : ?>
            <div class="exp-latest-meta-item">
              <div class="exp-latest-meta-n"><?php echo esc_html( $route['km'] ); ?></div>
              <div class="exp-latest-meta-l"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div>
            </div>
          <?php endif; ?>
          <?php if ( $route['dias'] ) : ?>
            <div class="exp-latest-meta-item">
              <div class="exp-latest-meta-n"><?php echo esc_html( $route['dias'] ); ?></div>
              <div class="exp-latest-meta-l"><?php esc_html_e( 'Días', 'enterprise-moto' ); ?></div>
            </div>
          <?php endif; ?>
          <?php if ( $route['etapa'] ) : ?>
            <div class="exp-latest-meta-item">
              <div class="exp-latest-meta-n" style="font-size:14px;letter-spacing:.02em;"><?php echo esc_html( $route['etapa'] ); ?></div>
              <div class="exp-latest-meta-l"><?php esc_html_e( 'Tramo', 'enterprise-moto' ); ?></div>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <a href="<?php echo esc_url( $permalink ); ?>" class="btn btn--dark">
          <?php esc_html_e( 'Leer la etapa', 'enterprise-moto' ); ?> →
        </a>
      </div>

    </div><!-- /exp-latest-card -->
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════
     CONTENIDO GUTENBERG (si existe, siempre visible)
════════════════════════════════════════════ -->
<?php
$gutenberg_content = get_the_content( null, false, $page_id );
$has_gutenberg     = ! empty( trim( strip_tags( $gutenberg_content ) ) );
if ( $has_gutenberg ) :
?>
<div class="exp-gutenberg-content container" style="padding-top:40px;padding-bottom:20px;">
  <?php echo apply_filters( 'the_content', $gutenberg_content ); ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════
     ETAPAS — Timeline o Carrusel según metabox
════════════════════════════════════════════ -->
<div class="exp-layout container">

  <!-- Columna principal de etapas -->
  <div class="exp-timeline-col">

    <div class="exp-timeline-head">
      <h2 class="exp-timeline-title">
        <?php echo $exp_estado === 'activo'
          ? esc_html__( 'Etapas realizadas', 'enterprise-moto' )
          : ( $exp_estado === 'preparando'
              ? esc_html__( 'Etapas previstas', 'enterprise-moto' )
              : esc_html__( 'Bitácora completa', 'enterprise-moto' ) );
        ?>
      </h2>
      <span class="exp-timeline-count">
        <?php printf( esc_html__( '%d etapas', 'enterprise-moto' ), $total_etapas ); ?>
      </span>
    </div>

    <?php if ( $etapas_query->have_posts() ) :

      if ( $is_carousel ) :
        // ── MODO CARRUSEL ────────────────────────────────────────────────
        $carousel_id = 'cuaderno-carousel-' . $page_id;
    ?>
    <div class="ps-carousel-wrap <?php echo $is_large ? 'is-large' : ''; ?>" data-carousel="<?php echo esc_attr( $carousel_id ); ?>">
      <div class="ps-carousel" id="<?php echo esc_attr( $carousel_id ); ?>">
        <?php while ( $etapas_query->have_posts() ) : $etapas_query->the_post();
          $route_e  = enterprise_get_route_data();
          $excerpt  = get_the_excerpt();
          $permalink = add_query_arg( 'from_cuaderno', $page_id, get_permalink() );
        ?>
        <div class="ps-card <?php echo $is_large ? 'ps-card--large' : ''; ?>">
          <a href="<?php echo esc_url( $permalink ); ?>" class="ps-card-link">
            <?php if ( has_post_thumbnail() ) : ?>
              <div class="ps-card-thumb">
                <?php the_post_thumbnail( $is_large ? 'enterprise-wide' : 'enterprise-thumb' ); ?>
              </div>
            <?php endif; ?>
            <div class="ps-card-body">
              <?php if ( $pres_date ) : ?>
                <div class="ps-card-date"><?php the_date( 'd M Y' ); ?></div>
              <?php endif; ?>
              <div class="ps-card-title"><?php the_title(); ?></div>
              <?php if ( $pres_excerpt && $excerpt ) : ?>
                <div class="ps-card-excerpt"><?php echo esc_html( $excerpt ); ?></div>
              <?php endif; ?>
              <?php if ( $pres_km && $route_e['km'] ) : ?>
                <div class="ps-card-km"><?php echo esc_html( enterprise_km_display( $route_e['km'] ) ); ?></div>
              <?php endif; ?>
            </div>
          </a>
        </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      <button class="ps-prev" aria-label="<?php esc_attr_e( 'Anterior', 'enterprise-moto' ); ?>">‹</button>
      <button class="ps-next" aria-label="<?php esc_attr_e( 'Siguiente', 'enterprise-moto' ); ?>">›</button>
      <div class="ps-dots" aria-hidden="true"></div>
    </div>

      <?php else :
        // ── MODO TIMELINE ────────────────────────────────────────────────
    ?>
    <ol class="exp-timeline" reversed style="list-style:none;padding:0;">
      <?php
      $etapa_num = $total_etapas;
      $first     = true;
      while ( $etapas_query->have_posts() ) :
        $etapas_query->the_post();
        $route_e   = enterprise_get_route_data();
        $is_latest = $first;
        $first     = false;
      ?>
      <li class="exp-tl-item <?php echo $is_latest ? 'is-latest' : ''; ?>">
        <div class="exp-tl-dot-col" aria-hidden="true">
          <div class="exp-tl-dot <?php echo $is_latest ? 'is-active' : 'is-done'; ?>">
            <?php echo str_pad( $etapa_num, 2, '0', STR_PAD_LEFT ); ?>
          </div>
          <div class="exp-tl-connector <?php echo $etapa_num > 1 ? 'is-done' : ''; ?>"></div>
        </div>
        <a href="<?php echo esc_url( add_query_arg( 'from_cuaderno', $page_id, get_permalink() ) ); ?>" class="exp-tl-card <?php echo $is_latest ? 'is-latest' : ''; ?>">
          <div class="exp-tl-thumb">
            <?php if ( has_post_thumbnail() ) :
              the_post_thumbnail( 'enterprise-thumb' );
            else : ?>
              <div class="exp-tl-thumb-fallback">🏍️</div>
            <?php endif; ?>
          </div>
          <div class="exp-tl-body">
            <?php if ( $pres_date ) : ?>
              <div class="exp-tl-date">
                <?php the_date( 'd M Y' ); ?> · <?php printf( esc_html__( 'Día %d', 'enterprise-moto' ), $etapa_num ); ?>
              </div>
            <?php endif; ?>
            <div class="exp-tl-title"><?php the_title(); ?></div>
            <?php if ( $pres_excerpt ) :
              $exc = get_the_excerpt(); if ( $exc ) : ?>
                <div class="exp-tl-excerpt"><?php echo esc_html( $exc ); ?></div>
            <?php endif; endif; ?>
            <div class="exp-tl-footer">
              <?php if ( $pres_km && $route_e['km'] ) : ?>
                <span class="exp-tl-km"><?php echo esc_html( enterprise_km_display( $route_e['km'] ) ); ?></span>
              <?php endif; ?>
              <span class="exp-tl-arrow" aria-hidden="true">→</span>
            </div>
          </div>
        </a>
      </li>
      <?php $etapa_num--; endwhile; wp_reset_postdata(); ?>
    </ol>

      <?php endif; // fin carousel vs timeline ?>

    <?php else : ?>
      <p style="color:var(--mid);padding:40px 0;">
        <?php esc_html_e( 'Todavía no hay etapas publicadas. ¡Pronto!', 'enterprise-moto' ); ?>
      </p>
    <?php endif; ?>
  </div><!-- /exp-timeline-col -->

  <!-- Summary sticky -->
  <aside class="exp-summary-col" role="complementary" aria-label="<?php esc_attr_e( 'Resumen del viaje', 'enterprise-moto' ); ?>">

    <!-- Resumen -->
    <div class="exp-summary-box">
      <div class="exp-summary-title"><?php echo esc_html( $exp_nombre ); ?></div>
      <dl class="exp-summary-list">
        <?php if ( $exp_salida ) : ?>
          <div class="exp-summary-row">
            <dt><?php esc_html_e( 'Salida', 'enterprise-moto' ); ?></dt>
            <dd><?php echo esc_html( $exp_salida ); ?></dd>
          </div>
        <?php endif; ?>
        <?php if ( $exp_duracion ) : ?>
          <div class="exp-summary-row">
            <dt><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></dt>
            <dd><?php echo esc_html( $exp_duracion ); ?></dd>
          </div>
        <?php endif; ?>
        <?php if ( $exp_km ) : ?>
          <div class="exp-summary-row">
            <dt><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></dt>
            <dd class="gold"><?php echo esc_html( $exp_km ); ?></dd>
          </div>
        <?php endif; ?>
        <div class="exp-summary-row">
          <dt><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></dt>
          <dd><?php echo intval( $total_etapas ); ?></dd>
        </div>
        <?php if ( $exp_paises ) : ?>
          <div class="exp-summary-row">
            <dt><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></dt>
            <dd><?php echo esc_html( $exp_paises ); ?></dd>
          </div>
        <?php endif; ?>
        <div class="exp-summary-row">
          <dt><?php esc_html_e( 'Estado', 'enterprise-moto' ); ?></dt>
          <dd style="color:<?php echo esc_attr( $estado_color ); ?>;display:flex;align-items:center;gap:6px;">
            <?php if ( $exp_estado === 'activo' ) : ?><span class="live-dot" aria-hidden="true"></span><?php endif; ?>
            <?php echo esc_html( $estado_label ); ?>
          </dd>
        </div>
      </dl>
      <?php if ( $prog_show && $prog_bar ) : ?>
        <div class="exp-summary-progress">
          <div class="exp-summary-progress-label">
            <?php esc_html_e( 'Progreso', 'enterprise-moto' ); ?>
            <span><?php echo intval( $prog_pct ); ?>%</span>
          </div>
          <div class="exp-summary-track">
            <div class="exp-summary-fill" style="width:<?php echo intval( $prog_pct ); ?>%"></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Suscripción (Jetpack/Mailchimp hook) -->
    <div class="exp-subscribe-box">
      <div class="exp-subscribe-title"><?php esc_html_e( 'Seguir este viaje', 'enterprise-moto' ); ?></div>
      <p class="exp-subscribe-desc">
        <?php esc_html_e( 'Recibe un aviso cada vez que publiquemos una nueva etapa.', 'enterprise-moto' ); ?>
      </p>
      <?php
      // Si Jetpack está activo usa el shortcode de suscripción
      if ( shortcode_exists( 'jetpack_subscription_form' ) ) :
        echo do_shortcode( '[jetpack_subscription_form subscribe_text="" subscribe_button="' . esc_attr__( 'Seguir', 'enterprise-moto' ) . '" show_subscribers_total="false"]' );
      else :
        // Formulario básico de fallback
        ?>
        <form class="exp-subscribe-form" action="<?php echo esc_url( home_url( '/?subscribe=success' ) ); ?>" method="get">
          <input type="email" name="email" placeholder="<?php esc_attr_e( 'tu@email.com', 'enterprise-moto' ); ?>" required aria-label="<?php esc_attr_e( 'Tu email', 'enterprise-moto' ); ?>">
          <button type="submit"><?php esc_html_e( 'Seguir', 'enterprise-moto' ); ?></button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Otros viajes (cuadernos finalizados) -->
    <?php
    // Buscar cuadernos finalizados: hermanos del portal con _exp_estado = finalizado
    $portal_id_nav = wp_get_post_parent_id( $page_id );
    $otras = get_posts( array(
      'post_type'   => 'page',
      'post_parent' => $portal_id_nav ?: 0,
      'meta_key'    => '_exp_estado',
      'meta_value'  => 'finalizado',
      'exclude'     => array( $page_id ),
      'numberposts' => 4,
      'post_status' => 'publish',
    ) );
    // Enlace a la página del portal (off-route) donde están todos los cuadernos
    $portal_url_nav = $portal_id_nav ? get_permalink( $portal_id_nav ) : home_url( '/cuaderno-de-bitacora/' );
    if ( ! empty( $otras ) ) :
    ?>
    <div class="exp-other-box">
      <div class="exp-other-title">
        <a href="<?php echo esc_url( $portal_url_nav ); ?>" style="color:inherit;text-decoration:none;">
          <?php esc_html_e( 'Otros viajes', 'enterprise-moto' ); ?> →
        </a>
      </div>
      <ul class="exp-other-list">
        <?php foreach ( $otras as $otra ) :
          $otra_km   = enterprise_km_display( enterprise_cuaderno_stats( $otra->ID )['km'] );
          $otra_live = ( get_post_meta( $otra->ID, '_exp_estado', true ) === 'activo' );
        ?>
          <li class="exp-other-item">
            <a href="<?php echo esc_url( get_permalink( $otra->ID ) ); ?>" class="exp-other-link">
              <span class="exp-other-name"><?php echo esc_html( $otra->post_title ); ?></span>
              <span class="exp-other-meta">
                <?php if ( $otra_live ) : ?>
                  <span style="color:#ff6b6b;font-size:9px;">● <?php esc_html_e( 'En ruta', 'enterprise-moto' ); ?></span>
                <?php elseif ( $otra_km ) : ?>
                  <?php echo esc_html( $otra_km ); ?>
                <?php endif; ?>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

  </aside>

</div><!-- /exp-layout -->

<?php get_footer(); ?>
