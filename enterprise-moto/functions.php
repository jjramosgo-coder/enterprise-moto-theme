<?php
/**
 * Bitácora Enterprise — functions.php
 * Configuración del tema, menus, widgets y scripts.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ENTERPRISE_VERSION', '2.18.0' );

/* Tope de nombres distintos en el ticker de la plantilla «Colección de viajes» (#5). */
if ( ! defined( 'ENTERPRISE_COLECCION_TICKER_MAX' ) ) {
    define( 'ENTERPRISE_COLECCION_TICKER_MAX', 16 );
}

/* ─────────────────────────────────────────
   SETUP DEL TEMA
───────────────────────────────────────── */
function enterprise_setup() {
    // Traducciones
    load_theme_textdomain( 'enterprise-moto', get_template_directory() . '/languages' );

    // Soporte para título automático en <head>
    add_theme_support( 'title-tag' );

    // Imágenes destacadas en posts y páginas
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'enterprise-hero',    1600, 900,  true );
    add_image_size( 'enterprise-card',    800,  600,  true );
    add_image_size( 'enterprise-thumb',   400,  300,  true );
    add_image_size( 'enterprise-wide',    1200, 500,  true );

    // Menús de navegación
    register_nav_menus( array(
        'primary'   => __( 'Menú principal',  'enterprise-moto' ),
        'footer'    => __( 'Menú del footer', 'enterprise-moto' ),
    ) );

    // Feed RSS automático
    add_theme_support( 'automatic-feed-links' );

    // HTML5
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ) );

    // Gutenberg: desactivar estilos por defecto del editor (usamos los nuestros)
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );

    // Editor colors (Gutenberg palette)
    add_theme_support( 'editor-color-palette', array(
        array( 'name' => __( 'Negro',  'enterprise-moto' ), 'slug' => 'negro',  'color' => '#0e0e0e' ),
        array( 'name' => __( 'Dorado', 'enterprise-moto' ), 'slug' => 'dorado', 'color' => '#f2c118' ),
        array( 'name' => __( 'Blanco', 'enterprise-moto' ), 'slug' => 'blanco', 'color' => '#ffffff' ),
        array( 'name' => __( 'Gris',   'enterprise-moto' ), 'slug' => 'gris',   'color' => '#5a5a5a' ),
        array( 'name' => __( 'Crema',  'enterprise-moto' ), 'slug' => 'crema',  'color' => '#f5f5f2' ),
    ) );

    // Ancho de contenido para el editor
    add_theme_support( 'custom-line-height' );
    add_theme_support( 'custom-spacing' );
}
add_action( 'after_setup_theme', 'enterprise_setup' );

// Ancho del contenido para el editor de bloques
function enterprise_content_width() {
    $GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'enterprise_content_width', 0 );

/* ─────────────────────────────────────────
   SCRIPTS Y ESTILOS
───────────────────────────────────────── */
function enterprise_scripts() {
    // Google Fonts
    wp_enqueue_style(
        'enterprise-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,700;1,9..40,300;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap',
        array(),
        null
    );

    // Hoja de estilos principal
    wp_enqueue_style(
        'enterprise-style',
        get_stylesheet_uri(),
        array( 'enterprise-fonts' ),
        ENTERPRISE_VERSION
    );

    // CSS del editor de Gutenberg (frontend)
    wp_enqueue_style(
        'enterprise-blocks',
        get_template_directory_uri() . '/assets/css/blocks.css',
        array( 'enterprise-style' ),
        ENTERPRISE_VERSION
    );

    // JavaScript principal
    wp_enqueue_script(
        'enterprise-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        ENTERPRISE_VERSION,
        true
    );

    // Comentarios con hilos
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }

    // Pasar variables PHP → JS
    wp_localize_script( 'enterprise-main', 'enterpriseData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'homeUrl' => home_url( '/' ),
        'isHome'  => ( is_home() || is_front_page() ) ? 'true' : 'false',
    ) );
}
add_action( 'wp_enqueue_scripts', 'enterprise_scripts' );

// CSS en el editor de Gutenberg (backend)
function enterprise_editor_styles() {
    add_editor_style( array(
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,700;1,9..40,300;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap',
        'style.css',
        'assets/css/editor-style.css',
    ) );
}
add_action( 'after_setup_theme', 'enterprise_editor_styles' );

/* ─────────────────────────────────────────
   ÁREAS DE WIDGETS
───────────────────────────────────────── */
function enterprise_widgets_init() {
    // Sidebar del post
    register_sidebar( array(
        'name'          => __( 'Sidebar de ruta', 'enterprise-moto' ),
        'id'            => 'sidebar-post',
        'description'   => __( 'Aparece a la derecha de cada entrada de ruta.', 'enterprise-moto' ),
        'before_widget' => '<div id="%1$s" class="sidebar-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer col 2
    register_sidebar( array(
        'name'          => __( 'Footer — Secciones', 'enterprise-moto' ),
        'id'            => 'footer-1',
        'description'   => __( 'Segunda columna del footer.', 'enterprise-moto' ),
        'before_widget' => '<div id="%1$s" class="%2$s footer-widget-area">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer col 3
    register_sidebar( array(
        'name'          => __( 'Footer — Blog', 'enterprise-moto' ),
        'id'            => 'footer-2',
        'description'   => __( 'Tercera columna del footer.', 'enterprise-moto' ),
        'before_widget' => '<div id="%1$s" class="%2$s footer-widget-area">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'enterprise_widgets_init' );

/* ─────────────────────────────────────────
   CAMPOS PERSONALIZADOS DE POST (metaboxes)
───────────────────────────────────────── */
/* enterprise_register_meta_boxes() — reemplazado por el metabox de tipo de entrada */

function enterprise_route_data_callback( $post ) {
    wp_nonce_field( 'enterprise_route_data_nonce', 'enterprise_route_nonce' );
    $km      = get_post_meta( $post->ID, '_route_km',      true );
    $dias    = get_post_meta( $post->ID, '_route_dias',    true );
    $paises  = get_post_meta( $post->ID, '_route_paises',  true );
    $etapa   = get_post_meta( $post->ID, '_route_etapa',   true );
    $ferrys  = get_post_meta( $post->ID, '_route_ferrys',  true );
    $custom1_label = get_post_meta( $post->ID, '_route_custom1_label', true );
    $custom1_value = get_post_meta( $post->ID, '_route_custom1_value', true );
    ?>
    <style>
        .enterprise-meta-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 8px; }
        .enterprise-meta-field label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #555; text-transform: uppercase; letter-spacing: .05em; }
        .enterprise-meta-field input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 2px; font-size: 14px; }
        .enterprise-meta-tip { background: #fffbea; border-left: 3px solid #f2c118; padding: 10px 14px; margin-top: 12px; font-size: 13px; color: #555; }
    </style>
    <div class="enterprise-meta-grid">
        <div class="enterprise-meta-field">
            <label><?php _e( 'Kilómetros totales', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_km" value="<?php echo esc_attr( $km ); ?>" placeholder="Ej: 2.800 km">
        </div>
        <div class="enterprise-meta-field">
            <label><?php _e( 'Días de ruta', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_dias" value="<?php echo esc_attr( $dias ); ?>" placeholder="Ej: 12">
        </div>
        <div class="enterprise-meta-field">
            <label><?php _e( 'Países recorridos', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_paises" value="<?php echo esc_attr( $paises ); ?>" placeholder="Ej: 4">
        </div>
        <div class="enterprise-meta-field">
            <label><?php _e( 'Etapa / Tramo', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_etapa" value="<?php echo esc_attr( $etapa ); ?>" placeholder="Ej: Porto Torres → BCN">
        </div>
        <div class="enterprise-meta-field">
            <label><?php _e( 'Ferrys', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_ferrys" value="<?php echo esc_attr( $ferrys ); ?>" placeholder="Ej: 3">
        </div>
        <div class="enterprise-meta-field">
            <label><?php _e( 'Dato extra — etiqueta', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_custom1_label" value="<?php echo esc_attr( $custom1_label ); ?>" placeholder="Ej: Ferry">
        </div>
    </div>
    <div class="enterprise-meta-grid" style="margin-top:8px">
        <div class="enterprise-meta-field">
            <label><?php _e( 'Dato extra — valor', 'enterprise-moto' ); ?></label>
            <input type="text" name="route_custom1_value" value="<?php echo esc_attr( $custom1_value ); ?>" placeholder="Ej: Grimaldi Lines">
        </div>
    </div>
    <p class="enterprise-meta-tip">
        <?php _e( 'Los datos que rellenes aquí aparecen en la franja de información rápida bajo el título de la ruta.', 'enterprise-moto' ); ?>
    </p>
    <?php
}

function enterprise_save_route_meta( $post_id ) {
    if ( ! isset( $_POST['enterprise_route_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enterprise_route_nonce'], 'enterprise_route_data_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = array(
        'route_km', 'route_dias', 'route_paises', 'route_etapa',
        'route_ferrys', 'route_custom1_label', 'route_custom1_value',
    );
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }
}
add_action( 'save_post', 'enterprise_save_route_meta' );

/* ─────────────────────────────────────────
   HELPER: buscar el primer bloque por nombre
   dentro de un árbol de bloques (recursivo).
   Devuelve el array del bloque (con 'attrs') o null.
───────────────────────────────────────── */
if ( ! function_exists( 'enterprise_find_first_block' ) ) {
    function enterprise_find_first_block( $blocks, $block_name ) {
        if ( ! is_array( $blocks ) ) return null;
        foreach ( $blocks as $block ) {
            if ( isset( $block['blockName'] ) && $block['blockName'] === $block_name ) {
                return $block;
            }
            if ( ! empty( $block['innerBlocks'] ) ) {
                $found = enterprise_find_first_block( $block['innerBlocks'], $block_name );
                if ( $found ) return $found;
            }
        }
        return null;
    }
}

/* ─────────────────────────────────────────
   HELPER: datos de ruta de un post
───────────────────────────────────────── */
function enterprise_get_route_data( $post_id = null ) {
    if ( ! $post_id ) $post_id = get_the_ID();
    $tipo = get_post_meta( $post_id, '_post_tipo', true ) ?: 'etapa';
    return array(
        /* Campos originales (_route_*) — siempre disponibles por backward compat */
        'km'          => get_post_meta( $post_id, '_route_km',            true ),
        'dias'        => get_post_meta( $post_id, '_route_dias',          true ),
        'paises'      => get_post_meta( $post_id, '_route_paises',        true ),
        'etapa'       => get_post_meta( $post_id, '_route_etapa',         true ),
        'ferrys'      => get_post_meta( $post_id, '_route_ferrys',        true ),
        'c1label'     => get_post_meta( $post_id, '_route_custom1_label', true ),
        'c1value'     => get_post_meta( $post_id, '_route_custom1_value', true ),
        /* Campos del nuevo metabox tipado */
        'tipo'        => $tipo,
        'tramo'       => get_post_meta( $post_id, '_post_tramo',          true ),
        'etapa_km'    => get_post_meta( $post_id, '_post_km',             true ),
        'horas_moto'  => get_post_meta( $post_id, '_post_horas_moto',     true ),
        'horas_ferry' => get_post_meta( $post_id, '_post_horas_ferry',    true ),
        'duracion'    => get_post_meta( $post_id, '_post_duracion',       true ),
        'custom_label'=> get_post_meta( $post_id, '_post_custom_label',   true ),
        'custom_value'=> get_post_meta( $post_id, '_post_custom_value',   true ),
        /* Tipo D calculado */
        'km_calc'     => get_post_meta( $post_id, '_post_km_calculado',   true ),
        'km_inc'      => get_post_meta( $post_id, '_post_km_incompleto',  true ),
        'ferry_count' => get_post_meta( $post_id, '_post_ferry_count',    true ),
        'etapas_count'=> get_post_meta( $post_id, '_post_etapas_count',   true ),
    );
}

/* ─────────────────────────────────────────
   HELPER: thumbnail con fallback
───────────────────────────────────────── */
function enterprise_thumbnail( $size = 'enterprise-card', $class = '' ) {
    if ( has_post_thumbnail() ) {
        the_post_thumbnail( $size, array( 'class' => $class ) );
    }
}

/* ─────────────────────────────────────────
   HELPER: ticker de destinos
───────────────────────────────────────── */
function enterprise_ticker_items() {
    $terms = get_terms( array(
        'taxonomy'   => 'category',
        'hide_empty' => true,
        'number'     => 20,
    ) );
    $items = array();
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $items[] = strtoupper( $term->name );
        }
    }
    // Fallback si no hay categorías
    if ( empty( $items ) ) {
        $items = array( 'ALPUJARRAS', 'ALBARRACÍN', 'LISBOA', 'CERDEÑA', 'SICILIA', 'SORRENTO', 'NAVARRA', 'PIRINEOS' );
    }
    // Duplicar para bucle infinito
    $items = array_merge( $items, $items );
    return $items;
}

/* ─────────────────────────────────────────
   HELPER: estadísticas del blog para el hero
───────────────────────────────────────── */
function enterprise_get_stats() {
    $count = wp_count_posts();
    return array(
        'posts'     => $count->publish ?? 0,
        'year'      => date( 'Y' ),
    );
}

/* ─────────────────────────────────────────
   KM PARA PRESENTACIÓN (unidad defensiva)
───────────────────────────────────────── */
/**
 * Devuelve el valor de km listo para pintar, añadiendo la unidad «km»
 * de forma defensiva. Solo presentación: no lee metas ni toca datos.
 *
 * @param string $km Valor crudo (_route_km / _exp_km), con o sin unidad.
 * @return string   Cadena para mostrar; '' si la entrada está vacía.
 */
function enterprise_km_display( $km ) {
    $km = (string) $km;
    if ( $km === '' ) {
        return '';
    }
    if ( ! preg_match( '/km\s*$/i', $km ) ) {
        $km .= ' km';
    }
    return $km;
}

/* ─────────────────────────────────────────
   QUERY DE ETAPAS/ENTRADAS POR FILTROS (lógica compartida)
───────────────────────────────────────── */
/**
 * Construye la WP_Query de entradas a partir de los atributos de filtro
 * usados por el bloque «Etapas de ruta» (enterprise/post-stages) y, en
 * adelante, por «Colección de viajes» (enterprise/trip-collection).
 *
 * Extraída sin cambios desde blocks/post-stages/render.php para poder
 * reutilizar exactamente la misma resolución de filtros → entradas. El
 * render de post-stages debe permanecer byte-idéntico.
 *
 * Atributos que consume: categoryIds (array), tagIds (array), tagRelation
 * (AND|OR), filterDateFrom, filterDateTo, postsPerPage, orderBy, order.
 * Los atributos de presentación (layout, cardSize, heading, showX…) no se
 * usan aquí.
 *
 * @param array $attributes Atributos del bloque.
 * @return WP_Query
 */
function enterprise_stage_query( $attributes ) {

    $category_ids   = isset( $attributes['categoryIds'] )  && is_array( $attributes['categoryIds'] )
                        ? array_map( 'intval', $attributes['categoryIds'] ) : array();
    $tag_ids        = isset( $attributes['tagIds'] )       && is_array( $attributes['tagIds'] )
                        ? array_map( 'intval', $attributes['tagIds'] ) : array();
    $filter_date_from = isset( $attributes['filterDateFrom'] ) ? sanitize_text_field( $attributes['filterDateFrom'] ) : '';
    $filter_date_to   = isset( $attributes['filterDateTo'] )   ? sanitize_text_field( $attributes['filterDateTo'] )   : '';
    $tag_relation   = isset( $attributes['tagRelation'] ) && $attributes['tagRelation'] === 'AND' ? 'AND' : 'IN';
    $posts_per_page = isset( $attributes['postsPerPage'] ) ? intval( $attributes['postsPerPage'] )        : 6;
    $order_by       = isset( $attributes['orderBy'] )      ? sanitize_key( $attributes['orderBy'] )       : 'date';
    $order          = isset( $attributes['order'] )        ? sanitize_key( $attributes['order'] )         : 'DESC';

    $query_args = array(
        'post_type'      => 'post',
        'posts_per_page' => $posts_per_page,
        'orderby'        => $order_by,
        'order'          => strtoupper( $order ),
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    );

    /*
     * tax_query con relación AND entre categorías y etiquetas:
     * - Si hay categorías Y etiquetas → post debe cumplir ambas condiciones
     * - Si solo hay categorías → OR entre ellas (posts de cualquiera)
     * - Si solo hay etiquetas  → OR entre ellas
     */
    $tax_query = array();

    if ( ! empty( $category_ids ) ) {
        $tax_query[] = array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $category_ids,
            'operator' => 'IN',   // OR entre categorías seleccionadas
        );
    }

    if ( ! empty( $tag_ids ) ) {
        $tax_query[] = array(
            'taxonomy' => 'post_tag',
            'field'    => 'term_id',
            'terms'    => $tag_ids,
            'operator' => $tag_relation, // AND = todas las etiquetas | IN = cualquiera (OR)
        );
    }

    if ( ! empty( $tax_query ) ) {
        $tax_query['relation'] = count( $tax_query ) > 1 ? 'AND' : 'AND';
        $query_args['tax_query'] = $tax_query;
    }

    // Filtro de fecha absoluta (desde / hasta)
    if ( $filter_date_from || $filter_date_to ) {
        $dq = array( 'relation' => 'AND' );
        if ( $filter_date_from ) $dq[] = array( 'after'  => $filter_date_from . ' 00:00:00', 'inclusive' => true );
        if ( $filter_date_to )   $dq[] = array( 'before' => $filter_date_to   . ' 23:59:59', 'inclusive' => true );
        $query_args['date_query'] = $dq;
    }

    return new WP_Query( $query_args );
}

/* ─────────────────────────────────────────
   CLAVE DE IDENTIDAD DE BLOQUE DE FILTRADO (navegación #8)
───────────────────────────────────────── */
/**
 * Clave de identidad de un bloque de filtrado para la navegación (#8).
 * Hash corto y estable de los atributos que determinan la SECUENCIA (no layout),
 * para desambiguar entre varios bloques de la misma página. La usan
 * blocks/trip-collection/render.php (al pintar la tarjeta) y single.php (al casar
 * el bloque de origen): única fuente de verdad del identificador, para que ambos
 * lados no diverjan. Se hashean los atributos ORIGINALES del bloque (los mismos
 * que lee single.php al parsear la página), no los mutados por la guarda showAll.
 * Los valores por defecto coinciden con enterprise_stage_query().
 */
function enterprise_collection_block_key( $attributes ) {
    $cat = ( isset( $attributes['categoryIds'] ) && is_array( $attributes['categoryIds'] ) )
             ? array_map( 'intval', $attributes['categoryIds'] ) : array();
    $tag = ( isset( $attributes['tagIds'] ) && is_array( $attributes['tagIds'] ) )
             ? array_map( 'intval', $attributes['tagIds'] ) : array();
    sort( $cat ); sort( $tag );
    $norm = array(
        'cat'   => $cat,
        'tag'   => $tag,
        'trel'  => isset( $attributes['tagRelation'] ) && $attributes['tagRelation'] === 'AND' ? 'AND' : 'IN',
        'dfrom' => isset( $attributes['filterDateFrom'] ) ? (string) $attributes['filterDateFrom'] : '',
        'dto'   => isset( $attributes['filterDateTo'] )   ? (string) $attributes['filterDateTo']   : '',
        'obw'   => isset( $attributes['orderBy'] ) ? (string) $attributes['orderBy'] : 'date',
        'ord'   => isset( $attributes['order'] )   ? (string) $attributes['order']   : 'DESC',
        'ppp'   => isset( $attributes['postsPerPage'] ) ? intval( $attributes['postsPerPage'] ) : 6,
        'all'   => ! empty( $attributes['showAll'] ) ? 1 : 0,
    );
    return substr( md5( wp_json_encode( $norm ) ), 0, 8 );
}

/* ─────────────────────────────────────────
   CONTEXTO DE NAVEGACIÓN PRESENTE EN LA REQUEST (#13)
───────────────────────────────────────── */
/**
 * Contexto de navegación presente en la request (#13).
 * Lee y SANEA los parámetros de origen del enlace de entrada; NO valida su
 * semántica (eso lo hace single.php al consumirlos). Fuente única de «qué
 * cuenta como parámetro de origen», para que el estampado del ancestro
 * (render.php) y el consumo (single.php) no diverjan.
 *
 * @return array Subconjunto presente y saneado. p.ej.
 *   array( 'from_col' => 12, 'col_key' => 'a1b2c3d4' ) | array( 'from_cat' => 'italia' )
 *   | array( 'from_loc' => 34, 'loc_cat' => 5, 'loc_tag' => '60,59' ) | array()
 */
function enterprise_nav_origin_params() {
    $out = array();
    if ( isset( $_GET['from_post'] ) && intval( $_GET['from_post'] ) ) {
        $out['from_post'] = intval( $_GET['from_post'] );
    }
    if ( isset( $_GET['from_cuaderno'] ) && intval( $_GET['from_cuaderno'] ) ) {
        $out['from_cuaderno'] = intval( $_GET['from_cuaderno'] );
    }
    if ( isset( $_GET['from_col'] ) && intval( $_GET['from_col'] ) ) {
        $out['from_col'] = intval( $_GET['from_col'] );
        $out['col_key']  = isset( $_GET['col_key'] ) ? sanitize_key( $_GET['col_key'] ) : '';
    }
    // #18: contexto «localización» (página-destino + categoría del carrusel + tags
    // del marcador). loc_tag se re-serializa a cadena separada por comas para que
    // el estampado del ancestro (post-stages) y el consumo (single.php) coincidan.
    if ( isset( $_GET['from_loc'] ) && intval( $_GET['from_loc'] ) ) {
        $out['from_loc'] = intval( $_GET['from_loc'] );
        $out['loc_cat']  = isset( $_GET['loc_cat'] ) ? intval( $_GET['loc_cat'] ) : 0;
        $out['loc_tag']  = isset( $_GET['loc_tag'] ) ? implode( ',', wp_parse_id_list( wp_unslash( $_GET['loc_tag'] ) ) ) : '';
        // #21 (ampliación): arrastrar también el origen del mapa por el ancestro
        // (colección/post-stages), para que «← Volver al mapa» sobreviva al viaje
        // destino → colección → etapa → vuelta. Solo si es entero positivo.
        $loc_src = isset( $_GET['loc_src'] ) ? intval( $_GET['loc_src'] ) : 0;
        if ( $loc_src > 0 ) { $out['loc_src'] = $loc_src; }
    }
    if ( isset( $_GET['from_cat'] ) && sanitize_key( $_GET['from_cat'] ) ) {
        $out['from_cat'] = sanitize_key( $_GET['from_cat'] );
    }
    return $out;
}

/* ─────────────────────────────────────────
   BLOQUES DE FILTRADO EN UNA PÁGINA (recolección recursiva)
───────────────────────────────────────── */
/**
 * Recorre recursivamente un árbol de bloques (parse_blocks) y devuelve los que
 * actúan como «bloques de filtrado» de entradas: enterprise/post-stages y
 * enterprise/trip-collection. Compartida por la plantilla «Colección de
 * viajes» y por el cálculo de estadísticas de colección (#5, R3).
 *
 * Generaliza la versión que vivía dentro de page-bitacora-bloques.php (que solo
 * reconocía post-stages); al estar definida aquí, la copia local de esa
 * plantilla —guardada con function_exists— queda inerte hasta su reescritura.
 */
if ( ! function_exists( 'enterprise_collect_stage_blocks' ) ) {
    function enterprise_collect_stage_blocks( $blocks ) {
        $out = array();
        if ( ! is_array( $blocks ) ) return $out;
        foreach ( $blocks as $b ) {
            $name = isset( $b['blockName'] ) ? $b['blockName'] : '';
            if ( 'enterprise/post-stages' === $name || 'enterprise/trip-collection' === $name ) {
                $out[] = $b;
            }
            if ( ! empty( $b['innerBlocks'] ) ) {
                $out = array_merge( $out, enterprise_collect_stage_blocks( $b['innerBlocks'] ) );
            }
        }
        return $out;
    }
}

/* ─────────────────────────────────────────
   DATOS DE TARJETA DE VIAJE (por entrada)
───────────────────────────────────────── */
/**
 * Calcula, para una entrada, los datos que muestra una tarjeta de la
 * «Colección de viajes» y que luego agregará el hero (#5, R2/R7). Mapea el
 * modelo real de _post_tipo (NO existe un «tipo C» de campo: la opción 'etapa'
 * cubre tanto etapa suelta como salida de un día):
 *   - 'viaje' (tipo D): usa las cachés _post_km_calculado / _post_etapas_count
 *     / _post_ferry_count / _post_km_incompleto. Badge «Viaje».
 *   - cualquier otro (por defecto 'etapa'): salida única → km = _post_km,
 *     1 etapa, 1 ferry si hay _post_horas_ferry. Badge «Salida».
 * Año: _post_fecha_inicio (YYYY-MM-DD); si falta, año de publicación.
 * El km se devuelve en crudo (pásalo por enterprise_km_display() al pintar).
 *
 * @param int $post_id
 * @return array{tipo:string,tipo_label:string,km:string,km_inc:bool,etapas:int,ferrys:int,year:string}
 */
function enterprise_trip_card_data( $post_id ) {
    $tipo = get_post_meta( $post_id, '_post_tipo', true ) ?: 'etapa';

    if ( 'viaje' === $tipo ) {
        $km     = get_post_meta( $post_id, '_post_km_calculado', true );
        $km_inc = (bool) get_post_meta( $post_id, '_post_km_incompleto', true );
        $etapas = (int) get_post_meta( $post_id, '_post_etapas_count', true );
        $ferrys = (int) get_post_meta( $post_id, '_post_ferry_count', true );
        $label  = __( 'Viaje', 'enterprise-moto' );
    } else {
        $km     = get_post_meta( $post_id, '_post_km', true );
        $km_inc = false;
        $etapas = 1;
        $ferrys = get_post_meta( $post_id, '_post_horas_ferry', true ) ? 1 : 0;
        $label  = __( 'Salida', 'enterprise-moto' );
    }

    $fecha_ini = get_post_meta( $post_id, '_post_fecha_inicio', true );
    $year      = $fecha_ini ? substr( (string) $fecha_ini, 0, 4 ) : get_the_date( 'Y', $post_id );

    return array(
        'tipo'       => $tipo,
        'tipo_label' => $label,
        'km'         => $km,          // crudo; usar enterprise_km_display() al pintar
        'km_inc'     => $km_inc,
        'etapas'     => (int) $etapas,
        'ferrys'     => (int) $ferrys,
        'year'       => $year,
    );
}

/* ─────────────────────────────────────────
   CONJUNTO ÚNICO DE ENTRADAS DE UNA PÁGINA DE COLECCIÓN
───────────────────────────────────────── */
/**
 * Dada una página de la plantilla «Colección de viajes», recolecta sus bloques
 * de filtrado (enterprise_collect_stage_blocks), resuelve cada uno con la query
 * compartida (enterprise_stage_query) y devuelve la UNIÓN DEDUPLICADA de IDs de
 * entrada en orden de aparición. Fuente única para el ticker (Fase 3) y para el
 * cálculo de estadísticas de la colección (Fase 4). Un post presente en dos
 * bloques cuenta una sola vez.
 *
 * @param int $page_id
 * @return int[] IDs de entrada, deduplicados, en orden de aparición.
 */
function enterprise_collection_post_ids( $page_id ) {
    $content = get_post_field( 'post_content', $page_id );
    if ( ! $content ) {
        return array();
    }
    $blocks        = parse_blocks( $content );
    $filter_blocks = enterprise_collect_stage_blocks( $blocks );

    $ids = array();
    foreach ( $filter_blocks as $b ) {
        $attrs = ( isset( $b['attrs'] ) && is_array( $b['attrs'] ) ) ? $b['attrs'] : array();
        // #11 R3: «sin límite» del bloque trip-collection, también en este punto de
        // resolución (el hero/ticker cuentan todas). showAll solo lo emite
        // trip-collection; post-stages no lo lleva, así que su resolución no cambia.
        if ( ! empty( $attrs['showAll'] ) ) {
            $attrs['postsPerPage'] = -1;
        }
        $query = enterprise_stage_query( $attrs );
        foreach ( $query->posts as $p ) {
            $pid = is_object( $p ) ? (int) $p->ID : (int) $p;
            if ( $pid && ! in_array( $pid, $ids, true ) ) {
                $ids[] = $pid;
            }
        }
    }
    return $ids;
}

/* ─────────────────────────────────────────
   CIFRAS DE LA COLECCIÓN (cacheadas al guardar) — #5 Fase 4
───────────────────────────────────────── */
/**
 * Parseo de _post_paises de UNA entrada: separa por « · » o coma, hace trim y
 * pone la inicial en mayúscula. La deduplicación ENTRE entradas la hace el
 * llamante (case-insensitive).
 *
 * @param string $raw
 * @return string[]
 */
function enterprise_parse_paises( $raw ) {
    if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
        return array();
    }
    $parts = preg_split( '/\s*[·,]\s*/u', $raw );
    $out   = array();
    foreach ( (array) $parts as $p ) {
        $p = trim( $p );
        if ( '' === $p ) {
            continue;
        }
        if ( function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
            $p = mb_strtoupper( mb_substr( $p, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $p, 1, null, 'UTF-8' );
        } else {
            $p = ucfirst( $p );
        }
        $out[] = $p;
    }
    return $out;
}

/**
 * Convierte un valor de km almacenado a entero. Puede venir con separador de
 * miles a la española ("1.448" = 1448; así lo guarda enterprise_calculate_viaje_stats
 * vía number_format), por lo que se descartan los puntos de miles y cualquier
 * parte decimal tras coma. Cadena vacía o sin dígitos → null (no aporta km).
 *
 * @param mixed $raw
 * @return int|null
 */
function enterprise_km_to_int( $raw ) {
    $s = trim( (string) $raw );
    if ( '' === $s ) {
        return null;
    }
    $s = preg_replace( '/,.*$/', '', $s );    // descarta parte decimal ",5"
    $s = preg_replace( '/[^\d]/', '', $s );   // quita puntos de miles y cualquier no-dígito
    return ( '' === $s ) ? null : (int) $s;
}

/**
 * Computa y persiste las cifras del hero de una página «Colección de viajes»
 * (§3.4) sobre el CONJUNTO ÚNICO de entradas (enterprise_collection_post_ids).
 * Reutiliza enterprise_trip_card_data() por entrada (mismo branching viaje/salida
 * que las tarjetas). Guarda en _col_stats (+ _col_stats_updated). No pinta nada:
 * el formateo del km (≈, miles, sin unidad) lo hace la plantilla.
 *
 * @param int $page_id
 * @return array Cifras persistidas.
 */
function enterprise_compute_collection_stats( $page_id ) {
    $ids = enterprise_collection_post_ids( $page_id );

    $km     = 0;
    $km_inc = false;
    $etapas = 0;
    $ferrys = 0;
    $paises = array();   // clave normalizada (minúsculas) => display; dedup case-insensitive

    foreach ( $ids as $pid ) {
        $d = enterprise_trip_card_data( $pid );

        // Kilómetros: el valor almacenado puede venir con separador de miles a la
        // española ("1.448" = 1448; así lo guarda enterprise_calculate_viaje_stats
        // vía number_format), así que se normaliza a entero. Vacío o sin dígitos →
        // la entrada no aporta km → marca incompleto (un 0 sí cuenta).
        $km_int = enterprise_km_to_int( $d['km'] );
        if ( null === $km_int ) {
            $km_inc = true;
        } else {
            $km += $km_int;
        }
        if ( ! empty( $d['km_inc'] ) ) {
            $km_inc = true;   // viaje con km propio incompleto
        }

        $etapas += (int) $d['etapas'];
        $ferrys += (int) $d['ferrys'];

        // Países: unión deduplicada entre entradas (R6-países).
        foreach ( enterprise_parse_paises( get_post_meta( $pid, '_post_paises', true ) ) as $pais ) {
            $key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $pais, 'UTF-8' ) : strtolower( $pais );
            if ( ! isset( $paises[ $key ] ) ) {
                $paises[ $key ] = $pais;
            }
        }
    }

    $stats = array(
        'viajes'        => count( $ids ),
        'km'            => $km,
        'km_incompleto' => $km_inc,
        'etapas'        => $etapas,
        'paises'        => count( $paises ),
        'ferrys'        => $ferrys,
    );

    update_post_meta( $page_id, '_col_stats', $stats );
    update_post_meta(
        $page_id,
        '_col_stats_updated',
        date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
    );

    return $stats;
}

/**
 * R8 — Recalcula las cifras al guardar la PÁGINA de la plantilla de colección.
 */
function enterprise_recache_collection_on_page_save( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( ! ( $post instanceof WP_Post ) || 'page' !== $post->post_type ) return;
    if ( ! current_user_can( 'edit_page', $post_id ) ) return;
    if ( 'page-templates/template-trip-coleccion.php' !== get_post_meta( $post_id, '_wp_page_template', true ) ) return;

    enterprise_compute_collection_stats( $post_id );
}
add_action( 'save_post', 'enterprise_recache_collection_on_page_save', 20, 2 );

/**
 * R9 — Frescura sin cálculo en caliente: al guardar una entrada (post), recacha
 * TODAS las páginas de la plantilla «Colección de viajes». Prioridad 20 para
 * correr DESPUÉS de enterprise_post_stage_save (10), que actualiza las cachés por
 * entrada (_post_km_calculado, _post_etapas_count, _post_ferry_count). El
 * recálculo usa update_post_meta sobre las páginas (no dispara save_post → sin
 * recursión). Volumen bajo → coste trivial. La query re-deriva el conjunto
 * vigente, así que una despublicación desde el editor también actualiza las
 * cifras; el vaciado a papelera no dispara save_post (edge conocido: se corrige
 * al re-guardar la página).
 */
function enterprise_recache_collections_on_post_save( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( ! ( $post instanceof WP_Post ) || 'post' !== $post->post_type ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $pages = get_posts( array(
        'post_type'        => 'page',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'meta_key'         => '_wp_page_template',
        'meta_value'       => 'page-templates/template-trip-coleccion.php',
        'no_found_rows'    => true,
        'suppress_filters' => true,
    ) );

    foreach ( $pages as $pg ) {
        enterprise_compute_collection_stats( (int) $pg );
    }
}
add_action( 'save_post', 'enterprise_recache_collections_on_post_save', 20, 2 );

/* ─────────────────────────────────────────
   ENCOLADO CSS: plantilla «Colección de viajes»
───────────────────────────────────────── */
/**
 * Carga coleccion.css cuando la plantilla activa es la nueva. El handle
 * «enterprise-coleccion» se registra en enterprise_register_blocks(). El
 * carrusel (si se inserta un bloque post-stages) se auto-encola por has_block.
 */
function enterprise_coleccion_assets() {
    if ( ! is_page() ) {
        return;
    }
    if ( 'page-templates/template-trip-coleccion.php' !== get_page_template_slug( get_queried_object_id() ) ) {
        return;
    }
    wp_enqueue_style( 'enterprise-coleccion' );
}
add_action( 'wp_enqueue_scripts', 'enterprise_coleccion_assets' );

/* ─────────────────────────────────────────
   PAGINACIÓN PERSONALIZADA
───────────────────────────────────────── */
function enterprise_pagination() {
    $args = array(
        'mid_size'           => 2,
        'prev_text'          => '← ' . __( 'Anterior', 'enterprise-moto' ),
        'next_text'          => __( 'Siguiente', 'enterprise-moto' ) . ' →',
        'before_page_number' => '',
        'after_page_number'  => '',
    );
    $links = paginate_links( $args );
    if ( $links ) {
        echo '<nav class="pagination" aria-label="' . esc_attr__( 'Paginación', 'enterprise-moto' ) . '">';
        echo $links;
        echo '</nav>';
    }
}

/* ─────────────────────────────────────────
   EXCERPT LIMPIO
───────────────────────────────────────── */
function enterprise_excerpt_length( $length ) { return 25; }
add_filter( 'excerpt_length', 'enterprise_excerpt_length', 999 );
function enterprise_excerpt_more( $more ) { return '…'; }
add_filter( 'excerpt_more', 'enterprise_excerpt_more' );

/* ─────────────────────────────────────────
   CATEGORÍA DEL POST COMO TAG
───────────────────────────────────────── */
function enterprise_first_category( $post_id = null ) {
    $cats = $post_id ? get_the_category( $post_id ) : get_the_category();
    if ( ! empty( $cats ) ) {
        return esc_html( $cats[0]->name );
    }
    return __( 'Ruta', 'enterprise-moto' );
}

/* ─────────────────────────────────────────
   ELIMINAR EMOJIS (limpieza)
───────────────────────────────────────── */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/* ─────────────────────────────────────────
   IDENTIDAD DE MARCA: favicon / app-icons
───────────────────────────────────────── */

/**
 * Emite los <link> de favicon / app-icon de marca en el <head> del frontend.
 * Fuente única: el tema (assets/images/), no el Site Icon nativo (que no admite SVG).
 * Cache-busting por filemtime(), coherente con el resto de assets del tema.
 */
function enterprise_emit_brand_icons() {
	$links = array(
		array( 'rel' => 'icon',             'file' => 'favicon.ico',      'extra' => ' sizes="32x32"' ),
		array( 'rel' => 'icon',             'file' => 'favicon.svg',      'extra' => ' type="image/svg+xml"' ),
		array( 'rel' => 'apple-touch-icon', 'file' => 'apple-touch-icon.png', 'extra' => '' ),
		array( 'rel' => 'manifest',         'file' => 'site.webmanifest', 'extra' => '' ),
	);
	foreach ( $links as $l ) {
		$path = get_theme_file_path( 'assets/images/' . $l['file'] );
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$uri = add_query_arg( 'ver', filemtime( $path ), get_theme_file_uri( 'assets/images/' . $l['file'] ) );
		printf( "<link rel=\"%s\" href=\"%s\"%s>\n", esc_attr( $l['rel'] ), esc_url( $uri ), $l['extra'] );
	}
}
add_action( 'wp_head', 'enterprise_emit_brand_icons' );

// Fuente única: retirar la emisión del Site Icon nativo del <head> del frontend.
remove_action( 'wp_head', 'wp_site_icon', 99 );

/* ─────────────────────────────────────────
   IDENTIDAD DE MARCA: Open Graph / Twitter Card
───────────────────────────────────────── */

/**
 * Emite las etiquetas Open Graph + Twitter Card para las previsualizaciones al
 * compartir enlaces. og:image de marca único para todo el sitio; título y
 * descripción siguen a la página actual. Mismas convenciones que
 * enterprise_emit_brand_icons() (hook wp_head, get_theme_file_path/uri +
 * filemtime para la imagen, escapado).
 */
function enterprise_emit_og_tags() {
	$is_singular = is_singular();

	// Título / descripción siguen a la página; la imagen es siempre la de marca.
	if ( is_front_page() || is_home() ) {
		$og_title = get_bloginfo( 'name' );
		$og_desc  = get_bloginfo( 'description' );
		$og_url   = home_url( '/' );
		$og_type  = 'website';
	} elseif ( $is_singular ) {
		$og_title = wp_get_document_title();
		$excerpt  = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_queried_object_id() ) ), 40, '…' );
		$og_desc  = $excerpt !== '' ? $excerpt : get_bloginfo( 'description' );
		$og_url   = get_permalink();
		$og_type  = 'article';
	} else {
		$og_title = wp_get_document_title();
		$og_desc  = get_bloginfo( 'description' );
		$og_url   = home_url( '/' );
		$og_type  = 'website';
	}

	// Imagen de marca (única, para todo el sitio); URL absoluta + cache-busting.
	$img_rel  = 'assets/images/og-image.png';
	$img_path = get_theme_file_path( $img_rel );
	$img_uri  = file_exists( $img_path )
		? add_query_arg( 'ver', filemtime( $img_path ), get_theme_file_uri( $img_rel ) )
		: '';

	$tags = array(
		array( 'property', 'og:type',        $og_type ),
		array( 'property', 'og:site_name',   get_bloginfo( 'name' ) ),
		array( 'property', 'og:locale',      get_locale() ),
		array( 'property', 'og:title',       $og_title ),
		array( 'property', 'og:description', $og_desc ),
		array( 'property', 'og:url',         $og_url ),
	);
	if ( $img_uri !== '' ) {
		$tags[] = array( 'property', 'og:image',        $img_uri );
		$tags[] = array( 'property', 'og:image:type',   'image/png' );
		$tags[] = array( 'property', 'og:image:width',  '1200' );
		$tags[] = array( 'property', 'og:image:height', '630' );
		$tags[] = array( 'property', 'og:image:alt',    'Bitácora Enterprise' );
	}
	// La Twitter Card refleja los valores og.
	$tags[] = array( 'name', 'twitter:card',        'summary_large_image' );
	$tags[] = array( 'name', 'twitter:title',       $og_title );
	$tags[] = array( 'name', 'twitter:description', $og_desc );
	if ( $img_uri !== '' ) {
		$tags[] = array( 'name', 'twitter:image',   $img_uri );
	}

	foreach ( $tags as $t ) {
		list( $attr, $key, $val ) = $t;
		if ( $val === '' || $val === null ) {
			continue;
		}
		$val = ( $key === 'og:image' || $key === 'og:url' || $key === 'twitter:image' )
			? esc_url( $val ) : esc_attr( $val );
		printf( "<meta %s=\"%s\" content=\"%s\">\n", esc_attr( $attr ), esc_attr( $key ), $val );
	}
}
add_action( 'wp_head', 'enterprise_emit_og_tags', 5 );

/* ─────────────────────────────────────────
   PERMITIR SUBIDA DE ARCHIVOS GPX
   a la biblioteca de medios de WordPress
───────────────────────────────────────── */
add_filter( 'upload_mimes', function ( $mimes ) {
    $mimes['gpx'] = 'application/gpx+xml';
    return $mimes;
} );

// WordPress 5.1+ verifica también el contenido real del archivo.
// Este filtro desactiva esa comprobación para GPX, que es XML válido.
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
    if ( substr( $filename, -4 ) === '.gpx' ) {
        $data['ext']  = 'gpx';
        $data['type'] = 'application/gpx+xml';
    }
    return $data;
}, 10, 4 );

/* ─────────────────────────────────────────
   BODY CLASSES EXTRA
───────────────────────────────────────── */
function enterprise_body_classes( $classes ) {
    if ( is_singular( 'post' ) )         $classes[] = 'single-route';
    if ( is_home() || is_front_page() )  $classes[] = 'is-home';
    if ( get_theme_mod( 'enterprise_custom_cursor', true ) ) {
        $classes[] = 'cursor-custom-enabled';
    }
    return $classes;
}
add_filter( 'body_class', 'enterprise_body_classes' );

/* ─────────────────────────────────────────
   SOPORTE RSS
───────────────────────────────────────── */
function enterprise_feed_links() {
    add_theme_support( 'automatic-feed-links' );
}

// Incluir funciones auxiliares
require_once get_template_directory() . '/inc/fallback-menu.php';

/* ─────────────────────────────────────────
   CARGAR CSS DE EXPEDICIÓN SOLO EN LA
   PLANTILLA CUADERNO DE BITÁCORA
───────────────────────────────────────── */
function enterprise_expedition_styles() {
    if ( ! is_page() ) return;

    $template = get_page_template_slug( get_queried_object_id() );
    if ( 'page-cuaderno-de-bitacora.php' !== $template ) return;

    wp_enqueue_style(
        'enterprise-expedition',
        get_template_directory_uri() . '/assets/css/expedition.css',
        array( 'enterprise-style' ),
        ENTERPRISE_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_expedition_styles' );

/* ─────────────────────────────────────────
   METABOXES PARA LA PÁGINA DE EXPEDICIÓN
───────────────────────────────────────── */
function enterprise_register_expedition_metabox( $post_type = 'page', $post = null ) {
    // La «Colección de viajes» no usa el metabox de expedición (#5 Fase 3b):
    // no lo registramos en esa plantilla. El resto de páginas no se ven afectadas.
    if ( $post instanceof WP_Post
        && 'page-templates/template-trip-coleccion.php' === get_post_meta( $post->ID, '_wp_page_template', true ) ) {
        return;
    }
    add_meta_box(
        'enterprise_expedition_data',
        __( 'Datos de la expedición', 'enterprise-moto' ),
        'enterprise_expedition_metabox_cb',
        'page',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'enterprise_register_expedition_metabox', 10, 2 );

function enterprise_expedition_metabox_cb( $post ) {
    $template          = get_post_meta( $post->ID, '_wp_page_template', true );
    $allowed_templates = array(
        'page-cuaderno-de-bitacora.php',
    );

    if ( ! in_array( $template, $allowed_templates, true ) ) {
        echo '<p style="color:#888;font-size:13px;">' .
             esc_html__( 'Asigna la plantilla "Cuaderno de bitácora" para activar estos campos.', 'enterprise-moto' ) .
             '</p>';
        return;
    }

    $has_parent   = (bool) wp_get_post_parent_id( $post->ID );
    $exp_estado_v = get_post_meta( $post->ID, '_exp_estado', true );
    $is_portal    = ! $has_parent && empty( $exp_estado_v );

    if ( $is_portal ) {
        echo '<div style="padding:12px 14px;background:#f0f6fc;border-left:3px solid #72aee6;">';
        echo '<p style="font-size:13px;font-weight:600;margin:0 0 8px;">' . esc_html__( '📋 Esta es la página portal del Cuaderno de bitácora', 'enterprise-moto' ) . '</p>';
        echo '<p style="font-size:12px;color:#555;margin:0 0 6px;line-height:1.6;">' . esc_html__( 'No configures los datos del viaje aquí. Este portal enruta automáticamente:', 'enterprise-moto' ) . '</p>';
        echo '<ul style="font-size:12px;color:#555;margin:0;padding-left:18px;line-height:1.8;">';
        echo '<li>' . esc_html__( 'Si hay un cuaderno hijo con estado "Activo" → redirige a él.', 'enterprise-moto' ) . '</li>';
        echo '<li>' . esc_html__( 'Si no hay ninguno activo → muestra el estado "Fuera de ruta".', 'enterprise-moto' ) . '</li>';
        echo '</ul>';
        echo '<p style="font-size:12px;color:#555;margin:8px 0 0;line-height:1.6;"><strong>' . esc_html__( 'Para iniciar un nuevo viaje:', 'enterprise-moto' ) . '</strong> ';
        echo esc_html__( 'crea una nueva página hija de esta con la plantilla "Cuaderno de bitácora", rellena sus datos y pon su estado en "Activo".', 'enterprise-moto' ) . '</p>';
        echo '</div>';
        return;
    }

    wp_nonce_field( 'enterprise_expedition_nonce_action', 'enterprise_expedition_nonce' );

    // ── Helpers de estilo ──────────────────────────────────────────────────
    $s_section = 'margin-top:20px;border:1px solid #e0e0e0;border-radius:3px;overflow:hidden;';
    $s_sheader = 'background:#f6f7f7;padding:8px 14px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#555;border-bottom:1px solid #e0e0e0;';
    $s_sbody   = 'padding:14px;';
    $s_grid2   = 'display:grid;grid-template-columns:1fr 1fr;gap:12px;';
    $s_label   = 'display:block;font-size:11px;font-weight:600;color:#555;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;';
    $s_input   = 'width:100%;padding:7px 9px;border:1px solid #ddd;font-size:13px;box-sizing:border-box;';

    // ── SECCIÓN 1: Datos de la expedición ─────────────────────────────────
    echo '<div style="' . $s_section . '">';
    echo '<div style="' . $s_sheader . '">📋 ' . esc_html__( 'Datos de la expedición', 'enterprise-moto' ) . '</div>';
    echo '<div style="' . $s_sbody . '">';
    echo '<div style="' . $s_grid2 . '">';

    // Plantilla «Cuaderno de bitácora»: duración y progreso se CALCULAN en caliente,
    // así que se retiran del metabox. _exp_fecha_fin es opcional y sin semántica
    // «en curso» — el estado lo da _exp_estado.
    $exp_fields = array(
        '_exp_nombre'      => array( 'label' => __( 'Nombre del viaje',          'enterprise-moto' ), 'placeholder' => 'Ej: Sicilia 2026' ),
        '_exp_subtitulo'   => array( 'label' => __( 'Descripción / ruta',        'enterprise-moto' ), 'placeholder' => 'Ej: BCN → Palermo → Cerdeña → BCN' ),
        '_exp_fecha_inicio' => array( 'label' => __( 'Fecha de inicio',          'enterprise-moto' ), 'placeholder' => 'AAAA-MM-DD', 'type' => 'date' ),
        '_exp_fecha_fin'   => array( 'label' => __( 'Fecha de fin',              'enterprise-moto' ), 'placeholder' => 'AAAA-MM-DD', 'type' => 'date' ),
        '_exp_salida'      => array( 'label' => __( 'Texto salida (auto si hay fechas)', 'enterprise-moto' ), 'placeholder' => 'Ej: 23 Mar 2026' ),
        '_exp_km'          => array( 'label' => __( 'Kilómetros totales',        'enterprise-moto' ), 'placeholder' => 'Ej: ~3.200 km (vacío = auto)' ),
        '_exp_paises'      => array( 'label' => __( 'Países recorridos',         'enterprise-moto' ), 'placeholder' => 'Ej: España · Francia · Italia' ),
    );
    foreach ( $exp_fields as $key => $f ) {
        $val  = get_post_meta( $post->ID, $key, true );
        $type = isset( $f['type'] ) ? $f['type'] : 'text';
        echo '<div><label style="' . $s_label . '">' . esc_html( $f['label'] ) . '</label>';
        echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( $f['placeholder'] ) . '" style="' . $s_input . '"></div>';
    }
    echo '</div></div></div>';

    // ── SECCIÓN 2: Estado ──────────────────────────────────────────────────
    $exp_estado = get_post_meta( $post->ID, '_exp_estado', true ) ?: 'finalizado';
    echo '<div style="' . $s_section . 'border-color:#f2c118;">';
    echo '<div style="' . $s_sheader . 'background:#fffbea;border-color:#f2c118;">⚡ ' . esc_html__( 'Estado del cuaderno', 'enterprise-moto' ) . '</div>';
    echo '<div style="' . $s_sbody . '">';
    echo '<select name="_exp_estado" style="' . $s_input . '">';
    $estados = array(
        'preparando' => '🔧 En preparación — accesible solo por URL directa, sin aparecer en listados',
        'activo'     => '✈ Activo — cuaderno en curso (redirige desde el portal, badge animado)',
        'finalizado' => '✓ Finalizado — archivado en "Cuadernos anteriores"',
    );
    foreach ( $estados as $val => $lbl ) {
        echo '<option value="' . esc_attr( $val ) . '" ' . selected( $exp_estado, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
    }
    echo '</select>';
    echo '</div></div>';

    // Las secciones 3–5 solo aplican a la plantilla "Cuaderno de bitácora" (automática)

    // ── SECCIÓN 3: Filtros (solo page-cuaderno-de-bitacora) ───────────────
    $filt_cat_ids     = get_post_meta( $post->ID, '_filt_category_ids',  true ) ?: array();
    $filt_tag_ids     = get_post_meta( $post->ID, '_filt_tag_ids',       true ) ?: array();
    $filt_tag_rel     = get_post_meta( $post->ID, '_filt_tag_relation',  true ) ?: 'OR';
    $filt_date_from   = get_post_meta( $post->ID, '_filt_date_from',     true ) ?: '';
    $filt_date_to     = get_post_meta( $post->ID, '_filt_date_to',       true ) ?: '';

    // Obtener todas las categorías y etiquetas para los checkboxes
    $all_cats = get_categories( array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
    $all_tags = get_tags( array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );

    // Normalizar a arrays de enteros
    $filt_cat_ids = is_array( $filt_cat_ids ) ? array_map( 'intval', $filt_cat_ids ) : array();
    $filt_tag_ids = is_array( $filt_tag_ids ) ? array_map( 'intval', $filt_tag_ids ) : array();

    echo '<div style="' . $s_section . '">';
    echo '<div style="' . $s_sheader . '">🔍 ' . esc_html__( 'Filtros de entradas', 'enterprise-moto' ) . '</div>';
    echo '<div style="' . $s_sbody . '">';

    // Categorías jerárquicas (OR entre ellas siempre, igual que el bloque)
    echo '<div style="margin-bottom:14px;">';
    echo '<label style="' . $s_label . '">' . esc_html__( 'Categorías (OR entre seleccionadas)', 'enterprise-moto' ) . '</label>';
    echo '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;padding:8px;background:#fafafa;">';

    // Función recursiva para mostrar categorías con jerarquía
    $render_cats = function( $cats, $parent_id = 0, $depth = 0 ) use ( &$render_cats, $filt_cat_ids, $s_label ) {
        foreach ( $cats as $cat ) {
            if ( $cat->parent !== $parent_id ) continue;
            $indent  = str_repeat( '&nbsp;&nbsp;&nbsp;', $depth );
            $prefix  = $depth > 0 ? '└ ' : '';
            $checked = in_array( $cat->term_id, $filt_cat_ids, true ) ? 'checked' : '';
            echo '<label style="display:block;font-size:12px;margin-bottom:5px;white-space:nowrap;">';
            echo '<input type="checkbox" name="_filt_category_ids[]" value="' . esc_attr( $cat->term_id ) . '" ' . $checked . '> ';
            echo $indent . $prefix . esc_html( $cat->name ) . ' <span style="color:#aaa;">(' . intval( $cat->count ) . ')</span>';
            echo '</label>';
            $render_cats( $cats, $cat->term_id, $depth + 1 );
        }
    };
    $render_cats( $all_cats, 0, 0 );

    echo '</div></div>';

    // Etiquetas + relación AND/OR
    echo '<div style="margin-bottom:14px;">';
    echo '<div style="display:flex;align-items:center;gap:16px;margin-bottom:6px;">';
    echo '<label style="' . $s_label . 'margin:0;">' . esc_html__( 'Etiquetas', 'enterprise-moto' ) . '</label>';
    echo '<span style="font-size:11px;color:#555;">' . esc_html__( 'Relación entre etiquetas:', 'enterprise-moto' ) . '</span>';
    echo '<label style="font-size:12px;"><input type="radio" name="_filt_tag_relation" value="OR" ' . checked( $filt_tag_rel, 'OR', false ) . '> OR</label>';
    echo '<label style="font-size:12px;"><input type="radio" name="_filt_tag_relation" value="AND" ' . checked( $filt_tag_rel, 'AND', false ) . '> AND</label>';
    echo '</div>';
    echo '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;padding:8px;background:#fafafa;">';
    foreach ( $all_tags as $tag ) {
        $checked = in_array( $tag->term_id, $filt_tag_ids, true ) ? 'checked' : '';
        echo '<label style="display:block;font-size:12px;margin-bottom:5px;white-space:nowrap;">';
        echo '<input type="checkbox" name="_filt_tag_ids[]" value="' . esc_attr( $tag->term_id ) . '" ' . $checked . '> ';
        echo esc_html( $tag->name ) . ' <span style="color:#aaa;">(' . intval( $tag->count ) . ')</span>';
        echo '</label>';
    }
    echo '</div></div>';

    // Fechas absolutas
    echo '<div style="' . $s_grid2 . '">';
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Fecha desde (inclusive)', 'enterprise-moto' ) . '</label>';
    echo '<input type="date" name="_filt_date_from" value="' . esc_attr( $filt_date_from ) . '" style="' . $s_input . '"></div>';
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Fecha hasta (inclusive, vacío = hoy)', 'enterprise-moto' ) . '</label>';
    echo '<input type="date" name="_filt_date_to" value="' . esc_attr( $filt_date_to ) . '" style="' . $s_input . '"></div>';
    echo '</div>';

    echo '</div></div>'; // /sbody /section

    // ── SECCIÓN 4: Cantidad y orden ───────────────────────────────────────
    $filt_limit   = get_post_meta( $post->ID, '_filt_limit',    true ); // '' = sin límite
    $filt_orderby = get_post_meta( $post->ID, '_filt_orderby',  true ) ?: 'date';
    $filt_order   = get_post_meta( $post->ID, '_filt_order',    true ) ?: 'DESC';

    echo '<div style="' . $s_section . '">';
    echo '<div style="' . $s_sheader . '">📊 ' . esc_html__( 'Cantidad y orden', 'enterprise-moto' ) . '</div>';
    echo '<div style="' . $s_sbody . $s_grid2 . '">';

    // Límite
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Cantidad máxima (vacío = todas)', 'enterprise-moto' ) . '</label>';
    echo '<input type="number" name="_filt_limit" value="' . esc_attr( $filt_limit ) . '" placeholder="' . esc_attr__( 'Vacío = sin límite', 'enterprise-moto' ) . '" min="1" style="' . $s_input . '"></div>';

    // Ordenar por
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Ordenar por', 'enterprise-moto' ) . '</label>';
    echo '<select name="_filt_orderby" style="' . $s_input . '">';
    $order_opts = array(
        'date'          => __( 'Fecha de publicación', 'enterprise-moto' ),
        'title'         => __( 'Título (A–Z)',          'enterprise-moto' ),
        'menu_order'    => __( 'Orden manual',           'enterprise-moto' ),
        'modified'      => __( 'Última modificación',   'enterprise-moto' ),
        'rand'          => __( 'Aleatorio',              'enterprise-moto' ),
    );
    foreach ( $order_opts as $val => $lbl ) {
        echo '<option value="' . esc_attr( $val ) . '" ' . selected( $filt_orderby, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
    }
    echo '</select></div>';

    // Dirección
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Dirección', 'enterprise-moto' ) . '</label>';
    echo '<select name="_filt_order" style="' . $s_input . '">';
    echo '<option value="DESC" ' . selected( $filt_order, 'DESC', false ) . '>' . esc_html__( 'Descendente (más reciente primero)', 'enterprise-moto' ) . '</option>';
    echo '<option value="ASC" '  . selected( $filt_order, 'ASC',  false ) . '>' . esc_html__( 'Ascendente (más antiguo primero)',   'enterprise-moto' ) . '</option>';
    echo '</select></div>';

    echo '</div></div>'; // /sbody /section

    // ── SECCIÓN 5: Presentación ───────────────────────────────────────────
    $pres_layout    = get_post_meta( $post->ID, '_pres_layout',    true ) ?: 'timeline';
    $pres_card_size = get_post_meta( $post->ID, '_pres_card_size', true ) ?: 'normal';
    $pres_excerpt   = get_post_meta( $post->ID, '_pres_show_excerpt', true );
    $pres_km        = get_post_meta( $post->ID, '_pres_show_km',    true );
    $pres_date      = get_post_meta( $post->ID, '_pres_show_date',  true );
    // Defaults (vacío → true en primera vez)
    $pres_excerpt = ( $pres_excerpt === '' ) ? true : (bool) $pres_excerpt;
    $pres_km      = ( $pres_km      === '' ) ? true : (bool) $pres_km;
    $pres_date    = ( $pres_date    === '' ) ? true : (bool) $pres_date;

    echo '<div style="' . $s_section . '">';
    echo '<div style="' . $s_sheader . '">🎨 ' . esc_html__( 'Presentación', 'enterprise-moto' ) . '</div>';
    echo '<div style="' . $s_sbody . '">';
    echo '<div style="' . $s_grid2 . 'margin-bottom:12px;">';

    // Layout
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Modo de visualización', 'enterprise-moto' ) . '</label>';
    echo '<select name="_pres_layout" style="' . $s_input . '">';
    echo '<option value="timeline" ' . selected( $pres_layout, 'timeline',  false ) . '>' . esc_html__( '📋 Timeline vertical', 'enterprise-moto' ) . '</option>';
    echo '<option value="carousel" ' . selected( $pres_layout, 'carousel',  false ) . '>' . esc_html__( '🎠 Carrusel horizontal', 'enterprise-moto' ) . '</option>';
    echo '</select></div>';

    // Tamaño tarjeta
    echo '<div><label style="' . $s_label . '">' . esc_html__( 'Tamaño de tarjeta', 'enterprise-moto' ) . '</label>';
    echo '<select name="_pres_card_size" style="' . $s_input . '">';
    echo '<option value="normal" ' . selected( $pres_card_size, 'normal', false ) . '>' . esc_html__( 'Normal', 'enterprise-moto' ) . '</option>';
    echo '<option value="large"  ' . selected( $pres_card_size, 'large',  false ) . '>' . esc_html__( 'Grande', 'enterprise-moto' ) . '</option>';
    echo '</select></div>';
    echo '</div>';

    // Campos visibles
    echo '<label style="' . $s_label . '">' . esc_html__( 'Campos visibles en las tarjetas', 'enterprise-moto' ) . '</label>';
    echo '<div style="display:flex;gap:20px;">';
    echo '<label style="font-size:12px;"><input type="checkbox" name="_pres_show_excerpt" value="1" ' . checked( $pres_excerpt, true, false ) . '> ' . esc_html__( 'Extracto', 'enterprise-moto' ) . '</label>';
    echo '<label style="font-size:12px;"><input type="checkbox" name="_pres_show_km"      value="1" ' . checked( $pres_km,      true, false ) . '> ' . esc_html__( 'Kilómetros', 'enterprise-moto' ) . '</label>';
    echo '<label style="font-size:12px;"><input type="checkbox" name="_pres_show_date"    value="1" ' . checked( $pres_date,    true, false ) . '> ' . esc_html__( 'Fecha', 'enterprise-moto' ) . '</label>';
    echo '</div>';

    echo '</div></div>'; // /sbody /section
}

function enterprise_save_expedition_meta( $post_id ) {
    if ( ! isset( $_POST['enterprise_expedition_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enterprise_expedition_nonce'], 'enterprise_expedition_nonce_action' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_page', $post_id ) ) return;

    // ── Datos de expedición (texto) — comunes a ambas plantillas ───────────
    $text_fields = array(
        '_exp_nombre', '_exp_subtitulo', '_exp_salida', '_exp_km', '_exp_paises',
    );
    foreach ( $text_fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    // Campos de fecha de expedición
    foreach ( array( '_exp_fecha_inicio', '_exp_fecha_fin' ) as $date_field ) {
        if ( isset( $_POST[ $date_field ] ) ) {
            $date_val = sanitize_text_field( $_POST[ $date_field ] );
            if ( $date_val === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_val) ) {
                update_post_meta( $post_id, $date_field, $date_val );
            }
        }
    }

    // Estado del cuaderno
    if ( isset( $_POST['_exp_estado'] ) && in_array( $_POST['_exp_estado'], array( 'preparando', 'activo', 'finalizado' ), true ) ) {
        update_post_meta( $post_id, '_exp_estado', $_POST['_exp_estado'] );
        update_post_meta( $post_id, '_exp_en_ruta', $_POST['_exp_estado'] === 'activo' ? '1' : '' );
    }

    // ── Filtros (solo page-cuaderno-de-bitacora) ───────────────────────────
    // Categorías (array de enteros)
    $cat_ids = isset( $_POST['_filt_category_ids'] ) && is_array( $_POST['_filt_category_ids'] )
        ? array_map( 'intval', $_POST['_filt_category_ids'] )
        : array();
    update_post_meta( $post_id, '_filt_category_ids', $cat_ids );

    // Etiquetas (array de enteros)
    $tag_ids = isset( $_POST['_filt_tag_ids'] ) && is_array( $_POST['_filt_tag_ids'] )
        ? array_map( 'intval', $_POST['_filt_tag_ids'] )
        : array();
    update_post_meta( $post_id, '_filt_tag_ids', $tag_ids );

    // Relación de etiquetas: OR o AND
    $tag_rel = ( isset( $_POST['_filt_tag_relation'] ) && $_POST['_filt_tag_relation'] === 'AND' ) ? 'AND' : 'OR';
    update_post_meta( $post_id, '_filt_tag_relation', $tag_rel );

    // Fechas absolutas de filtro
    foreach ( array( '_filt_date_from', '_filt_date_to' ) as $df ) {
        if ( isset( $_POST[ $df ] ) ) {
            $v = sanitize_text_field( $_POST[ $df ] );
            if ( $v === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ) {
                update_post_meta( $post_id, $df, $v );
            }
        }
    }

    // ── Cantidad y orden ───────────────────────────────────────────────────
    if ( isset( $_POST['_filt_limit'] ) ) {
        $limit = trim( $_POST['_filt_limit'] );
        update_post_meta( $post_id, '_filt_limit', $limit === '' ? '' : max( 1, intval( $limit ) ) );
    }

    $valid_orderby = array( 'date', 'title', 'menu_order', 'modified', 'rand' );
    if ( isset( $_POST['_filt_orderby'] ) && in_array( $_POST['_filt_orderby'], $valid_orderby, true ) ) {
        update_post_meta( $post_id, '_filt_orderby', $_POST['_filt_orderby'] );
    }
    $valid_order = array( 'ASC', 'DESC' );
    if ( isset( $_POST['_filt_order'] ) && in_array( strtoupper( $_POST['_filt_order'] ), $valid_order, true ) ) {
        update_post_meta( $post_id, '_filt_order', strtoupper( $_POST['_filt_order'] ) );
    }

    // ── Presentación ───────────────────────────────────────────────────────
    $valid_layouts = array( 'timeline', 'carousel' );
    if ( isset( $_POST['_pres_layout'] ) && in_array( $_POST['_pres_layout'], $valid_layouts, true ) ) {
        update_post_meta( $post_id, '_pres_layout', $_POST['_pres_layout'] );
    }
    $valid_sizes = array( 'normal', 'large' );
    if ( isset( $_POST['_pres_card_size'] ) && in_array( $_POST['_pres_card_size'], $valid_sizes, true ) ) {
        update_post_meta( $post_id, '_pres_card_size', $_POST['_pres_card_size'] );
    }
    // Checkboxes: presentes = true, ausentes = false
    update_post_meta( $post_id, '_pres_show_excerpt', isset( $_POST['_pres_show_excerpt'] ) ? true : false );
    update_post_meta( $post_id, '_pres_show_km',      isset( $_POST['_pres_show_km'] )      ? true : false );
    update_post_meta( $post_id, '_pres_show_date',    isset( $_POST['_pres_show_date'] )    ? true : false );
}
add_action( 'save_post_page', 'enterprise_save_expedition_meta' );

/* ─────────────────────────────────────────
   REGISTRO BLOQUES DE MAPA
───────────────────────────────────────── */
function enterprise_register_map_blocks() {

    require_once get_template_directory() . '/blocks/location-map/render.php';
    require_once get_template_directory() . '/blocks/route-map/render.php';
    require_once get_template_directory() . '/blocks/routes-by-location/render.php';

    /* ── Script location-map (editor) ── */
    wp_register_script(
        'enterprise-block-location-map',
        get_template_directory_uri() . '/assets/js/block-location-map.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( get_template_directory() . '/assets/js/block-location-map.js' ),
        true
    );

    /* ── Script route-map (editor) ── */
    wp_register_script(
        'enterprise-block-route-map',
        get_template_directory_uri() . '/assets/js/block-route-map.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( get_template_directory() . '/assets/js/block-route-map.js' ),
        true
    );

    /* ── Bloque location-map ── */
    register_block_type( 'enterprise/location-map', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-location-map',
        'render_callback' => 'enterprise_render_location_map_block',
        'attributes'      => array(
            'markers'     => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'object' ) ),
            'mapHeight'   => array( 'type' => 'string',  'default' => 'md'   ),
            'mapZoom'     => array( 'type' => 'integer', 'default' => 6      ),
            'heading'     => array( 'type' => 'string',  'default' => ''     ),
            'showLegend'  => array( 'type' => 'boolean', 'default' => true   ),
            'showNumbers' => array( 'type' => 'boolean', 'default' => true   ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque animated-route-map ── */
    wp_register_script(
        'enterprise-block-animated-route-map',
        get_template_directory_uri() . '/assets/js/block-animated-route-map.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( get_template_directory() . '/assets/js/block-animated-route-map.js' ),
        true
    );

    require_once get_template_directory() . '/blocks/animated-route-map/render.php';

    register_block_type( 'enterprise/animated-route-map', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-animated-route-map',
        'render_callback' => 'enterprise_render_animated_route_map_block',
        'attributes'      => array(
            'gpxUrl'       => array( 'type' => 'string',  'default' => ''        ),
            'heading'      => array( 'type' => 'string',  'default' => ''        ),
            'mapHeight'    => array( 'type' => 'string',  'default' => 'md'      ),
            'routeColor'   => array( 'type' => 'string',  'default' => '#001f5c' ),
            'markerColor'  => array( 'type' => 'string',  'default' => '#f2c118' ),
            'routeWeight'  => array( 'type' => 'integer', 'default' => 4         ),
            'showElevation'=> array( 'type' => 'boolean', 'default' => true      ),
            'showStats'    => array( 'type' => 'boolean', 'default' => true      ),
            'startLabel'   => array( 'type' => 'string',  'default' => ''        ),
            'endLabel'     => array( 'type' => 'string',  'default' => ''        ),
            'statKm'       => array( 'type' => 'string',  'default' => ''        ),
            'statDuration' => array( 'type' => 'string',  'default' => ''        ),
            'statElevGain' => array( 'type' => 'string',  'default' => ''        ),
            'description'  => array( 'type' => 'string',  'default' => ''        ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque route-map ── */
    register_block_type( 'enterprise/route-map', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-route-map',
        'render_callback' => 'enterprise_render_route_map_block',
        'attributes'      => array(
            /* GPX 1 — ruta principal */
            'gpxUrl'       => array( 'type' => 'string',  'default' => ''                ),
            'gpxLabel1'    => array( 'type' => 'string',  'default' => 'Ruta planificada'),
            'routeColor'   => array( 'type' => 'string',  'default' => '#001f5c'         ),
            /* GPX 2 — segunda ruta opcional */
            'gpxUrl2'      => array( 'type' => 'string',  'default' => ''                ),
            'gpxLabel2'    => array( 'type' => 'string',  'default' => 'Ruta GPS'        ),
            'routeColor2'  => array( 'type' => 'string',  'default' => '#c0392b'         ),
            /* Configuración */
            'heading'      => array( 'type' => 'string',  'default' => ''       ),
            'mapHeight'    => array( 'type' => 'string',  'default' => 'md'     ),
            'routeWeight'  => array( 'type' => 'integer', 'default' => 4        ),
            'showElevation'=> array( 'type' => 'boolean', 'default' => true     ),
            'showStats'    => array( 'type' => 'boolean', 'default' => true     ),
            'startLabel'   => array( 'type' => 'string',  'default' => ''       ),
            'endLabel'     => array( 'type' => 'string',  'default' => ''       ),
            'statKm'       => array( 'type' => 'string',  'default' => ''       ),
            'statDuration' => array( 'type' => 'string',  'default' => ''       ),
            'statElevGain' => array( 'type' => 'string',  'default' => ''       ),
            'description'  => array( 'type' => 'string',  'default' => ''       ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Script routes-by-location (editor) ──
       wp-api-fetch: el Modal lee categorías/etiquetas por REST (term pickers).
       OpenLayers se carga bajo demanda desde el propio JS al abrir el Modal. */
    wp_register_script(
        'enterprise-block-routes-by-location',
        get_template_directory_uri() . '/assets/js/block-routes-by-location.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch' ),
        filemtime( get_template_directory() . '/assets/js/block-routes-by-location.js' ),
        true
    );

    /* ── Bloque routes-by-location ──
       Mapa de rutas por localización: cada marcador guarda un filtro compuesto
       (IDs de categoría/etiqueta) en lugar de una URL fija. Mismos atributos de
       presentación que location-map; los campos por-marcador (name, lat, lng,
       description opcional, filterCatIds, filterTagIds) viven dentro de markers[]. */
    register_block_type( 'enterprise/routes-by-location', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-routes-by-location',
        'render_callback' => 'enterprise_render_routes_by_location_block',
        'attributes'      => array(
            'markers'     => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'object' ) ),
            'mapHeight'   => array( 'type' => 'string',  'default' => 'md'   ),
            'mapZoom'     => array( 'type' => 'integer', 'default' => 6      ),
            'heading'     => array( 'type' => 'string',  'default' => ''     ),
            'showLegend'  => array( 'type' => 'boolean', 'default' => true   ),
            'showNumbers' => array( 'type' => 'boolean', 'default' => true   ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque interactive-region-map (#43 / #54) ──
       Mapa coroplético SVG nativo de regiones (nivel-1, Europa). NO reutiliza el
       motor OpenLayers (§13.19). Fase #43: esqueleto + render inline estático del
       SVG; navegación en #44. Se registra SIN clave 'category' (la agrupación bajo
       «Enterprise Moto» es #39). #54 añade 'attributes': colores, grosores y
       opacidades configurables por nivel (paleta global, juego cerrado); la
       personalización por región sigue siendo #47. */
    require_once get_template_directory() . '/blocks/interactive-region-map/render.php';

    wp_register_script(
        'enterprise-block-interactive-region-map',
        get_template_directory_uri() . '/assets/js/block-interactive-region-map.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        filemtime( get_template_directory() . '/assets/js/block-interactive-region-map.js' ),
        true
    );

    register_block_type( 'enterprise/interactive-region-map', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-interactive-region-map',
        'render_callback' => 'enterprise_render_interactive_region_map_block',
        /* #54 — Colores configurables (nivel 2). Defaults = asignación de la paleta
           «Editorial» (§3.1 del requisito). Los de color/grosor/opacidad solo se
           aplican cuando colorSource='theme'; en 'asset' manda el activo horneado. */
        'attributes'      => array(
            'colorSource'     => array( 'type' => 'string', 'default' => 'asset'     ),
            'palette'         => array( 'type' => 'string', 'default' => 'editorial' ),
            'landFill'        => array( 'type' => 'string', 'default' => '#1a1a1a'   ),
            'baseStroke'      => array( 'type' => 'string', 'default' => '#0e0e0e'   ),
            'countryStroke'   => array( 'type' => 'string', 'default' => '#f2c118'   ),
            'regionFill'      => array( 'type' => 'string', 'default' => '#2a2a2a'   ),
            'regionStroke'    => array( 'type' => 'string', 'default' => '#3a3a3a'   ),
            'provinceFill'    => array( 'type' => 'string', 'default' => '#5a5a5a'   ),
            'provinceStroke'  => array( 'type' => 'string', 'default' => '#2a2a2a'   ),
            'hoverAccent'     => array( 'type' => 'string', 'default' => '#c9a010'   ),
            'baseStrokeWidth' => array( 'type' => 'number', 'default' => 0.5 ),
            't0StrokeWidth'   => array( 'type' => 'number', 'default' => 0.8 ),
            't1StrokeWidth'   => array( 'type' => 'number', 'default' => 0.5 ),
            't2StrokeWidth'   => array( 'type' => 'number', 'default' => 0.3 ),
            'baseOpacity'     => array( 'type' => 'number', 'default' => 1 ),
            't0Opacity'       => array( 'type' => 'number', 'default' => 1 ),
            't1Opacity'       => array( 'type' => 'number', 'default' => 1 ),
            't2Opacity'       => array( 'type' => 'number', 'default' => 1 ),
            'backCanvas'      => array( 'type' => 'string', 'default' => 'light' ),
        ),
        'supports'        => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );
}
add_action( 'init', 'enterprise_register_map_blocks' );

/* ─────────────────────────────────────────
   MAPAS EN FRONTEND (OpenLayers, solo si hay bloques de mapa)
───────────────────────────────────────── */
function enterprise_map_frontend_assets() {
    if ( ! is_singular() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;

    $has_location   = has_block( 'enterprise/location-map',         $post );
    $has_route      = has_block( 'enterprise/route-map',             $post );
    $has_animated   = has_block( 'enterprise/animated-route-map',    $post );
    $has_comparison = has_block( 'enterprise/route-comparison',      $post );
    $has_rbl        = has_block( 'enterprise/routes-by-location',    $post );
    $has_rmm        = has_block( 'enterprise/route-metadata-map',     $post );
    if ( ! $has_location && ! $has_route && ! $has_animated && ! $has_comparison && ! $has_rbl && ! $has_rmm ) return;

    /* ── OpenLayers — para ambos bloques de mapa ── */
    wp_enqueue_style(
        'openlayers',
        'https://cdn.jsdelivr.net/npm/ol@9.2.4/ol.css',
        array(),
        '9.2.4'
    );
    wp_enqueue_script(
        'openlayers',
        'https://cdn.jsdelivr.net/npm/ol@9.2.4/dist/ol.js',
        array(),
        '9.2.4',
        true
    );

    /* ── Lógica de mapas del tema ── */
    wp_enqueue_script(
        'enterprise-map-frontend',
        get_template_directory_uri() . '/assets/js/map-frontend.js',
        array( 'openlayers' ),
        filemtime( get_template_directory() . '/assets/js/map-frontend.js' ),
        true
    );

    /* ── CSS de mapas del tema ── */
    wp_enqueue_style(
        'enterprise-maps',
        get_template_directory_uri() . '/assets/css/maps.css',
        array( 'enterprise-style' ),
        ENTERPRISE_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_map_frontend_assets' );

/* ─────────────────────────────────────────
   CSS + JS PROPIOS DEL BLOQUE route-metadata-map (#56, Fase 2 de #45)
   El mapa reutiliza OpenLayers + map-frontend.js (encolados arriba). Esto añade el
   estilo del espejo de estadísticas y el JS de los tooltips «info». Condicional por
   has_block + cache-busting por filemtime.
───────────────────────────────────────── */
function enterprise_route_metadata_map_assets() {
    if ( ! is_singular() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    if ( ! has_block( 'enterprise/route-metadata-map', $post ) ) return;

    $css_path = get_template_directory() . '/assets/css/route-metadata-map.css';
    wp_enqueue_style(
        'enterprise-route-metadata-map',
        get_template_directory_uri() . '/assets/css/route-metadata-map.css',
        array( 'enterprise-style' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : ENTERPRISE_VERSION
    );

    $js_path = get_template_directory() . '/assets/js/route-metadata-map-front.js';
    wp_enqueue_script(
        'enterprise-route-metadata-map-front',
        get_template_directory_uri() . '/assets/js/route-metadata-map-front.js',
        array(),
        file_exists( $js_path ) ? filemtime( $js_path ) : ENTERPRISE_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_route_metadata_map_assets' );

/* Textos de los tooltips «info» del espejo de estadísticas (§3.10). Mapa asociativo
   localizado: clave = ruta del campo en el JSON de metadatos → texto. Una etiqueta
   muestra botón «info» SOLO si tiene entrada no vacía aquí. Contenido definicional
   (no de BD ni por-post). El contenido definitivo y la elección de qué etiquetas lo
   llevan se difieren a otra sesión; aquí solo el mecanismo + dos entradas de ejemplo. */
function enterprise_route_metadata_stat_info() {
    return array(
        'summary.overall_difficulty_score' => __( 'Puntuación global de dificultad (0–100) que combina la exigencia física y la técnica.', 'enterprise-moto' ),
        'technical_curves.sinuosity_index' => __( 'Índice de sinuosidad: relación entre la longitud real del trazado y la distancia en línea recta; a mayor valor, más revirado.', 'enterprise-moto' ),
    );
}

/* ─────────────────────────────────────────
   CSS + JS DEL BLOQUE interactive-region-map (#43 / #51)
   Nativo SVG: NO carga OpenLayers ni comparte enterprise_map_frontend_assets()
   (§13.19). Dimensionado responsive + motor de navegación (SVG único, DOM-driven,
   sin fetch). Encolado condicional por has_block, cache-busting por filemtime.
───────────────────────────────────────── */
function enterprise_region_map_assets() {
    if ( ! is_singular() && ! is_page() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    if ( ! has_block( 'enterprise/interactive-region-map', $post ) ) return;

    $css_path = get_template_directory() . '/assets/css/interactive-region-map.css';
    wp_enqueue_style(
        'enterprise-interactive-region-map',
        get_template_directory_uri() . '/assets/css/interactive-region-map.css',
        array( 'enterprise-style' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : ENTERPRISE_VERSION
    );

    $js_path = get_template_directory() . '/assets/js/region-map-frontend.js';
    wp_enqueue_script(
        'enterprise-region-map-frontend',
        get_template_directory_uri() . '/assets/js/region-map-frontend.js',
        array(),
        file_exists( $js_path ) ? filemtime( $js_path ) : ENTERPRISE_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_region_map_assets' );

/* ─────────────────────────────────────────
   RESOLUTOR DEL ACTIVO DEL MAPA (#58)
   Fuente única para localizar un fichero del par mapa+árbol: devuelve la copia del
   ALMACÉN del sitio (uploads/enterprise-maps/) si existe; si no, la copia SEMILLA
   del tema (assets/maps/). Lo usa render.php para el SVG; #55 lo reutilizará para
   el árbol. $filename es un literal interno fijo en las llamadas
   ('enterprise-eu.svg', 'map-regions-global.json'): ninguna entrada de usuario
   llega hasta aquí. Si wp_upload_dir() reporta error, cae a la copia del tema.
───────────────────────────────────────── */
function enterprise_map_asset_path( $filename ) {
    $upload = wp_upload_dir();
    if ( empty( $upload['error'] ) && ! empty( $upload['basedir'] ) ) {
        $stored = trailingslashit( $upload['basedir'] ) . 'enterprise-maps/' . $filename;
        if ( file_exists( $stored ) ) {
            return $stored;
        }
    }
    return get_template_directory() . '/assets/maps/' . $filename;
}

/* ─────────────────────────────────────────
   PÁGINA DE ADMIN «Enterprise Moto» + SUBIDA VALIDADA DEL PAR (#58)
   Menú propio de nivel superior. En #58 la subida del par mapa+árbol al almacén del
   sitio (uploads/enterprise-maps/) se valida de forma TRANSACCIONAL: los dos ficheros
   presentes + árbol con formato esperado + sello data-tree-sha256 del SVG que casa
   (hash_equals) con el sha256 de los bytes exactos del árbol subido. Si algo falla,
   no se guarda nada. El árbol es un artefacto generado inmutable: se sube verbatim.
   #55 amplía la capacidad de la PÁGINA a editores (edit_others_posts) para la sección
   «Sincronizar regiones»; la SUBIDA del par sigue siendo SOLO de administradores
   (manage_options), acotada dentro de la propia página y en su handler.
───────────────────────────────────────── */

/* Menú de nivel superior (admin_menu). */
function enterprise_moto_admin_menu() {
    add_menu_page(
        'Enterprise Moto',                      // título de la página (<title>)
        'Enterprise Moto',                      // etiqueta del menú
        'edit_others_posts',                    // capacidad: administradores + editores (#55; era manage_options en #58)
        'enterprise-moto',                      // slug
        'enterprise_moto_render_admin_page',    // callback de render
        'dashicons-location-alt'                // icono
    );
}
add_action( 'admin_menu', 'enterprise_moto_admin_menu' );

/* Render de la página: aviso de estado (del arg que fija la redirección) + formulario. */
function enterprise_moto_render_admin_page() {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( esc_html__( 'No tienes permiso para acceder a esta página.', 'enterprise-moto' ) );
    }

    /* Aviso de estado de la subida del par (#58, solo administradores). */
    $status = isset( $_GET['enterprise_map_status'] ) ? sanitize_key( wp_unslash( $_GET['enterprise_map_status'] ) ) : '';
    if ( '' !== $status ) {
        $notices = array(
            'ok'            => array( 'success', 'El mapa y su árbol se han actualizado correctamente.' ),
            'missing'       => array( 'error',   'Debes subir los dos ficheros: el mapa (SVG) y el árbol de regiones (JSON).' ),
            'bad_tree'      => array( 'error',   'El fichero del árbol no tiene el formato esperado.' ),
            'no_seal'       => array( 'error',   'El mapa (SVG) no incluye el sello del árbol (data-tree-sha256): no se ha guardado nada.' ),
            'hash_mismatch' => array( 'error',   'El árbol subido no coincide con el mapa (hash distinto): no se ha guardado nada.' ),
            'store_error'   => array( 'error',   'No se ha podido escribir en el almacén del sitio: no se ha guardado nada.' ),
        );
        if ( isset( $notices[ $status ] ) ) {
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr( $notices[ $status ][0] ),
                esc_html( $notices[ $status ][1] )
            );
        }
    }

    /* Aviso de estado de «Sincronizar regiones» (#55, admins + editores). El reporte
       de «Analizar» no cabe en un arg de URL: viaja por un transient por usuario que
       pinta enterprise_regiones_render_report_panel() más abajo. */
    $sync_status = isset( $_GET['enterprise_regiones'] ) ? sanitize_key( wp_unslash( $_GET['enterprise_regiones'] ) ) : '';
    if ( '' !== $sync_status ) {
        $sync_notices = array(
            'applied'      => array( 'success', 'Las regiones se han sincronizado correctamente.' ),
            'read_error'   => array( 'error',   'No se ha podido leer el árbol de regiones (¿has subido el par mapa+árbol?).' ),
            'bad_format'   => array( 'error',   'El árbol de regiones no tiene el formato esperado.' ),
            'need_analyze' => array( 'error',   'Analiza los cambios antes de aplicar.' ),
        );
        if ( isset( $sync_notices[ $sync_status ] ) ) {
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr( $sync_notices[ $sync_status ][0] ),
                esc_html( $sync_notices[ $sync_status ][1] )
            );
        }
    }
    ?>
    <div class="wrap">
        <h1>Enterprise Moto</h1>

        <?php if ( current_user_can( 'manage_options' ) ) : ?>
        <h2><?php echo esc_html( 'Mapa de regiones' ); ?></h2>
        <p><?php echo esc_html( 'Sube el par indivisible del mapa: el SVG maestro y su árbol de regiones (JSON). Se validan juntos por hash; si no cuadran, no se guarda nada.' ); ?></p>

        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="enterprise_upload_map_pair" />
            <?php wp_nonce_field( 'enterprise_upload_map_pair', 'enterprise_map_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="enterprise_map_svg"><?php echo esc_html( 'Mapa (SVG)' ); ?></label></th>
                    <td><input type="file" id="enterprise_map_svg" name="map_svg" accept=".svg,image/svg+xml" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="enterprise_map_tree"><?php echo esc_html( 'Árbol de regiones (JSON)' ); ?></label></th>
                    <td><input type="file" id="enterprise_map_tree" name="map_tree" accept=".json,application/json" required /></td>
                </tr>
            </table>
            <?php submit_button( 'Subir mapa y árbol' ); ?>
        </form>
        <?php endif; ?>

        <?php if ( current_user_can( 'edit_others_posts' ) ) : ?>
        <h2><?php echo esc_html( 'Sincronizar regiones' ); ?></h2>
        <p><?php echo esc_html( 'Siembra y actualiza los términos de la taxonomía "Regiones" a partir del árbol de regiones del mapa. La operación es no destructiva: nunca borra términos ni reasigna entradas.' ); ?></p>

        <?php enterprise_regiones_render_report_panel(); ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="enterprise_sync_regiones" />
            <?php wp_nonce_field( 'enterprise_sync_regiones', 'enterprise_regiones_nonce' ); ?>
            <p class="submit">
                <button type="submit" name="mode" value="analyze" class="button button-primary"><?php echo esc_html( 'Analizar cambios' ); ?></button>
            </p>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

/* ─────────────────────────────────────────
   PANEL DE REPORTE DE «SINCRONIZAR REGIONES» (#55)
   Lee el reporte precalculado del transient por usuario (lo fija el handler en modo
   «analyze», Commit 3), lo muestra por grupos y lo borra tras mostrarlo (un solo uso).
   En el Commit 2 todavía no hay handler que fije el transient: si no lo hay, no pinta
   nada. Forma esperada del reporte (contrato con enterprise_regiones_compute_sync(),
   Commit 3):
     array(
       'nuevas'          => [ array('code','name','admin'), … ],
       'actualizar'      => [ array('code','name','admin','old_name','old_admin','term_id'), … ],
       'descolgadas_con' => [ array('code','name','count','term_id'), … ],
       'descolgadas_sin' => [ array('code','name','count','term_id'), … ],
     )
───────────────────────────────────────── */
function enterprise_regiones_render_report_panel() {
    $key    = 'enterprise_regiones_report_' . get_current_user_id();
    $report = get_transient( $key );
    if ( false === $report || ! is_array( $report ) ) {
        return;
    }
    /* NO se borra el transient al pintar (§7.2): es la puerta de «previa antes de
       aplicar». Lo consume un «Aplicar» con éxito (handler), o expira por su TTL. */

    $nuevas     = ! empty( $report['nuevas'] )          && is_array( $report['nuevas'] )          ? $report['nuevas']          : array();
    $actualizar = ! empty( $report['actualizar'] )      && is_array( $report['actualizar'] )      ? $report['actualizar']      : array();
    $desc_con   = ! empty( $report['descolgadas_con'] ) && is_array( $report['descolgadas_con'] ) ? $report['descolgadas_con'] : array();
    $desc_sin   = ! empty( $report['descolgadas_sin'] ) && is_array( $report['descolgadas_sin'] ) ? $report['descolgadas_sin'] : array();

    if ( empty( $nuevas ) && empty( $actualizar ) && empty( $desc_con ) && empty( $desc_sin ) ) {
        echo '<div class="notice notice-info inline"><p>' . esc_html( 'Todo está al día: no hay cambios que aplicar.' ) . '</p></div>';
        return;
    }

    enterprise_regiones_render_report_bucket( 'Nuevas regiones', $nuevas, 'nueva' );
    enterprise_regiones_render_report_bucket( 'Nombres a actualizar', $actualizar, 'actualizar' );
    enterprise_regiones_render_report_bucket( 'Descolgadas con entradas (no se tocan)', $desc_con, 'descolgada' );
    enterprise_regiones_render_report_bucket( 'Descolgadas sin entradas', $desc_sin, 'descolgada' );

    /* La confirmación vive AQUÍ (§7, Decisión C): «Aplicar» solo es alcanzable desde un
       reporte recién analizado, y solo si hay algo aplicable (nuevas o a actualizar). Un
       reporte de solo descolgadas —o «Todo está al día»— no muestra botón de aplicar. */
    if ( ! empty( $nuevas ) || ! empty( $actualizar ) ) {
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="enterprise_sync_regiones" />
            <input type="hidden" name="mode" value="apply" />
            <?php wp_nonce_field( 'enterprise_sync_regiones', 'enterprise_regiones_nonce' ); ?>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html( 'Aplicar estos cambios' ); ?></button>
            </p>
        </form>
        <?php
    }
}

/* Render de un grupo del reporte (título + nº + lista). No emite nada si está vacío. */
function enterprise_regiones_render_report_bucket( $title, $items, $kind ) {
    if ( empty( $items ) ) {
        return;
    }
    echo '<h4>' . esc_html( $title ) . ' (' . (int) count( $items ) . ')</h4>';
    echo '<ul style="list-style:disc;margin-left:2em;">';
    foreach ( $items as $it ) {
        $code = isset( $it['code'] ) ? (string) $it['code'] : '';
        $name = isset( $it['name'] ) ? (string) $it['name'] : '';
        if ( 'actualizar' === $kind ) {
            $old  = isset( $it['old_name'] ) ? (string) $it['old_name'] : '';
            $line = $code . ' — «' . $old . '» → «' . $name . '»';
        } elseif ( 'descolgada' === $kind ) {
            $count = isset( $it['count'] ) ? (int) $it['count'] : 0;
            $line  = $code . ' — ' . $name . ' (' . $count . ' entradas)';
        } else {
            $line = $code . ' — ' . $name;
        }
        echo '<li>' . esc_html( $line ) . '</li>';
    }
    echo '</ul>';
}

/* Handler de la subida (admin_post): valida el par y escribe ambos, o rechaza sin
   escribir nada. Redirige a la página con un arg de estado que dispara el aviso. */
function enterprise_upload_map_pair_handler() {
    check_admin_referer( 'enterprise_upload_map_pair', 'enterprise_map_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permiso para realizar esta acción.', 'enterprise-moto' ) );
    }

    $redirect = admin_url( 'admin.php?page=enterprise-moto' );

    /* 1) Ambos ficheros presentes y subidos sin error (validar todo antes de escribir). */
    if (
        empty( $_FILES['map_svg'] ) || empty( $_FILES['map_tree'] ) ||
        ! isset( $_FILES['map_svg']['error'], $_FILES['map_tree']['error'], $_FILES['map_svg']['tmp_name'], $_FILES['map_tree']['tmp_name'] ) ||
        UPLOAD_ERR_OK !== $_FILES['map_svg']['error'] ||
        UPLOAD_ERR_OK !== $_FILES['map_tree']['error'] ||
        ! is_uploaded_file( $_FILES['map_svg']['tmp_name'] ) ||
        ! is_uploaded_file( $_FILES['map_tree']['tmp_name'] )
    ) {
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'missing', $redirect ) );
        exit;
    }

    $svg_name  = sanitize_file_name( $_FILES['map_svg']['name'] );
    $tree_name = sanitize_file_name( $_FILES['map_tree']['name'] );

    $svg_bytes  = file_get_contents( $_FILES['map_svg']['tmp_name'] );
    $tree_bytes = file_get_contents( $_FILES['map_tree']['tmp_name'] );

    /* 2) Árbol: extensión .json + json_decode válido + objeto con _meta y tree no vacío. */
    $tree_data = json_decode( (string) $tree_bytes, true );
    if (
        '.json' !== strtolower( (string) substr( $tree_name, -5 ) ) ||
        ! is_array( $tree_data ) ||
        ! isset( $tree_data['_meta'] ) ||
        empty( $tree_data['tree'] ) ||
        ! is_array( $tree_data['tree'] )
    ) {
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'bad_tree', $redirect ) );
        exit;
    }

    /* 3) SVG: extensión .svg + contenido con <svg + sello data-tree-sha256. */
    if (
        '.svg' !== strtolower( (string) substr( $svg_name, -4 ) ) ||
        false === stripos( (string) $svg_bytes, '<svg' ) ||
        ! preg_match( '/data-tree-sha256\s*=\s*"([a-f0-9]{64})"/i', (string) $svg_bytes, $m )
    ) {
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'no_seal', $redirect ) );
        exit;
    }
    $svg_hash = strtolower( $m[1] );

    /* 4) Hash bloqueante: sello del SVG == sha256 de los bytes exactos del árbol. */
    if ( ! hash_equals( $svg_hash, hash( 'sha256', (string) $tree_bytes ) ) ) {
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'hash_mismatch', $redirect ) );
        exit;
    }

    /* 5) Todo válido: escribir el par al almacén bajo nombres canónicos, vía nombres
          temporales + rename() (los dos o ninguno en disco). */
    $upload = wp_upload_dir();
    if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'store_error', $redirect ) );
        exit;
    }
    $dir = trailingslashit( $upload['basedir'] ) . 'enterprise-maps/';
    if ( ! wp_mkdir_p( $dir ) ) {
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'store_error', $redirect ) );
        exit;
    }

    $svg_tmp  = $dir . 'enterprise-eu.svg.' . wp_generate_password( 8, false ) . '.tmp';
    $tree_tmp = $dir . 'map-regions-global.json.' . wp_generate_password( 8, false ) . '.tmp';

    if ( false === file_put_contents( $svg_tmp, $svg_bytes ) || false === file_put_contents( $tree_tmp, $tree_bytes ) ) {
        @unlink( $svg_tmp );
        @unlink( $tree_tmp );
        wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'store_error', $redirect ) );
        exit;
    }

    rename( $svg_tmp,  $dir . 'enterprise-eu.svg' );
    rename( $tree_tmp, $dir . 'map-regions-global.json' );

    wp_safe_redirect( add_query_arg( 'enterprise_map_status', 'ok', $redirect ) );
    exit;
}
add_action( 'admin_post_enterprise_upload_map_pair', 'enterprise_upload_map_pair_handler' );

/* ─────────────────────────────────────────
   SUBIDA + VALIDACIÓN + ALMACENAMIENTO DE LA RUTA REGISTRADA (#56, Fase 2 de #45)
   Endpoint REST (primera ruta REST del tema) que recibe del modal del bloque
   `enterprise/route-metadata-map` los tres ficheros (GPX planificada opcional, GPX
   registrada requerida, JSON de metadatos requerido) + año/mes/día. Valida en servidor
   (fecha real, consistencia de hash y duplicidad) y almacena de forma transaccional
   (temp + rename, patrón #58) en:
     uploads/routes/recorded/<year>/<month>/
   Nombres canónicos con base <md> = <month><day> (2 dígitos):
     · planificada → <md>.gpx           · registrada → <md>_track.gpx
     · metadatos   → <md>_metadata.json
   `trip` es INFORMATIVO: no participa en el path ni en el nombre (no se usa en disco).
   La puerta DURA de completitud (bloqueo de publicación) es del Commit 6, no de aquí.
───────────────────────────────────────── */

/* Rellena a 2 dígitos (solo cifras). '' si no hay cifras. */
function enterprise_rmm_pad2( $v ) {
    $v = preg_replace( '/\D/', '', (string) $v );
    if ( '' === $v ) return '';
    return substr( str_pad( $v, 2, '0', STR_PAD_LEFT ), -2 );
}

/* True si <year><month><day> es una fecha real (año de 4 cifras). */
function enterprise_rmm_valid_ymd( $y, $m, $d ) {
    if ( ! preg_match( '/^\d{4}$/', (string) $y ) ) return false;
    return checkdate( (int) $m, (int) $d, (int) $y );
}

/* Registro de la ruta REST: POST = subir; DELETE = borrar los ficheros del bloque. */
function enterprise_rmm_register_rest() {
    $can = function () { return current_user_can( 'edit_posts' ); };
    register_rest_route( 'enterprise/v1', '/route-metadata', array(
        array(
            'methods'             => 'POST',
            'callback'            => 'enterprise_rmm_upload_handler',
            'permission_callback' => $can,
        ),
        array(
            'methods'             => 'DELETE',
            'callback'            => 'enterprise_rmm_delete_handler',
            'permission_callback' => $can,
        ),
    ) );
}
add_action( 'rest_api_init', 'enterprise_rmm_register_rest' );

/* Handler: valida TODO antes de escribir; escribe temp + rename (todos o ninguno). */
function enterprise_rmm_upload_handler( $request ) {

    /* 1) Fecha: año de 4 cifras + composición real. */
    $year  = preg_replace( '/\D/', '', (string) $request->get_param( 'year' ) );
    $month = enterprise_rmm_pad2( $request->get_param( 'month' ) );
    $day   = enterprise_rmm_pad2( $request->get_param( 'day' ) );
    if ( ! preg_match( '/^\d{4}$/', $year ) || ! enterprise_rmm_valid_ymd( $year, $month, $day ) ) {
        return new WP_Error( 'ent_rmm_bad_date', 'La fecha (año/mes/día) no es válida.', array( 'status' => 400 ) );
    }

    /* 2) Ficheros presentes y subidos sin error. */
    $files = $request->get_file_params();

    $recorded_ok = ! empty( $files['recorded'] )
        && isset( $files['recorded']['tmp_name'], $files['recorded']['error'] )
        && UPLOAD_ERR_OK === $files['recorded']['error']
        && is_uploaded_file( $files['recorded']['tmp_name'] );
    if ( ! $recorded_ok ) {
        return new WP_Error( 'ent_rmm_no_recorded', 'Falta el GPX de la ruta registrada (track).', array( 'status' => 400 ) );
    }

    $meta_ok = ! empty( $files['metadata'] )
        && isset( $files['metadata']['tmp_name'], $files['metadata']['error'] )
        && UPLOAD_ERR_OK === $files['metadata']['error']
        && is_uploaded_file( $files['metadata']['tmp_name'] );
    if ( ! $meta_ok ) {
        return new WP_Error( 'ent_rmm_no_meta', 'Faltan los metadatos de la ruta (JSON).', array( 'status' => 400 ) );
    }

    $has_planned = ! empty( $files['planned'] )
        && isset( $files['planned']['tmp_name'], $files['planned']['error'] )
        && UPLOAD_ERR_OK === $files['planned']['error']
        && is_uploaded_file( $files['planned']['tmp_name'] );

    $recorded_bytes = file_get_contents( $files['recorded']['tmp_name'] );
    $meta_bytes     = file_get_contents( $files['metadata']['tmp_name'] );
    $planned_bytes  = $has_planned ? file_get_contents( $files['planned']['tmp_name'] ) : null;

    /* 3) Metadatos: JSON válido + gpx-data-sha256 con formato de 64 hex. */
    $meta = json_decode( (string) $meta_bytes, true );
    if ( ! is_array( $meta )
        || ! isset( $meta['metadata']['gpx-data-sha256'] )
        || ! preg_match( '/^[a-fA-F0-9]{64}$/', (string) $meta['metadata']['gpx-data-sha256'] ) ) {
        return new WP_Error( 'ent_rmm_bad_meta',
            'El fichero de metadatos no es válido o le falta «gpx-data-sha256».', array( 'status' => 400 ) );
    }
    $declared = strtolower( (string) $meta['metadata']['gpx-data-sha256'] );

    /* 4) Consistencia (§3.6.1): sha256 de los bytes de la GPX registrada == declarado. */
    $actual = hash( 'sha256', (string) $recorded_bytes );
    if ( ! hash_equals( $declared, $actual ) ) {
        return new WP_Error( 'ent_rmm_hash_mismatch',
            'El GPX de la ruta registrada no coincide con el hash «gpx-data-sha256» de los metadatos.',
            array( 'status' => 400 ) );
    }

    /* 5) Carpeta de almacenamiento: uploads/routes/recorded/<year>/<month>/ */
    $upload = wp_upload_dir();
    if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
        return new WP_Error( 'ent_rmm_store', 'No se pudo acceder al almacén de subidas.', array( 'status' => 500 ) );
    }
    $dir = trailingslashit( $upload['basedir'] ) . 'routes/recorded/' . $year . '/' . $month . '/';
    if ( ! wp_mkdir_p( $dir ) ) {
        return new WP_Error( 'ent_rmm_store', 'No se pudo crear la carpeta de almacenamiento.', array( 'status' => 500 ) );
    }

    /* 6) Duplicidad (§3.6.2): base <md> = <month><day>; el sufijo (N) va ANTES de la
          extensión de cada nombre (0801(N).gpx / 0801_track(N).gpx / 0801_metadata(N).json). */
    $md     = $month . $day;
    $suffix = '';
    $track_path = $dir . $md . '_track.gpx';
    if ( file_exists( $track_path ) ) {
        $existing = hash( 'sha256', (string) file_get_contents( $track_path ) );
        if ( hash_equals( $existing, $actual ) ) {
            // Igual → ese track ya se subió desde OTRO bloque; no se reescribe. Para
            // reemplazarlo, el autor debe ir a ese bloque y usar «Borrar ficheros».
            return new WP_REST_Response( array(
                'ok'        => false,
                'duplicate' => true,
                'message'   => 'Este track ya se ha subido desde otro bloque (mismo contenido). Para reemplazarlo, ve a ese bloque y usa «Borrar ficheros» antes de volver a subirlo.',
            ), 200 );
        }
        // Distinto → mismo sufijo (N) que libere los TRES nombres a la vez.
        $n = 1;
        do {
            $s = '(' . $n . ')';
            $free = ! file_exists( $dir . $md . $s . '.gpx' )
                 && ! file_exists( $dir . $md . '_track' . $s . '.gpx' )
                 && ! file_exists( $dir . $md . '_metadata' . $s . '.json' );
            $n++;
        } while ( ! $free && $n < 1000 );
        if ( ! $free ) {
            return new WP_Error( 'ent_rmm_store', 'No se pudo asignar un nombre libre para esa fecha.', array( 'status' => 500 ) );
        }
        $suffix = $s;
    }

    /* 7) Escritura transaccional: todos los temporales, o ninguno; luego rename.
          Sufijo (N) antes de la extensión de cada nombre. */
    $targets = array(
        'recorded' => array( $dir . $md . '_track' . $suffix . '.gpx',     $recorded_bytes ),
        'metadata' => array( $dir . $md . '_metadata' . $suffix . '.json', $meta_bytes ),
    );
    if ( $has_planned ) {
        $targets['planned'] = array( $dir . $md . $suffix . '.gpx', $planned_bytes );
    }
    $temps = array();
    $write_ok = true;
    foreach ( $targets as $key => $t ) {
        $tmp = $t[0] . '.' . wp_generate_password( 8, false ) . '.tmp';
        if ( false === file_put_contents( $tmp, $t[1] ) ) { $write_ok = false; break; }
        $temps[ $key ] = $tmp;
    }
    if ( ! $write_ok ) {
        foreach ( $temps as $tmp ) { @unlink( $tmp ); }
        return new WP_Error( 'ent_rmm_store', 'No se pudieron escribir los ficheros.', array( 'status' => 500 ) );
    }
    foreach ( $targets as $key => $t ) {
        rename( $temps[ $key ], $t[0] );
    }

    /* 8) Respuesta: sufijo aplicado + nombres canónicos + URL pública de la carpeta. */
    $resp = array(
        'ok'     => true,
        'suffix' => $suffix,
        'url'    => trailingslashit( $upload['baseurl'] ) . 'routes/recorded/' . $year . '/' . $month . '/',
        'files'  => array(
            'recorded' => $md . '_track' . $suffix . '.gpx',
            'metadata' => $md . '_metadata' . $suffix . '.json',
        ),
    );
    if ( $has_planned ) {
        $resp['files']['planned'] = $md . $suffix . '.gpx';
    }
    return new WP_REST_Response( $resp, 200 );
}

/* Handler de borrado (DELETE): elimina los tres ficheros del bloque —<md>(N).gpx,
   <md>_track(N).gpx, <md>_metadata(N).json— en uploads/routes/recorded/<year>/<month>/,
   con el sufijo (N) ANTES de la extensión. `md` = <month><day>; `suffix` = '' o '(N)',
   ambos validados con formato estricto para evitar travesías. Tolerante si algún fichero
   ya no existe. */
function enterprise_rmm_delete_handler( $request ) {

    $year   = preg_replace( '/\D/', '', (string) $request->get_param( 'year' ) );
    $month  = enterprise_rmm_pad2( $request->get_param( 'month' ) );
    $day    = enterprise_rmm_pad2( $request->get_param( 'day' ) );
    $suffix = (string) $request->get_param( 'suffix' );

    if ( ! preg_match( '/^\d{4}$/', $year ) || ! preg_match( '/^\d{2}$/', $month ) || ! preg_match( '/^\d{2}$/', $day ) ) {
        return new WP_Error( 'ent_rmm_bad_date', 'Fecha inválida.', array( 'status' => 400 ) );
    }
    // suffix = '' o «(N)». Sin barras ni «..».
    if ( '' !== $suffix && ! preg_match( '/^\(\d+\)$/', $suffix ) ) {
        return new WP_Error( 'ent_rmm_bad_suffix', 'Sufijo de fichero inválido.', array( 'status' => 400 ) );
    }

    $upload = wp_upload_dir();
    if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
        return new WP_Error( 'ent_rmm_store', 'No se pudo acceder al almacén de subidas.', array( 'status' => 500 ) );
    }
    $dir = trailingslashit( $upload['basedir'] ) . 'routes/recorded/' . $year . '/' . $month . '/';

    $md      = $month . $day;
    $names   = array(
        $md . $suffix . '.gpx',
        $md . '_track' . $suffix . '.gpx',
        $md . '_metadata' . $suffix . '.json',
    );
    $deleted = array();
    foreach ( $names as $name ) {
        $path = $dir . $name;
        if ( file_exists( $path ) ) {
            if ( @unlink( $path ) ) {
                $deleted[] = $name;
            } else {
                return new WP_Error( 'ent_rmm_delete', 'No se pudo borrar «' . $name . '».', array( 'status' => 500 ) );
            }
        }
    }

    return new WP_REST_Response( array( 'ok' => true, 'deleted' => $deleted ), 200 );
}

/* ─────────────────────────────────────────
   PUERTA DE PUBLICACIÓN + INGESTA GEOGRÁFICA (#56, Fase 2 de #45 · Commit 6)
   - Puerta DURA de completitud: no se puede publicar si hay algún bloque
     route-metadata-map sin validar (cliente = lockPostSaving; servidor = este veto
     autoritativo por rest_pre_insert_post → WP_Error, que en WP 7.0.2 muestra el mensaje).
     Decisión A del operador: la puerta salta con cualquier bloque incompleto presente,
     sin condicionar al tipo de entrada.
   - Ingesta NO bloqueante: al publicar una entrada de tipo «etapa», por cada bloque con
     el interruptor de inventario activo y metadatos VALID (re-leídos en servidor), se
     recogen sus administrative_regions[].id, se mapean a términos por region_code y se
     fija el conjunto UNIÓN en la taxonomía `regiones` por REEMPLAZO (el conteo se recalcula).
───────────────────────────────────────── */

/* Recolecta recursivamente los bloques enterprise/route-metadata-map de un árbol. */
function enterprise_rmm_collect_blocks( $blocks ) {
    $out = array();
    if ( ! is_array( $blocks ) ) return $out;
    foreach ( $blocks as $b ) {
        if ( isset( $b['blockName'] ) && 'enterprise/route-metadata-map' === $b['blockName'] ) {
            $out[] = $b;
        }
        if ( ! empty( $b['innerBlocks'] ) ) {
            $out = array_merge( $out, enterprise_rmm_collect_blocks( $b['innerBlocks'] ) );
        }
    }
    return $out;
}

/* Lee y decodifica el _metadata.json almacenado de un bloque (por sus atributos). */
function enterprise_rmm_block_metadata( $attrs ) {
    $year   = preg_replace( '/\D/', '', (string) ( isset( $attrs['year'] )  ? $attrs['year']  : '' ) );
    $month  = substr( str_pad( preg_replace( '/\D/', '', (string) ( isset( $attrs['month'] ) ? $attrs['month'] : '' ) ), 2, '0', STR_PAD_LEFT ), -2 );
    $day    = substr( str_pad( preg_replace( '/\D/', '', (string) ( isset( $attrs['day'] )   ? $attrs['day']   : '' ) ), 2, '0', STR_PAD_LEFT ), -2 );
    $suffix = (string) ( isset( $attrs['assetSuffix'] ) ? $attrs['assetSuffix'] : '' );
    if ( '' !== $suffix && ! preg_match( '/^\(\d+\)$/', $suffix ) ) $suffix = '';
    if ( ! preg_match( '/^\d{4}$/', $year ) || ! preg_match( '/^\d{2}$/', $month ) || ! preg_match( '/^\d{2}$/', $day ) ) return null;

    $upload = wp_upload_dir();
    if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) return null;
    $path = trailingslashit( $upload['basedir'] ) . 'routes/recorded/' . $year . '/' . $month . '/' . $month . $day . '_metadata' . $suffix . '.json';
    if ( ! file_exists( $path ) ) return null;
    $decoded = json_decode( (string) file_get_contents( $path ), true );
    return is_array( $decoded ) ? $decoded : null;
}

/* Puerta dura: veto si al publicar hay algún bloque route-metadata-map sin validar. */
function enterprise_rmm_publish_gate( $prepared_post, $request ) {

    /* Solo bloquea si el estado objetivo es «publish». */
    $status = '';
    if ( isset( $request['status'] ) )                 $status = (string) $request['status'];
    elseif ( isset( $prepared_post->post_status ) )    $status = (string) $prepared_post->post_status;
    elseif ( ! empty( $prepared_post->ID ) )           $status = (string) get_post_status( $prepared_post->ID );
    if ( 'publish' !== $status ) return $prepared_post;

    /* Contenido que se va a guardar. */
    $content = '';
    if ( isset( $request['content'] ) ) {
        if ( is_array( $request['content'] ) && isset( $request['content']['raw'] ) ) $content = (string) $request['content']['raw'];
        elseif ( is_string( $request['content'] ) )                                   $content = (string) $request['content'];
    }
    if ( '' === $content && isset( $prepared_post->post_content ) ) $content = (string) $prepared_post->post_content;
    if ( '' === $content ) return $prepared_post;

    $blocks = enterprise_rmm_collect_blocks( parse_blocks( $content ) );
    foreach ( $blocks as $b ) {
        $attrs = ( isset( $b['attrs'] ) && is_array( $b['attrs'] ) ) ? $b['attrs'] : array();
        if ( empty( $attrs['validated'] ) ) {
            return new WP_Error(
                'ent_rmm_incomplete',
                'No se puede publicar: hay un bloque "Mapa de ruta con metadatos" sin completar. Abre el bloque, carga los ficheros y guárdalos antes de publicar.',
                array( 'status' => 400 )
            );
        }
    }
    return $prepared_post;
}
add_filter( 'rest_pre_insert_post', 'enterprise_rmm_publish_gate', 10, 2 );

/* Ingesta geográfica no bloqueante: al publicar una etapa, fija los términos `regiones`
   como UNIÓN de los bloques que contribuyen (interruptor activo + metadatos VALID). */
function enterprise_rmm_ingest_geo( $post, $request, $creating ) {
    if ( ! $post || 'publish' !== get_post_status( $post ) ) return;
    $tipo = get_post_meta( $post->ID, '_post_tipo', true ) ?: 'etapa';
    if ( 'etapa' !== $tipo ) return; // ingesta solo para Tipo B/C (etapa).

    $content = get_post_field( 'post_content', $post->ID );
    if ( ! $content ) return;
    $blocks = enterprise_rmm_collect_blocks( parse_blocks( $content ) );

    /* Unión de términos de los bloques que contribuyen. SIN bloques → unión vacía → el
       reemplazo de abajo retira los términos previos (borrar el bloque decrementa el conteo). */
    $union = array();
    if ( ! empty( $blocks ) ) :

    /* Índice region_code → term_id (mismo patrón que #55, l. ~2364). */
    $terms = get_terms( array( 'taxonomy' => 'regiones', 'hide_empty' => false ) );
    if ( is_wp_error( $terms ) ) $terms = array();
    $term_by_code = array();
    foreach ( $terms as $t ) {
        $code = (string) get_term_meta( $t->term_id, 'region_code', true );
        if ( '' === $code || isset( $term_by_code[ $code ] ) ) continue;
        $term_by_code[ $code ] = (int) $t->term_id;
    }

    foreach ( $blocks as $b ) {
        $attrs = ( isset( $b['attrs'] ) && is_array( $b['attrs'] ) ) ? $b['attrs'] : array();
        if ( empty( $attrs['validated'] ) ) continue;
        $use = array_key_exists( 'useGeoInventory', $attrs ) ? (bool) $attrs['useGeoInventory'] : true;
        if ( ! $use ) continue;

        $meta = enterprise_rmm_block_metadata( $attrs );
        if ( ! is_array( $meta ) ) continue;
        if ( ! isset( $meta['validation_status'] ) || 'VALID' !== $meta['validation_status'] ) continue;
        if ( empty( $meta['administrative_regions'] ) || ! is_array( $meta['administrative_regions'] ) ) continue;

        foreach ( $meta['administrative_regions'] as $reg ) {
            if ( ! isset( $reg['id'] ) ) continue;
            $code = (string) $reg['id'];
            if ( isset( $term_by_code[ $code ] ) ) $union[ $term_by_code[ $code ] ] = true;
        }
    }

    endif; /* ! empty( $blocks ) */

    /* Reemplazo (todos los niveles). Unión vacía → se retiran los términos previos. */
    $term_ids = array_map( 'intval', array_keys( $union ) );
    wp_set_object_terms( $post->ID, $term_ids, 'regiones', false );
}
add_action( 'rest_after_insert_post', 'enterprise_rmm_ingest_geo', 10, 3 );

/* ─────────────────────────────────────────
   TAXONOMÍA «Regiones» + TERM META (#55, Fase 1 de #45)
   Capa de datos geográfica consultable: taxonomía propia PLANA `regiones`
   (términos = unidades del mapa) sobre `post`, registrada MÍNIMA en la parte
   pública (Decisión D: sin páginas/URLs por región; el destino del clic es de
   #46). Dos term meta descriptivas cuya identidad es el código:
     · region_code  = código ISO = id del <path> del SVG = CLAVE DE JOIN.
     · region_admin = nivel administrativo (0 país / 1 región / 2 provincia / …).
   Aquí NO se siembran términos: la siembra/actualización es la herramienta
   «Sincronizar regiones» (previa + confirmar, no destructiva), no un seed
   silencioso en init. Este registro solo declara la taxonomía y sus metas.
───────────────────────────────────────── */
function enterprise_register_regiones_taxonomy() {
    register_taxonomy(
        'regiones',
        'post',
        array(
            'labels'             => array(
                'name'          => 'Regiones',
                'singular_name' => 'Región',
            ),
            'hierarchical'       => false,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_admin_column'  => false,
            'rewrite'            => false,
            'show_in_rest'       => false,
        )
    );

    register_term_meta(
        'regiones',
        'region_code',
        array(
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => false,
        )
    );

    register_term_meta(
        'regiones',
        'region_admin',
        array(
            'type'         => 'integer',
            'single'       => true,
            'show_in_rest' => false,
        )
    );
}
add_action( 'init', 'enterprise_register_regiones_taxonomy' );

/* ─────────────────────────────────────────
   MOTOR DE «SINCRONIZAR REGIONES» (#55, Fase 1 de #45)
   Siembra/actualización IDEMPOTENTE y NO DESTRUCTIVA de los términos de la taxonomía
   `regiones` desde el árbol autoritativo del mapa (map-regions-global.json), leído del
   almacén del sitio vía el resolutor de #58. El cómputo (enterprise_regiones_compute_sync)
   NO escribe: aplana el árbol a todos los niveles, casa por `region_code` y clasifica en
   cuatro grupos (nuevas / a actualizar / descolgadas con entradas / descolgadas sin
   entradas). El handler ejecuta «Analizar» (guarda el reporte en un transient por usuario
   y redirige) o «Aplicar» (recalcula sobre el árbol vigente y crea/actualiza; nunca borra
   ni reasigna). Emparejamiento SIEMPRE por código (Decisión E); «actualizar» refresca
   nombre + nivel (Decisión Opción A); término con posts protegido (Decisión F).
───────────────────────────────────────── */

/* Aplana el árbol (recursivo, todos los niveles) a una lista de { code, name, admin }.
   Los nodos sin `code` no son sembrables: se ignoran, pero se recorre a sus hijos. */
function enterprise_regiones_flatten_tree( $branch, &$out ) {
    if ( ! is_array( $branch ) ) {
        return;
    }
    foreach ( $branch as $node ) {
        if ( ! is_array( $node ) ) {
            continue;
        }
        $code = isset( $node['code'] ) ? (string) $node['code'] : '';
        if ( '' !== $code ) {
            $out[] = array(
                'code'  => $code,
                'name'  => isset( $node['name'] ) ? (string) $node['name'] : '',
                'admin' => isset( $node['admin'] ) ? (int) $node['admin'] : 0,
            );
        }
        if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
            enterprise_regiones_flatten_tree( $node['children'], $out );
        }
    }
}

/* Dry-run: lee el árbol, lo aplana, carga los términos existentes indexados por
   `region_code` y clasifica. NO escribe nada. Devuelve el reporte de cuatro grupos, o
   array( 'error' => 'read_error'|'bad_format' ) si el árbol no se puede leer/parsear. */
function enterprise_regiones_compute_sync() {
    $path = enterprise_map_asset_path( 'map-regions-global.json' );
    if ( ! file_exists( $path ) ) {
        return array( 'error' => 'read_error' );
    }
    $bytes = file_get_contents( $path );
    if ( false === $bytes ) {
        return array( 'error' => 'read_error' );
    }
    $data = json_decode( (string) $bytes, true );
    if (
        ! is_array( $data ) ||
        ! isset( $data['_meta'] ) ||
        empty( $data['tree'] ) ||
        ! is_array( $data['tree'] )
    ) {
        return array( 'error' => 'bad_format' );
    }

    /* Aplanar el árbol (todos los niveles). */
    $nodes = array();
    enterprise_regiones_flatten_tree( $data['tree'], $nodes );

    $tree_codes = array();
    foreach ( $nodes as $n ) {
        $tree_codes[ $n['code'] ] = true;
    }

    /* Términos existentes indexados por `region_code` (primera aparición). */
    $terms = get_terms( array( 'taxonomy' => 'regiones', 'hide_empty' => false ) );
    if ( is_wp_error( $terms ) ) {
        $terms = array();
    }
    $term_by_code = array();
    foreach ( $terms as $t ) {
        $code = (string) get_term_meta( $t->term_id, 'region_code', true );
        if ( isset( $term_by_code[ $code ] ) ) {
            continue;
        }
        $admin_meta = get_term_meta( $t->term_id, 'region_admin', true );
        $term_by_code[ $code ] = array(
            'term_id' => (int) $t->term_id,
            'name'    => (string) $t->name,
            'admin'   => ( '' === $admin_meta ? null : (int) $admin_meta ),
            'count'   => (int) $t->count,
        );
    }

    /* Clasificar cada nodo del árbol: nueva / a actualizar / sin cambios. */
    $nuevas     = array();
    $actualizar = array();
    foreach ( $nodes as $n ) {
        $code = $n['code'];
        if ( ! isset( $term_by_code[ $code ] ) ) {
            $nuevas[] = array( 'code' => $code, 'name' => $n['name'], 'admin' => $n['admin'] );
            continue;
        }
        $t             = $term_by_code[ $code ];
        $name_differs  = ( $t['name'] !== $n['name'] );
        $admin_differs = ( null === $t['admin'] || (int) $t['admin'] !== (int) $n['admin'] );
        if ( $name_differs || $admin_differs ) {
            $actualizar[] = array(
                'code'      => $code,
                'name'      => $n['name'],
                'admin'     => $n['admin'],
                'old_name'  => $t['name'],
                'old_admin' => $t['admin'],
                'term_id'   => $t['term_id'],
            );
        }
    }

    /* Clasificar cada término cuyo código YA NO está en el árbol: descolgado
       (separado por si tiene posts). Solo se reporta, nunca se toca. */
    $desc_con = array();
    $desc_sin = array();
    foreach ( $term_by_code as $code => $t ) {
        if ( isset( $tree_codes[ $code ] ) ) {
            continue;
        }
        $entry = array( 'code' => $code, 'name' => $t['name'], 'count' => $t['count'], 'term_id' => $t['term_id'] );
        if ( $t['count'] > 0 ) {
            $desc_con[] = $entry;
        } else {
            $desc_sin[] = $entry;
        }
    }

    return array(
        'nuevas'          => $nuevas,
        'actualizar'      => $actualizar,
        'descolgadas_con' => $desc_con,
        'descolgadas_sin' => $desc_sin,
    );
}

/* Aplica el reporte: crea las nuevas y actualiza nombre + nivel de las «a actualizar».
   NUNCA borra ni reasigna: los grupos «descolgadas» se ignoran aquí. */
function enterprise_regiones_apply_sync( $report ) {
    $nuevas     = ! empty( $report['nuevas'] )     && is_array( $report['nuevas'] )     ? $report['nuevas']     : array();
    $actualizar = ! empty( $report['actualizar'] ) && is_array( $report['actualizar'] ) ? $report['actualizar'] : array();

    foreach ( $nuevas as $n ) {
        $code  = isset( $n['code'] )  ? (string) $n['code'] : '';
        $name  = isset( $n['name'] )  ? (string) $n['name'] : '';
        $admin = isset( $n['admin'] ) ? (int) $n['admin']   : 0;
        if ( '' === $code ) {
            continue;
        }

        $slug = strtolower( $code );
        $res  = wp_insert_term( $name, 'regiones', array( 'slug' => $slug ) );

        /* Colisión de slug (no debería ocurrir con slug = código): reintento con slug
           sufijado; el join sigue en `region_code`. No se aborta la tanda. */
        if ( is_wp_error( $res ) && 'term_exists' === $res->get_error_code() ) {
            $res = wp_insert_term( $name, 'regiones', array( 'slug' => $slug . '-' . strtolower( wp_generate_password( 4, false ) ) ) );
        }
        if ( is_wp_error( $res ) || empty( $res['term_id'] ) ) {
            continue;
        }
        $term_id = (int) $res['term_id'];
        update_term_meta( $term_id, 'region_code', $code );
        update_term_meta( $term_id, 'region_admin', $admin );
    }

    foreach ( $actualizar as $u ) {
        $term_id = isset( $u['term_id'] ) ? (int) $u['term_id'] : 0;
        $name    = isset( $u['name'] )    ? (string) $u['name'] : '';
        $admin   = isset( $u['admin'] )   ? (int) $u['admin']   : 0;
        if ( ! $term_id ) {
            continue;
        }
        wp_update_term( $term_id, 'regiones', array( 'name' => $name ) ); // code y slug intactos
        update_term_meta( $term_id, 'region_admin', $admin );
    }
}

/* Handler admin_post: nonce + capacidad, luego «analyze» (reporte al transient +
   redirect) o «apply» (recalcula sobre el árbol vigente y escribe + redirect). */
function enterprise_sync_regiones_handler() {
    check_admin_referer( 'enterprise_sync_regiones', 'enterprise_regiones_nonce' );
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        wp_die( esc_html__( 'No tienes permiso para realizar esta acción.', 'enterprise-moto' ) );
    }

    $redirect = admin_url( 'admin.php?page=enterprise-moto' );
    $mode     = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';

    $report = enterprise_regiones_compute_sync();
    if ( isset( $report['error'] ) ) {
        wp_safe_redirect( add_query_arg( 'enterprise_regiones', $report['error'], $redirect ) );
        exit;
    }

    if ( 'apply' === $mode ) {
        /* Puerta de «previa siempre» (§7.2, Decisión C): exige el transient del análisis
           previo del usuario. Si no existe (POST directo, pestaña obsoleta) → no escribe. */
        $tkey     = 'enterprise_regiones_report_' . get_current_user_id();
        $analyzed = get_transient( $tkey );
        if ( false === $analyzed || ! is_array( $analyzed ) ) {
            wp_safe_redirect( add_query_arg( 'enterprise_regiones', 'need_analyze', $redirect ) );
            exit;
        }
        /* Se aplica el reporte RECIÉN recalculado ($report, Decisión 10 intacta), no el
           del transient: este solo prueba que hubo previa. Se consume tras aplicar. */
        enterprise_regiones_apply_sync( $report );
        delete_transient( $tkey );
        wp_safe_redirect( add_query_arg( 'enterprise_regiones', 'applied', $redirect ) );
        exit;
    }

    /* Cualquier otro modo (incl. «analyze»): dry-run. El reporte no cabe en la URL;
       viaja por un transient por usuario que pinta el panel de la página. */
    set_transient( 'enterprise_regiones_report_' . get_current_user_id(), $report, 5 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( 'enterprise_regiones', 'analyzed', $redirect ) );
    exit;
}
add_action( 'admin_post_enterprise_sync_regiones', 'enterprise_sync_regiones_handler' );

function enterprise_register_blocks() {
    // Cargar el render callback
    require_once get_template_directory() . '/blocks/post-stages/render.php';

    // Versión basada en la fecha de modificación del archivo → cache bust automático
    $block_js_path = get_template_directory() . '/assets/js/block-post-stages.js';
    $block_js_ver  = file_exists( $block_js_path )
        ? filemtime( $block_js_path )
        : ENTERPRISE_VERSION;

    // Registrar el script del editor
    wp_register_script(
        'enterprise-block-post-stages',
        get_template_directory_uri() . '/assets/js/block-post-stages.js',
        array(
            'wp-blocks', 'wp-element', 'wp-block-editor',
            'wp-components', 'wp-data', 'wp-api-fetch',
            'wp-server-side-render',
        ),
        $block_js_ver,
        true
    );

    // Registrar el bloque con render PHP
    register_block_type( 'enterprise/post-stages', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-post-stages',
        'render_callback' => 'enterprise_render_post_stages_block',
        'attributes'      => array(
            'categoryIds'   => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'integer' ) ),
            'tagIds'        => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'integer' ) ),
            'filterDateFrom' => array( 'type' => 'string', 'default' => '' ),
            'filterDateTo'   => array( 'type' => 'string', 'default' => '' ),
            'tagRelation'   => array( 'type' => 'string',  'default' => 'OR'  ),
            'postsPerPage'  => array( 'type' => 'integer', 'default' => 6          ),
            'orderBy'       => array( 'type' => 'string',  'default' => 'date'     ),
            'order'         => array( 'type' => 'string',  'default' => 'DESC'     ),
            'layout'        => array( 'type' => 'string',  'default' => 'carousel' ),
            'cardSize'      => array( 'type' => 'string',  'default' => 'normal'   ),
            'heading'       => array( 'type' => 'string',  'default' => ''         ),
            'showExcerpt'   => array( 'type' => 'boolean', 'default' => true       ),
            'showKm'        => array( 'type' => 'boolean', 'default' => true       ),
            'showDate'      => array( 'type' => 'boolean', 'default' => true       ),
        ),
        'supports' => array(
            'html'  => false,
            'align' => array( 'wide', 'full' ),
        ),
    ) );

    /* ── Bloque: Colección de viajes (enterprise/trip-collection, #5) ─── */
    require_once get_template_directory() . '/blocks/trip-collection/render.php';

    $tc_js_path = get_template_directory() . '/assets/js/block-trip-collection.js';
    wp_register_script(
        'enterprise-block-trip-collection',
        get_template_directory_uri() . '/assets/js/block-trip-collection.js',
        array(
            'wp-blocks', 'wp-element', 'wp-block-editor',
            'wp-components', 'wp-data', 'wp-api-fetch',
            'wp-server-side-render',
        ),
        file_exists( $tc_js_path ) ? filemtime( $tc_js_path ) : ENTERPRISE_VERSION,
        true
    );

    // Estilo de la colección. Se registra aquí y se adjunta al bloque (style)
    // para que las tarjetas se pinten estilizadas allí donde se inserte el
    // bloque; la plantilla template-trip-coleccion.php reutilizará el mismo
    // handle en la Fase 3 (WordPress deduplica el encolado).
    $col_css_path = get_template_directory() . '/assets/css/coleccion.css';
    wp_register_style(
        'enterprise-coleccion',
        get_template_directory_uri() . '/assets/css/coleccion.css',
        array(),
        file_exists( $col_css_path ) ? filemtime( $col_css_path ) : ENTERPRISE_VERSION
    );

    register_block_type( 'enterprise/trip-collection', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-trip-collection',
        'style'           => 'enterprise-coleccion',
        'render_callback' => 'enterprise_render_trip_collection_block',
        'attributes'      => array(
            'categoryIds'    => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'integer' ) ),
            'tagIds'         => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'integer' ) ),
            'filterDateFrom' => array( 'type' => 'string',  'default' => ''     ),
            'filterDateTo'   => array( 'type' => 'string',  'default' => ''     ),
            'tagRelation'    => array( 'type' => 'string',  'default' => 'OR'   ),
            'postsPerPage'   => array( 'type' => 'integer', 'default' => 6      ),
            'orderBy'        => array( 'type' => 'string',  'default' => 'date' ),
            'order'          => array( 'type' => 'string',  'default' => 'DESC' ),
            'showAll'        => array( 'type' => 'boolean', 'default' => false  ),
            'layout'         => array( 'type' => 'string',  'default' => 'carousel' ),
        ),
        'supports' => array(
            'html'  => false,
            'align' => array( 'wide', 'full' ),
        ),
    ) );

    /* ── Bloque: Carrusel de fotos ─────────────────────────────────── */
    require_once get_template_directory() . '/blocks/photo-gallery/render.php';

    $pg_js_path = get_template_directory() . '/assets/js/block-photo-gallery.js';
    wp_register_script(
        'enterprise-block-photo-gallery',
        get_template_directory_uri() . '/assets/js/block-photo-gallery.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
        file_exists( $pg_js_path ) ? filemtime( $pg_js_path ) : ENTERPRISE_VERSION,
        true
    );
    register_block_type( 'enterprise/photo-gallery', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-photo-gallery',
        'render_callback' => 'enterprise_render_photo_gallery_block',
        'attributes'      => array(
            'imageIds'      => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'integer' ) ),
            'heading'       => array( 'type' => 'string',  'default' => ''        ),
            'autoplay'      => array( 'type' => 'boolean', 'default' => false     ),
            'autoplayDelay' => array( 'type' => 'integer', 'default' => 4000      ),
            'imageSize'     => array( 'type' => 'string',  'default' => 'large'   ),
            'showCaptions'  => array( 'type' => 'boolean', 'default' => true      ),
            'containerRatio'=> array( 'type' => 'string',  'default' => '16/9'   ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque: Stories ───────────────────────────────────────────── */
    require_once get_template_directory() . '/blocks/stories/render.php';

    $st_js_path = get_template_directory() . '/assets/js/block-stories.js';
    wp_register_script(
        'enterprise-block-stories',
        get_template_directory_uri() . '/assets/js/block-stories.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
        file_exists( $st_js_path ) ? filemtime( $st_js_path ) : ENTERPRISE_VERSION,
        true
    );
    register_block_type( 'enterprise/stories', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-stories',
        'render_callback' => 'enterprise_render_stories_block',
        'attributes'      => array(
            'items'    => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'object' ) ),
            'heading'  => array( 'type' => 'string',  'default' => ''       ),
            'duration' => array( 'type' => 'integer', 'default' => 5000     ),
            'loop'     => array( 'type' => 'boolean', 'default' => false    ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque: Ruta planificada vs realizada ─────────────────────── */
    require_once get_template_directory() . '/blocks/route-comparison/render.php';

    $rc_js_path = get_template_directory() . '/assets/js/block-route-comparison.js';
    wp_register_script(
        'enterprise-block-route-comparison',
        get_template_directory_uri() . '/assets/js/block-route-comparison.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        file_exists( $rc_js_path ) ? filemtime( $rc_js_path ) : ENTERPRISE_VERSION,
        true
    );

    register_block_type( 'enterprise/route-comparison', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-route-comparison',
        'render_callback' => 'enterprise_render_route_comparison_block',
        'attributes'      => array(
            'gpxUrl'        => array( 'type' => 'string',  'default' => ''        ),
            'gpxUrl2'       => array( 'type' => 'string',  'default' => ''        ),
            'gpxLabel1'     => array( 'type' => 'string',  'default' => 'GPX1 — Ruta planificada' ),
            'gpxLabel2'     => array( 'type' => 'string',  'default' => 'GPX2 — Ruta realizada'   ),
            'heading'       => array( 'type' => 'string',  'default' => ''        ),
            'description'   => array( 'type' => 'string',  'default' => ''        ),
            'mapHeight'     => array( 'type' => 'string',  'default' => 'md'      ),
            'routeColor'    => array( 'type' => 'string',  'default' => '#001f5c' ),
            'routeColor2'   => array( 'type' => 'string',  'default' => '#c0392b' ),
            'markerColor'   => array( 'type' => 'string',  'default' => '#f2c118' ),
            'routeWeight'   => array( 'type' => 'integer', 'default' => 4         ),
            'showElevation' => array( 'type' => 'boolean', 'default' => true      ),
            'showStats'     => array( 'type' => 'boolean', 'default' => true      ),
            'startLabel'    => array( 'type' => 'string',  'default' => ''        ),
            'endLabel'      => array( 'type' => 'string',  'default' => ''        ),
            'statKm'        => array( 'type' => 'string',  'default' => ''        ),
            'statDuration'  => array( 'type' => 'string',  'default' => ''        ),
            'statElevGain'  => array( 'type' => 'string',  'default' => ''        ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque: Mapa de ruta con metadatos (#56 · Fase 2 del plan #45) ─── */
    require_once get_template_directory() . '/blocks/route-metadata-map/render.php';

    $rmm_js_path = get_template_directory() . '/assets/js/block-route-metadata-map.js';
    wp_register_script(
        'enterprise-block-route-metadata-map',
        get_template_directory_uri() . '/assets/js/block-route-metadata-map.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch', 'wp-data' ),
        file_exists( $rmm_js_path ) ? filemtime( $rmm_js_path ) : ENTERPRISE_VERSION,
        true
    );

    register_block_type( 'enterprise/route-metadata-map', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-route-metadata-map',
        'render_callback' => 'enterprise_render_route_metadata_map_block',
        'attributes'      => array(
            'year'            => array( 'type' => 'string',  'default' => ''        ),
            'month'           => array( 'type' => 'string',  'default' => ''        ),
            'day'             => array( 'type' => 'string',  'default' => ''        ),
            'trip'            => array( 'type' => 'string',  'default' => ''        ),
            'validated'       => array( 'type' => 'boolean', 'default' => false     ),
            'useGeoInventory' => array( 'type' => 'boolean', 'default' => true      ),
            'assetSuffix'     => array( 'type' => 'string',  'default' => ''        ),
            'gpxLabel1'       => array( 'type' => 'string',  'default' => 'GPX1 — Ruta planificada' ),
            'gpxLabel2'       => array( 'type' => 'string',  'default' => 'GPX2 — Ruta realizada'   ),
            'heading'         => array( 'type' => 'string',  'default' => ''        ),
            'description'     => array( 'type' => 'string',  'default' => ''        ),
            'mapHeight'       => array( 'type' => 'string',  'default' => 'md'      ),
            'routeColor'      => array( 'type' => 'string',  'default' => '#001f5c' ),
            'routeColor2'     => array( 'type' => 'string',  'default' => '#c0392b' ),
            'markerColor'     => array( 'type' => 'string',  'default' => '#f2c118' ),
            'routeWeight'     => array( 'type' => 'integer', 'default' => 4         ),
            'showElevation'   => array( 'type' => 'boolean', 'default' => true      ),
            'showStats'       => array( 'type' => 'boolean', 'default' => true      ),
            'startLabel'      => array( 'type' => 'string',  'default' => ''        ),
            'endLabel'        => array( 'type' => 'string',  'default' => ''        ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloques: Markdown simple + Markdown con estilo ───────────── */
    require_once get_template_directory() . '/blocks/markdown/render.php';
    require_once get_template_directory() . '/blocks/markdown-styled/render.php';

    $md_js = get_template_directory() . '/assets/js/block-markdown.js';
    wp_register_script( 'enterprise-block-markdown',
        get_template_directory_uri() . '/assets/js/block-markdown.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        file_exists( $md_js ) ? filemtime( $md_js ) : ENTERPRISE_VERSION, true );

    register_block_type( 'enterprise/markdown', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-markdown',
        'render_callback' => 'enterprise_render_markdown_block',
        'attributes'      => array(
            'markdownContent' => array( 'type' => 'string', 'default' => '' ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    register_block_type( 'enterprise/markdown-styled', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-markdown',
        'render_callback' => 'enterprise_render_markdown_styled_block',
        'attributes'      => array(
            'markdownContent' => array( 'type' => 'string',  'default' => ''        ),
            'fontFamily'      => array( 'type' => 'string',  'default' => 'dm-sans' ),
            'fontSize'        => array( 'type' => 'integer', 'default' => 15        ),
            'textColor'       => array( 'type' => 'string',  'default' => ''        ),
            'bgColor'         => array( 'type' => 'string',  'default' => '#1a1a1a' ),
            'padding'         => array( 'type' => 'integer', 'default' => 24        ),
            'borderColor'     => array( 'type' => 'string',  'default' => '#f2c118' ),
            'borderWidth'     => array( 'type' => 'integer', 'default' => 4         ),
            'showBorder'      => array( 'type' => 'boolean', 'default' => true      ),
            'customCss'       => array( 'type' => 'string',  'default' => ''        ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque: YouTube Vídeo ─────────────────────────────────────── */
    require_once get_template_directory() . '/blocks/youtube-video/render.php';
    $yv_js = get_template_directory() . '/assets/js/block-youtube-video.js';
    wp_register_script( 'enterprise-block-youtube-video',
        get_template_directory_uri() . '/assets/js/block-youtube-video.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        file_exists( $yv_js ) ? filemtime( $yv_js ) : ENTERPRISE_VERSION, true );
    register_block_type( 'enterprise/youtube-video', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-youtube-video',
        'render_callback' => 'enterprise_render_youtube_video_block',
        'attributes'      => array(
            'videoUrl'    => array( 'type' => 'string',  'default' => ''    ),
            'videoTitle'  => array( 'type' => 'string',  'default' => ''    ),
            'channel'     => array( 'type' => 'string',  'default' => ''    ),
            'duration'    => array( 'type' => 'string',  'default' => ''    ),
            'description' => array( 'type' => 'string',  'default' => ''    ),
            'ratio'       => array( 'type' => 'string',  'default' => '16/9'),
            'heading'     => array( 'type' => 'string',  'default' => ''    ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque: YouTube Reels ─────────────────────────────────────── */
    require_once get_template_directory() . '/blocks/youtube-reels/render.php';
    $yr_js = get_template_directory() . '/assets/js/block-youtube-reels.js';
    wp_register_script( 'enterprise-block-youtube-reels',
        get_template_directory_uri() . '/assets/js/block-youtube-reels.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        file_exists( $yr_js ) ? filemtime( $yr_js ) : ENTERPRISE_VERSION, true );
    register_block_type( 'enterprise/youtube-reels', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-youtube-reels',
        'render_callback' => 'enterprise_render_youtube_reels_block',
        'attributes'      => array(
            'items'       => array( 'type' => 'array',   'default' => array(), 'items' => array( 'type' => 'object' ) ),
            'heading'     => array( 'type' => 'string',  'default' => ''   ),
            'showTitles'  => array( 'type' => 'boolean', 'default' => true ),
            'desktopCols' => array( 'type' => 'integer', 'default' => 3    ),
        ),
        'supports' => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
    ) );

    /* ── Bloque: Tip / Aviso ───────────────────────────────────────── */
    require_once get_template_directory() . '/blocks/tip-box/render.php';

    $tip_js_path = get_template_directory() . '/assets/js/block-tip-box.js';
    wp_register_script(
        'enterprise-block-tip-box',
        get_template_directory_uri() . '/assets/js/block-tip-box.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        file_exists( $tip_js_path ) ? filemtime( $tip_js_path ) : ENTERPRISE_VERSION,
        true
    );
    register_block_type( 'enterprise/tip-box', array(
        'api_version'     => 3,
        'editor_script'   => 'enterprise-block-tip-box',
        'render_callback' => 'enterprise_render_tip_box_block',
        'attributes'      => array(
            'tipType'  => array( 'type' => 'string', 'default' => 'consejo' ),
            'tipText'  => array( 'type' => 'string', 'default' => ''        ),
            'tipTitle' => array( 'type' => 'string', 'default' => ''        ),
            'tipIcon'  => array( 'type' => 'string', 'default' => 'auto'    ),
        ),
        'supports' => array( 'html' => false ),
    ) );
}
add_action( 'init', 'enterprise_register_blocks' );

/* ─────────────────────────────────────────
   CARGAR CSS/JS DEL CARRUSEL EN FRONTEND
───────────────────────────────────────── */
function enterprise_carousel_assets() {
    // Solo cargar si hay bloques de carrusel en la página actual
    if ( ! is_singular() && ! is_page() ) return;

    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    // #11 R7: el carrusel/timeline lo usan tanto post-stages como trip-collection.
    // #18: la página-destino de «Rutas por localización» también reutiliza el
    // carrusel (carruseles de .post-card por categoría) vía su plantilla.
    $needs_carousel = has_block( 'enterprise/post-stages', $post )
                   || has_block( 'enterprise/trip-collection', $post )
                   || ( 'page-templates/template-routes-by-location.php' === get_page_template_slug( $post ) );
    if ( ! $needs_carousel ) return;

    $carousel_css_path = get_template_directory() . '/assets/css/carousel.css';
    wp_enqueue_style(
        'enterprise-carousel',
        get_template_directory_uri() . '/assets/css/carousel.css',
        array( 'enterprise-style' ),
        file_exists( $carousel_css_path ) ? filemtime( $carousel_css_path ) : ENTERPRISE_VERSION
    );

    $carousel_js_path = get_template_directory() . '/assets/js/carousel.js';
    wp_enqueue_script(
        'enterprise-carousel',
        get_template_directory_uri() . '/assets/js/carousel.js',
        array(),
        file_exists( $carousel_js_path ) ? filemtime( $carousel_js_path ) : ENTERPRISE_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_carousel_assets' );

/* ─────────────────────────────────────────
   CARGAR CSS/JS DE BLOQUES DE MEDIA
   (photo-gallery + stories)
───────────────────────────────────────── */
function enterprise_media_blocks_assets() {
    if ( ! is_singular() && ! is_page() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    $has_gallery = has_block( 'enterprise/photo-gallery', $post );
    $has_stories = has_block( 'enterprise/stories', $post );
    if ( ! $has_gallery && ! $has_stories ) return;

    $css_path = get_template_directory() . '/assets/css/blocks-media.css';
    $js_path  = get_template_directory() . '/assets/js/blocks-media.js';

    wp_enqueue_style(
        'enterprise-blocks-media',
        get_template_directory_uri() . '/assets/css/blocks-media.css',
        array( 'enterprise-style' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : ENTERPRISE_VERSION
    );
    wp_enqueue_script(
        'enterprise-blocks-media',
        get_template_directory_uri() . '/assets/js/blocks-media.js',
        array(),
        file_exists( $js_path ) ? filemtime( $js_path ) : ENTERPRISE_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_media_blocks_assets' );

/* ─────────────────────────────────────────
   CARGAR CSS DEL BLOQUE TIP-BOX
───────────────────────────────────────── */
function enterprise_tip_box_assets() {
    if ( ! is_singular() && ! is_page() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    if ( ! has_block( 'enterprise/tip-box', $post ) ) return;

    $css_path = get_template_directory() . '/assets/css/tip-box.css';
    wp_enqueue_style(
        'enterprise-tip-box',
        get_template_directory_uri() . '/assets/css/tip-box.css',
        array( 'enterprise-style' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : ENTERPRISE_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_tip_box_assets' );

/* ─────────────────────────────────────────
   CARGAR CSS/JS DE BLOQUES YOUTUBE
───────────────────────────────────────── */
function enterprise_youtube_assets() {
    if ( ! is_singular() && ! is_page() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    $has_video = has_block( 'enterprise/youtube-video', $post );
    $has_reels = has_block( 'enterprise/youtube-reels', $post );
    if ( ! $has_video && ! $has_reels ) return;

    $css_path = get_template_directory() . '/assets/css/youtube.css';
    $js_path  = get_template_directory() . '/assets/js/youtube.js';
    wp_enqueue_style( 'enterprise-youtube',
        get_template_directory_uri() . '/assets/css/youtube.css',
        array( 'enterprise-style' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : ENTERPRISE_VERSION );
    wp_enqueue_script( 'enterprise-youtube',
        get_template_directory_uri() . '/assets/js/youtube.js',
        array(),
        file_exists( $js_path ) ? filemtime( $js_path ) : ENTERPRISE_VERSION,
        true );
}
add_action( 'wp_enqueue_scripts', 'enterprise_youtube_assets' );

/* ─────────────────────────────────────────
   CARGAR CSS BLOQUES MARKDOWN
───────────────────────────────────────── */
function enterprise_markdown_assets() {
    if ( ! is_singular() && ! is_page() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;
    if ( ! has_block( 'enterprise/markdown', $post ) && ! has_block( 'enterprise/markdown-styled', $post ) ) return;

    $css_path = get_template_directory() . '/assets/css/markdown.css';
    wp_enqueue_style( 'enterprise-markdown',
        get_template_directory_uri() . '/assets/css/markdown.css',
        array( 'enterprise-style' ),
        file_exists( $css_path ) ? filemtime( $css_path ) : ENTERPRISE_VERSION );
}
add_action( 'wp_enqueue_scripts', 'enterprise_markdown_assets' );


/* Encolado de la antigua plantilla de bloques retirado en #5 Fase 3b: la
   «Colección de viajes» carga assets/css/coleccion.css vía enterprise_coleccion_assets();
   el carrusel (si hay bloque post-stages) se auto-encola por has_block. */
/* ─────────────────────────────────────────
   BLOCK PATTERN: Carrusel de etapas listo
───────────────────────────────────────── */
function enterprise_register_block_patterns() {
    if ( ! function_exists( 'register_block_pattern_category' ) ) return;

    register_block_pattern_category( 'enterprise-moto', array(
        'label' => __( 'Enterprise Moto', 'enterprise-moto' ),
    ) );

    register_block_pattern( 'enterprise-moto/carousel-etapas', array(
        'title'       => __( 'Carrusel de etapas de ruta', 'enterprise-moto' ),
        'description' => __( 'Carrusel horizontal filtrado por categorías y/o etiquetas.', 'enterprise-moto' ),
        'categories'  => array( 'enterprise-moto' ),
        'content'     => '<!-- wp:enterprise/post-stages {"categoryIds":[],"tagIds":[],"postsPerPage":6,"layout":"carousel","cardSize":"normal","heading":"Etapas del viaje"} /-->',
    ) );
    // ELIMINADO: enterprise-moto/timeline-etapas
    // El bloque 'Etapas de ruta' ya cubre esta función con modo layout:"timeline"
    // Ver: sección "Elementos pendientes de eliminar" del documento de diseño
}
add_action( 'init', 'enterprise_register_block_patterns' );

/* ─────────────────────────────────────────
   FUNCIONES DE PORTADA (index.php)
   Definidas aquí para evitar redeclaración.
───────────────────────────────────────── */
/* ── Tarjeta de post ── */
function enterprise_home_post_card( $post_id, $num, $section_cat_name = '', $section_cat_slug = '' ) {
    $route    = enterprise_get_route_data( $post_id );
    /* Mostrar la categoría del contexto de la sección, no la primera del post */
    $cat_name = $section_cat_name ?: enterprise_first_category( $post_id );
    $thumb    = get_the_post_thumbnail_url( $post_id, 'enterprise-card' );
    ?>
    <?php
    /* #13 (§7): estampar ?from_cat con el SLUG REAL del término de la sección (identidad
       navegable), no reconstruido desde el nombre/título visible. Sin slug → no se estampa
       (fallback seguro: nunca un from_cat erróneo). La etiqueta visible sigue usando el nombre. */
    $card_permalink = get_permalink( $post_id );
    if ( $section_cat_slug ) {
        $card_permalink = add_query_arg( 'from_cat', sanitize_key( $section_cat_slug ), $card_permalink );
    }
    ?>
    <article class="post-card" id="post-<?php echo intval( $post_id ); ?>">
        <a href="<?php echo esc_url( $card_permalink ); ?>" tabindex="-1" aria-hidden="true">
            <div class="post-card-thumb">
                <div class="post-card-thumb-inner">
                    <?php if ( $thumb ) : ?>
                        <img src="<?php echo esc_url( $thumb ); ?>"
                             alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                             loading="lazy">
                    <?php else : ?>
                        <div class="post-card-thumb-fallback">🏍️</div>
                    <?php endif; ?>
                </div>
                <span class="post-card-num" aria-hidden="true">
                    <?php echo str_pad( $num, 2, '0', STR_PAD_LEFT ); ?>
                </span>
            </div>
        </a>
        <div class="post-card-body">
            <div class="entry-tags">
                <?php if ( $cat_name ) : ?>
                    <span class="entry-tag entry-tag--cat"><?php echo esc_html( $cat_name ); ?></span>
                <?php endif; ?>
                <span class="entry-tag entry-tag--date">
                    <?php echo esc_html( get_the_date( 'Y', $post_id ) ); ?>
                </span>
            </div>
            <h3 class="post-card-title">
                <a href="<?php echo esc_url( $card_permalink ); ?>">
                    <?php echo esc_html( get_the_title( $post_id ) ); ?>
                </a>
            </h3>
            <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt( $post_id ) ); ?></p>
            <div class="post-card-footer">
                <div class="post-card-km">
                    <?php if ( $route['km'] ) : echo esc_html( $route['km'] );
                    else : ?><span><?php esc_html_e( 'Detalles', 'enterprise-moto' ); ?></span><?php endif; ?>
                </div>
                <a href="<?php echo esc_url( $card_permalink ); ?>"
                   class="post-card-arrow"
                   aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">→</a>
            </div>
        </div>
    </article>
    <?php
}

/* ── Sección con título + grid + CTA ── */
function enterprise_home_section( $eyebrow, $title, $posts, $cta_url, $cta_label, $section_cat = '', $section_cat_slug = '' ) {
    if ( empty( $posts ) ) return;
    ?>
    <section class="home-group-section">
        <div class="container">
            <div class="home-group-head">
                <div class="home-group-eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
                <h2 class="home-group-title"><?php echo esc_html( strtoupper( $title ) ); ?></h2>
                <a href="<?php echo esc_url( $cta_url ); ?>" class="home-group-cta-top">
                    <?php echo esc_html( $cta_label ); ?> →
                </a>
            </div>
            <div class="posts-grid">
                <?php foreach ( $posts as $i => $post ) :
                    enterprise_home_post_card( $post->ID, $i + 1, $section_cat, $section_cat_slug );
                endforeach; ?>
            </div>
            <div class="home-group-footer">
                <a href="<?php echo esc_url( $cta_url ); ?>" class="btn btn--dark">
                    <?php echo esc_html( $cta_label ); ?> →
                </a>
            </div>
        </div>
    </section>
    <?php
}

/* ═══════════════════════════════════════════════════════════════
   PERSONALIZADOR DE WORDPRESS — Configuración de la portada
   Apariencia → Personalizar → Portada / Quiénes somos
═══════════════════════════════════════════════════════════════ */
function enterprise_customizer( $wp_customize ) {

    /* ────────────────────────────────────────
       PANEL PRINCIPAL
    ──────────────────────────────────────── */
    $wp_customize->add_panel( 'enterprise_home', array(
        'title'    => __( '🏍 Configuración de la portada', 'enterprise-moto' ),
        'priority' => 30,
    ) );

    /* ════════════════════════════════════════
       SECCIÓN: ESTADÍSTICAS DEL HERO
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'enterprise_hero', array(
        'title' => __( 'Hero — Estadísticas', 'enterprise-moto' ),
        'panel' => 'enterprise_home',
    ) );

    $wp_customize->add_setting( 'enterprise_paises', array(
        'default'           => '4',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'enterprise_paises', array(
        'label'   => __( 'Número de países recorridos', 'enterprise-moto' ),
        'section' => 'enterprise_hero',
        'type'    => 'number',
    ) );

    /* Categorías para la estadística "Días de ruta publicados" */
    $wp_customize->add_setting( 'enterprise_dias_ruta_cats', array(
        'default'           => 'etapa',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'enterprise_dias_ruta_cats', array(
        'label'       => __( 'Categorías para «Días de ruta publicados»', 'enterprise-moto' ),
        'description' => __( 'Slugs de categoría separados por coma. Se suman los "count" de todas las categorías indicadas. Ej: cuaderno-etapa, etapa, jornada', 'enterprise-moto' ),
        'section'     => 'enterprise_hero',
        'type'        => 'text',
    ) );

    /* Categorías para "Última ruta publicada" en la portada */
    $wp_customize->add_setting( 'enterprise_latest_cats', array(
        'default'           => 'etapa',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'enterprise_latest_cats', array(
        'label'       => __( 'Categorías para "Última ruta publicada"', 'enterprise-moto' ),
        'description' => __( 'Nombres o slugs separados por coma. La portada mostrará el post más reciente que pertenezca a alguna de estas categorías. Vacío = cualquier post.', 'enterprise-moto' ),
        'section'     => 'enterprise_hero',
        'type'        => 'text',
    ) );

    /* ════════════════════════════════════════
       SECCIÓN: MAPA DE RUTAS POR LOCALIZACIÓN (#17, rediseño #18)
       Página-destino a la que enlazan las localizaciones del bloque:
       carruseles de entradas por categoría del marcador.
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'enterprise_rbl', array(
        'title' => __( 'Mapa de rutas por localización', 'enterprise-moto' ),
        'panel' => 'enterprise_home',
    ) );

    $wp_customize->add_setting( 'enterprise_rbl_dest_page', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'enterprise_rbl_dest_page', array(
        'label'       => __( 'Página-destino', 'enterprise-moto' ),
        'description' => __( 'Página a la que enlaza cada localización del bloque «Mapa de rutas por localización». Crea una Página con la plantilla «Mapa de rutas por localización» y selecciónala aquí.', 'enterprise-moto' ),
        'section'     => 'enterprise_rbl',
        'type'        => 'dropdown-pages',
    ) );

    /* ════════════════════════════════════════
       SECCIONES DE GRUPOS (hasta 6)
       Cada sección tiene: título, tipo (cat/tag), slug, max posts
    ════════════════════════════════════════ */
    $type_choices = array(
        ''        => __( '— Desactivada —', 'enterprise-moto' ),
        'cat'     => __( 'Categoría', 'enterprise-moto' ),
        'tag'     => __( 'Etiqueta', 'enterprise-moto' ),
        'cat_children' => __( 'Hijos de categoría (auto)', 'enterprise-moto' ),
    );

    for ( $i = 1; $i <= 6; $i++ ) {
        $section_id = 'enterprise_home_group_' . $i;

        $defaults = array(
            1 => array( 'title' => '',                   'type' => 'cat_children', 'slug' => 'tipo-de-salida', 'max' => 3 ),
            2 => array( 'title' => 'Destinos',           'type' => 'tag',          'slug' => 'italia',         'max' => 3 ),
            3 => array( 'title' => 'Tipo de ruta',       'type' => 'tag',          'slug' => 'panoramica',     'max' => 3 ),
            4 => array( 'title' => '',                   'type' => '',             'slug' => '',               'max' => 3 ),
            5 => array( 'title' => '',                   'type' => '',             'slug' => '',               'max' => 3 ),
            6 => array( 'title' => '',                   'type' => '',             'slug' => '',               'max' => 3 ),
        );
        $def = $defaults[ $i ];

        $wp_customize->add_section( $section_id, array(
            'title' => sprintf( __( 'Sección %d de la portada', 'enterprise-moto' ), $i ),
            'panel' => 'enterprise_home',
        ) );

        /* Tipo */
        $wp_customize->add_setting( 'enterprise_group_' . $i . '_type', array(
            'default'           => $def['type'],
            'sanitize_callback' => 'sanitize_key',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'enterprise_group_' . $i . '_type', array(
            'label'   => __( 'Tipo de agrupación', 'enterprise-moto' ),
            'section' => $section_id,
            'type'    => 'select',
            'choices' => $type_choices,
        ) );

        /* Slug — acepta múltiples valores separados por coma (para etiquetas) */
        $wp_customize->add_setting( 'enterprise_group_' . $i . '_slug', array(
            'default'           => $def['slug'],
            'sanitize_callback' => 'enterprise_sanitize_slug_list',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'enterprise_group_' . $i . '_slug', array(
            'label'       => __( 'Nombre o slug', 'enterprise-moto' ),
            'description' => __( 'Escribe el nombre o slug de la categoría/etiqueta. Para etiquetas puedes poner varios separados por coma (ej: italia, sicilia). Con "Hijos de categoría (auto)" no se usa.', 'enterprise-moto' ),
            'section'     => $section_id,
            'type'        => 'text',
        ) );

        /* Título personalizado */
        $wp_customize->add_setting( 'enterprise_group_' . $i . '_title', array(
            'default'           => $def['title'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'enterprise_group_' . $i . '_title', array(
            'label'       => __( 'Título de sección (opcional)', 'enterprise-moto' ),
            'description' => __( 'Déjalo vacío para usar el nombre de la categoría/etiqueta.', 'enterprise-moto' ),
            'section'     => $section_id,
            'type'        => 'text',
        ) );

        /* Max posts */
        $wp_customize->add_setting( 'enterprise_group_' . $i . '_max', array(
            'default'           => $def['max'],
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'enterprise_group_' . $i . '_max', array(
            'label'   => __( 'Máximo de entradas', 'enterprise-moto' ),
            'section' => $section_id,
            'type'    => 'number',
            'input_attrs' => array( 'min' => 1, 'max' => 8, 'step' => 1 ),
        ) );

        /* Etiqueta del eyebrow */
        $wp_customize->add_setting( 'enterprise_group_' . $i . '_eyebrow', array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( 'enterprise_group_' . $i . '_eyebrow', array(
            'label'       => __( 'Etiqueta pequeña sobre el título (eyebrow)', 'enterprise-moto' ),
            'description' => __( 'Ej: "Tipo de salida", "Destino destacado", "Tipo de ruta". Vacío = se genera automáticamente.', 'enterprise-moto' ),
            'section'     => $section_id,
            'type'        => 'text',
        ) );
    }

    /* ════════════════════════════════════════
       SECCIÓN: QUIÉNES SOMOS
    ════════════════════════════════════════ */
    $wp_customize->add_section( 'enterprise_about', array(
        'title'    => __( 'Quiénes somos', 'enterprise-moto' ),
        'panel'    => 'enterprise_home',
        'priority' => 80,
    ) );

    /* Imagen */
    $wp_customize->add_setting( 'enterprise_about_image', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'enterprise_about_image', array(
        'label'         => __( 'Imagen de la sección "Quiénes somos"', 'enterprise-moto' ),
        'description'   => __( 'Sube o selecciona una foto. Recomendado: vertical, mínimo 800×1000px.', 'enterprise-moto' ),
        'section'       => 'enterprise_about',
        'mime_type'     => 'image',
    ) ) );

    /* Título */
    $wp_customize->add_setting( 'enterprise_about_title', array(
        'default'           => 'JUANJO & MARÍA JOSÉ',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'enterprise_about_title', array(
        'label'   => __( 'Título de la sección', 'enterprise-moto' ),
        'section' => 'enterprise_about',
        'type'    => 'text',
    ) );

    /* Texto */
    $wp_customize->add_setting( 'enterprise_about_text', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'enterprise_about_text', array(
        'label'       => __( 'Texto descriptivo', 'enterprise-moto' ),
        'description' => __( 'Vacío = se lee del contenido de la página "acerca-de".', 'enterprise-moto' ),
        'section'     => 'enterprise_about',
        'type'        => 'textarea',
    ) );

    /* Enlace de la página about */
    $wp_customize->add_setting( 'enterprise_about_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'enterprise_about_url', array(
        'label'       => __( 'URL del botón "Sobre el blog"', 'enterprise-moto' ),
        'description' => __( 'Vacío = usa la página "acerca-de" si existe.', 'enterprise-moto' ),
        'section'     => 'enterprise_about',
        'type'        => 'url',
    ) );
}
add_action( 'customize_register', 'enterprise_customizer' );

/* ────────────────────────────────────────
   Helper: leer configuración de un grupo
──────────────────────────────────────── */
function enterprise_get_group_config( $i ) {
    /* Defaults que coinciden exactamente con los de add_setting()
       para que las secciones aparezcan incluso antes de que el
       usuario publique desde el Personalizador.               */
    static $defaults = array(
        1 => array( 'type' => 'cat_children', 'slug' => 'tipo-de-salida', 'title' => '',             'max' => 3, 'eyebrow' => '' ),
        2 => array( 'type' => 'tag',          'slug' => 'italia',         'title' => 'Destinos',     'max' => 3, 'eyebrow' => 'Destino destacado' ),
        3 => array( 'type' => 'tag',          'slug' => 'panoramica',     'title' => 'Tipo de ruta', 'max' => 3, 'eyebrow' => 'Tipo de ruta' ),
        4 => array( 'type' => '',             'slug' => '',               'title' => '',             'max' => 3, 'eyebrow' => '' ),
        5 => array( 'type' => '',             'slug' => '',               'title' => '',             'max' => 3, 'eyebrow' => '' ),
        6 => array( 'type' => '',             'slug' => '',               'title' => '',             'max' => 3, 'eyebrow' => '' ),
    );
    $def = isset( $defaults[ $i ] ) ? $defaults[ $i ] : array( 'type'=>'', 'slug'=>'', 'title'=>'', 'max'=>3, 'eyebrow'=>'' );

    return array(
        'type'    => get_theme_mod( 'enterprise_group_' . $i . '_type',    $def['type']    ),
        'slug'    => get_theme_mod( 'enterprise_group_' . $i . '_slug',    $def['slug']    ),
        'title'   => get_theme_mod( 'enterprise_group_' . $i . '_title',   $def['title']   ),
        'max'     => max( 1, intval( get_theme_mod( 'enterprise_group_' . $i . '_max', $def['max'] ) ) ),
        'eyebrow' => get_theme_mod( 'enterprise_group_' . $i . '_eyebrow', $def['eyebrow'] ),
    );
}

/* ─────────────────────────────────────────
   SANITIZAR LISTA DE SLUGS (coma-separada)
───────────────────────────────────────── */
function enterprise_sanitize_slug_list( $value ) {
    /* Acepta nombres o slugs separados por coma.
       Solo eliminamos caracteres peligrosos, sin slugificar,
       para que los nombres con tildes (ej: "Andalucía") funcionen. */
    $parts = array_map( 'trim', explode( ',', $value ) );
    $parts = array_filter( array_map( 'sanitize_text_field', $parts ) );
    return implode( ', ', $parts );
}

/* ─────────────────────────────────────────
   AJAX: AUTOCOMPLETE DE TÉRMINOS
   Busca categorías y etiquetas por nombre.
   Usado por el Personalizador.
───────────────────────────────────────── */
function enterprise_customizer_term_search() {
    check_ajax_referer( 'enterprise_customizer_nonce', 'nonce' );
    if ( ! current_user_can( 'edit_theme_options' ) ) wp_die( -1 );

    $q   = sanitize_text_field( $_GET['q'] ?? '' );
    $tax = sanitize_key( $_GET['tax'] ?? '' ); // 'category' | 'post_tag' | ''

    if ( strlen( $q ) < 2 ) wp_send_json_success( array() );

    $taxonomies = $tax ? array( $tax ) : array( 'category', 'post_tag' );
    $results    = array();

    foreach ( $taxonomies as $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'name__like' => $q,
            'hide_empty' => false,
            'number'     => 8,
            'fields'     => 'all',
        ) );
        if ( is_wp_error( $terms ) ) continue;
        foreach ( $terms as $term ) {
            $results[] = array(
                'slug'  => $term->slug,
                'name'  => $term->name,
                'tax'   => $taxonomy === 'category' ? 'cat' : 'tag',
                'count' => $term->count,
                'label' => $term->name . ' (' . ( $taxonomy === 'category' ? 'cat' : 'etiq' ) . ', ' . $term->count . ')',
            );
        }
    }

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_enterprise_term_search', 'enterprise_customizer_term_search' );

/* ─────────────────────────────────────────
   ENCOLAR JS DEL PERSONALIZADOR
───────────────────────────────────────── */
function enterprise_customizer_controls_enqueue() {
    wp_enqueue_script(
        'enterprise-customizer-controls',
        get_template_directory_uri() . '/assets/js/customizer-controls.js',
        array( 'customize-controls', 'jquery' ),
        filemtime( get_template_directory() . '/assets/js/customizer-controls.js' ),
        true
    );
    wp_localize_script( 'enterprise-customizer-controls', 'enterpriseCustomizer', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'enterprise_customizer_nonce' ),
    ) );
}
add_action( 'customize_controls_enqueue_scripts', 'enterprise_customizer_controls_enqueue' );

/* ─────────────────────────────────────────
   ADMIN: AUTOCOMPLETE EN METABOX
───────────────────────────────────────── */
function enterprise_metabox_scripts( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    global $post;
    if ( ! $post || $post->post_type !== 'post' ) return;

    /* Autocomplete de términos (categorías/etiquetas en páginas de expedición) */
    $template = get_page_template_slug( $post->ID );
    if ( strpos( $template, 'cuaderno' ) !== false || strpos( $template, 'bitacora' ) !== false ) {
        wp_enqueue_script(
            'enterprise-metabox-autocomplete',
            get_template_directory_uri() . '/assets/js/metabox-autocomplete.js',
            array( 'jquery' ),
            filemtime( get_template_directory() . '/assets/js/metabox-autocomplete.js' ),
            true
        );
    }

    /* JS del metabox de tipo de entrada (todos los posts) */
    wp_enqueue_script(
        'enterprise-metabox-post-tipo',
        get_template_directory_uri() . '/assets/js/metabox-post-tipo.js',
        array( 'jquery' ),
        filemtime( get_template_directory() . '/assets/js/metabox-post-tipo.js' ),
        true
    );
    wp_localize_script( 'enterprise-metabox-post-tipo', 'enterpriseMetabox', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'enterprise_customizer_nonce' ),
        'i18n'    => array(
            'searching' => __( 'Buscando...', 'enterprise-moto' ),
            'noResults' => __( 'Sin resultados', 'enterprise-moto' ),
        ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'enterprise_metabox_scripts' );

/* ═══════════════════════════════════════════════════════════════
   PERSONALIZADOR — Cuaderno de bitácora · Estado fuera de ruta
═══════════════════════════════════════════════════════════════ */
function enterprise_customizer_offroute( $wp_customize ) {

    $wp_customize->add_section( 'enterprise_offroute', array(
        'title'    => __( '🏕 Fuera de ruta — Próxima expedición', 'enterprise-moto' ),
        'panel'    => 'enterprise_home',
        'priority' => 90,
    ) );

    $fields = array(
        'enterprise_next_title'     => array( 'text',     __( 'Título de la próxima expedición',  'enterprise-moto' ), '', 'Ej: Portugal · Costa Atlántica' ),
        'enterprise_next_subtitle'  => array( 'text',     __( 'Subtítulo / descripción breve',    'enterprise-moto' ), '', 'Ej: De Tarragona a Lagos bordeando el Atlántico' ),
        'enterprise_next_date'      => array( 'text',     __( 'Fecha de salida (YYYY-MM-DD)',      'enterprise-moto' ), '', 'Ej: 2026-07-15' ),
        'enterprise_next_countries' => array( 'text',     __( 'Países',                           'enterprise-moto' ), '', 'Ej: España · Portugal' ),
        'enterprise_next_days'      => array( 'text',     __( 'Días estimados',                   'enterprise-moto' ), '', 'Ej: 8 días' ),
        'enterprise_next_km'        => array( 'text',     __( 'Kilómetros estimados',             'enterprise-moto' ), '', 'Ej: ≈ 2.000 km' ),
        'enterprise_next_desc'      => array( 'textarea', __( 'Descripción larga (opcional)',     'enterprise-moto' ), '', '' ),
        'enterprise_next_tag'       => array( 'text',     __( 'Etiqueta "Mientras tanto" (slug)',   'enterprise-moto' ), 'mientras-tanto', 'Slug del tag (alternativo a las categorías)' ),
        'enterprise_meanwhile_cats' => array( 'text',     __( 'Categorías "Mientras tanto" (nombres, separados por coma)', 'enterprise-moto' ), '', 'Ej: Preparativos, Mecánica, Rutas cortas. Si se especifica, tiene prioridad sobre la etiqueta.' ),
    );

    foreach ( $fields as $id => $cfg ) {
        $wp_customize->add_setting( $id, array(
            'default'           => $cfg[2],
            'sanitize_callback' => $cfg[0] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $id, array(
            'label'       => $cfg[1],
            'section'     => 'enterprise_offroute',
            'type'        => $cfg[0],
            'description' => $cfg[3],
        ) );
    }

    /* Imagen de la ruta */
    $wp_customize->add_setting( 'enterprise_next_image', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'enterprise_next_image', array(
        'label'     => __( 'Imagen / mapa de la próxima ruta', 'enterprise-moto' ),
        'section'   => 'enterprise_offroute',
        'mime_type' => 'image',
    ) ) );
}
add_action( 'customize_register', 'enterprise_customizer_offroute' );

/* ─────────────────────────────────────────
   PERSONALIZADOR — Cursor + Hero off-route
───────────────────────────────────────── */
function enterprise_customizer_extras( $wp_customize ) {

    /* ── Cursor personalizado ── */
    $wp_customize->add_section( 'enterprise_cursor_section', array(
        'title'    => __( '🖱 Cursor personalizado', 'enterprise-moto' ),
        'priority' => 200,
    ) );
    $wp_customize->add_setting( 'enterprise_custom_cursor', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'enterprise_custom_cursor', array(
        'label'       => __( 'Activar cursor personalizado', 'enterprise-moto' ),
        'description' => __( 'Punto + anillo animado. Solo visible en ordenadores con ratón.', 'enterprise-moto' ),
        'section'     => 'enterprise_cursor_section',
        'type'        => 'checkbox',
    ) );

    /* ── Hero fuera de ruta: imagen y textos ── */
    $wp_customize->add_section( 'enterprise_offroute_hero', array(
        'title'    => __( '🏍 Fuera de ruta — Hero', 'enterprise-moto' ),
        'panel'    => 'enterprise_home',
        'priority' => 85,
    ) );

    // Foto del garaje
    $wp_customize->add_setting( 'enterprise_offroute_hero_image', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'enterprise_offroute_hero_image', array(
        'label'       => __( 'Foto del garaje / moto (lado derecho del hero)', 'enterprise-moto' ),
        'description' => __( 'Si no hay foto, se muestra el emoji animado.', 'enterprise-moto' ),
        'section'     => 'enterprise_offroute_hero',
        'mime_type'   => 'image',
    ) ) );

    // Texto principal del hero
    $wp_customize->add_setting( 'enterprise_offroute_hero_text', array(
        'default'           => 'El asfalto puede esperar. Aquí van los preparativos, las rutas soñadas y todo lo que pasa en el garaje entre expedición y expedición.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'enterprise_offroute_hero_text', array(
        'label'   => __( 'Texto del hero (bajo el título principal)', 'enterprise-moto' ),
        'section' => 'enterprise_offroute_hero',
        'type'    => 'textarea',
    ) );

    // Texto sección "Mientras tanto"
    $wp_customize->add_setting( 'enterprise_offroute_meanwhile_desc', array(
        'default'           => 'Lo que pasa entre expedición y expedición: preparativos, mecánica, rutas cortas y reflexiones desde el garaje.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'enterprise_offroute_meanwhile_desc', array(
        'label'   => __( 'Descripción sección "Mientras tanto"', 'enterprise-moto' ),
        'section' => 'enterprise_offroute_hero',
        'type'    => 'textarea',
    ) );
}
add_action( 'customize_register', 'enterprise_customizer_extras' );


/* ═══════════════════════════════════════════════════════════════
   METABOX DE ETAPA EN ENTRADAS (posts)
   Campos: km, horas en moto, horas en ferry, duración total
═══════════════════════════════════════════════════════════════ */
/* ═══════════════════════════════════════════════════════════════
   METABOX DE ENTRADA — TIPO + CAMPOS CONDICIONALES  v2.3.1
   _post_tipo: etapa | viaje | jornada | generica
   Reemplaza el antiguo metabox "Datos de la ruta".
   Backward compat: sigue leyendo _route_* en enterprise_get_route_data()
═══════════════════════════════════════════════════════════════ */
function enterprise_post_stage_metabox() {
    add_meta_box(
        'enterprise-post-stage',
        __( '🏍 Tipo de entrada y datos', 'enterprise-moto' ),
        'enterprise_post_stage_render',
        'post', 'normal', 'high'
    );
}
add_action( 'add_meta_boxes', 'enterprise_post_stage_metabox' );

function enterprise_post_stage_render( $post ) {
    wp_nonce_field( 'enterprise_post_stage_nonce', 'enterprise_post_stage_nonce' );

    /* Leer todos los campos — new _post_* con fallback a _route_* */
    $tipo          = get_post_meta( $post->ID, '_post_tipo',           true ) ?: 'etapa';
    $ticker_name   = get_post_meta( $post->ID, '_post_ticker_name',    true );
    $tramo         = get_post_meta( $post->ID, '_post_tramo',          true )
                  ?: get_post_meta( $post->ID, '_route_etapa',         true );
    $km            = get_post_meta( $post->ID, '_post_km',             true )
                  ?: get_post_meta( $post->ID, '_route_km',            true );
    $horas_moto    = get_post_meta( $post->ID, '_post_horas_moto',     true );
    $horas_ferry   = get_post_meta( $post->ID, '_post_horas_ferry',    true );
    $duracion      = get_post_meta( $post->ID, '_post_duracion',       true );
    $custom_label  = get_post_meta( $post->ID, '_post_custom_label',   true )
                  ?: get_post_meta( $post->ID, '_route_custom1_label',  true );
    $custom_value  = get_post_meta( $post->ID, '_post_custom_value',   true )
                  ?: get_post_meta( $post->ID, '_route_custom1_value',  true );

    /* Tipo D — nuevos campos de filtro por ID */
    $fecha_ini      = get_post_meta( $post->ID, '_post_fecha_inicio',    true );
    $fecha_fin      = get_post_meta( $post->ID, '_post_fecha_fin',       true );
    $paises         = get_post_meta( $post->ID, '_post_paises',          true )
                   ?: get_post_meta( $post->ID, '_route_paises',         true );
    $viaje_cat_ids  = get_post_meta( $post->ID, '_post_viaje_cat_ids',   true ) ?: array();
    $viaje_tag_ids  = get_post_meta( $post->ID, '_post_viaje_tag_ids',   true ) ?: array();
    $viaje_tag_rel  = get_post_meta( $post->ID, '_post_viaje_tag_rel',   true ) ?: 'OR';
    $km_calc        = get_post_meta( $post->ID, '_post_km_calculado',    true );
    $ferry_count    = get_post_meta( $post->ID, '_post_ferry_count',     true );
    $etapas_count   = get_post_meta( $post->ID, '_post_etapas_count',    true );

    $viaje_cat_ids = is_array( $viaje_cat_ids ) ? array_map( 'intval', $viaje_cat_ids ) : array();
    $viaje_tag_ids = is_array( $viaje_tag_ids ) ? array_map( 'intval', $viaje_tag_ids ) : array();

    $all_cats = get_categories( array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
    $all_tags = get_tags(       array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );

    $s = 'display:block;font-size:11px;font-weight:700;color:#444;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;';
    $i = 'width:100%;padding:8px 10px;border:1px solid #ddd;font-size:13px;box-sizing:border-box;';
    ?>
    <style>
    .ent-mb-group { display:none; }
    .ent-mb-group.active { display:block; }
    .ent-mb-grid  { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px; }
    .ent-mb-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-top:14px; }
    .ent-mb-sep   { border:none; border-top:1px solid #eee; margin:16px 0 8px; }
    .ent-mb-calc  { background:#f9f9f9; border:1px solid #e0e0e0; padding:12px 14px; margin-top:14px; font-size:12px; line-height:1.7; }
    .ent-mb-note  { font-size:11px; color:#888; margin:6px 0 0; }
    </style>

    <!-- Selector de tipo -->
    <div style="margin-bottom:16px;">
        <label for="ent_post_tipo" style="<?php echo $s; ?>"><?php esc_html_e( 'Tipo de entrada', 'enterprise-moto' ); ?></label>
        <select id="ent_post_tipo" name="_post_tipo" style="<?php echo $i; ?>font-weight:600;">
            <option value="etapa"   <?php selected( $tipo, 'etapa'   ); ?>><?php esc_html_e( '🏍 Etapa / Salida de un día', 'enterprise-moto' ); ?></option>
            <option value="viaje"   <?php selected( $tipo, 'viaje'   ); ?>><?php esc_html_e( '📋 Viaje de varios días (a posteriori)', 'enterprise-moto' ); ?></option>
            <option value="jornada" <?php selected( $tipo, 'jornada' ); ?>><?php esc_html_e( '🚶 Jornada (sin moto)', 'enterprise-moto' ); ?></option>
            <option value="generica"<?php selected( $tipo, 'generica'); ?>><?php esc_html_e( '📝 Entrada genérica', 'enterprise-moto' ); ?></option>
        </select>
    </div>

    <!-- Nombre en el ticker (colecciones, #5 R4). Común a viaje/etapa/jornada; oculto en genérica -->
    <div class="ent-mb-ticker" style="margin-bottom:16px;<?php echo $tipo === 'generica' ? 'display:none;' : ''; ?>">
        <label for="ent_post_ticker_name" style="<?php echo $s; ?>"><?php esc_html_e( 'Nombre en el ticker', 'enterprise-moto' ); ?></label>
        <input type="text" id="ent_post_ticker_name" name="_post_ticker_name" value="<?php echo esc_attr( $ticker_name ); ?>"
               placeholder="<?php esc_attr_e( 'Ej: SICILIA 2026', 'enterprise-moto' ); ?>" style="<?php echo $i; ?>">
        <p class="ent-mb-note"><?php esc_html_e( 'Texto que aparece en el ticker de las páginas de colección de viajes. Si se deja vacío, se usa el título de la entrada.', 'enterprise-moto' ); ?></p>
    </div>

    <!-- ══ TIPO B/C: ETAPA / SALIDA DE UN DÍA ══ -->
    <div class="ent-mb-group<?php echo $tipo === 'etapa' ? ' active' : ''; ?>" data-tipo="etapa">
        <div>
            <label style="<?php echo $s; ?>"><?php esc_html_e( 'Tramo (origen → destino)', 'enterprise-moto' ); ?></label>
            <input type="text" name="_post_tramo" value="<?php echo esc_attr( $tramo ); ?>"
                   placeholder="<?php esc_attr_e( 'Ej: Tarragona → Zaragoza', 'enterprise-moto' ); ?>" style="<?php echo $i; ?>">
        </div>
        <div class="ent-mb-grid">
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_km" value="<?php echo esc_attr( $km ); ?>" placeholder="Ej: 280 km" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Horas en moto', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_horas_moto" value="<?php echo esc_attr( $horas_moto ); ?>" placeholder="Ej: 4h 30min" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Horas en ferry / barco', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_horas_ferry" value="<?php echo esc_attr( $horas_ferry ); ?>" placeholder="Ej: 22h — vacío si no hay" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Duración total del día', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_duracion" value="<?php echo esc_attr( $duracion ); ?>" placeholder="Ej: 8h 30min" style="<?php echo $i; ?>"></div>
        </div>
        <hr class="ent-mb-sep">
        <div class="ent-mb-grid">
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Dato extra — etiqueta', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_custom_label" value="<?php echo esc_attr( $custom_label ); ?>" placeholder="Ej: Altitud máxima" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Dato extra — valor', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_custom_value" value="<?php echo esc_attr( $custom_value ); ?>" placeholder="Ej: 2.250 m" style="<?php echo $i; ?>"></div>
        </div>
    </div>

    <!-- ══ TIPO D: VIAJE DE VARIOS DÍAS ══ -->
    <div class="ent-mb-group<?php echo $tipo === 'viaje' ? ' active' : ''; ?>" data-tipo="viaje">

        <div class="ent-mb-grid">
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Fecha de inicio', 'enterprise-moto' ); ?></label>
                <input type="date" name="_post_fecha_inicio" value="<?php echo esc_attr( $fecha_ini ); ?>" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Fecha de fin', 'enterprise-moto' ); ?></label>
                <input type="date" name="_post_fecha_fin" value="<?php echo esc_attr( $fecha_fin ); ?>" style="<?php echo $i; ?>"></div>
        </div>

        <div style="margin-top:14px;">
            <label style="<?php echo $s; ?>"><?php esc_html_e( 'Países recorridos', 'enterprise-moto' ); ?></label>
            <input type="text" name="_post_paises" value="<?php echo esc_attr( $paises ); ?>"
                   placeholder="Ej: España · Francia · Italia" style="<?php echo $i; ?>">
        </div>

        <hr class="ent-mb-sep">
        <p style="font-size:12px;color:#555;margin:0 0 12px;">
            <strong><?php esc_html_e( 'Filtros para calcular estadísticas', 'enterprise-moto' ); ?></strong><br>
            <?php esc_html_e( 'Usa los mismos filtros que en el bloque "Etapas de ruta" dentro de esta entrada. Al guardar se calculan km totales, ferrys y número de etapas.', 'enterprise-moto' ); ?>
        </p>

        <?php
        // ── Categorías (OR entre seleccionadas) ────────────────────────
        echo '<div style="margin-bottom:14px;">';
        echo '<label style="' . $s . '">' . esc_html__( 'Categorías de las etapas (OR entre seleccionadas)', 'enterprise-moto' ) . '</label>';
        echo '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;padding:8px;background:#fafafa;">';
        $render_cats_d = function( $cats, $parent_id = 0, $depth = 0 ) use ( &$render_cats_d, $viaje_cat_ids ) {
            foreach ( $cats as $cat ) {
                if ( $cat->parent !== $parent_id ) continue;
                $indent  = str_repeat( '&nbsp;&nbsp;&nbsp;', $depth );
                $prefix  = $depth > 0 ? '└ ' : '';
                $checked = in_array( $cat->term_id, $viaje_cat_ids, true ) ? 'checked' : '';
                echo '<label style="display:block;font-size:12px;margin-bottom:5px;white-space:nowrap;">';
                echo '<input type="checkbox" name="_post_viaje_cat_ids[]" value="' . esc_attr( $cat->term_id ) . '" ' . $checked . '> ';
                echo $indent . $prefix . esc_html( $cat->name ) . ' <span style="color:#aaa;">(' . intval( $cat->count ) . ')</span>';
                echo '</label>';
                $render_cats_d( $cats, $cat->term_id, $depth + 1 );
            }
        };
        $render_cats_d( $all_cats, 0, 0 );
        echo '</div></div>';

        // ── Etiquetas (AND/OR) ─────────────────────────────────────────
        echo '<div style="margin-bottom:14px;">';
        echo '<div style="display:flex;align-items:center;gap:16px;margin-bottom:6px;">';
        echo '<label style="' . $s . 'margin:0;">' . esc_html__( 'Etiquetas de las etapas', 'enterprise-moto' ) . '</label>';
        echo '<span style="font-size:11px;color:#555;">' . esc_html__( 'Relación:', 'enterprise-moto' ) . '</span>';
        echo '<label style="font-size:12px;"><input type="radio" name="_post_viaje_tag_rel" value="OR" ' . checked( $viaje_tag_rel, 'OR', false ) . '> OR</label>';
        echo '<label style="font-size:12px;"><input type="radio" name="_post_viaje_tag_rel" value="AND" ' . checked( $viaje_tag_rel, 'AND', false ) . '> AND</label>';
        echo '</div>';
        echo '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;padding:8px;background:#fafafa;">';
        foreach ( $all_tags as $tag ) {
            $checked = in_array( $tag->term_id, $viaje_tag_ids, true ) ? 'checked' : '';
            echo '<label style="display:block;font-size:12px;margin-bottom:5px;white-space:nowrap;">';
            echo '<input type="checkbox" name="_post_viaje_tag_ids[]" value="' . esc_attr( $tag->term_id ) . '" ' . $checked . '> ';
            echo esc_html( $tag->name ) . ' <span style="color:#aaa;">(' . intval( $tag->count ) . ')</span>';
            echo '</label>';
        }
        echo '</div></div>';
        ?>

        <?php if ( $etapas_count || $km_calc ) : ?>
        <div class="ent-mb-calc">
            <strong><?php esc_html_e( 'Estadísticas calculadas al último guardado:', 'enterprise-moto' ); ?></strong><br>
            <?php if ( $etapas_count ) echo intval( $etapas_count ) . ' ' . esc_html__( 'etapas', 'enterprise-moto' ); ?>
            <?php if ( $ferry_count ) echo ' · ' . intval( $ferry_count ) . ' ' . esc_html__( 'con ferry', 'enterprise-moto' ); ?>
            <?php if ( $km_calc ) echo '<br>' . esc_html( $km_calc ) . ' km' . ( get_post_meta( $post->ID, '_post_km_incompleto', true ) ? ' <em>(≈ incompleto)</em>' : '' ); ?>
            <br><span style="color:#999;font-size:10px;"><?php esc_html_e( 'Guarda la entrada para recalcular.', 'enterprise-moto' ); ?></span>
        </div>
        <?php endif; ?>

        <hr class="ent-mb-sep">
        <div class="ent-mb-grid">
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Dato extra — etiqueta', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_custom_label" value="<?php echo esc_attr( $custom_label ); ?>" placeholder="Ej: Ferry" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Dato extra — valor', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_custom_value" value="<?php echo esc_attr( $custom_value ); ?>" placeholder="Ej: Grimaldi Lines" style="<?php echo $i; ?>"></div>
        </div>
    </div>

    <!-- ══ TIPO A: JORNADA ══ -->
    <div class="ent-mb-group<?php echo $tipo === 'jornada' ? ' active' : ''; ?>" data-tipo="jornada">
        <p class="ent-mb-note"><?php esc_html_e( 'Día sin moto (visita, descanso, actividad). Sin datos numéricos de ruta.', 'enterprise-moto' ); ?></p>
        <div class="ent-mb-grid" style="margin-top:12px;">
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Dato extra — etiqueta', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_custom_label" value="<?php echo esc_attr( $custom_label ); ?>" placeholder="Ej: Ciudad visitada" style="<?php echo $i; ?>"></div>
            <div><label style="<?php echo $s; ?>"><?php esc_html_e( 'Dato extra — valor', 'enterprise-moto' ); ?></label>
                <input type="text" name="_post_custom_value" value="<?php echo esc_attr( $custom_value ); ?>" placeholder="Ej: Palermo" style="<?php echo $i; ?>"></div>
        </div>
    </div>

    <!-- ══ TIPO E: GENÉRICA ══ -->
    <div class="ent-mb-group<?php echo $tipo === 'generica' ? ' active' : ''; ?>" data-tipo="generica">
        <p class="ent-mb-note"><?php esc_html_e( 'Entrada de contenido libre (preparativos, equipación, reflexiones...). Sin campos de ruta.', 'enterprise-moto' ); ?></p>
    </div>
    <?php
}

/* ─────────────────────────────────────────
   ESTADÍSTICAS DEL CUADERNO (EN CALIENTE) — R1 de #4
   Fuente única para todos los consumidores (barra lateral, hero,
   tarjeta del grid, cabecera agregada, listas «otras»). Calcula en
   caliente porque las etapas de un cuaderno cambian sin re-guardar la
   página; por eso NO se cachea al guardar (a diferencia del viaje tipo D).
   Replica la query _filt_* y el parseo entero de km ya usados en
   page-cuaderno-de-bitacora.php. Devuelve el km SIN unidad forzada: el
   pintado añade «km» con enterprise_km_display().
───────────────────────────────────────── */
function enterprise_cuaderno_stats( $page_id ) {
    $page_id = intval( $page_id );

    /* Estado canónico: _exp_estado; fallback al legacy _exp_en_ruta solo
       si _exp_estado está vacío (mismo criterio que la plantilla). */
    $en_ruta = get_post_meta( $page_id, '_exp_en_ruta', true );
    $estado  = get_post_meta( $page_id, '_exp_estado', true );
    if ( $estado === '' ) {
        $estado = ( $en_ruta === '1' ) ? 'activo' : 'finalizado';
    }

    /* Query de etapas por filtros _filt_* — idéntica a la del template. */
    $filt_cat_ids   = get_post_meta( $page_id, '_filt_category_ids', true ) ?: array();
    $filt_tag_ids   = get_post_meta( $page_id, '_filt_tag_ids',      true ) ?: array();
    $filt_tag_rel   = get_post_meta( $page_id, '_filt_tag_relation', true ) ?: 'OR';
    $filt_date_from = get_post_meta( $page_id, '_filt_date_from',    true ) ?: '';
    $filt_date_to   = get_post_meta( $page_id, '_filt_date_to',      true ) ?: '';
    $filt_limit     = get_post_meta( $page_id, '_filt_limit',        true );
    $filt_orderby   = get_post_meta( $page_id, '_filt_orderby',      true ) ?: 'date';
    $filt_order     = get_post_meta( $page_id, '_filt_order',        true ) ?: 'DESC';

    $filt_cat_ids = is_array( $filt_cat_ids ) ? array_map( 'intval', $filt_cat_ids ) : array();
    $filt_tag_ids = is_array( $filt_tag_ids ) ? array_map( 'intval', $filt_tag_ids ) : array();

    $query_args = array(
        'post_type'              => 'post',
        'posts_per_page'         => ( $filt_limit !== '' && intval( $filt_limit ) > 0 ) ? intval( $filt_limit ) : -1,
        'orderby'                => $filt_orderby,
        'order'                  => strtoupper( $filt_order ),
        'post_status'            => 'publish',
        // Rendimiento (obligatorio): cebar la meta cache en bloque para que la
        // suma de _route_km NO dispare un get_post_meta por etapa. Con esto el
        // coste es ~constante respecto al número de etapas.
        'update_post_meta_cache' => true,
    );

    $tax_query = array();
    if ( ! empty( $filt_cat_ids ) ) {
        $tax_query[] = array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $filt_cat_ids,
            'operator' => 'IN',
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
        $tax_query['relation'] = 'AND';
        $query_args['tax_query'] = $tax_query;
    }

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

    $q        = new WP_Query( $query_args );
    $etapas   = $q->posts;
    $n_etapas = (int) $q->found_posts;

    /* km: el override manual _exp_km gana (tal cual, incluidos valores curados
       como «~3.200 km»); si está vacío, suma en caliente de _route_km con el
       mismo parseo entero del template. Sin forzar unidad. */
    $exp_km = get_post_meta( $page_id, '_exp_km', true );
    if ( $exp_km !== '' && $exp_km !== false ) {
        $km = (string) $exp_km;
    } else {
        $km_total = 0;
        foreach ( $etapas as $etapa ) {
            $km_num = preg_replace( '/[^0-9]/', '', (string) get_post_meta( $etapa->ID, '_route_km', true ) );
            if ( is_numeric( $km_num ) ) {
                $km_total += intval( $km_num );
            }
        }
        $km = ( $km_total > 0 ) ? number_format( $km_total, 0, ',', '.' ) : '';
    }

    /* Fechas resueltas. R5: cuaderno 'finalizado' heredado sin _exp_fecha_fin →
       fin = fecha de la etapa más reciente (la primera de la query en el orden
       actual). Nunca se usa time() como «fin en curso» (esa semántica se retira). */
    $fecha_inicio = get_post_meta( $page_id, '_exp_fecha_inicio', true ) ?: '';
    $fecha_fin    = get_post_meta( $page_id, '_exp_fecha_fin',    true ) ?: '';
    $fin_heredada = false;
    if ( $fecha_fin === '' && $estado === 'finalizado' && ! empty( $etapas ) ) {
        $fecha_fin    = get_the_date( 'Y-m-d', $etapas[0] );
        $fin_heredada = true;
    }

    /* Días. Guardas: sin inicio no se calcula nada; dias_totales solo si hay fin
       resoluble; toda división aguas abajo debe comprobar dias_totales > 0. */
    $dias_totales       = 0;
    $dias_transcurridos = 0;
    if ( $fecha_inicio ) {
        $ts_inicio = strtotime( $fecha_inicio );
        if ( $fecha_fin ) {
            $ts_fin = strtotime( $fecha_fin );
            if ( $ts_fin >= $ts_inicio ) {
                $dias_totales = max( 1, (int) round( ( $ts_fin - $ts_inicio ) / DAY_IN_SECONDS ) + 1 );
            }
        }
        $ts_hoy = current_time( 'timestamp' );
        if ( $ts_hoy >= $ts_inicio ) {
            $dias_transcurridos = max( 1, (int) round( ( $ts_hoy - $ts_inicio ) / DAY_IN_SECONDS ) + 1 );
        }
    }

    return array(
        'estado'             => $estado,
        'km'                 => $km,                 // sin unidad; pintar con enterprise_km_display()
        'etapas'             => $n_etapas,
        'dias_totales'       => $dias_totales,       // 0 si no hay fin resoluble
        'dias_transcurridos' => $dias_transcurridos, // 0 si aún no ha empezado o sin inicio
        'fecha_inicio'       => $fecha_inicio,
        'fecha_fin'          => $fecha_fin,          // resuelta (puede venir de R5)
        'fin_heredada'       => $fin_heredada,       // true si la fin salió de la última etapa
    );
}

/* ─────────────────────────────────────────
   CALCULAR ESTADÍSTICAS DEL VIAJE (TIPO D)
   Usa los mismos filtros que los bloques Timeline/Carrusel
───────────────────────────────────────── */
function enterprise_calculate_viaje_stats( $post_id ) {
    $cat_ids   = get_post_meta( $post_id, '_post_viaje_cat_ids',  true ) ?: array();
    $tag_ids   = get_post_meta( $post_id, '_post_viaje_tag_ids',  true ) ?: array();
    $tag_rel   = get_post_meta( $post_id, '_post_viaje_tag_rel',  true ) ?: 'OR';
    $fecha_ini = get_post_meta( $post_id, '_post_fecha_inicio',   true );
    $fecha_fin = get_post_meta( $post_id, '_post_fecha_fin',      true );

    $cat_ids = is_array( $cat_ids ) ? array_map( 'intval', $cat_ids ) : array();
    $tag_ids = is_array( $tag_ids ) ? array_map( 'intval', $tag_ids ) : array();

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array( array( 'key' => '_post_tipo', 'value' => 'etapa' ) ),
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
        $args['tax_query'] = $tax_q;
    }

    if ( $fecha_ini ) {
        $dq = array( 'relation' => 'AND', array( 'after' => $fecha_ini . ' 00:00:00', 'inclusive' => true ) );
        if ( $fecha_fin ) $dq[] = array( 'before' => $fecha_fin . ' 23:59:59', 'inclusive' => true );
        $args['date_query'] = $dq;
    }

    $ids      = get_posts( $args );
    $km_total = 0; $km_inc = false; $ferry = 0;
    foreach ( $ids as $eid ) {
        $km = get_post_meta( $eid, '_post_km', true );
        if ( $km ) {
            $km_total += floatval( preg_replace( '/[^0-9.,]/', '', str_replace( ',', '.', $km ) ) );
        } else { $km_inc = true; }
        if ( get_post_meta( $eid, '_post_horas_ferry', true ) ) $ferry++;
    }
    return array(
        'km_total'      => $km_total > 0 ? number_format( $km_total, 0, ',', '.' ) : '',
        'km_incompleto' => $km_inc,
        'ferry_count'   => $ferry,
        'etapas_count'  => count( $ids ),
    );
}

/* ─────────────────────────────────────────
   GUARDAR METABOX DE TIPO DE ENTRADA
───────────────────────────────────────── */
function enterprise_post_stage_save( $post_id ) {
    if ( ! isset( $_POST['enterprise_post_stage_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enterprise_post_stage_nonce'], 'enterprise_post_stage_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $tipo = in_array( $_POST['_post_tipo'] ?? '', array( 'etapa', 'viaje', 'jornada', 'generica' ), true )
        ? $_POST['_post_tipo'] : 'etapa';
    update_post_meta( $post_id, '_post_tipo', $tipo );

    /* Campos de etapa (B/C) */
    foreach ( array( '_post_tramo', '_post_km', '_post_horas_moto', '_post_horas_ferry', '_post_duracion' ) as $f ) {
        update_post_meta( $post_id, $f, sanitize_text_field( $_POST[ $f ] ?? '' ) );
        /* Sincronizar _route_* para backward compat con single.php */
        if ( $f === '_post_km'    ) update_post_meta( $post_id, '_route_km',    sanitize_text_field( $_POST[ $f ] ?? '' ) );
        if ( $f === '_post_tramo' ) update_post_meta( $post_id, '_route_etapa', sanitize_text_field( $_POST[ $f ] ?? '' ) );
    }

    /* Campos comunes (dato extra) */
    update_post_meta( $post_id, '_post_custom_label', sanitize_text_field( $_POST['_post_custom_label'] ?? '' ) );
    update_post_meta( $post_id, '_post_custom_value', sanitize_text_field( $_POST['_post_custom_value'] ?? '' ) );
    /* Sync _route_custom1_* */
    update_post_meta( $post_id, '_route_custom1_label', sanitize_text_field( $_POST['_post_custom_label'] ?? '' ) );
    update_post_meta( $post_id, '_route_custom1_value', sanitize_text_field( $_POST['_post_custom_value'] ?? '' ) );

    /* Nombre en el ticker de colecciones (#5 R4) — solo presentación, sin transformación */
    update_post_meta( $post_id, '_post_ticker_name', sanitize_text_field( $_POST['_post_ticker_name'] ?? '' ) );

    /* Campos de viaje (D) */
    update_post_meta( $post_id, '_post_paises',           sanitize_text_field( $_POST['_post_paises']           ?? '' ) );
    update_post_meta( $post_id, '_post_paises', sanitize_text_field( $_POST['_post_paises'] ?? '' ) );

    // Nuevos filtros tipo D por ID (checkboxes)
    $viaje_cat_ids = isset( $_POST['_post_viaje_cat_ids'] ) && is_array( $_POST['_post_viaje_cat_ids'] )
        ? array_map( 'intval', $_POST['_post_viaje_cat_ids'] ) : array();
    update_post_meta( $post_id, '_post_viaje_cat_ids', $viaje_cat_ids );

    $viaje_tag_ids = isset( $_POST['_post_viaje_tag_ids'] ) && is_array( $_POST['_post_viaje_tag_ids'] )
        ? array_map( 'intval', $_POST['_post_viaje_tag_ids'] ) : array();
    update_post_meta( $post_id, '_post_viaje_tag_ids', $viaje_tag_ids );

    $viaje_tag_rel = ( isset( $_POST['_post_viaje_tag_rel'] ) && $_POST['_post_viaje_tag_rel'] === 'AND' ) ? 'AND' : 'OR';
    update_post_meta( $post_id, '_post_viaje_tag_rel', $viaje_tag_rel );
    foreach ( array( '_post_fecha_inicio', '_post_fecha_fin' ) as $f ) {
        $v = sanitize_text_field( $_POST[ $f ] ?? '' );
        if ( $v === '' || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) update_post_meta( $post_id, $f, $v );
    }

    /* Calcular y cachear estadísticas si es tipo D */
    if ( $tipo === 'viaje' ) {
        $stats = enterprise_calculate_viaje_stats( $post_id );
        update_post_meta( $post_id, '_post_km_calculado',  $stats['km_total'] );
        update_post_meta( $post_id, '_post_km_incompleto', $stats['km_incompleto'] ? '1' : '' );
        update_post_meta( $post_id, '_post_ferry_count',   $stats['ferry_count'] );
        update_post_meta( $post_id, '_post_etapas_count',  $stats['etapas_count'] );
        /* Sync _route_* para backward compat */
        update_post_meta( $post_id, '_route_km',    $stats['km_total'] );
        update_post_meta( $post_id, '_route_ferrys', $stats['ferry_count'] );
        $paises = sanitize_text_field( $_POST['_post_paises'] ?? '' );
        update_post_meta( $post_id, '_route_paises', $paises );
        /* Días de ruta desde fechas */
        $fi = get_post_meta( $post_id, '_post_fecha_inicio', true );
        $ff = get_post_meta( $post_id, '_post_fecha_fin',    true );
        if ( $fi ) {
            $dias = max( 1, round( ( ( $ff ? strtotime( $ff ) : time() ) - strtotime( $fi ) ) / DAY_IN_SECONDS ) + 1 );
            update_post_meta( $post_id, '_route_dias', $dias );
        }
    }
}
add_action( 'save_post', 'enterprise_post_stage_save' );


/* ─────────────────────────────────────────
   CREAR CATEGORÍAS DEL CUADERNO
   cuaderno-etapa y cuaderno-jornada se crean
   automáticamente si no existen.
───────────────────────────────────────── */
function enterprise_ensure_cuaderno_terms() {
    $terms = array(
        array( 'name' => 'Cuaderno-etapa',   'slug' => 'cuaderno-etapa'   ),
        array( 'name' => 'Cuaderno-jornada', 'slug' => 'cuaderno-jornada' ),
    );
    foreach ( $terms as $term ) {
        if ( ! term_exists( $term['slug'], 'category' ) ) {
            wp_insert_term( $term['name'], 'category', array( 'slug' => $term['slug'] ) );
        }
    }
}
add_action( 'init', 'enterprise_ensure_cuaderno_terms' );

/* ═══════════════════════════════════════════════════════════════
   TEMPLATE: CUADERNO DE BITÁCORA — ESTADO FUERA DE RUTA
═══════════════════════════════════════════════════════════════ */
function enterprise_render_off_route( $page_id, $exp_nombre ) {

    /* ── Datos de la próxima expedición desde el Personalizador ── */
    $next_title    = get_theme_mod( 'enterprise_next_title',     '' );
    $next_subtitle = get_theme_mod( 'enterprise_next_subtitle',  '' );
    $next_date     = get_theme_mod( 'enterprise_next_date',      '' ); // YYYY-MM-DD
    $next_countries= get_theme_mod( 'enterprise_next_countries', '' );
    $next_days     = get_theme_mod( 'enterprise_next_days',      '' );
    $next_km       = get_theme_mod( 'enterprise_next_km',        '' );
    $next_desc     = get_theme_mod( 'enterprise_next_desc',      '' );
    $next_img_id   = get_theme_mod( 'enterprise_next_image',     '' );
    $meanwhile_tag  = get_theme_mod( 'enterprise_next_tag',        'mientras-tanto' );
    $meanwhile_cats = get_theme_mod( 'enterprise_meanwhile_cats',  '' );

    /* ── Stats acumuladas: contar todas las expediciones pasadas ── */
    $exp_pages  = get_posts( array(
        'post_type'   => 'page',
        'post_parent' => $page_id,
        'post_status' => 'publish',
        'meta_key'    => '_exp_estado',
        'meta_value'  => 'finalizado',
        'numberposts' => -1,
        'fields'      => 'ids',
    ) );
    $total_exps = count( $exp_pages );
    $total_km   = 0;
    $all_paises = array();
    foreach ( $exp_pages as $eid ) {
        $km = enterprise_cuaderno_stats( $eid )['km']; // #4 R2: coherente con las tarjetas (override manual o suma en caliente)
        if ( $km ) $total_km += intval( preg_replace( '/[^0-9]/', '', $km ) );
        $p = get_post_meta( $eid, '_exp_paises', true );
        if ( $p ) foreach ( explode( '·', $p ) as $pa ) $all_paises[] = trim( $pa );
    }
    $total_paises = count( array_unique( array_filter( $all_paises ) ) ) ?: 4;
    $total_km_fmt = $total_km ? number_format( $total_km, 0, ',', '.' ) . ' km' : '—';

    /* ── Posts "Mientras tanto" ─────────────────────────────────────
       Prioridad: categorías > etiqueta > sin filtro (últimos posts)  */
    $meanwhile_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $meanwhile_cats ) {
        /* Categorías por nombre (Personalizador) */
        $cat_ids = array();
        foreach ( array_filter( array_map( 'trim', explode( ',', $meanwhile_cats ) ) ) as $cat_name ) {
            $t = get_term_by( 'name', $cat_name, 'category' )
              ?: get_term_by( 'slug', sanitize_title( $cat_name ), 'category' );
            if ( $t && ! is_wp_error( $t ) ) $cat_ids[] = $t->term_id;
        }
        if ( ! empty( $cat_ids ) ) $meanwhile_args['category__in'] = $cat_ids;
    } elseif ( $meanwhile_tag ) {
        /* Fallback: etiqueta */
        $mt_term = get_term_by( 'name', $meanwhile_tag, 'post_tag' )
                ?: get_term_by( 'slug', $meanwhile_tag, 'post_tag' );
        if ( $mt_term ) $meanwhile_args['tag_id'] = $mt_term->term_id;
    }
    $meanwhile_posts = get_posts( $meanwhile_args );

    /* ── Expediciones pasadas: páginas hijas del portal con estado finalizado ── */
    $past_exps = get_posts( array(
        'post_type'   => 'page',
        'post_parent' => $page_id,   /* hijas del portal */
        'post_status' => 'publish',
        'numberposts' => 3,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_query'  => array( array(
            'key'   => '_exp_estado',
            'value' => 'finalizado',
        ) ),
    ) );

    /* ── Countdown JS — validar formato YYYY-MM-DD ── */
    $ts = $next_date ? strtotime( $next_date ) : false;
    $countdown_js = ( $ts && $ts > time() )
        ? 'var __nextDate = new Date(' . intval($ts) . '000);'  /* timestamp ms, sin problemas de parsing */
        : 'var __nextDate = null;';
    ?>

<!-- ════════════════════════════════════════
     CUADERNO DE BITÁCORA · FUERA DE RUTA
════════════════════════════════════════ -->
<style>
.off-route-hero{display:grid;grid-template-columns:1fr 1fr;min-height:calc(100vh - 64px);}
.or-left{padding:72px 60px 72px;display:flex;flex-direction:column;justify-content:flex-end;background:var(--black);}
.or-right{background:var(--surface);position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.or-kicker{font-size:10px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.or-kicker::before{content:'';width:28px;height:1px;background:var(--gold);}
.or-title{font-family:var(--font-display);font-size:clamp(64px,8vw,110px);line-height:.92;letter-spacing:.03em;margin-bottom:20px;}
.or-title .ol{-webkit-text-stroke:1px rgba(245,244,239,.35);color:transparent;}
.or-title .gd{color:var(--gold);}
.or-sub{font-size:15px;font-weight:300;color:rgba(245,244,239,.55);line-height:1.75;margin-bottom:36px;max-width:460px;}
.or-stats{display:flex;gap:32px;padding:28px 0;border-top:1px solid var(--border);margin-bottom:32px;}
.or-stat-n{font-family:var(--font-display);font-size:40px;letter-spacing:.04em;line-height:1;color:var(--white);}
.or-stat-n .u{font-size:18px;color:var(--gold);}
.or-stat-l{font-size:9px;letter-spacing:.15em;text-transform:uppercase;color:var(--mid);margin-top:4px;}
.or-actions{display:flex;gap:14px;flex-wrap:wrap;}
.or-visual{text-align:center;}
.or-moto{font-size:100px;opacity:.12;display:block;animation:or-breathe 4s ease-in-out infinite;}
@keyframes or-breathe{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.or-grid{position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(rgba(242,193,24,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(242,193,24,.03) 1px,transparent 1px);background-size:56px 56px;}
.or-bignumber{position:absolute;right:24px;bottom:24px;font-family:var(--font-display);font-size:200px;line-height:1;color:rgba(242,193,24,.04);pointer-events:none;user-select:none;}

/* Próxima expedición */
.next-exp{padding:88px 60px;display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:start;border-bottom:1px solid var(--border);}
.countdown-box{display:flex;gap:0;background:var(--surface);border:1px solid var(--border);width:fit-content;margin-top:32px;}
.cd-unit{text-align:center;padding:20px 24px;border-right:1px solid var(--border);}
.cd-unit:last-child{border-right:none;}
.cd-n{font-family:var(--font-display);font-size:44px;color:var(--gold);line-height:1;display:block;}
.cd-l{font-size:9px;letter-spacing:.15em;text-transform:uppercase;color:var(--mid);display:block;margin-top:4px;}
.next-details{display:flex;gap:20px;flex-wrap:wrap;margin:20px 0;}
.next-detail{font-size:12px;color:var(--mid);display:flex;align-items:center;gap:6px;}
.next-img{width:100%;aspect-ratio:4/3;object-fit:cover;border:1px solid var(--border);}
.next-img-placeholder{width:100%;aspect-ratio:4/3;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--border);}

/* Mientras tanto */
.meanwhile-section{padding:88px 60px;border-bottom:1px solid var(--border);}
.section-head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:48px;}
.section-htitle{font-family:var(--font-display);font-size:clamp(36px,5vw,60px);letter-spacing:.06em;}


.mt-thumb{background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:40px;opacity:.4;flex:1;min-height:160px;position:relative;overflow:hidden;}
.mt-card--feat .mt-thumb{min-height:300px;}
.mt-body{padding:22px 26px;border-top:1px solid var(--border);}
.mt-tag{font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);display:block;margin-bottom:8px;}
.mt-title{font-family:'DM Serif Display',serif;font-size:20px;line-height:1.25;margin-bottom:8px;}
.mt-card--feat .mt-title{font-size:26px;}
.mt-excerpt{font-size:12px;font-weight:300;color:rgba(245,244,239,.5);line-height:1.6;margin-bottom:14px;}
.mt-meta{display:flex;align-items:center;justify-content:space-between;font-size:10px;color:var(--mid);}
.mt-arrow{color:var(--gold);font-size:16px;transition:transform .2s;}
.mt-card:hover .mt-arrow{transform:translateX(4px);}
.mt-no-posts{padding:48px;text-align:center;color:var(--mid);font-size:14px;background:var(--surface);border:1px dashed var(--border);}

/* Pasadas */
.past-section{padding:88px 60px;}
.past-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#2a2a2a;margin-top:48px;}
.past-card{background:#0e0e0e;padding:32px;position:relative;overflow:hidden;text-decoration:none;color:#f5f4ef;min-height:260px;display:flex;flex-direction:column;transition:background .2s;}
.past-card:hover{background:#1a1a1a;}
.past-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:#2a2a2a;transition:background .2s;}
.past-card:hover::after{background:#f2c118;}
.past-decnum{position:absolute;right:20px;bottom:12px;font-family:var(--font-display);font-size:180px;line-height:1;color:rgba(242,193,24,.04);letter-spacing:-.03em;pointer-events:none;}
.past-year{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:#f2c118;margin-bottom:14px;}
.past-name{font-family:var(--font-display);font-size:34px;letter-spacing:.04em;line-height:1.05;margin-bottom:10px;color:#f5f4ef;}
.past-route{font-size:11px;font-weight:300;color:rgba(245,244,239,.55);flex:1;padding-bottom:20px;}
.past-stats{display:flex;gap:18px;padding-top:18px;border-top:1px solid #2a2a2a;}
.ps-n{font-family:var(--font-display);font-size:20px;line-height:1;color:#f5f4ef;}
.ps-l{font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:rgba(245,244,239,.45);}

@media(max-width:768px){
  .off-route-hero,.next-exp,.mt-grid,.past-grid{grid-template-columns:1fr;}
  .or-left{padding:48px 24px;}
  .next-exp,.meanwhile-section,.past-section{padding:60px 24px;}
  .mt-card--feat{grid-row:auto;}
  .or-bignumber{display:none;}
}
</style>

<!-- HERO -->
<section class="off-route-hero">
  <div class="or-left">
    <div class="or-kicker"><?php esc_html_e( 'Cuaderno de bitácora', 'enterprise-moto' ); ?></div>

    <h1 class="or-title">
      <span class="ol"><?php esc_html_e( 'LA MOTO', 'enterprise-moto' ); ?></span><br>
      <span class="gd"><?php esc_html_e( 'ESPERA', 'enterprise-moto' ); ?></span><br>
      <span><?php esc_html_e( 'PRÓXIMA', 'enterprise-moto' ); ?></span><br>
      <span class="ol"><?php esc_html_e( 'AVENTURA', 'enterprise-moto' ); ?></span>
    </h1>

    <p class="or-sub">
      <?php echo esc_html( get_theme_mod( 'enterprise_offroute_hero_text',
        __( 'El asfalto puede esperar. Aquí van los preparativos, las rutas soñadas y todo lo que pasa en el garaje entre expedición y expedición.', 'enterprise-moto' )
      ) ); ?>
    </p>

    <div class="or-stats">
      <div>
        <div class="or-stat-n"><?php echo intval($total_exps); ?> <span class="u"><?php esc_html_e('exp','enterprise-moto');?></span></div>
        <div class="or-stat-l"><?php esc_html_e( 'Expediciones completadas', 'enterprise-moto' ); ?></div>
      </div>
      <div>
        <div class="or-stat-n"><?php echo esc_html($total_km_fmt); ?></div>
        <div class="or-stat-l"><?php esc_html_e( 'Kilómetros acumulados', 'enterprise-moto' ); ?></div>
      </div>
      <div>
        <div class="or-stat-n"><?php echo intval($total_paises); ?></div>
        <div class="or-stat-l"><?php esc_html_e( 'Países visitados', 'enterprise-moto' ); ?></div>
      </div>
    </div>

    <div class="or-actions">
      <?php if ( $next_title ) : ?>
      <a href="#proxima-exp" class="btn btn--gold"><?php esc_html_e( 'Ver próxima ruta', 'enterprise-moto' ); ?> →</a>
      <?php endif; ?>
      <a href="#mientras-tanto" class="btn btn--dark"><?php esc_html_e( 'Mientras tanto', 'enterprise-moto' ); ?> ↓</a>
    </div>
  </div>

  <div class="or-right">
    <div class="or-grid"></div>
    <?php $hero_img_id = get_theme_mod( 'enterprise_offroute_hero_image', '' ); ?>
    <div class="or-visual" style="<?php echo $hero_img_id ? 'width:100%;height:100%;' : ''; ?>">
      <?php if ( $hero_img_id ) :
        echo wp_get_attachment_image( intval($hero_img_id), 'large', false, array(
          'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.7;'
        ) );
      else : ?>
      <span class="or-moto">🏍️</span>
      <?php endif; ?>
      <p style="font-family:var(--font-display);font-size:11px;letter-spacing:.3em;text-transform:uppercase;color:var(--border);text-align:center;margin-top:16px;">
        GARAJE · EN ESPERA<br>
        <?php if ( $next_date ) echo '<span style="opacity:.4">— ' . esc_html( date_i18n( 'F Y', strtotime($next_date) ) ) . ' —</span>'; ?>
      </p>
    </div>
    <div class="or-bignumber"><?php echo esc_html( date('y') ); ?></div>
  </div>
</section>

<?php if ( $next_title ) : ?>
<!-- PRÓXIMA EXPEDICIÓN -->
<section class="next-exp" id="proxima-exp">
  <div>
    <div class="section-label" style="font-size:10px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:20px;display:flex;align-items:center;gap:10px;">
      <?php esc_html_e( 'Próxima expedición', 'enterprise-moto' ); ?>
      <span style="display:block;width:40px;height:1px;background:var(--border);"></span>
    </div>
    <h2 style="font-family:var(--font-display);font-size:clamp(44px,6vw,80px);line-height:.94;letter-spacing:.04em;margin-bottom:20px;">
      <?php echo nl2br( esc_html( strtoupper( $next_title ) ) ); ?>
    </h2>
    <?php if ( $next_subtitle ) : ?>
    <p style="font-family:'DM Serif Display',serif;font-style:italic;font-size:17px;color:var(--mid);margin-bottom:16px;line-height:1.5;">
      <?php echo esc_html( $next_subtitle ); ?>
    </p>
    <?php endif; ?>
    <?php if ( $next_desc ) : ?>
    <p style="font-size:14px;font-weight:300;line-height:1.8;color:rgba(245,244,239,.6);margin-bottom:16px;">
      <?php echo esc_html( $next_desc ); ?>
    </p>
    <?php endif; ?>
    <div class="next-details">
      <?php if ( $next_date )     echo '<span class="next-detail">📅 ' . esc_html( date_i18n( 'j F Y', strtotime($next_date) ) ) . '</span>'; ?>
      <?php if ( $next_countries ) echo '<span class="next-detail">🗺️ ' . esc_html( $next_countries ) . '</span>'; ?>
      <?php if ( $next_days )     echo '<span class="next-detail">⏱️ ' . esc_html( $next_days ) . '</span>'; ?>
      <?php if ( $next_km )       echo '<span class="next-detail">🏁 ' . esc_html( $next_km ) . '</span>'; ?>
    </div>

    <?php if ( $next_date ) : ?>
    <div class="countdown-box">
      <div class="cd-unit"><span class="cd-n" id="cd-d">—</span><span class="cd-l"><?php esc_html_e( 'Días', 'enterprise-moto' ); ?></span></div>
      <div class="cd-unit"><span class="cd-n" id="cd-h">—</span><span class="cd-l"><?php esc_html_e( 'Horas', 'enterprise-moto' ); ?></span></div>
      <div class="cd-unit"><span class="cd-n" id="cd-m">—</span><span class="cd-l"><?php esc_html_e( 'Minutos', 'enterprise-moto' ); ?></span></div>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if ( $next_img_id ) : ?>
      <?php echo wp_get_attachment_image( intval($next_img_id), 'large', false, array( 'class' => 'next-img' ) ); ?>
    <?php else : ?>
      <div class="next-img-placeholder">
        <span style="font-size:48px;opacity:.2;">🗺️</span>
        <span style="font-size:11px;letter-spacing:.15em;text-transform:uppercase;color:var(--border);">
          <?php esc_html_e( 'Añade la imagen de la ruta en el Personalizador', 'enterprise-moto' ); ?>
        </span>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- MIENTRAS TANTO -->
<section class="meanwhile-section" id="mientras-tanto">
  <div class="section-head">
    <h2 class="section-htitle">
      <?php esc_html_e( 'MIENTRAS', 'enterprise-moto' ); ?><br>
      <span style="-webkit-text-stroke:1px rgba(255,255,255,.25);color:transparent;"><?php esc_html_e( 'TANTO', 'enterprise-moto' ); ?></span>
    </h2>
    <p style="font-size:12px;color:var(--mid);max-width:260px;text-align:right;line-height:1.7;">
      <?php echo esc_html( get_theme_mod( 'enterprise_offroute_meanwhile_desc',
        __( 'Lo que pasa entre expedición y expedición: preparativos, mecánica, rutas cortas y reflexiones desde el garaje.', 'enterprise-moto' )
      ) ); ?>
    </p>
  </div>

  <?php if ( empty( $meanwhile_posts ) ) : ?>
    <div class="mt-no-posts">
      <p><?php esc_html_e( 'Próximamente. Configura las categorías o etiqueta en el Personalizador para que aparezcan aquí.', 'enterprise-moto' ); ?></p>
    </div>
  <?php else : ?>
  <div class="posts-grid">
    <?php foreach ( $meanwhile_posts as $i => $mp ) :
      enterprise_home_post_card( $mp->ID, $i + 1 );
    endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php if ( ! empty( $past_exps ) ) : ?>
<!-- EXPEDICIONES PASADAS -->
<section class="past-section">
  <div class="section-label" style="font-size:10px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px;">
    <?php esc_html_e( 'Bitácora de expediciones', 'enterprise-moto' ); ?>
  </div>
  <h2 style="font-family:var(--font-display);font-size:clamp(36px,5vw,60px);letter-spacing:.06em;">
    <?php esc_html_e( 'VIAJES ', 'enterprise-moto' ); ?><span style="-webkit-text-stroke:1px rgba(255,255,255,.25);color:transparent;"><?php esc_html_e( 'COMPLETADOS', 'enterprise-moto' ); ?></span>
  </h2>
  <div class="past-grid">
    <?php foreach ( $past_exps as $n => $pe ) :
      // #4 R2: km y etapas desde la fuente única (resuelve el «punto A»:
      // km calculado si _exp_km está vacío; etapas por _filt_* en vez del
      // deprecado _exp_categoria, que solía dar 0).
      $p_stats  = enterprise_cuaderno_stats( $pe->ID );
      $p_nombre = get_post_meta( $pe->ID, '_exp_nombre', true ) ?: get_the_title( $pe->ID );
      $p_paises = get_post_meta( $pe->ID, '_exp_paises', true );
      $p_year   = get_the_date( 'Y', $pe->ID );
    ?>
    <a class="past-card" href="<?php echo esc_url( get_permalink( $pe->ID ) ); ?>">
      <div class="past-year"><?php echo esc_html( $p_year ); ?> · <?php esc_html_e( 'Completada', 'enterprise-moto' ); ?></div>
      <h3 class="past-name"><?php echo esc_html( strtoupper( $p_nombre ) ); ?></h3>
      <p class="past-route"><?php echo esc_html( get_the_excerpt( $pe->ID ) ?: ( $p_paises ?: '—' ) ); ?></p>
      <div class="past-stats">
        <div><div class="ps-n"><?php echo esc_html( enterprise_km_display( $p_stats['km'] ) ?: '—' ); ?></div><div class="ps-l"><?php esc_html_e( 'Kilómetros', 'enterprise-moto' ); ?></div></div>
        <div><div class="ps-n"><?php echo intval( $p_stats['etapas'] ); ?></div><div class="ps-l"><?php esc_html_e( 'Etapas', 'enterprise-moto' ); ?></div></div>
      </div>
      <div class="past-decnum"><?php echo str_pad( $n+1, 2, '0', STR_PAD_LEFT ); ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ( $next_date ) : ?>
<script>
<?php echo $countdown_js; ?>
(function() {
  function pad(n){ return String(n).padStart(2,'0'); }
  function set(id, v){ var el=document.getElementById(id); if(el) el.textContent=v; }
  function update() {
    if (!__nextDate || isNaN(__nextDate.getTime())) return;
    var diff = __nextDate - new Date();
    if (diff <= 0) { set('cd-d','00'); set('cd-h','00'); set('cd-m','00'); return; }
    set('cd-d', pad(Math.floor(diff/86400000)));
    set('cd-h', pad(Math.floor((diff%86400000)/3600000)));
    set('cd-m', pad(Math.floor((diff%3600000)/60000)));
  }
  update();
  setInterval(update, 30000);
})();
</script>
<?php endif; ?>

<?php
} // end enterprise_render_off_route
