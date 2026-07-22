<?php
/**
 * Bitácora Enterprise — header.php
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content"><?php esc_html_e( 'Ir al contenido', 'enterprise-moto' ); ?></a>

<div class="site-wrapper">

  <!-- ── NAV ── -->
  <header class="site-header" id="site-header" role="banner">

    <!-- Logo -->
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" rel="home">
      <div class="logo-icon" aria-hidden="true">
        <?php if ( has_site_icon() ) : ?>
          <img src="<?php echo esc_url( get_site_icon_url( 40 ) ); ?>"
               alt=""
               width="40" height="40"
               style="width:40px;height:40px;object-fit:contain;display:block;">
        <?php else : ?>
          🏍️
        <?php endif; ?>
      </div>
      <div class="logo-text-wrap">
        <span class="logo-name"><?php bloginfo( 'name' ); ?></span>
        <span class="logo-sub"><?php bloginfo( 'description' ); ?></span>
      </div>
    </a>

    <!-- Menú -->
    <div class="nav-wrapper" id="nav-wrapper">
      <?php
      wp_nav_menu( array(
        'theme_location' => 'primary',
        'menu_class'     => '',
        'container'      => 'nav',
        'container_class'=> 'main-navigation',
        'container_id'   => 'main-navigation',
        'fallback_cb'    => 'enterprise_fallback_menu',
        'depth'          => 2,
      ) );
      ?>
    </div>

    <!-- Hamburger (móvil) -->
    <button class="nav-toggle" id="nav-toggle" aria-controls="nav-wrapper" aria-expanded="false" aria-label="<?php esc_attr_e( 'Abrir menú', 'enterprise-moto' ); ?>">
      <span class="nav-toggle-bar"></span>
      <span class="nav-toggle-bar"></span>
      <span class="nav-toggle-bar"></span>
    </button>

  </header><!-- /site-header -->

  <main id="main-content" class="site-main" tabindex="-1">
