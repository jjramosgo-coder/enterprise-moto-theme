<?php get_header(); ?>

<div class="error-404-wrap">
  <div>
    <div class="error-404-num" aria-hidden="true">404</div>
    <h1 class="error-404-title"><?php esc_html_e( 'Ruta no encontrada', 'enterprise-moto' ); ?></h1>
    <p class="error-404-desc">
      <?php esc_html_e( 'Parece que esta carretera no existe. Puede que hayas tomado un desvío equivocado.', 'enterprise-moto' ); ?>
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--dark">
        ← <?php esc_html_e( 'Volver al inicio', 'enterprise-moto' ); ?>
      </a>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--ghost">
        <?php esc_html_e( 'Ver todas las rutas', 'enterprise-moto' ); ?>
      </a>
    </div>
  </div>
</div>

<?php get_footer(); ?>
