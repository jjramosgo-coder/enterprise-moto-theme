<?php
/**
 * Bitácora Enterprise — footer.php
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
?>
  </main><!-- /site-main -->

  <!-- ── FOOTER ── -->
  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-grid">

        <!-- Brand -->
        <div>
          <?php
          $lockup_rel  = 'assets/images/lockup-footer.svg';
          $lockup_path = get_theme_file_path( $lockup_rel );
          if ( file_exists( $lockup_path ) ) :
            $lockup_uri = add_query_arg( 'ver', filemtime( $lockup_path ), get_theme_file_uri( $lockup_rel ) );
          ?>
            <img class="footer-brand-lockup" src="<?php echo esc_url( $lockup_uri ); ?>"
                 alt="<?php esc_attr_e( 'Bitácora Enterprise', 'enterprise-moto' ); ?>">
          <?php endif; ?>
          <p class="footer-brand-desc"><?php esc_html_e( 'Viajar en moto por asfalto, disfrutar del camino y contarlo después.', 'enterprise-moto' ); ?></p>
        </div>

        <!-- Widget footer 1 -->
        <div>
          <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
            <?php dynamic_sidebar( 'footer-1' ); ?>
          <?php else : ?>
            <div class="footer-widget-area">
              <h3 class="widget-title"><?php esc_html_e( 'Secciones', 'enterprise-moto' ); ?></h3>
              <?php
              wp_nav_menu( array(
                'theme_location' => 'footer',
                'container'      => false,
                'fallback_cb'    => false,
                'depth'          => 1,
              ) );
              ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Widget footer 2 -->
        <div>
          <?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
            <?php dynamic_sidebar( 'footer-2' ); ?>
          <?php else : ?>
            <div class="footer-widget-area">
              <h3 class="widget-title"><?php esc_html_e( 'Blog', 'enterprise-moto' ); ?></h3>
              <ul>
                <li><a href="<?php echo esc_url( get_page_link( get_page_by_path( 'acerca-de' ) ) ); ?>"><?php esc_html_e( 'Acerca del blog', 'enterprise-moto' ); ?></a></li>
                <li><a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>">RSS</a></li>
                <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></li>
              </ul>
            </div>
          <?php endif; ?>
        </div>

      </div><!-- /footer-grid -->

      <div class="footer-bottom">
        <span>
          &copy; <?php echo date( 'Y' ); ?>
          <?php
          $lic_page = get_page_by_path( 'licencia-de-contenido' );
          $lic_url  = $lic_page ? get_permalink( $lic_page ) : home_url( '/' );
          ?>
          <a href="<?php echo esc_url( $lic_url ); ?>"><?php bloginfo( 'name' ); ?></a>
          &mdash; <?php esc_html_e( 'Juanjo & María José', 'enterprise-moto' ); ?>
        </span>
        <span><?php esc_html_e( 'Hecho con ☕ y muchos kilómetros', 'enterprise-moto' ); ?></span>
      </div>

    </div><!-- /container -->
  </footer><!-- /site-footer -->

</div><!-- /site-wrapper -->

<?php wp_footer(); ?>
</body>
</html>
