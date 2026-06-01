<?php
/**
 * Block: enterprise/stories
 * Historias verticales estilo WhatsApp/Instagram: imágenes o vídeos
 * con barra de progreso, navegación táctil y pausa al mantener pulsado.
 */
defined( 'ABSPATH' ) || exit;

function enterprise_render_stories_block( $attributes, $content ) {
    $items    = isset( $attributes['items'] ) && is_array( $attributes['items'] )
                ? $attributes['items'] : array();
    $duration = isset( $attributes['duration'] ) ? max( 1000, intval( $attributes['duration'] ) ) : 5000;
    $heading  = isset( $attributes['heading'] )  ? sanitize_text_field( $attributes['heading'] )  : '';
    $loop     = isset( $attributes['loop'] )     ? (bool) $attributes['loop']                     : false;

    if ( empty( $items ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . esc_html__( '📖 Stories: añade imágenes o vídeos en el panel del bloque.', 'enterprise-moto' )
                 . '</p>';
        }
        return '';
    }

    $id = 'st-' . uniqid();

    $out  = '<div class="ent-stories-wrap" id="' . esc_attr( $id ) . '"'
          . ' data-duration="' . esc_attr( $duration ) . '"'
          . ' data-loop="' . ( $loop ? 'true' : 'false' ) . '">';

    // Thumbnails / avatares de apertura
    $out .= '<div class="ent-stories-thumbs" role="list">';
    foreach ( $items as $i => $item ) {
        $type     = isset( $item['type'] ) ? $item['type'] : 'image';
        $label    = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : ( $i + 1 );
        $thumb_id = isset( $item['imageId'] ) ? intval( $item['imageId'] ) : 0;
        $thumb_src = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'thumbnail' ) : false;
        $icon      = ( $type === 'video' ) ? '🎬' : '📷';

        $out .= '<button class="ent-stories-thumb-btn" data-index="' . $i . '" role="listitem" aria-label="' . esc_attr( $label ) . '">';
        if ( $thumb_src ) {
            $out .= '<img src="' . esc_url( $thumb_src[0] ) . '" alt="' . esc_attr( $label ) . '">';
        } else {
            $out .= '<span class="ent-stories-thumb-icon">' . $icon . '</span>';
        }
        $out .= '<span class="ent-stories-thumb-label">' . esc_html( $label ) . '</span>';
        $out .= '</button>';
    }
    $out .= '</div>';

    // Visor (overlay)
    $out .= '<div class="ent-stories-viewer" hidden aria-modal="true" role="dialog">';

    // Barras de progreso
    $out .= '<div class="ent-stories-bars">';
    foreach ( $items as $i => $_ ) {
        $out .= '<div class="ent-stories-bar"><div class="ent-stories-fill"></div></div>';
    }
    $out .= '</div>';

    // Cabecera del visor
    $out .= '<div class="ent-stories-header">';
    $out .= '<span class="ent-stories-label"></span>';
    $out .= '<button class="ent-stories-close" aria-label="' . esc_attr__( 'Cerrar', 'enterprise-moto' ) . '">✕</button>';
    $out .= '</div>';

    // Slide content
    $out .= '<div class="ent-stories-content">';
    foreach ( $items as $i => $item ) {
        $type     = isset( $item['type'] ) ? $item['type'] : 'image';
        $image_id = isset( $item['imageId'] ) ? intval( $item['imageId'] ) : 0;
        $video_url = isset( $item['videoUrl'] ) ? esc_url_raw( $item['videoUrl'] ) : '';
        $caption  = isset( $item['caption'] )  ? sanitize_text_field( $item['caption'] ) : '';
        $label    = isset( $item['label'] )    ? sanitize_text_field( $item['label'] )   : '';

        $out .= '<div class="ent-stories-slide" data-index="' . $i . '" data-type="' . esc_attr( $type ) . '" data-label="' . esc_attr( $label ) . '" hidden>';

        if ( $type === 'video' && $video_url ) {
            $out .= '<video class="ent-stories-video" src="' . esc_url( $video_url ) . '" playsinline muted></video>';
        } elseif ( $image_id ) {
            $src = wp_get_attachment_image_src( $image_id, 'large' );
            $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            if ( $src ) {
                $out .= '<img class="ent-stories-img" src="' . esc_url( $src[0] ) . '" alt="' . esc_attr( $alt ) . '">';
            }
        }

        if ( $caption ) {
            $out .= '<div class="ent-stories-caption">' . esc_html( $caption ) . '</div>';
        }

        $out .= '</div>';
    }
    $out .= '</div>'; // /content

    // Zonas de toque: anterior / pausar / siguiente
    $out .= '<div class="ent-stories-tap-prev" aria-label="' . esc_attr__( 'Anterior', 'enterprise-moto' ) . '"></div>';
    $out .= '<div class="ent-stories-tap-hold" aria-label="' . esc_attr__( 'Mantener para pausar', 'enterprise-moto' ) . '"></div>';
    $out .= '<div class="ent-stories-tap-next" aria-label="' . esc_attr__( 'Siguiente', 'enterprise-moto' ) . '"></div>';

    $out .= '</div>'; // /viewer
    $out .= '</div>'; // /wrap

    return $out;
}
