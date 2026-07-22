<?php
/**
 * Block: enterprise/youtube-video
 * Un único vídeo o Short de YouTube con contenedor estilizado.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
defined( 'ABSPATH' ) || exit;

function enterprise_render_youtube_video_block( $attributes ) {
    $url         = isset( $attributes['videoUrl'] )    ? esc_url_raw( $attributes['videoUrl'] )           : '';
    $title       = isset( $attributes['videoTitle'] )  ? sanitize_text_field( $attributes['videoTitle'] ) : '';
    $channel     = isset( $attributes['channel'] )     ? sanitize_text_field( $attributes['channel'] )    : '';
    $duration    = isset( $attributes['duration'] )    ? sanitize_text_field( $attributes['duration'] )   : '';
    $description = isset( $attributes['description'] ) ? sanitize_textarea_field( $attributes['description'] ) : '';
    $ratio       = isset( $attributes['ratio'] )       ? $attributes['ratio'] : '16/9';
    $heading     = isset( $attributes['heading'] )     ? sanitize_text_field( $attributes['heading'] )    : '';

    if ( ! $url ) return '';

    $video_id = '';
    if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
        $video_id = $m[1];
    }
    if ( ! $video_id ) return '';

    $is_short  = ( $ratio === '9/16' || strpos( $url, '/shorts/' ) !== false );
    $thumb_url = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
    $embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&rel=0';
    $uid       = 'yt-' . $video_id . '-' . wp_rand( 100, 999 );

    ob_start(); ?>
<div class="ent-yt-wrap<?php echo $is_short ? ' ent-yt-wrap--short' : ''; ?>" id="<?php echo esc_attr( $uid ); ?>">

    <?php if ( $heading ) : ?>
    <h2 class="ent-yt-heading"><?php echo esc_html( $heading ); ?></h2>
    <?php endif; ?>

    <div class="ent-yt-card" style="--yt-ratio:<?php echo esc_attr( $ratio ); ?>;">

        <?php if ( $channel || $duration ) : ?>
        <div class="ent-yt-meta">
            <?php if ( $channel ) : ?>
            <span class="ent-yt-channel">
                <span class="ent-yt-channel-icon" aria-hidden="true">▶</span>
                <?php echo esc_html( $channel ); ?>
            </span>
            <?php endif; ?>
            <?php if ( $duration ) : ?>
            <span class="ent-yt-duration"><?php echo esc_html( $duration ); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="ent-yt-stage"
             data-video-id="<?php echo esc_attr( $video_id ); ?>"
             data-embed-url="<?php echo esc_attr( $embed_url ); ?>">
            <div class="ent-yt-thumb" aria-hidden="true">
                <img src="<?php echo esc_url( $thumb_url ); ?>"
                     alt="<?php echo $title ? esc_attr( $title ) : esc_attr__( 'Miniatura del vídeo', 'enterprise-moto' ); ?>"
                     loading="lazy">
                <div class="ent-yt-thumb-overlay"></div>
            </div>
            <button class="ent-yt-play" aria-label="<?php esc_attr_e( 'Reproducir vídeo', 'enterprise-moto' ); ?>">
                <span class="ent-yt-play-icon" aria-hidden="true"></span>
            </button>
            <div class="ent-yt-iframe-wrap" hidden></div>
        </div>

        <?php if ( $title || $description ) : ?>
        <div class="ent-yt-footer">
            <?php if ( $title ) : ?>
            <div class="ent-yt-title"><?php echo esc_html( $title ); ?></div>
            <?php endif; ?>
            <?php if ( $description ) : ?>
            <div class="ent-yt-desc"><?php echo esc_html( $description ); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>
    <?php
    return ob_get_clean();
}
