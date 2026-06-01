<?php
/**
 * Template Name: Bitácora con bloques
 *
 * Igual que el Cuaderno de bitácora automático, pero el cuerpo
 * de etapas lo compones tú con bloques de Gutenberg.
 *
 * El héroe, las estadísticas y la columna de resumen se configuran
 * con los mismos campos personalizados que el cuaderno automático.
 * Usa el bloque "Etapas de ruta" para añadir carruseles o timelines.
 *
 * CAMPOS PERSONALIZADOS (metabox "Datos de la expedición"):
 *   _exp_nombre      → Título del viaje
 *   _exp_subtitulo   → Descripción / ruta
 *   _exp_salida      → Fecha de salida
 *   _exp_duracion    → Duración total
 *   _exp_km          → Kilómetros totales
 *   _exp_paises      → Países
 *   _exp_progreso    → Progreso 0-100
 *   _exp_en_ruta     → 1 = en ruta activo
 */

get_header();

$page_id       = get_the_ID();
$exp_nombre    = get_post_meta( $page_id, '_exp_nombre',    true ) ?: get_the_title();
$exp_subtitulo = get_post_meta( $page_id, '_exp_subtitulo', true ) ?: get_the_excerpt();
$exp_salida    = get_post_meta( $page_id, '_exp_salida',    true ) ?: '';
$exp_duracion  = get_post_meta( $page_id, '_exp_duracion',  true ) ?: '';
$exp_km        = get_post_meta( $page_id, '_exp_km',        true ) ?: '';
$exp_paises    = get_post_meta( $page_id, '_exp_paises',    true ) ?: '';
$exp_en_ruta   = get_post_meta( $page_id, '_exp_en_ruta',   true );
$exp_progreso  = intval( get_post_meta( $page_id, '_exp_progreso', true ) ?: 0 );

$estado_label = $exp_en_ruta
    ? __( 'En ruta ahora mismo', 'enterprise-moto' )
    : __( 'Viaje completado',    'enterprise-moto' );
$estado_color = $exp_en_ruta ? '#ff6b6b' : '#4ade80';
?>

<!-- ════════════════════════════════════════════
     HERO (idéntico al cuaderno automático)
════════════════════════════════════════════ -->
<section class="exp-hero">
  <div class="exp-hero-watermark" aria-hidden="true">
    <?php echo esc_html( strtoupper( $exp_nombre ) ); ?>
  </div>
  <div class="exp-hero-inner container">
    <div class="exp-hero-left">

      <nav class="exp-breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'enterprise-moto' ); ?></a>
        <span aria-hidden="true">›</span>
        <span><?php echo esc_html( $exp_nombre ); ?></span>
      </nav>

      <div class="exp-status-badge <?php echo $exp_en_ruta ? 'is-live' : 'is-done'; ?>">
        <?php if ( $exp_en_ruta ) : ?><span class="live-dot" aria-hidden="true"></span><?php endif; ?>
        <?php echo esc_html( $estado_label ); ?>
      </div>

      <h1 class="exp-title"><?php echo esc_html( $exp_nombre ); ?></h1>

      <?php if ( $exp_subtitulo ) : ?>
        <p class="exp-subtitle"><?php echo esc_html( $exp_subtitulo ); ?></p>
      <?php endif; ?>

      <div class="exp-stats">
        <?php if ( $exp_duracion ) : ?>
          <div class="exp-stat"><div class="exp-stat-n"><?php echo esc_html( $exp_duracion ); ?></div><div class="exp-stat-l"><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></div></div>
        <?php endif; ?>
        <?php if ( $exp_km ) : ?>
          <div class="exp-stat"><div class="exp-stat-n"><?php echo esc_html( $exp_km ); ?></div><div class="exp-stat-l"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div></div>
        <?php endif; ?>
        <?php if ( $exp_paises ) : ?>
          <div class="exp-stat"><div class="exp-stat-n"><?php echo esc_html( $exp_paises ); ?></div><div class="exp-stat-l"><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></div></div>
        <?php endif; ?>
        <?php if ( $exp_salida ) : ?>
          <div class="exp-stat"><div class="exp-stat-n" style="font-size:20px;letter-spacing:.02em;"><?php echo esc_html( $exp_salida ); ?></div><div class="exp-stat-l"><?php esc_html_e( 'Salida', 'enterprise-moto' ); ?></div></div>
        <?php endif; ?>
      </div>

    </div>

    <?php if ( $exp_en_ruta || $exp_progreso > 0 ) : ?>
    <div class="exp-hero-right">
      <div class="exp-progress-widget">
        <div class="exp-progress-label">
          <?php esc_html_e( 'Progreso del viaje', 'enterprise-moto' ); ?>
          <span><?php echo intval( $exp_progreso ); ?>%</span>
        </div>
        <div class="exp-progress-track" role="progressbar"
             aria-valuenow="<?php echo intval( $exp_progreso ); ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="exp-progress-fill" style="width:<?php echo intval( $exp_progreso ); ?>%"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- ════════════════════════════════════════════
     TICKER ANIMADO
     Lee los posts seleccionados en todos los
     bloques enterprise/post-stages de la página
════════════════════════════════════════════ -->
<?php
/*
 * TICKER — orden de prioridad:
 *
 * 1. Bloques enterprise/post-stages con categoría específica (categoryId > 0)
 *    → solo si el bloque tiene una categoría seleccionada, no "todas"
 * 2. Campo _exp_categoria del metabox
 *    → lee los títulos de los posts de esa categoría
 * 3. Fallback: categorías del blog
 */
$ticker_titles = array();

// ── 1. Leer bloques con categoryId explícito ──────────────────────────────
$parsed_blocks = parse_blocks( get_the_content( null, false, $page_id ) );

if ( ! function_exists( 'enterprise_collect_stage_blocks' ) ) {
    function enterprise_collect_stage_blocks( $blocks ) {
        $out = array();
        foreach ( $blocks as $b ) {
            if ( 'enterprise/post-stages' === $b['blockName'] ) $out[] = $b;
            if ( ! empty( $b['innerBlocks'] ) )
                $out = array_merge( $out, enterprise_collect_stage_blocks( $b['innerBlocks'] ) );
        }
        return $out;
    }
}

$stage_blocks  = enterprise_collect_stage_blocks( $parsed_blocks );
$seen_cat_ids  = array();

foreach ( $stage_blocks as $block ) {
    // Leer array de categorías (nuevo formato)
    $cat_ids = isset( $block['attrs']['categoryIds'] ) && is_array( $block['attrs']['categoryIds'] )
               ? array_map( 'intval', $block['attrs']['categoryIds'] )
               : array();

    // Compatibilidad hacia atrás con el atributo singular categoryId
    if ( empty( $cat_ids ) && isset( $block['attrs']['categoryId'] ) && intval( $block['attrs']['categoryId'] ) > 0 ) {
        $cat_ids = array( intval( $block['attrs']['categoryId'] ) );
    }

    // Sin categorías concretas → saltamos (no queremos posts de "todas")
    if ( empty( $cat_ids ) ) continue;

    $per = isset( $block['attrs']['postsPerPage'] ) ? intval( $block['attrs']['postsPerPage'] ) : 6;
    $ob  = isset( $block['attrs']['orderBy'] )      ? sanitize_key( $block['attrs']['orderBy'] ) : 'date';
    $ord = isset( $block['attrs']['order'] )        ? strtoupper( $block['attrs']['order'] )     : 'DESC';

    // Evitar repetir la misma combinación de categorías
    $key = implode( '_', $cat_ids );
    if ( in_array( $key, $seen_cat_ids, true ) ) continue;
    $seen_cat_ids[] = $key;

    $tq = new WP_Query( array(
        'post_type'      => 'post',
        'cat'            => implode( ',', $cat_ids ), // OR entre categorías
        'posts_per_page' => $per,
        'orderby'        => $ob,
        'order'          => $ord,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ) );
    foreach ( $tq->posts as $pid ) {
        $title = strtoupper( get_the_title( $pid ) );
        if ( $title && ! in_array( $title, $ticker_titles, true ) ) {
            $ticker_titles[] = $title;
        }
    }
    wp_reset_postdata();
}

// ── 2. Campo _exp_categoria + _exp_etiquetas del metabox ─────────────────
if ( empty( $ticker_titles ) ) {
    $exp_cat_slug  = trim( get_post_meta( $page_id, '_exp_categoria',  true ) );
    $exp_tags_raw  = trim( get_post_meta( $page_id, '_exp_etiquetas',  true ) );

    // Parsear slugs de etiquetas separados por coma
    $tag_ids = array();
    if ( $exp_tags_raw ) {
        $tag_slugs = array_filter( array_map( 'trim', explode( ',', $exp_tags_raw ) ) );
        foreach ( $tag_slugs as $slug ) {
            $term = get_term_by( 'slug', sanitize_title( $slug ), 'post_tag' );
            if ( $term && ! is_wp_error( $term ) ) {
                $tag_ids[] = $term->term_id;
            }
        }
    }

    // Solo procedemos si hay al menos categoría o etiqueta
    if ( $exp_cat_slug || ! empty( $tag_ids ) ) {

        $tq_args = array(
            'post_type'      => 'post',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'fields'         => 'ids',
        );

        $tax_clauses = array();

        if ( $exp_cat_slug ) {
            $cat_obj = get_term_by( 'slug', $exp_cat_slug, 'category' );
            if ( $cat_obj && ! is_wp_error( $cat_obj ) ) {
                $tax_clauses[] = array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $cat_obj->term_id,
                    'operator' => 'IN',
                );
            }
        }

        if ( ! empty( $tag_ids ) ) {
            $tax_clauses[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'term_id',
                'terms'    => $tag_ids,
                'operator' => 'IN',
            );
        }

        if ( ! empty( $tax_clauses ) ) {
            // AND entre categoría y etiquetas si ambas están presentes
            if ( count( $tax_clauses ) > 1 ) {
                $tax_clauses['relation'] = 'AND';
            }
            $tq_args['tax_query'] = $tax_clauses;
        }

        $tq = new WP_Query( $tq_args );
        foreach ( $tq->posts as $pid ) {
            $title = strtoupper( get_the_title( $pid ) );
            if ( $title ) $ticker_titles[] = $title;
        }
        wp_reset_postdata();
    }
}

// ── 3. Fallback: categorías del blog ─────────────────────────────────────
if ( empty( $ticker_titles ) ) {
    $ticker_titles = enterprise_ticker_items();
}

// Duplicar para el bucle infinito del CSS
$ticker_titles_loop = array_merge( $ticker_titles, $ticker_titles );
?>
<?php if ( ! empty( $ticker_titles ) ) : ?>
<div class="site-ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php foreach ( $ticker_titles_loop as $ticker_item ) : ?>
      <span class="ticker-item"><?php echo esc_html( $ticker_item ); ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════
     LAYOUT: CONTENIDO (BLOQUES) + SUMMARY
════════════════════════════════════════════ -->
<div class="exp-blocks-layout container">

  <!-- Contenido Gutenberg -->
  <div class="exp-blocks-content">
    <?php while ( have_posts() ) : the_post(); ?>
      <div class="entry-content exp-blocks-entry">
        <?php the_content(); ?>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- Summary sticky (igual que cuaderno automático) -->
  <aside class="exp-summary-col" role="complementary">

    <div class="exp-summary-box">
      <div class="exp-summary-title"><?php echo esc_html( $exp_nombre ); ?></div>
      <dl class="exp-summary-list">
        <?php if ( $exp_salida ) : ?>
          <div class="exp-summary-row"><dt><?php esc_html_e( 'Salida', 'enterprise-moto' ); ?></dt><dd><?php echo esc_html( $exp_salida ); ?></dd></div>
        <?php endif; ?>
        <?php if ( $exp_duracion ) : ?>
          <div class="exp-summary-row"><dt><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></dt><dd><?php echo esc_html( $exp_duracion ); ?></dd></div>
        <?php endif; ?>
        <?php if ( $exp_km ) : ?>
          <div class="exp-summary-row"><dt><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></dt><dd class="gold"><?php echo esc_html( $exp_km ); ?></dd></div>
        <?php endif; ?>
        <?php if ( $exp_paises ) : ?>
          <div class="exp-summary-row"><dt><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></dt><dd><?php echo esc_html( $exp_paises ); ?></dd></div>
        <?php endif; ?>
        <div class="exp-summary-row">
          <dt><?php esc_html_e( 'Estado', 'enterprise-moto' ); ?></dt>
          <dd style="color:<?php echo esc_attr( $estado_color ); ?>;display:flex;align-items:center;gap:6px;">
            <?php if ( $exp_en_ruta ) : ?><span class="live-dot" aria-hidden="true"></span><?php endif; ?>
            <?php echo esc_html( $estado_label ); ?>
          </dd>
        </div>
      </dl>
      <?php if ( $exp_progreso > 0 ) : ?>
        <div class="exp-summary-progress">
          <div class="exp-summary-progress-label"><?php esc_html_e( 'Progreso', 'enterprise-moto' ); ?><span><?php echo intval( $exp_progreso ); ?>%</span></div>
          <div class="exp-summary-track"><div class="exp-summary-fill" style="width:<?php echo intval( $exp_progreso ); ?>%"></div></div>
        </div>
      <?php endif; ?>
    </div>

    <div class="exp-subscribe-box">
      <div class="exp-subscribe-title"><?php esc_html_e( 'Seguir este viaje', 'enterprise-moto' ); ?></div>
      <p class="exp-subscribe-desc"><?php esc_html_e( 'Recibe un aviso cada vez que publiquemos una nueva etapa.', 'enterprise-moto' ); ?></p>
      <?php if ( shortcode_exists( 'jetpack_subscription_form' ) ) :
        echo do_shortcode( '[jetpack_subscription_form subscribe_text="" subscribe_button="' . esc_attr__( 'Seguir', 'enterprise-moto' ) . '" show_subscribers_total="false"]' );
      else : ?>
        <form class="exp-subscribe-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
          <input type="email" name="email" placeholder="<?php esc_attr_e( 'tu@email.com', 'enterprise-moto' ); ?>" required>
          <button type="submit"><?php esc_html_e( 'Seguir', 'enterprise-moto' ); ?></button>
        </form>
      <?php endif; ?>
    </div>

    <?php
    $otras = get_posts( array(
      'post_type'   => 'page',
      'meta_key'    => '_wp_page_template',
      'meta_value'  => 'page-bitacora-bloques.php',
      'exclude'     => array( $page_id ),
      'numberposts' => 4,
    ) );
    // También incluir las del cuaderno automático
    $otras_auto = get_posts( array(
      'post_type'   => 'page',
      'meta_key'    => '_wp_page_template',
      'meta_value'  => 'page-cuaderno-de-bitacora.php',
      'exclude'     => array( $page_id ),
      'numberposts' => 4,
    ) );
    $otras = array_merge( $otras, $otras_auto );
    if ( ! empty( $otras ) ) : ?>
    <div class="exp-other-box">
      <div class="exp-other-title"><?php esc_html_e( 'Otras expediciones', 'enterprise-moto' ); ?></div>
      <ul class="exp-other-list">
        <?php foreach ( $otras as $otra ) :
          $otra_km   = get_post_meta( $otra->ID, '_exp_km', true );
          $otra_live = get_post_meta( $otra->ID, '_exp_en_ruta', true );
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

</div><!-- /exp-blocks-layout -->

<?php get_footer(); ?>
