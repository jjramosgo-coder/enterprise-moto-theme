<?php
/**
 * Bitácora Enterprise — inc/fallback-menu.php
 * Menú de fallback si no hay ninguno asignado en el panel.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

function enterprise_fallback_menu() {
    echo '<nav class="main-navigation" id="main-navigation">';
    echo '<ul>';

    // Página de inicio
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Inicio', 'enterprise-moto' ) . '</a></li>';

    // Páginas principales
    $pages = get_pages( array( 'number' => 5, 'sort_column' => 'menu_order' ) );
    foreach ( $pages as $page ) {
        echo '<li><a href="' . esc_url( get_permalink( $page->ID ) ) . '">' . esc_html( $page->post_title ) . '</a></li>';
    }

    // Categorías como último recurso
    $cats = get_categories( array( 'number' => 3, 'hide_empty' => true ) );
    foreach ( $cats as $cat ) {
        echo '<li><a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a></li>';
    }

    echo '</ul>';
    echo '</nav>';
}
