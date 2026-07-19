<?php
/**
 * Enterprise Moto — index.php v1.8.0
 *
 * Las secciones de la portada se configuran en:
 * Apariencia → Personalizar → 🏍 Configuración de la portada
 *
 * No edites este archivo directamente.
 */

/* ── Datos globales ── */
$dias_ruta_cats_setting = get_theme_mod( 'enterprise_dias_ruta_cats', 'etapa' );
$dias_ruta_slugs = array_filter( array_map( 'trim', explode( ',', $dias_ruta_cats_setting ) ) );
$etapa_count = 0;
foreach ( $dias_ruta_slugs as $slug ) {
    $cat = get_category_by_slug( sanitize_title( $slug ) );
    if ( $cat ) $etapa_count += intval( $cat->count );
}
if ( ! $etapa_count && empty( $dias_ruta_slugs ) ) {
    $etapa_count = intval( wp_count_posts()->publish );
}

/* Última ruta publicada — filtrada por categorías configuradas en el Personalizador */
$_latest_cats_setting = get_theme_mod( 'enterprise_latest_cats', 'etapa' );
$_latest_args = array( 'posts_per_page' => 1, 'post_status' => 'publish' );

if ( ! empty( trim( $_latest_cats_setting ) ) ) {
    $_latest_cat_names = array_filter( array_map( 'trim', explode( ',', $_latest_cats_setting ) ) );
    $_latest_cat_ids   = array();
    foreach ( $_latest_cat_names as $_cn ) {
        /* Buscar por nombre primero, luego por slug */
        $_ct = get_term_by( 'name', $_cn, 'category' ) ?: get_term_by( 'slug', sanitize_title( $_cn ), 'category' );
        if ( $_ct && ! is_wp_error( $_ct ) ) $_latest_cat_ids[] = $_ct->term_id;
    }
    if ( ! empty( $_latest_cat_ids ) ) {
        $_latest_args['category__in'] = $_latest_cat_ids;
    }
}

$latest_query = new WP_Query( $_latest_args );
$latest_post  = $latest_query->have_posts() ? $latest_query->posts[0] : null;
wp_reset_postdata();

/* Número de países desde el Personalizador (con cache de transient como fallback) */
$paises = get_theme_mod( 'enterprise_paises', '' );
if ( ! $paises ) {
    $paises = get_transient( 'enterprise_paises_count' );
    if ( false === $paises ) {
        $paises = 4;
        set_transient( 'enterprise_paises_count', $paises, DAY_IN_SECONDS );
    }
}

get_header();
?>

<!-- ══ HERO ══ -->
<section class="home-hero" aria-label="<?php esc_attr_e( 'Presentación del blog', 'enterprise-moto' ); ?>">
  <div class="hero-left">
    <div class="hero-tag"><?php esc_html_e( 'Blog de viajes en moto — España & Europa', 'enterprise-moto' ); ?></div>
    <div class="hero-headline">
      <h1>
        <span class="line-white">RUTAS</span>
        <span class="line-gold">REALES.</span>
        <span class="line-outline">SIN</span>
        <span class="line-white">FILTROS.</span>
      </h1>
      <p class="hero-desc">
        <?php printf(
          esc_html__( '%s comparte sus rutas sobre la Enterprise. Carreteras con curvas, paisajes que valen la parada y todo lo que de verdad importa cuando viajas en moto.', 'enterprise-moto' ),
          get_bloginfo( 'name' )
        ); ?>
      </p>
    </div>
    <div class="hero-bottom">
      <div class="hero-stats">
        <div>
          <div class="hero-stat-num"><?php echo intval( $etapa_count ); ?>+</div>
          <div class="hero-stat-label"><?php esc_html_e( 'Días de ruta publicados', 'enterprise-moto' ); ?></div>
        </div>
        <div>
          <div class="hero-stat-num"><?php echo intval( $paises ); ?></div>
          <div class="hero-stat-label"><?php esc_html_e( 'Países recorridos', 'enterprise-moto' ); ?></div>
        </div>
        <div>
          <div class="hero-stat-num"><?php echo esc_html( date( 'Y' ) ); ?></div>
          <div class="hero-stat-label"><?php esc_html_e( 'Temporada activa', 'enterprise-moto' ); ?></div>
        </div>
      </div>
      <a href="<?php echo esc_url( home_url( '/las-rutas/' ) ); ?>" class="btn btn--gold">
        <?php esc_html_e( 'Explorar todas las rutas', 'enterprise-moto' ); ?> →
      </a>
    </div>
  </div>
  <div class="hero-right">
    <?php if ( $latest_post && has_post_thumbnail( $latest_post->ID ) ) :
      echo get_the_post_thumbnail( $latest_post->ID, 'enterprise-hero', array( 'class' => 'hero-featured-image', 'alt' => esc_attr( get_the_title( $latest_post->ID ) ) ) );
    else : ?>
      <div class="hero-placeholder">🏍️</div>
    <?php endif; ?>
    <?php if ( $latest_post ) : ?>
    <div class="hero-photo-caption">
      <span class="hero-photo-label"><?php echo esc_html( get_the_title( $latest_post->ID ) ); ?></span>
      <a href="#ultima-ruta" class="hero-photo-tag"><?php esc_html_e( 'Última ruta', 'enterprise-moto' ); ?></a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ TICKER ══ -->
<div class="site-ticker" aria-hidden="true">
  <div class="ticker-track">
    <?php foreach ( enterprise_ticker_items() as $item ) :
      echo '<span class="ticker-item">' . esc_html( $item ) . '</span>';
    endforeach; ?>
  </div>
</div>

<!-- ══ ÚLTIMA RUTA DESTACADA ══ -->
<?php if ( $latest_post ) :
  $route = enterprise_get_route_data( $latest_post->ID );
  $cat   = get_the_category( $latest_post->ID );
  // #13 (§7): el destacado es un único ítem sin listado detrás; no hay secuencia ni categoría
  // única que estampar. Se deja el permalink plano a propósito (fallback → portada). No añadir
  // from_cat aquí: fabricaría una secuencia inexistente y desviaría el «Volver» (ver §7.2.3).
?>
<section class="featured-section" id="ultima-ruta">
  <div class="container">
    <div class="section-eyebrow"><?php esc_html_e( 'Última ruta publicada', 'enterprise-moto' ); ?></div>
    <div class="featured-grid">
      <div>
        <div class="featured-number" aria-hidden="true">01</div>
        <div class="featured-meta">
          <div class="entry-tags" style="margin-bottom:16px">
            <span class="entry-tag entry-tag--new"><?php esc_html_e( 'Nueva', 'enterprise-moto' ); ?></span>
            <?php if ( ! empty( $cat ) ) : ?>
              <span class="entry-tag entry-tag--cat"><?php echo esc_html( $cat[0]->name ); ?></span>
            <?php endif; ?>
            <span class="entry-tag entry-tag--date"><?php echo esc_html( get_the_date( 'F Y', $latest_post->ID ) ); ?></span>
          </div>
          <h2 class="featured-title">
            <a href="<?php echo esc_url( get_permalink( $latest_post->ID ) ); ?>" style="color:inherit;text-decoration:none;">
              <?php echo esc_html( get_the_title( $latest_post->ID ) ); ?>
            </a>
          </h2>
          <p class="featured-excerpt"><?php echo esc_html( get_the_excerpt( $latest_post->ID ) ); ?></p>
          <?php if ( ! empty( array_filter( $route ) ) ) : ?>
          <div class="featured-stats">
            <?php if ( $route['dias'] ) : ?><div class="featured-stat"><div class="featured-stat-num"><?php echo esc_html( $route['dias'] ); ?></div><div class="featured-stat-label"><?php esc_html_e( 'Días', 'enterprise-moto' ); ?></div></div><?php endif; ?>
            <?php if ( $route['km'] ) : ?><div class="featured-stat"><div class="featured-stat-num"><?php echo esc_html( $route['km'] ); ?></div><div class="featured-stat-label"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div></div><?php endif; ?>
            <?php if ( $route['paises'] ) : ?><div class="featured-stat"><div class="featured-stat-num"><?php echo esc_html( $route['paises'] ); ?></div><div class="featured-stat-label"><?php esc_html_e( 'Países', 'enterprise-moto' ); ?></div></div><?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="featured-actions">
            <a href="<?php echo esc_url( get_permalink( $latest_post->ID ) ); ?>" class="btn btn--dark">
              <?php esc_html_e( 'Leer el relato', 'enterprise-moto' ); ?> →
            </a>
          </div>
        </div>
      </div>
      <div class="featured-image-wrap">
        <div class="featured-image">
          <?php if ( has_post_thumbnail( $latest_post->ID ) ) :
            echo get_the_post_thumbnail( $latest_post->ID, 'enterprise-card', array( 'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;' ) );
          else : ?><div class="featured-image-no-img">🏍️</div><?php endif; ?>
          <div class="featured-image-meta">
            <span class="featured-image-title"><?php echo esc_html( get_the_title( $latest_post->ID ) ); ?></span>
            <?php if ( $route['km'] ) : ?><span class="featured-image-km"><?php echo esc_html( $route['km'] ); ?></span><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════
     SECCIONES CONFIGURABLES DESDE EL PERSONALIZADOR
     Apariencia → Personalizar → Configuración de la portada
══════════════════════════════════════════ -->
<?php
for ( $g = 1; $g <= 6; $g++ ) :
    $cfg = enterprise_get_group_config( $g );
    if ( ! $cfg['type'] ) continue; /* Sección desactivada */

    $posts       = array();
    $cta_url     = home_url( '/' );
    $cta_label   = __( 'Ver todas', 'enterprise-moto' );
    $section_cat = '';
    $section_cat_slug = '';
    $eyebrow     = $cfg['eyebrow'];

    if ( 'cat_children' === $cfg['type'] ) {
        /* ── Hijos de categoría: generar una sub-sección por cada hijo ── */
        $parent_cat = null;
        if ( $cfg['slug'] ) {
            $parent_cat = get_term_by( 'name', $cfg['slug'], 'category' );
            if ( ! $parent_cat || is_wp_error( $parent_cat ) ) {
                $parent_cat = get_term_by( 'slug', sanitize_title( $cfg['slug'] ), 'category' );
            }
        }
        if ( ! $parent_cat || is_wp_error( $parent_cat ) ) continue;

        $hijos = get_categories( array( 'parent' => $parent_cat->term_id, 'hide_empty' => true ) );
        foreach ( $hijos as $hijo ) :
            $hijo_posts = get_posts( array(
                'category'       => $hijo->term_id,
                'posts_per_page' => $cfg['max'],
                'orderby'        => 'date', 'order' => 'DESC',
                'post_status'    => 'publish',
            ) );
            if ( empty( $hijo_posts ) ) continue;
            enterprise_home_section(
                $cfg['eyebrow'] ?: __( 'Tipo de salida', 'enterprise-moto' ),
                $cfg['title'] ?: $hijo->name,
                $hijo_posts,
                get_term_link( $hijo ),
                sprintf( __( 'Ver todas las rutas de %s', 'enterprise-moto' ), $hijo->name ),
                $hijo->name,
                $hijo->slug
            );
        endforeach;
        continue; /* Saltar el bloque de abajo */

    } elseif ( 'cat' === $cfg['type'] && $cfg['slug'] ) {
        /* Buscar por nombre primero, luego por slug (compatibilidad) */
        $term = get_term_by( 'name', $cfg['slug'], 'category' );
        if ( ! $term || is_wp_error( $term ) ) {
            $term = get_term_by( 'slug', sanitize_title( $cfg['slug'] ), 'category' );
        }
        if ( ! $term || is_wp_error( $term ) ) continue;
        $posts       = get_posts( array(
            'category'       => $term->term_id,
            'posts_per_page' => $cfg['max'],
            'orderby'        => 'date', 'order' => 'DESC',
            'post_status'    => 'publish',
        ) );
        $cta_url     = get_term_link( $term );
        $cta_label   = sprintf( __( 'Ver todas las rutas de %s', 'enterprise-moto' ), $term->name );
        $section_cat = $cfg['title'] ?: $term->name;
        $section_cat_slug = $term->slug;
        if ( ! $eyebrow ) $eyebrow = __( 'Categoría', 'enterprise-moto' );

    } elseif ( 'tag' === $cfg['type'] && $cfg['slug'] ) {
        /* Múltiples etiquetas separadas por coma → una sección por etiqueta */
        $tag_parts = array_filter( array_map( 'trim', explode( ',', $cfg['slug'] ) ) );
        if ( empty( $tag_parts ) ) continue;

        foreach ( $tag_parts as $tag_value ) {
            /* Buscar por nombre primero, luego por slug (compatibilidad) */
            $tag_term = get_term_by( 'name', $tag_value, 'post_tag' );
            if ( ! $tag_term || is_wp_error( $tag_term ) ) {
                $tag_term = get_term_by( 'slug', sanitize_title( $tag_value ), 'post_tag' );
            }
            if ( ! $tag_term || is_wp_error( $tag_term ) || ! $tag_term->count ) continue;

            $tag_posts = get_posts( array(
                'tag_id'         => $tag_term->term_id,
                'posts_per_page' => $cfg['max'],
                'orderby'        => 'date', 'order' => 'DESC',
                'post_status'    => 'publish',
            ) );
            if ( empty( $tag_posts ) ) continue;

            enterprise_home_section(
                $cfg['eyebrow'] ?: __( 'Destino destacado', 'enterprise-moto' ),
                $cfg['title'] ?: $tag_term->name,
                $tag_posts,
                get_term_link( $tag_term ),
                sprintf( __( 'Ver todas las rutas de %s', 'enterprise-moto' ), $tag_term->name ),
                $tag_term->name,
                '' /* #13 (§7): una etiqueta no es categoría; el modelo no tiene from_tag → no se estampa (intencionado, §7.2.2) */
            );
        }
        continue; /* Cada etiqueta ya generó su propia sección arriba */
    }

    if ( empty( $posts ) ) continue;

    enterprise_home_section(
        $eyebrow,
        $cfg['title'] ?: $section_cat,
        $posts,
        $cta_url,
        $cta_label,
        $section_cat,
        $section_cat_slug
    );

endfor;
?>

<!-- ══ SOBRE EL BLOG ══ -->
<?php
/* Configuración desde el Personalizador */
$about_img_id  = get_theme_mod( 'enterprise_about_image', '' );
$about_title   = get_theme_mod( 'enterprise_about_title', 'JUANJO & MARÍA JOSÉ' );
$about_text_cm = get_theme_mod( 'enterprise_about_text',  '' );
$about_url_cm  = get_theme_mod( 'enterprise_about_url',   '' );

/* Fallbacks */
$about_page  = get_page_by_path( 'acerca-de' );
$about_text  = $about_text_cm ?: ( $about_page ? wp_trim_words( $about_page->post_content, 55 ) : '' );
$about_url   = $about_url_cm  ?: ( $about_page ? get_permalink( $about_page->ID ) : home_url( '/acerca-de/' ) );
?>
<section class="about-section">
  <div class="container">
    <div class="about-grid">
      <div>
        <div class="about-eyebrow"><?php esc_html_e( 'Quiénes somos', 'enterprise-moto' ); ?></div>
        <h2 class="about-title"><?php echo nl2br( esc_html( $about_title ) ); ?></h2>
        <?php if ( $about_text ) : ?>
          <div class="about-text"><?php echo wp_kses_post( $about_text ); ?></div>
        <?php endif; ?>
        <a href="<?php echo esc_url( $about_url ); ?>" class="btn btn--gold" style="margin-top:8px;">
          <?php esc_html_e( 'Sobre el blog', 'enterprise-moto' ); ?> →
        </a>
      </div>
      <div class="about-image-wrap">
        <div class="about-image">
          <?php if ( $about_img_id ) :
            echo wp_get_attachment_image( intval( $about_img_id ), 'enterprise-card', false,
              array( 'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;' ) );
          elseif ( $about_page && has_post_thumbnail( $about_page->ID ) ) :
            echo get_the_post_thumbnail( $about_page->ID, 'enterprise-card',
              array( 'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;' ) );
          else : ?>
            <div class="about-image-no-img">🏍️</div>
          <?php endif; ?>
          <div class="about-image-frame"></div>
        </div>
        <div class="about-image-badge"><?php esc_html_e( 'La Enterprise', 'enterprise-moto' ); ?></div>
      </div>
    </div>
  </div>
</section>

<?php get_footer(); ?>
