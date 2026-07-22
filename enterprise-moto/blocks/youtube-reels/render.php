<?php
/**
 * Block: enterprise/youtube-reels
 * Galería de YouTube Shorts con swipe en móvil y carrusel en desktop.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
defined( 'ABSPATH' ) || exit;

function enterprise_render_youtube_reels_block( $attributes ) {
    $items       = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
    $heading     = isset( $attributes['heading'] )     ? sanitize_text_field( $attributes['heading'] )  : '';
    $show_titles = isset( $attributes['showTitles'] )  ? (bool) $attributes['showTitles']               : true;
    $cols        = isset( $attributes['desktopCols'] ) ? intval( $attributes['desktopCols'] )            : 3;

    if ( empty( $items ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
                 . esc_html__( '▶ YouTube Reels: añade Shorts en el panel lateral.', 'enterprise-moto' )
                 . '</p>';
        }
        return '';
    }

    $uid = 'ytr-' . wp_rand( 1000, 9999 );

    ob_start(); ?>
<div class="ent-reels-wrap" id="<?php echo esc_attr( $uid ); ?>" style="--reels-cols:<?php echo intval( $cols ); ?>;">

    <?php if ( $heading ) : ?>
    <h2 class="ent-yt-heading"><?php echo esc_html( $heading ); ?></h2>
    <?php endif; ?>

    <!-- Flechas prev/next (visibles en desktop) -->
    <button class="ent-reels-prev" aria-label="<?php esc_attr_e( 'Anterior', 'enterprise-moto' ); ?>">‹</button>
    <button class="ent-reels-next" aria-label="<?php esc_attr_e( 'Siguiente', 'enterprise-moto' ); ?>">›</button>

    <div class="ent-reels-track">
        <?php foreach ( $items as $item ) :
            $url      = isset( $item['url'] )     ? esc_url_raw( $item['url'] )                   : '';
            $title    = isset( $item['title'] )   ? sanitize_text_field( $item['title'] )          : '';
            $channel  = isset( $item['channel'] ) ? sanitize_text_field( $item['channel'] )        : '';
            $duration = isset( $item['duration'] )? sanitize_text_field( $item['duration'] )       : '';

            if ( ! $url ) continue;

            $video_id = '';
            if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m ) ) {
                $video_id = $m[1];
            }
            if ( ! $video_id ) continue;

            $thumb_url = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
            $embed_url = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&rel=0';
        ?>
        <div class="ent-reel-card">

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

            <div class="ent-yt-stage ent-yt-stage--reel"
                 data-video-id="<?php echo esc_attr( $video_id ); ?>"
                 data-embed-url="<?php echo esc_attr( $embed_url ); ?>">
                <div class="ent-yt-thumb" aria-hidden="true">
                    <img src="<?php echo esc_url( $thumb_url ); ?>"
                         alt="<?php echo $title ? esc_attr( $title ) : esc_attr__( 'Short de YouTube', 'enterprise-moto' ); ?>"
                         loading="lazy">
                    <div class="ent-yt-thumb-overlay"></div>
                </div>
                <button class="ent-yt-play" aria-label="<?php esc_attr_e( 'Reproducir', 'enterprise-moto' ); ?>">
                    <span class="ent-yt-play-icon" aria-hidden="true"></span>
                </button>
                <div class="ent-yt-iframe-wrap" hidden></div>
            </div>

            <?php if ( $show_titles && $title ) : ?>
            <div class="ent-yt-footer">
                <div class="ent-yt-title"><?php echo esc_html( $title ); ?></div>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dots para móvil -->
    <div class="ent-reels-dots" aria-hidden="true">
        <?php foreach ( $items as $i => $_ ) : ?>
        <div class="ent-reels-dot<?php echo $i === 0 ? ' is-active' : ''; ?>"></div>
        <?php endforeach; ?>
    </div>

</div>
    <?php
    return ob_get_clean();
}
