<?php get_header(); ?>

<!-- ══ CABECERA DE ARCHIVO ══ -->
<div class="archive-header">
  <div class="container">
    <div class="archive-label">
      <?php
      if ( is_category() )      esc_html_e( 'Categoría', 'enterprise-moto' );
      elseif ( is_tag() )       esc_html_e( 'Etiqueta', 'enterprise-moto' );
      elseif ( is_author() )    esc_html_e( 'Autor', 'enterprise-moto' );
      elseif ( is_year() )      esc_html_e( 'Archivo — Año', 'enterprise-moto' );
      elseif ( is_month() )     esc_html_e( 'Archivo — Mes', 'enterprise-moto' );
      else                      esc_html_e( 'Archivo', 'enterprise-moto' );
      ?>
    </div>
    <h1 class="archive-title"><?php the_archive_title(); ?></h1>
    <?php
    $desc = get_the_archive_description();
    if ( $desc ) echo '<p class="archive-desc">' . wp_kses_post( $desc ) . '</p>';
    ?>
  </div>
</div>

<!-- ══ GRID DE POSTS ══ -->
<div class="archive-posts">
  <?php if ( have_posts() ) : ?>
    <div class="posts-grid">
      <?php $n = 1; while ( have_posts() ) : the_post();
        $route    = enterprise_get_route_data();
        $cat_name = enterprise_first_category();
      ?>
        <article <?php post_class( 'post-card' ); ?> id="post-<?php the_ID(); ?>">
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
    <p style="padding:40px 0;color:var(--mid);"><?php esc_html_e( 'No hay entradas en esta categoría todavía.', 'enterprise-moto' ); ?></p>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
