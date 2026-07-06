<?php
/**
 * Enterprise Moto — functions.php
 * Configuración del tema, menus, widgets y scripts.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ENTERPRISE_VERSION', '2.4.9' );

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
function enterprise_register_expedition_metabox() {
    add_meta_box(
        'enterprise_expedition_data',
        __( 'Datos de la expedición', 'enterprise-moto' ),
        'enterprise_expedition_metabox_cb',
        'page',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'enterprise_register_expedition_metabox' );

function enterprise_expedition_metabox_cb( $post ) {
    $template          = get_post_meta( $post->ID, '_wp_page_template', true );
    $allowed_templates = array(
        'page-cuaderno-de-bitacora.php',
        'page-bitacora-bloques.php',
    );

    if ( ! in_array( $template, $allowed_templates, true ) ) {
        echo '<p style="color:#888;font-size:13px;">' .
             esc_html__( 'Asigna la plantilla "Cuaderno de bitácora" o "Bitácora con bloques" para activar estos campos.', 'enterprise-moto' ) .
             '</p>';
        return;
    }

    $is_bloques   = ( 'page-bitacora-bloques.php' === $template );
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

    if ( $is_bloques ) {
        // Plantilla «Bitácora con bloques» CONGELADA (R4): conjunto de campos y
        // labels EXACTAMENTE como estaban, incluidos _exp_duracion y _exp_progreso
        // y la semántica «(vacío = en curso)» de la fecha de fin.
        $exp_fields = array(
            '_exp_nombre'      => array( 'label' => __( 'Nombre del viaje',          'enterprise-moto' ), 'placeholder' => 'Ej: Sicilia 2026' ),
            '_exp_subtitulo'   => array( 'label' => __( 'Descripción / ruta',        'enterprise-moto' ), 'placeholder' => 'Ej: BCN → Palermo → Cerdeña → BCN' ),
            '_exp_fecha_inicio' => array( 'label' => __( 'Fecha de inicio',          'enterprise-moto' ), 'placeholder' => 'AAAA-MM-DD', 'type' => 'date' ),
            '_exp_fecha_fin'   => array( 'label' => __( 'Fecha de fin (vacío = en curso)', 'enterprise-moto' ), 'placeholder' => 'AAAA-MM-DD', 'type' => 'date' ),
            '_exp_salida'      => array( 'label' => __( 'Texto salida (auto si hay fechas)', 'enterprise-moto' ), 'placeholder' => 'Ej: 23 Mar 2026' ),
            '_exp_duracion'    => array( 'label' => __( 'Duración (auto si hay fechas)', 'enterprise-moto' ), 'placeholder' => 'Ej: 18 días' ),
            '_exp_km'          => array( 'label' => __( 'Kilómetros totales',        'enterprise-moto' ), 'placeholder' => 'Ej: ~3.200 km (vacío = auto)' ),
            '_exp_paises'      => array( 'label' => __( 'Países recorridos',         'enterprise-moto' ), 'placeholder' => 'Ej: España · Francia · Italia' ),
            '_exp_progreso'    => array( 'label' => __( 'Progreso (0–100)',           'enterprise-moto' ), 'placeholder' => 'Ej: 75' ),
        );
    } else {
        // Plantilla «Cuaderno de bitácora» (R4): duración y progreso se CALCULAN en
        // caliente, así que se retiran del metabox. _exp_fecha_fin es opcional y sin
        // semántica «en curso» — el estado lo da _exp_estado.
        $exp_fields = array(
            '_exp_nombre'      => array( 'label' => __( 'Nombre del viaje',          'enterprise-moto' ), 'placeholder' => 'Ej: Sicilia 2026' ),
            '_exp_subtitulo'   => array( 'label' => __( 'Descripción / ruta',        'enterprise-moto' ), 'placeholder' => 'Ej: BCN → Palermo → Cerdeña → BCN' ),
            '_exp_fecha_inicio' => array( 'label' => __( 'Fecha de inicio',          'enterprise-moto' ), 'placeholder' => 'AAAA-MM-DD', 'type' => 'date' ),
            '_exp_fecha_fin'   => array( 'label' => __( 'Fecha de fin',              'enterprise-moto' ), 'placeholder' => 'AAAA-MM-DD', 'type' => 'date' ),
            '_exp_salida'      => array( 'label' => __( 'Texto salida (auto si hay fechas)', 'enterprise-moto' ), 'placeholder' => 'Ej: 23 Mar 2026' ),
            '_exp_km'          => array( 'label' => __( 'Kilómetros totales',        'enterprise-moto' ), 'placeholder' => 'Ej: ~3.200 km (vacío = auto)' ),
            '_exp_paises'      => array( 'label' => __( 'Países recorridos',         'enterprise-moto' ), 'placeholder' => 'Ej: España · Francia · Italia' ),
        );
    }
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
    if ( $is_bloques ) {
        // Para page-bitacora-bloques solo mostramos la nota del ticker
        $exp_cat_slug  = get_post_meta( $post->ID, '_exp_categoria', true );
        $exp_tags_raw  = get_post_meta( $post->ID, '_exp_etiquetas', true );
        echo '<div style="' . $s_section . '">';
        echo '<div style="' . $s_sheader . '">🎞 ' . esc_html__( 'Ticker (plantilla Bitácora con bloques)', 'enterprise-moto' ) . '</div>';
        echo '<div style="' . $s_sbody . '">';
        echo '<div style="' . $s_grid2 . '">';
        echo '<div><label style="' . $s_label . '">' . esc_html__( 'Slug de categoría (para el ticker)', 'enterprise-moto' ) . '</label>';
        echo '<input type="text" name="_exp_categoria" value="' . esc_attr( $exp_cat_slug ) . '" placeholder="Ej: cuaderno-etapa" style="' . $s_input . '"></div>';
        echo '<div><label style="' . $s_label . '">' . esc_html__( 'Etiquetas para el ticker (slugs, coma)', 'enterprise-moto' ) . '</label>';
        echo '<input type="text" name="_exp_etiquetas" value="' . esc_attr( $exp_tags_raw ) . '" placeholder="Ej: sicilia-2026, italia" style="' . $s_input . '"></div>';
        echo '</div>';
        echo '<p style="font-size:11px;color:#888;margin:10px 0 0;">🎞 ' . esc_html__( 'Prioridad ticker: (1) bloques con categoría → (2) Slug de categoría → (3) Etiquetas → (4) categorías del blog.', 'enterprise-moto' ) . '</p>';
        echo '</div></div>';
        return;
    }

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

    // Campos EXCLUSIVOS de la plantilla «Bitácora con bloques» (congelada, R4):
    // duración manual, progreso manual y los slugs del ticker. En «Cuaderno de
    // bitácora» NO se guardan (duración y progreso se calculan en caliente). Se
    // gatea por plantilla además de por isset, para no tocar esas metas en el
    // cuaderno aunque llegaran por POST.
    $is_bloques = ( 'page-bitacora-bloques.php' === get_post_meta( $post_id, '_wp_page_template', true ) );
    if ( $is_bloques ) {
        foreach ( array( '_exp_duracion', '_exp_categoria', '_exp_etiquetas' ) as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
        // Progreso: solo números 0-100
        if ( isset( $_POST['_exp_progreso'] ) ) {
            update_post_meta( $post_id, '_exp_progreso', min( 100, max( 0, intval( $_POST['_exp_progreso'] ) ) ) );
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
}
add_action( 'init', 'enterprise_register_map_blocks' );

/* ─────────────────────────────────────────
   LEAFLET EN FRONTEND (solo si hay bloques de mapa)
───────────────────────────────────────── */
function enterprise_map_frontend_assets() {
    if ( ! is_singular() ) return;
    $post = get_queried_object();
    if ( ! $post || ! isset( $post->post_content ) ) return;

    $has_location   = has_block( 'enterprise/location-map',         $post );
    $has_route      = has_block( 'enterprise/route-map',             $post );
    $has_animated   = has_block( 'enterprise/animated-route-map',    $post );
    $has_comparison = has_block( 'enterprise/route-comparison',      $post );
    if ( ! $has_location && ! $has_route && ! $has_animated && ! $has_comparison ) return;

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
    if ( ! has_block( 'enterprise/post-stages', $post ) ) return;

    wp_enqueue_style(
        'enterprise-carousel',
        get_template_directory_uri() . '/assets/css/carousel.css',
        array( 'enterprise-style' ),
        ENTERPRISE_VERSION
    );

    wp_enqueue_script(
        'enterprise-carousel',
        get_template_directory_uri() . '/assets/js/carousel.js',
        array(),
        ENTERPRISE_VERSION,
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

/* ─────────────────────────────────────────
   CARGAR CSS DE LA PLANTILLA BITÁCORA BLOQUES
───────────────────────────────────────── */
function enterprise_bitacora_bloques_styles() {
    if ( ! is_page() ) return;
    $template = get_page_template_slug( get_queried_object_id() );
    if ( 'page-bitacora-bloques.php' !== $template ) return;

    // Reutiliza el CSS de expedition (mismo diseño de héroe/summary)
    wp_enqueue_style(
        'enterprise-expedition',
        get_template_directory_uri() . '/assets/css/expedition.css',
        array( 'enterprise-style' ),
        ENTERPRISE_VERSION
    );
    // Y el carrusel (siempre, porque la plantilla usa bloques)
    wp_enqueue_style(
        'enterprise-carousel',
        get_template_directory_uri() . '/assets/css/carousel.css',
        array( 'enterprise-style' ),
        ENTERPRISE_VERSION
    );
    wp_enqueue_script(
        'enterprise-carousel',
        get_template_directory_uri() . '/assets/js/carousel.js',
        array(),
        ENTERPRISE_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'enterprise_bitacora_bloques_styles' );

/* ─────────────────────────────────────────
   CSS DEL LAYOUT de BITÁCORA BLOQUES
   (columna contenido + sidebar)
───────────────────────────────────────── */
function enterprise_bitacora_bloques_inline_css() {
    if ( ! is_page() ) return;
    $template = get_page_template_slug( get_queried_object_id() );
    if ( 'page-bitacora-bloques.php' !== $template ) return;

    $css = '
    .exp-blocks-layout {
        display: grid;
        grid-template-columns: 1fr 288px;
        gap: 48px;
        align-items: start;
        padding-top: 56px;
        padding-bottom: 72px;
    }
    .exp-blocks-content { min-width: 0; }
    .exp-blocks-entry > * + * { margin-top: 32px; }
    /* Bloque de etapas ocupa todo el ancho disponible */
    .exp-blocks-entry .ent-stages { margin-left: 0; margin-right: 0; }
    /* Alineación wide sale de la columna */
    .exp-blocks-entry .alignwide {
        margin-left: -32px;
        margin-right: -32px;
        max-width: calc(100% + 64px);
    }
    @media (max-width: 1100px) {
        .exp-blocks-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .exp-blocks-layout { padding: 36px 20px 56px; }
        .exp-blocks-entry .alignwide { margin-left: 0; margin-right: 0; max-width: 100%; }
    }
    ';

    wp_add_inline_style( 'enterprise-expedition', $css );
}
add_action( 'wp_enqueue_scripts', 'enterprise_bitacora_bloques_inline_css', 20 );

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
   METABOX: DATOS DE EXPEDICIÓN
   (también para page-bitacora-bloques.php)
───────────────────────────────────────── */
// La función enterprise_expedition_metabox_cb ya muestra el metabox
// en todas las páginas. Añadimos detección de la nueva plantilla:
add_filter( 'enterprise_show_expedition_metabox', function( $show, $template ) {
    if ( 'page-bitacora-bloques.php' === $template ) return true;
    return $show;
}, 10, 2 );

/* ─────────────────────────────────────────
   FUNCIONES DE PORTADA (index.php)
   Definidas aquí para evitar redeclaración.
───────────────────────────────────────── */
/* ── Tarjeta de post ── */
function enterprise_home_post_card( $post_id, $num, $section_cat_name = '' ) {
    $route    = enterprise_get_route_data( $post_id );
    /* Mostrar la categoría del contexto de la sección, no la primera del post */
    $cat_name = $section_cat_name ?: enterprise_first_category( $post_id );
    $thumb    = get_the_post_thumbnail_url( $post_id, 'enterprise-card' );
    ?>
    <?php
    /* Añadir ?from_cat al enlace para que single.php sepa la categoría de origen */
    $card_permalink = get_permalink( $post_id );
    if ( $section_cat_name ) {
        $section_cat_obj = get_category_by_slug( sanitize_title( $section_cat_name ) );
        if ( $section_cat_obj ) {
            $card_permalink = add_query_arg( 'from_cat', $section_cat_obj->slug, $card_permalink );
        }
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
function enterprise_home_section( $eyebrow, $title, $posts, $cta_url, $cta_label, $section_cat = '' ) {
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
                    enterprise_home_post_card( $post->ID, $i + 1, $section_cat );
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
