<?php
/**
 * Block: enterprise/markdown
 * Renderiza contenido Markdown como HTML heredando los estilos del tema.
 */
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/Parsedown.php';

function enterprise_render_markdown_block( $attributes ) {
    $content = isset( $attributes['markdownContent'] ) ? $attributes['markdownContent'] : '';
    if ( empty( trim( $content ) ) ) return '';

    static $parsedown = null;
    if ( $parsedown === null ) $parsedown = new Parsedown();
    $parsedown->setSafeMode( true );

    $html = $parsedown->text( $content );

    return '<div class="ent-markdown">' . $html . '</div>';
}
