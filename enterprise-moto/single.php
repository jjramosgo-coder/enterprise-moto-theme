<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<?php
$route    = enterprise_get_route_data();
$cats     = get_the_category();
$cat_name = enterprise_first_category();
?>

<!-- ══ HERO DEL POST ══ -->
<div class="post-hero-wrap">
  <div class="container">
    <?php
    /* Enlace de vuelta: usa la categoría de procedencia si viene del parámetro ?from_cat,
       o el post tipo D padre si viene de ?from_post,
       o el referer si viene de un archivo, o la página de rutas como fallback. */
    $from_cat_slug = isset( $_GET['from_cat'] )  ? sanitize_key( $_GET['from_cat'] )  : '';
    $from_post_id  = isset( $_GET['from_post'] ) ? intval( $_GET['from_post'] )        : 0;
    $from_cuaderno_id = isset( $_GET['from_cuaderno'] ) ? intval( $_GET['from_cuaderno'] ) : 0;
    // #8: contexto de origen «colección» (id de la página + clave del bloque concreto).
    $from_col_id = isset( $_GET['from_col'] ) ? intval( $_GET['from_col'] ) : 0;
    $col_key     = isset( $_GET['col_key'] )  ? sanitize_key( $_GET['col_key'] ) : '';
    // #18: contexto de origen «localización» (id de la página-destino + categoría del
    // carrusel + tags del marcador). loc_tag se guarda como array saneado de IDs.
    $from_loc_id = isset( $_GET['from_loc'] ) ? intval( $_GET['from_loc'] ) : 0;
    $loc_cat     = isset( $_GET['loc_cat'] )  ? intval( $_GET['loc_cat'] )  : 0;
    $loc_tag     = isset( $_GET['loc_tag'] )  ? wp_parse_id_list( wp_unslash( $_GET['loc_tag'] ) ) : array();
    // #21: id de la página que hospeda el mapa (rbl_src en el destino), propagado
    // como loc_src por el viaje de ida y vuelta para reponer «← Volver al mapa».
    $loc_src     = isset( $_GET['loc_src'] )  ? intval( $_GET['loc_src'] )  : 0;
    // Validar que from_post es realmente un post tipo D
    if ( $from_post_id && get_post_meta( $from_post_id, '_post_tipo', true ) !== 'viaje' ) {
        $from_post_id = 0;
    }
    // Validar que from_cuaderno es realmente una página de cuaderno (tiene _exp_estado)
    if ( $from_cuaderno_id && ! get_post_meta( $from_cuaderno_id, '_exp_estado', true ) ) {
        $from_cuaderno_id = 0;
    }
    // #8: validar que from_col es una página con la plantilla «Colección de viajes»
    if ( $from_col_id
         && 'page-templates/template-trip-coleccion.php' !== get_page_template_slug( $from_col_id ) ) {
        $from_col_id = 0;
    }
    // #18: validar que from_loc es una página con la plantilla «Rutas por localización»
    // y que trae una categoría de carrusel; sin ambas cosas no es un contexto loc válido.
    if ( $from_loc_id
         && 'page-templates/template-routes-by-location.php' !== get_page_template_slug( $from_loc_id ) ) {
        $from_loc_id = 0;
    }
    if ( ! $from_loc_id || $loc_cat < 1 ) {
        $from_loc_id = 0;
        $loc_cat     = 0;
        $loc_tag     = array();
        $loc_src     = 0;
    }
    // #13: ancestro = orígenes validados presentes, salvo el inmediato from_post.
    // Se construye desde los locales YA validados (no se re-lee $_GET), de modo que
    // un from_col/from_cat/from_cuaderno inválido no se arrastra.
    $nav_ancestor = array();
    if ( $from_cuaderno_id ) { $nav_ancestor['from_cuaderno'] = $from_cuaderno_id; }
    if ( $from_col_id )      { $nav_ancestor['from_col'] = $from_col_id; $nav_ancestor['col_key'] = $col_key; }
    if ( $from_loc_id )      { $nav_ancestor['from_loc'] = $from_loc_id; $nav_ancestor['loc_cat'] = $loc_cat; $nav_ancestor['loc_tag'] = implode( ',', $loc_tag ); if ( $loc_src > 0 ) { $nav_ancestor['loc_src'] = $loc_src; } }
    if ( $from_cat_slug )    { $nav_ancestor['from_cat'] = $from_cat_slug; }
    if ( $from_post_id ) {
        // #13: al volver al viaje, conservar el ancestro para que el viaje siga
        // sabiendo de dónde vino; from_post no se arrastra (autorreferente al viaje).
        $back_url   = $nav_ancestor
                        ? add_query_arg( $nav_ancestor, get_permalink( $from_post_id ) )
                        : get_permalink( $from_post_id );
        $back_label = esc_html__( '← Volver al viaje', 'enterprise-moto' );
        $active_context = 'post';
    } elseif ( $from_cuaderno_id ) {
        $back_url   = get_permalink( $from_cuaderno_id );
        $back_label = esc_html__( '← Volver al cuaderno', 'enterprise-moto' );
        $active_context = 'cuaderno';
    } elseif ( $from_col_id ) {
        $back_url   = get_permalink( $from_col_id );
        $back_label = esc_html__( '← Volver a la colección', 'enterprise-moto' );
        $active_context = 'col';
    } elseif ( $from_loc_id ) {
        // #18: volver a la vista de esa categoría del destino (§3.6): el mismo
        // carrusel, reconstruido con rbl_cat = loc_cat y rbl_tag = tags del marcador.
        $back_args = array( 'rbl_cat' => $loc_cat, 'rbl_tag' => implode( ',', $loc_tag ) );
        if ( $loc_src > 0 ) { $back_args['rbl_src'] = $loc_src; }
        $back_url   = add_query_arg( $back_args, get_permalink( $from_loc_id ) );
        $back_label = esc_html__( '← Volver', 'enterprise-moto' );
        $active_context = 'loc';
    } elseif ( $from_cat_slug ) {
        $from_cat_obj  = get_category_by_slug( $from_cat_slug );
        $back_url      = $from_cat_obj ? get_term_link( $from_cat_obj ) : home_url( '/las-rutas/' );
        $back_label    = esc_html__( '← Volver', 'enterprise-moto' );
        $active_context = 'cat';
    } else {
        $referer   = wp_get_referer();
        $back_url  = $referer ?: get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/las-rutas/' );
        $back_label = esc_html__( '← Volver', 'enterprise-moto' );
        $active_context = 'none';
    }
    ?>
    <a href="<?php echo esc_url( $back_url ); ?>" class="post-back">
      <?php echo $back_label; ?>
    </a>

    <div class="entry-tags" style="margin-bottom:16px">
      <span class="entry-tag entry-tag--cat"><?php echo esc_html( $cat_name ); ?></span>
      <span class="entry-tag entry-tag--date"><?php the_date( 'F Y' ); ?></span>
      <?php
      $tags = get_the_tags();
      if ( $tags ) :
        $tag = reset( $tags );
        echo '<span class="entry-tag entry-tag--date">' . esc_html( $tag->name ) . '</span>';
      endif;
      ?>
    </div>

    <h1 class="post-hero-title"><?php the_title(); ?></h1>

    <?php if ( has_excerpt() ) : ?>
      <p class="post-hero-sub"><?php the_excerpt(); ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- ══ FRANJA DE DATOS DE RUTA ══ -->
<?php
$tipo_entrada = $route['tipo'] ?: 'etapa';
$has_data = ( $tipo_entrada !== 'generica' ) && ! empty( array_filter( array_intersect_key( $route,
    array_flip( array('km','etapa_km','dias','paises','ferrys','horas_moto','horas_ferry','duracion','tramo','km_calc','etapas_count','c1label','custom_label') ) ) ) );
if ( $has_data ) : ?>
<div class="post-data-strip">
  <?php if ( $tipo_entrada === 'viaje' ) : ?>
    <?php /* ── Tipo D: Viaje de varios días ── */ ?>
    <?php $km_d = $route['km_calc'] ?: $route['km']; $km_pfx = $route['km_inc'] ? '≈ ' : ''; ?>
    <?php if ( $km_d ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $km_pfx . $km_d ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['dias'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Días de ruta', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $route['dias'] ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['paises'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $route['paises'] ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['etapas_count'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo intval( $route['etapas_count'] ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['ferry_count'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Etapas en ferry', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo intval( $route['ferry_count'] ); ?></div>
    </div>
    <?php endif; ?>

  <?php elseif ( $tipo_entrada === 'etapa' ) : ?>
    <?php /* ── Tipo B/C: Etapa / Salida de un día ── */ ?>
    <?php $tramo_v = $route['tramo'] ?: $route['etapa']; ?>
    <?php if ( $tramo_v ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Tramo', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $tramo_v ); ?></div>
    </div>
    <?php endif; ?>
    <?php $km_e = $route['etapa_km'] ?: $route['km']; if ( $km_e ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $km_e ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['horas_moto'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'En moto', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $route['horas_moto'] ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['horas_ferry'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Ferry / barco', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $route['horas_ferry'] ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $route['duracion'] ) : ?>
    <div class="post-data-item">
      <div class="post-data-label"><?php esc_html_e( 'Duración', 'enterprise-moto' ); ?></div>
      <div class="post-data-value"><?php echo esc_html( $route['duracion'] ); ?></div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php /* Dato extra — todos los tipos excepto genérica */ ?>
  <?php $cl = $route['custom_label'] ?: $route['c1label']; $cv = $route['custom_value'] ?: $route['c1value']; ?>
  <?php if ( $cl && $cv ) : ?>
  <div class="post-data-item">
    <div class="post-data-label"><?php echo esc_html( $cl ); ?></div>
    <div class="post-data-value"><?php echo esc_html( $cv ); ?></div>
  </div>
  <?php endif; ?>

  <div class="post-data-item">
    <div class="post-data-label"><?php esc_html_e( 'Publicado', 'enterprise-moto' ); ?></div>
    <div class="post-data-value" style="font-size:16px"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></div>
  </div>
</div>
<?php endif; ?>

<!-- ══ LAYOUT: CONTENIDO + SIDEBAR ══ -->
<div class="post-layout">

  <!-- Contenido -->
  <article class="post-content-area" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <!-- Imagen destacada -->
    <?php if ( has_post_thumbnail() ) : ?>
      <div class="post-cover">
        <?php the_post_thumbnail( 'enterprise-wide' ); ?>
        <?php
        $caption = get_the_post_thumbnail_caption();
        if ( $caption ) :
          echo '<p class="post-cover-caption">' . esc_html( $caption ) . '</p>';
        endif;
        ?>
      </div>
    <?php endif; ?>

    <!-- Contenido del post -->
    <div class="entry-content">
      <?php the_content(); ?>
    </div>

    <!-- Meta footer del post -->
    <footer class="post-meta-footer">
      <?php
      $tags = get_the_tags();
      if ( $tags ) :
        echo '<div class="post-tags">';
        foreach ( $tags as $tag ) :
          echo '<a href="' . esc_url( get_tag_link( $tag ) ) . '">' . esc_html( $tag->name ) . '</a>';
        endforeach;
        echo '</div>';
      endif;
      ?>
      <div class="post-share">
        <?php esc_html_e( 'Escrito por', 'enterprise-moto' ); ?>
        <strong><?php the_author(); ?></strong>
      </div>
    </footer>

  </article><!-- /post-content-area -->

  <!-- Sidebar -->
  <aside class="post-sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Información adicional de la ruta', 'enterprise-moto' ); ?>">
    <?php
    if ( is_active_sidebar( 'sidebar-post' ) ) :
      dynamic_sidebar( 'sidebar-post' );
    else :
      // Sidebar por defecto: entradas recientes de la misma categoría
      $current_cats = wp_get_post_categories( get_the_ID() );
      if ( ! empty( $current_cats ) ) :
        $related = new WP_Query( array(
          'category__in'   => $current_cats,
          'posts_per_page' => 4,
          'post__not_in'   => array( get_the_ID() ),
        ) );
        if ( $related->have_posts() ) :
          echo '<div class="sidebar-widget">';
          echo '<h3 class="widget-title">' . esc_html__( 'Rutas relacionadas', 'enterprise-moto' ) . '</h3>';
          echo '<ul style="list-style:none;padding:0;">';
          while ( $related->have_posts() ) : $related->the_post();
            $rel_route = enterprise_get_route_data();
            echo '<li style="border-bottom:1px solid var(--border);padding:10px 0;">';
            echo '<a href="' . esc_url( get_permalink() ) . '" style="font-size:14px;font-weight:500;color:var(--black);text-decoration:none;display:block;">' . esc_html( get_the_title() ) . '</a>';
            if ( $rel_route['km'] ) {
              echo '<span style="font-size:11px;color:var(--mid);">' . esc_html( $rel_route['km'] ) . '</span>';
            }
            echo '</li>';
          endwhile;
          echo '</ul></div>';
          wp_reset_postdata();
        endif;
      endif;

      // Widget: todas las rutas (archivo)
      echo '<div class="sidebar-widget">';
      echo '<h3 class="widget-title">' . esc_html__( 'Categorías', 'enterprise-moto' ) . '</h3>';
      echo '<ul style="list-style:none;padding:0;">';
      $sidebar_cats = get_categories( array( 'hide_empty' => true ) );
      foreach ( $sidebar_cats as $sc ) :
        echo '<li style="border-bottom:1px solid var(--border);padding:9px 0;">';
        echo '<a href="' . esc_url( get_category_link( $sc->term_id ) ) . '" style="font-size:14px;font-weight:500;color:var(--black);text-decoration:none;display:flex;justify-content:space-between;">';
        echo esc_html( $sc->name );
        echo '<span style="color:var(--mid);font-size:12px;">' . intval( $sc->count ) . '</span>';
        echo '</a></li>';
      endforeach;
      echo '</ul></div>';
    endif;
    ?>
  </aside>

</div><!-- /post-layout -->

<!-- ══ NAVEGACIÓN ENTRE POSTS ══ -->
<nav class="post-navigation" aria-label="<?php esc_attr_e( 'Navegación entre rutas', 'enterprise-moto' ); ?>">
  <?php
  $prev = null; $next = null;
  $nav_suffix = '';

  if ( $from_post_id ) {
      /* ── Contexto: venimos de un post tipo D ──────────────────────────
         CONTRATO DE NAVEGACIÓN (ver documento de diseño): «anterior/
         siguiente» recorren la secuencia en el MISMO orden que produce el
         listado mostrado; anterior = índice−1, siguiente = índice+1.
         En tipo D el listado lo genera el bloque «Etapas de ruta», cuyo
         criterio de orden y filtros viven en sus ATRIBUTOS (no en meta).
         Por eso la navegación lee ese bloque del contenido del post padre
         y replica su query exacta, garantizando que coincida con lo que se
         ve. Si no se encontrara el bloque (no debería: el enlace from_post
         lo genera el propio bloque), se usa un fallback por metadatos. */
      $nav_suffix = array_merge( array( 'from_post' => $from_post_id ), $nav_ancestor );

      $vd_blocks = parse_blocks( get_post_field( 'post_content', $from_post_id ) );
      $vd_stages = function_exists( 'enterprise_find_first_block' )
                   ? enterprise_find_first_block( $vd_blocks, 'enterprise/post-stages' )
                   : null;

      if ( $vd_stages && ! empty( $vd_stages['attrs'] ) ) {
          /* Replica EXACTA de la query de blocks/post-stages/render.php
             (mismos atributos y mismos valores por defecto). */
          $a       = $vd_stages['attrs'];
          $b_cat   = ( isset( $a['categoryIds'] ) && is_array( $a['categoryIds'] ) ) ? array_map( 'intval', $a['categoryIds'] ) : array();
          $b_tag   = ( isset( $a['tagIds'] )      && is_array( $a['tagIds'] ) )      ? array_map( 'intval', $a['tagIds'] )      : array();
          $b_trel  = ( isset( $a['tagRelation'] ) && $a['tagRelation'] === 'AND' ) ? 'AND' : 'IN';
          $b_dfrom = isset( $a['filterDateFrom'] ) ? sanitize_text_field( $a['filterDateFrom'] ) : '';
          $b_dto   = isset( $a['filterDateTo'] )   ? sanitize_text_field( $a['filterDateTo'] )   : '';
          $b_ppp   = isset( $a['postsPerPage'] )   ? intval( $a['postsPerPage'] )                : 6;
          $b_obw   = isset( $a['orderBy'] )        ? sanitize_key( $a['orderBy'] )               : 'date';
          $b_ord   = isset( $a['order'] )          ? sanitize_key( $a['order'] )                 : 'DESC';

          $nav_args = array(
              'post_type'      => 'post',
              'post_status'    => 'publish',
              'posts_per_page' => $b_ppp,
              'orderby'        => $b_obw,
              'order'          => strtoupper( $b_ord ),
              'fields'         => 'ids',
          );
          $tax_q = array();
          if ( ! empty( $b_cat ) ) {
              $tax_q[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $b_cat, 'operator' => 'IN' );
          }
          if ( ! empty( $b_tag ) ) {
              $tax_q[] = array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $b_tag, 'operator' => $b_trel );
          }
          if ( ! empty( $tax_q ) ) {
              $tax_q['relation'] = 'AND';
              $nav_args['tax_query'] = $tax_q;
          }
          if ( $b_dfrom || $b_dto ) {
              $dq = array( 'relation' => 'AND' );
              if ( $b_dfrom ) $dq[] = array( 'after'  => $b_dfrom . ' 00:00:00', 'inclusive' => true );
              if ( $b_dto )   $dq[] = array( 'before' => $b_dto   . ' 23:59:59', 'inclusive' => true );
              $nav_args['date_query'] = $dq;
          }
          $etapa_ids = get_posts( $nav_args );

      } else {
          /* Fallback por metadatos del viaje (orden cronológico ascendente). */
          $cat_ids   = get_post_meta( $from_post_id, '_post_viaje_cat_ids', true ) ?: array();
          $tag_ids   = get_post_meta( $from_post_id, '_post_viaje_tag_ids', true ) ?: array();
          $tag_rel   = get_post_meta( $from_post_id, '_post_viaje_tag_rel', true ) ?: 'OR';
          $fecha_ini = get_post_meta( $from_post_id, '_post_fecha_inicio',  true );
          $fecha_fin = get_post_meta( $from_post_id, '_post_fecha_fin',     true );
          $cat_ids   = is_array( $cat_ids ) ? array_map( 'intval', $cat_ids ) : array();
          $tag_ids   = is_array( $tag_ids ) ? array_map( 'intval', $tag_ids ) : array();

          $nav_args = array(
              'post_type'      => 'post',
              'post_status'    => 'publish',
              'posts_per_page' => -1,
              'orderby'        => 'date',
              'order'          => 'ASC',
              'fields'         => 'ids',
          );
          $tax_q = array();
          if ( ! empty( $cat_ids ) ) {
              $tax_q[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cat_ids, 'operator' => 'IN' );
          }
          if ( ! empty( $tag_ids ) ) {
              $tax_q[] = array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tag_ids, 'operator' => ( $tag_rel === 'AND' ? 'AND' : 'IN' ) );
          }
          if ( ! empty( $tax_q ) ) {
              $tax_q['relation'] = 'AND';
              $nav_args['tax_query'] = $tax_q;
          }
          if ( $fecha_ini ) {
              $dq = array( 'relation' => 'AND', array( 'after' => $fecha_ini . ' 00:00:00', 'inclusive' => true ) );
              if ( $fecha_fin ) $dq[] = array( 'before' => $fecha_fin . ' 23:59:59', 'inclusive' => true );
              $nav_args['date_query'] = $dq;
          }
          $etapa_ids = get_posts( $nav_args );
      }

      $current_pos = array_search( get_the_ID(), $etapa_ids, true );
      if ( $current_pos !== false ) {
          $prev_id = $current_pos > 0                       ? $etapa_ids[ $current_pos - 1 ] : null;
          $next_id = $current_pos < count( $etapa_ids ) - 1 ? $etapa_ids[ $current_pos + 1 ] : null;
          $prev    = $prev_id ? get_post( $prev_id ) : null;
          $next    = $next_id ? get_post( $next_id ) : null;
      }

  } elseif ( $from_cuaderno_id ) {
      /* ── Contexto: venimos de la página del cuaderno de bitácora ───────
         Replica la query de page-cuaderno-de-bitacora.php (mismos filtros
         _filt_* y mismo orden) para que «anterior/siguiente» recorran todo
         el conjunto del cuaderno (etapas y jornadas) en el orden mostrado. */
      $nav_suffix = array( 'from_cuaderno' => $from_cuaderno_id );

      $c_cat_ids   = get_post_meta( $from_cuaderno_id, '_filt_category_ids', true ) ?: array();
      $c_tag_ids   = get_post_meta( $from_cuaderno_id, '_filt_tag_ids',      true ) ?: array();
      $c_tag_rel   = get_post_meta( $from_cuaderno_id, '_filt_tag_relation', true ) ?: 'OR';
      $c_date_from = get_post_meta( $from_cuaderno_id, '_filt_date_from',    true ) ?: '';
      $c_date_to   = get_post_meta( $from_cuaderno_id, '_filt_date_to',      true ) ?: '';
      $c_limit     = get_post_meta( $from_cuaderno_id, '_filt_limit',        true );
      $c_orderby   = get_post_meta( $from_cuaderno_id, '_filt_orderby',      true ) ?: 'date';
      $c_order     = get_post_meta( $from_cuaderno_id, '_filt_order',        true ) ?: 'DESC';

      $c_cat_ids = is_array( $c_cat_ids ) ? array_map( 'intval', $c_cat_ids ) : array();
      $c_tag_ids = is_array( $c_tag_ids ) ? array_map( 'intval', $c_tag_ids ) : array();

      $nav_args = array(
          'post_type'      => 'post',
          'post_status'    => 'publish',
          'posts_per_page' => ( $c_limit !== '' && intval( $c_limit ) > 0 ) ? intval( $c_limit ) : -1,
          'orderby'        => $c_orderby,
          'order'          => strtoupper( $c_order ),
          'fields'         => 'ids',
      );
      $tax_q = array();
      if ( ! empty( $c_cat_ids ) ) {
          $tax_q[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $c_cat_ids, 'operator' => 'IN' );
      }
      if ( ! empty( $c_tag_ids ) ) {
          $tax_q[] = array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $c_tag_ids, 'operator' => ( $c_tag_rel === 'AND' ? 'AND' : 'IN' ) );
      }
      if ( ! empty( $tax_q ) ) {
          $tax_q['relation'] = 'AND';
          $nav_args['tax_query'] = $tax_q;
      }
      if ( $c_date_from || $c_date_to ) {
          $dq = array( 'relation' => 'AND' );
          if ( $c_date_from ) $dq[] = array( 'after'  => $c_date_from . ' 00:00:00', 'inclusive' => true );
          if ( $c_date_to )   $dq[] = array( 'before' => $c_date_to   . ' 23:59:59', 'inclusive' => true );
          $nav_args['date_query'] = $dq;
      }

      $cuaderno_ids = get_posts( $nav_args );
      $current_pos  = array_search( get_the_ID(), $cuaderno_ids, true );
      if ( $current_pos !== false ) {
          $prev_id = $current_pos > 0                          ? $cuaderno_ids[ $current_pos - 1 ] : null;
          $next_id = $current_pos < count( $cuaderno_ids ) - 1 ? $cuaderno_ids[ $current_pos + 1 ] : null;
          $prev    = $prev_id ? get_post( $prev_id ) : null;
          $next    = $next_id ? get_post( $next_id ) : null;
      }

  } elseif ( $from_col_id ) {
      /* ── Contexto: venimos de una «Colección de viajes» (#8) ───────────
         CONTRATO DE NAVEGACIÓN (§13.1/§6): «anterior/siguiente» recorren la
         secuencia en el MISMO orden que el listado mostrado. En una colección
         el listado lo genera el bloque enterprise/trip-collection, y una misma
         página puede tener VARIOS bloques de filtrado. Se localiza el bloque
         concreto por su clave de identidad (enterprise_collection_block_key,
         el mismo helper que usó la tarjeta al estampar col_key), se aplica la
         MISMA guarda showAll y se reutiliza enterprise_stage_query() —la misma
         resolución que el render del bloque— para que navegación y listado no
         puedan divergir. */
      $nav_suffix = array( 'from_col' => $from_col_id, 'col_key' => $col_key );

      $col_blocks   = enterprise_collect_stage_blocks( parse_blocks( get_post_field( 'post_content', $from_col_id ) ) );
      $target_attrs = null;
      foreach ( $col_blocks as $blk ) {
          if ( isset( $blk['blockName'] ) && 'enterprise/trip-collection' === $blk['blockName']
               && enterprise_collection_block_key( $blk['attrs'] ) === $col_key ) {
              $target_attrs = $blk['attrs'];
              break;
          }
      }
      if ( is_array( $target_attrs ) ) {
          /* Misma guarda showAll que el bloque (#11 R3) + misma query → misma secuencia. */
          if ( ! empty( $target_attrs['showAll'] ) ) {
              $target_attrs['postsPerPage'] = -1;
          }
          $col_q       = enterprise_stage_query( $target_attrs );
          $col_ids     = wp_list_pluck( $col_q->posts, 'ID' );
          $current_pos = array_search( get_the_ID(), $col_ids, true );
          if ( $current_pos !== false ) {
              $prev_id = $current_pos > 0                     ? $col_ids[ $current_pos - 1 ] : null;
              $next_id = $current_pos < count( $col_ids ) - 1 ? $col_ids[ $current_pos + 1 ] : null;
              $prev    = $prev_id ? get_post( $prev_id ) : null;
              $next    = $next_id ? get_post( $next_id ) : null;
          }
      }

  } elseif ( $from_loc_id && $loc_cat ) {
      /* ── Contexto: venimos del destino «Rutas por localización» (#18) ──
         CONTRATO DE NAVEGACIÓN (§6/§13.13): «anterior/siguiente» recorren la
         secuencia en el MISMO orden que el carrusel mostrado. Ese carrusel es
         (categoría loc_cat) AND (todas las etiquetas del marcador loc_tag), resuelto con
         enterprise_stage_query() usando los MISMOS atributos que la plantilla por
         carrusel (§3.1/§3.7), para que navegación y listado no puedan divergir. */
      $nav_suffix = array( 'from_loc' => $from_loc_id, 'loc_cat' => $loc_cat, 'loc_tag' => implode( ',', $loc_tag ) );
      if ( $loc_src > 0 ) { $nav_suffix['loc_src'] = $loc_src; }

      $loc_q       = enterprise_stage_query( array(
          'categoryIds'  => array( $loc_cat ),
          'tagIds'       => $loc_tag,
          'tagRelation'  => 'AND',
          'postsPerPage' => -1,
          'orderBy'      => 'date',
          'order'        => 'DESC',
      ) );
      $loc_ids     = wp_list_pluck( $loc_q->posts, 'ID' );
      $current_pos = array_search( get_the_ID(), $loc_ids, true );
      if ( $current_pos !== false ) {
          $prev_id = $current_pos > 0                     ? $loc_ids[ $current_pos - 1 ] : null;
          $next_id = $current_pos < count( $loc_ids ) - 1 ? $loc_ids[ $current_pos + 1 ] : null;
          $prev    = $prev_id ? get_post( $prev_id ) : null;
          $next    = $next_id ? get_post( $next_id ) : null;
      }

  } elseif ( $from_cat_slug ) {
      /* ── Contexto: venimos de una categoría ─────────────────────────── */
      $nav_suffix       = array( 'from_cat' => $from_cat_slug );
      $from_cat_obj_nav = get_category_by_slug( $from_cat_slug );
      $nav_excluded     = '';
      if ( $from_cat_obj_nav ) {
          $all_cat_ids  = get_terms( array( 'taxonomy' => 'category', 'fields' => 'ids', 'hide_empty' => false ) );
          $nav_excluded = implode( ',', array_diff( $all_cat_ids, array( $from_cat_obj_nav->term_id ) ) );
      }
      $prev = get_previous_post( true, $nav_excluded );
      $next = get_next_post(     true, $nav_excluded );

  } else {
      /* ── Fallback: navegar dentro de la categoría del post actual ─── */
      $prev = get_previous_post( true );
      $next = get_next_post(     true );
  }

  /* #8 + #13: etiquetas conscientes del CONTEXTO ACTIVO (innermost), no de la
     mera presencia de from_col. Una etapa alcanzada vía colección→viaje→etapa
     tiene contexto activo «post» (viaje) → «Ruta», aunque arrastre from_col. */
  $in_col_context = ( 'col' === $active_context );
  $nav_prev_label = $in_col_context ? esc_html__( 'Viaje anterior',  'enterprise-moto' )
                                    : esc_html__( 'Ruta anterior',   'enterprise-moto' );
  $nav_next_label = $in_col_context ? esc_html__( 'Siguiente viaje', 'enterprise-moto' )
                                    : esc_html__( 'Siguiente ruta',  'enterprise-moto' );
  ?>
  <div class="post-nav-item">
    <?php if ( $prev ) : ?>
      <div class="post-nav-label">← <?php echo $nav_prev_label; ?></div>
      <div class="post-nav-title">
        <a href="<?php echo esc_url( $nav_suffix ? add_query_arg( $nav_suffix, get_permalink( $prev->ID ) ) : get_permalink( $prev->ID ) ); ?>">
          <?php echo esc_html( get_the_title( $prev->ID ) ); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
  <div class="post-nav-item">
    <?php if ( $next ) : ?>
      <div class="post-nav-label"><?php echo $nav_next_label; ?> →</div>
      <div class="post-nav-title">
        <a href="<?php echo esc_url( $nav_suffix ? add_query_arg( $nav_suffix, get_permalink( $next->ID ) ) : get_permalink( $next->ID ) ); ?>">
          <?php echo esc_html( get_the_title( $next->ID ) ); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- ══ COMENTARIOS ══ -->
<?php if ( comments_open() || get_comments_number() ) : ?>
  <div class="comments-section">
    <?php comments_template(); ?>
  </div>
<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
