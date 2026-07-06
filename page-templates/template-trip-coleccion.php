<?php
/**
 * Template Name: Colección de viajes
 *
 * Página curada que agrupa viajes/rutas ya cerrados (p. ej. «De vacaciones»,
 * «Rutas de puente»), como alternativa a la vista por defecto de un archivo de
 * categoría. El cuerpo se compone con bloques Gutenberg (uno o varios bloques
 * «Colección de viajes» y/o «Etapas de ruta»); el hero muestra cifras agregadas
 * cacheadas al guardar (_col_stats, Fase 4) y un ticker alimentado por el campo
 * «Nombre en el ticker» (_post_ticker_name) de las entradas del conjunto único.
 *
 * NO usa el metabox de expedición (_exp_*): el título y el extracto del hero
 * salen del título y el extracto nativos de la página.
 *
 * Contrato de _col_stats (lo rellena la Fase 4): array con claves
 *   viajes (int), km (int total), km_incompleto (bool), etapas (int),
 *   paises (int), ferrys (int).  _col_stats_updated: texto de fecha ya formateado.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$page_id = get_the_ID();

/* ── Cifras cacheadas al guardar (Fase 4). Hasta entonces, 0/vacías. ── */
$stats      = get_post_meta( $page_id, '_col_stats', true );
$stats      = is_array( $stats ) ? $stats : array();
$col_viajes = isset( $stats['viajes'] ) ? intval( $stats['viajes'] ) : 0;
$col_km     = isset( $stats['km'] )     ? intval( $stats['km'] )     : 0;
$col_km_inc = ! empty( $stats['km_incompleto'] );
$col_etapas = isset( $stats['etapas'] ) ? intval( $stats['etapas'] ) : 0;
$col_paises = isset( $stats['paises'] ) ? intval( $stats['paises'] ) : 0;
$col_ferrys = isset( $stats['ferrys'] ) ? intval( $stats['ferrys'] ) : 0;
$col_updated = get_post_meta( $page_id, '_col_stats_updated', true );

$col_title    = get_the_title();
$col_subtitle = get_the_excerpt();

/* Título con la última palabra en dorado (como la maqueta). */
$parts = preg_split( '/\s+/', trim( $col_title ) );
if ( is_array( $parts ) && count( $parts ) > 1 ) {
    $last       = array_pop( $parts );
    $title_html = esc_html( implode( ' ', $parts ) ) . ' <em>' . esc_html( $last ) . '</em>';
} else {
    $title_html = '<em>' . esc_html( $col_title ) . '</em>';
}

/* Km del hero: número + prefijo ≈ si hay km incompletos, SIN unidad (la
 * etiqueta ya dice «Kilómetros»). Decisión validada; contrasta con spec §3.4. */
$km_display = $col_km ? number_format( $col_km, 0, ',', '.' ) : '0';
if ( $col_km_inc ) {
    $km_display = '≈' . $km_display;
}

/* ── Ticker: nombres curados del conjunto único, dedup, orden de aparición, tope N ── */
$ticker_names = array();
if ( function_exists( 'enterprise_collection_post_ids' ) ) {
    foreach ( enterprise_collection_post_ids( $page_id ) as $pid ) {
        $name = trim( (string) get_post_meta( $pid, '_post_ticker_name', true ) );
        if ( '' === $name ) {
            $name = get_the_title( $pid );
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
}
if ( defined( 'ENTERPRISE_COLECCION_TICKER_MAX' ) && count( $ticker_names ) > ENTERPRISE_COLECCION_TICKER_MAX ) {
    $ticker_names = array_slice( $ticker_names, 0, ENTERPRISE_COLECCION_TICKER_MAX );
}
$ticker_loop = array_merge( $ticker_names, $ticker_names ); // duplicado para el bucle infinito
?>

<!-- ══ HERO COLECCIÓN ══ -->
<section class="col-hero">
  <div class="col-hero-watermark" aria-hidden="true"><?php
    echo esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $col_title, 'UTF-8' ) : strtoupper( $col_title ) );
  ?></div>

  <div class="col-hero-inner">
    <nav class="col-breadcrumb">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'enterprise-moto' ); ?></a>
      <span aria-hidden="true">›</span>
      <span><?php echo esc_html( $col_title ); ?></span>
    </nav>

    <div class="col-badge">
      <?php esc_html_e( 'Colección', 'enterprise-moto' ); ?> ·
      <b><?php printf( esc_html( _n( '%d viaje', '%d viajes', $col_viajes, 'enterprise-moto' ) ), $col_viajes ); ?></b>
    </div>

    <h1 class="col-title"><?php echo $title_html; // ya escapado por partes ?></h1>

    <?php if ( $col_subtitle ) : ?>
      <p class="col-subtitle"><?php echo esc_html( $col_subtitle ); ?></p>
    <?php endif; ?>

    <div class="col-stats">
      <?php if ( $col_viajes > 0 ) : ?>
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $col_viajes ); ?></div><div class="col-stat-l"><?php esc_html_e( 'Viajes', 'enterprise-moto' ); ?></div></div>
      <?php endif; ?>
      <?php if ( $col_km > 0 ) : ?>
      <div class="col-stat"><div class="col-stat-n"><?php echo esc_html( $km_display ); ?></div><div class="col-stat-l"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div></div>
      <?php endif; ?>
      <?php if ( $col_etapas > 0 ) : ?>
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $col_etapas ); ?></div><div class="col-stat-l"><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></div></div>
      <?php endif; ?>
      <?php if ( $col_paises > 0 ) : ?>
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $col_paises ); ?></div><div class="col-stat-l"><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></div></div>
      <?php endif; ?>
      <?php if ( $col_ferrys > 0 ) : ?>
      <div class="col-stat"><div class="col-stat-n"><?php echo intval( $col_ferrys ); ?></div><div class="col-stat-l"><?php esc_html_e( 'Ferrys', 'enterprise-moto' ); ?></div></div>
      <?php endif; ?>
    </div>

    <?php if ( $col_updated ) : ?>
      <div class="col-updated"><?php
        /* translators: %s = fecha del último recálculo de las cifras */
        printf( esc_html__( 'Cifras de la colección · actualizadas %s', 'enterprise-moto' ), esc_html( $col_updated ) );
      ?></div>
    <?php endif; ?>
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

<!-- ══ CUERPO: contenido Gutenberg compuesto por el autor ══ -->
<div class="col-body">
  <div class="col-content entry-content">
    <?php
    while ( have_posts() ) : the_post();
        the_content();
    endwhile;
    ?>
  </div>
</div>

<?php get_footer(); ?>
