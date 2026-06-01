<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
  <label class="sr-only" for="s"><?php esc_html_e( 'Buscar rutas', 'enterprise-moto' ); ?></label>
  <div style="display:flex;max-width:480px;margin:0 auto;">
    <input
      type="search"
      id="s"
      name="s"
      class="search-field"
      placeholder="<?php esc_attr_e( 'Buscar ruta…', 'enterprise-moto' ); ?>"
      value="<?php echo esc_attr( get_search_query() ); ?>"
      autocomplete="off"
    >
    <button type="submit" class="search-submit" aria-label="<?php esc_attr_e( 'Buscar', 'enterprise-moto' ); ?>">→</button>
  </div>
</form>
