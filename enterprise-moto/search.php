<?php
/**
 * Bitácora Enterprise — search.php
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
get_header();
?>

<div class="search-header">
  <div class="container">
    <div class="search-header-label"><?php esc_html_e( 'Resultados de búsqueda', 'enterprise-moto' ); ?></div>
    <h1 class="search-header-title">
      <?php esc_html_e( 'Buscando:', 'enterprise-moto' ); ?>
      <span>&ldquo;<?php echo esc_html( get_search_query() ); ?>&rdquo;</span>
    </h1>
  </div>
</div>

<div class="archive-posts">
  <?php if ( have_posts() ) : ?>
    <p style="margin-bottom:32px;color:var(--mid);font-size:14px;">
      <?php printf( esc_html__( '%s resultado(s) encontrado(s).', 'enterprise-moto' ), '<strong>' . $wp_query->found_posts . '</strong>' ); ?>
    </p>
    <div class="posts-grid">
      <?php $n = 1; while ( have_posts() ) : the_post();
        $route    = enterprise_get_route_data();
        $cat_name = enterprise_first_category();
      ?>
        <article <?php post_class( 'post-card' ); ?>>
          <a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
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
            <h2 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <div class="post-card-footer">
              <div class="post-card-km">
                <?php if ( $route['km'] ) echo esc_html( $route['km'] ) . ' <span>km</span>';
                else echo '<span>' . esc_html__( 'Ruta', 'enterprise-moto' ) . '</span>'; ?>
              </div>
              <a href="<?php the_permalink(); ?>" class="post-card-arrow" aria-label="<?php echo esc_attr( get_the_title() ); ?>">→</a>
            </div>
          </div>
        </article>
      <?php $n++; endwhile; ?>
    </div>
    <?php enterprise_pagination(); ?>
  <?php else : ?>
    <div style="text-align:center;padding:60px 0;">
      <div style="font-family:var(--font-display);font-size:80px;color:var(--surface);margin-bottom:-10px;">404</div>
      <h2 style="font-family:var(--font-display);font-size:28px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;">
        <?php esc_html_e( 'Sin resultados', 'enterprise-moto' ); ?>
      </h2>
      <p style="color:var(--mid);margin-bottom:28px;">
        <?php esc_html_e( 'No hemos encontrado ninguna ruta con ese término. Prueba con otra búsqueda.', 'enterprise-moto' ); ?>
      </p>
      <?php get_search_form(); ?>
    </div>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
