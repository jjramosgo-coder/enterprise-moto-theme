<?php
/**
 * Bitácora Enterprise — page.php
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

<!-- ══ HERO DE PÁGINA ══ -->
<div class="page-hero-wrap">
  <div class="container">
    <?php if ( has_post_thumbnail() ) : ?>
      <div style="margin-bottom:28px;overflow:hidden;max-height:400px;">
        <?php the_post_thumbnail( 'enterprise-wide', array( 'style' => 'width:100%;object-fit:cover;' ) ); ?>
      </div>
    <?php endif; ?>
    <h1 class="page-hero-title"><?php the_title(); ?></h1>
  </div>
</div>

<!-- ══ CONTENIDO ══ -->
<div class="page-content-wrap">
  <div class="entry-content">
    <?php the_content(); ?>
  </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
