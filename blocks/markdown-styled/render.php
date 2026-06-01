<?php
/**
 * Block: enterprise/markdown-styled
 * Renderiza Markdown con opciones de estilo configurables en el inspector.
 */
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/Parsedown.php';

function enterprise_render_markdown_styled_block( $attributes ) {
    $content     = isset( $attributes['markdownContent'] ) ? $attributes['markdownContent']         : '';
    $font_family = isset( $attributes['fontFamily'] )      ? $attributes['fontFamily']               : 'dm-sans';
    $font_size   = isset( $attributes['fontSize'] )        ? intval( $attributes['fontSize'] )       : 15;
    $color       = isset( $attributes['textColor'] )       ? sanitize_hex_color( $attributes['textColor'] ) : '';
    $bg          = isset( $attributes['bgColor'] )         ? sanitize_hex_color( $attributes['bgColor'] )   : '';
    $padding     = isset( $attributes['padding'] )         ? intval( $attributes['padding'] )        : 24;
    $border      = isset( $attributes['borderColor'] )     ? sanitize_hex_color( $attributes['borderColor'] ) : '';
    $border_w    = isset( $attributes['borderWidth'] )     ? intval( $attributes['borderWidth'] )    : 4;
    $show_border = isset( $attributes['showBorder'] )      ? (bool) $attributes['showBorder']        : true;
    $custom_css  = isset( $attributes['customCss'] )       ? $attributes['customCss']                : '';

    if ( empty( trim( $content ) ) ) return '';

    static $parsedown = null;
    if ( $parsedown === null ) $parsedown = new Parsedown();
    $parsedown->setSafeMode( true );

    $html = $parsedown->text( $content );

    // Familia tipográfica
    $font_map = array(
        'bebas'     => "'Bebas Neue', var(--font-display, sans-serif)",
        'dm-sans'   => "'DM Sans', var(--font-body, sans-serif)",
        'dm-serif'  => "'DM Serif Display', var(--font-serif, serif)",
    );
    $font_css = isset( $font_map[ $font_family ] ) ? $font_map[ $font_family ] : $font_map['dm-sans'];

    // Construir estilos inline
    $styles = array(
        'font-family'  => $font_css,
        'font-size'    => $font_size . 'px',
        'line-height'  => '1.7',
        'padding'      => $padding . 'px',
    );
    if ( $color ) $styles['color']      = $color;
    if ( $bg )    $styles['background'] = $bg;
    if ( $show_border && $border ) {
        $styles['border-left'] = $border_w . 'px solid ' . $border;
    }

    $style_str = '';
    foreach ( $styles as $prop => $val ) {
        $style_str .= esc_attr( $prop ) . ':' . esc_attr( $val ) . ';';
    }

    // ID único para scoping del CSS personalizado
    $block_id = 'ent-mds-' . wp_rand( 10000, 99999 );

    // CSS personalizado — scoped al ID único del bloque
    $style_tag = '';
    if ( ! empty( trim( $custom_css ) ) ) {
        // Reemplazar .ent-markdown--styled por el selector con ID único
        $scoped = preg_replace(
            '/\.ent-markdown--styled\b/',
            '#' . $block_id,
            $custom_css
        );
        // Si no contiene .ent-markdown--styled, envolver todo con el ID
        if ( $scoped === $custom_css ) {
            $scoped = '#' . $block_id . ' { ' . $custom_css . ' }';
        }
        $style_tag = '<style>' . wp_strip_all_tags( $scoped ) . '</style>';
    }

    return $style_tag . '<div class="ent-markdown ent-markdown--styled" id="' . esc_attr( $block_id ) . '" style="' . $style_str . '">' . $html . '</div>';
}
