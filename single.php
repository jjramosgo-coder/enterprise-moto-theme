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
    // Validar que from_post es realmente un post tipo D
    if ( $from_post_id && get_post_meta( $from_post_id, '_post_tipo', true ) !== 'viaje' ) {
        $from_post_id = 0;
    }
    if ( $from_post_id ) {
        $back_url   = get_permalink( $from_post_id );
        $back_label = esc_html__( '← Volver al viaje', 'enterprise-moto' );
    } elseif ( $from_cat_slug ) {
        $from_cat_obj  = get_category_by_slug( $from_cat_slug );
        $back_url      = $from_cat_obj ? get_term_link( $from_cat_obj ) : home_url( '/las-rutas/' );
        $back_label    = esc_html__( '← Volver', 'enterprise-moto' );
    } else {
        $referer   = wp_get_referer();
        $back_url  = $referer ?: get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/las-rutas/' );
        $back_label = esc_html__( '← Volver', 'enterprise-moto' );
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
         Construir la misma query que usa enterprise_calculate_viaje_stats()
         para obtener la lista ordenada de etapas del viaje padre. */
      $nav_suffix  = array( 'from_post' => $from_post_id );
      $cat_ids     = get_post_meta( $from_post_id, '_post_viaje_cat_ids',  true ) ?: array();
      $tag_ids     = get_post_meta( $from_post_id, '_post_viaje_tag_ids',  true ) ?: array();
      $tag_rel     = get_post_meta( $from_post_id, '_post_viaje_tag_rel',  true ) ?: 'OR';
      $fecha_ini   = get_post_meta( $from_post_id, '_post_fecha_inicio',   true );
      $fecha_fin   = get_post_meta( $from_post_id, '_post_fecha_fin',      true );

      $cat_ids = is_array( $cat_ids ) ? array_map( 'intval', $cat_ids ) : array();
      $tag_ids = is_array( $tag_ids ) ? array_map( 'intval', $tag_ids ) : array();

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

      $etapa_ids  = get_posts( $nav_args );
      $current_pos = array_search( get_the_ID(), $etapa_ids, true );
      if ( $current_pos !== false ) {
          $prev_id = $current_pos > 0                       ? $etapa_ids[ $current_pos - 1 ] : null;
          $next_id = $current_pos < count( $etapa_ids ) - 1 ? $etapa_ids[ $current_pos + 1 ] : null;
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
  ?>
  <div class="post-nav-item">
    <?php if ( $prev ) : ?>
      <div class="post-nav-label">← <?php esc_html_e( 'Ruta anterior', 'enterprise-moto' ); ?></div>
      <div class="post-nav-title">
        <a href="<?php echo esc_url( $nav_suffix ? add_query_arg( $nav_suffix, get_permalink( $prev->ID ) ) : get_permalink( $prev->ID ) ); ?>">
          <?php echo esc_html( get_the_title( $prev->ID ) ); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
  <div class="post-nav-item">
    <?php if ( $next ) : ?>
      <div class="post-nav-label"><?php esc_html_e( 'Siguiente ruta', 'enterprise-moto' ); ?> →</div>
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
