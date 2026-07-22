<?php
/**
 * Block: enterprise/photo-gallery
 * Carrusel de fotos con autoplay, tamaño de imagen y lightbox básico.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
defined( 'ABSPATH' ) || exit;

function enterprise_render_photo_gallery_block( $attributes, $content ) {
    $image_ids   = isset( $attributes['imageIds'] ) && is_array( $attributes['imageIds'] )
                   ? array_map( 'intval', $attributes['imageIds'] ) : array();
    $autoplay    = isset( $attributes['autoplay'] )    ? (bool) $attributes['autoplay']          : false;
    $delay       = isset( $attributes['autoplayDelay'] ) ? intval( $attributes['autoplayDelay'] ) : 4000;
    $size        = isset( $attributes['imageSize'] )   ? sanitize_key( $attributes['imageSize'] ) : 'large';
    $heading     = isset( $attributes['heading'] )     ? sanitize_text_field( $attributes['heading'] ) : '';
    $show_captions = isset( $attributes['showCaptions'] ) ? (bool) $attributes['showCaptions']    : true;
    $ratio       = isset( $attributes['containerRatio'] ) ? $attributes['containerRatio'] : '16/9';

    // Opciones válidas de ratio
    $valid_ratios = array( '16/9', '4/3', '1/1', '3/4', '9/16', 'adaptive' );
    if ( ! in_array( $ratio, $valid_ratios, true ) ) $ratio = '16/9';

    $is_adaptive  = ( $ratio === 'adaptive' );
    $stage_class  = 'ent-gallery-stage' . ( $is_adaptive ? ' is-adaptive' : '' );
    $stage_style  = $is_adaptive ? '' : ' style="--pg-ratio:' . esc_attr( $ratio ) . ';"';

    if ( empty( $image_ids ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . esc_html__( '📷 Carrusel de fotos: selecciona imágenes en el panel del bloque.', 'enterprise-moto' )
                 . '</p>';
        }
        return '';
    }

    $id = 'pg-' . uniqid();

    $out  = '<div class="ent-gallery-wrap" id="' . esc_attr( $id ) . '"'
          . ' data-autoplay="' . ( $autoplay ? 'true' : 'false' ) . '"'
          . ' data-delay="' . esc_attr( $delay ) . '">';

    if ( $heading ) {
        $out .= '<h2 class="ent-gallery-heading">' . esc_html( $heading ) . '</h2>';
    }

    $out .= '<div class="' . esc_attr( $stage_class ) . '"' . $stage_style . '>';
    $out .= '<div class="ent-gallery-track">';

    foreach ( $image_ids as $img_id ) {
        $src     = wp_get_attachment_image_src( $img_id, $size );
        $full    = wp_get_attachment_image_src( $img_id, 'full' );
        $alt     = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
        $caption = wp_get_attachment_caption( $img_id );

        if ( ! $src ) continue;

        $out .= '<div class="ent-gallery-slide">';
        $out .= '<a class="ent-gallery-lb" href="' . esc_url( $full[0] ) . '" data-caption="' . esc_attr( $caption ) . '">';
        $out .= '<img src="' . esc_url( $src[0] ) . '" width="' . esc_attr( $src[1] ) . '" height="' . esc_attr( $src[2] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">';
        $out .= '</a>';
        if ( $show_captions && $caption ) {
            $out .= '<div class="ent-gallery-caption">' . esc_html( $caption ) . '</div>';
        }
        $out .= '</div>';
    }

    $out .= '</div>'; // /track

    $out .= '<button class="ent-gallery-prev" aria-label="' . esc_attr__( 'Anterior', 'enterprise-moto' ) . '">‹</button>';
    $out .= '<button class="ent-gallery-next" aria-label="' . esc_attr__( 'Siguiente', 'enterprise-moto' ) . '">›</button>';

    $out .= '<div class="ent-gallery-dots" aria-hidden="true">';
    foreach ( $image_ids as $i => $_ ) {
        $out .= '<button class="ent-gallery-dot' . ( $i === 0 ? ' is-active' : '' ) . '" data-index="' . $i . '"></button>';
    }
    $out .= '</div>';

    // Autoplay toggle
    $out .= '<button class="ent-gallery-play-toggle" aria-label="' . esc_attr__( 'Pausar/Reproducir', 'enterprise-moto' ) . '" data-autoplay="' . ( $autoplay ? '1' : '0' ) . '">';
    $out .= $autoplay ? '⏸' : '▶';
    $out .= '</button>';

    $out .= '</div>'; // /stage

    // Lightbox overlay (oculto, activado con JS)
    $out .= '<div class="ent-lb-overlay" aria-modal="true" role="dialog" hidden>';
    $out .= '<button class="ent-lb-close" aria-label="' . esc_attr__( 'Cerrar', 'enterprise-moto' ) . '">✕</button>';
    $out .= '<button class="ent-lb-prev" aria-label="' . esc_attr__( 'Anterior', 'enterprise-moto' ) . '">‹</button>';
    $out .= '<button class="ent-lb-next" aria-label="' . esc_attr__( 'Siguiente', 'enterprise-moto' ) . '">›</button>';
    $out .= '<div class="ent-lb-img-wrap"><img class="ent-lb-img" src="" alt=""></div>';
    $out .= '<div class="ent-lb-caption"></div>';
    $out .= '</div>';

    $out .= '</div>'; // /wrap

    return $out;
}
