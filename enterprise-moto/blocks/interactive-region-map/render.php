<?php
/**
 * Bitácora Enterprise — blocks/interactive-region-map/render.php
 * Mapa interactivo de regiones — render del maestro georreferenciado
 * (assets/maps/enterprise-eu.svg). Solo HTML: el SVG se emite inline en el DOM.
 * Sin <script> inline.
 *
 * #54 — Colores configurables (nivel 2). En modo 'theme' («Personalizar») el tema
 *   re-tiñe el mapa mediante custom properties inline sobre .ent-region-map, que el
 *   CSS consume con el valor horneado del activo como fallback. En modo 'asset'
 *   («Los del propio mapa») no se emite ningún override y manda el activo (nivel 1).
 *   El fondo (canvas) es invariante (§13.19): siempre lo declara el activo.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_interactive_region_map_block( $attributes, $content = '', $block = null ) {

	$svg_path = enterprise_map_asset_path( 'enterprise-eu.svg' );
	$svg      = @file_get_contents( $svg_path );

	/* Activo ausente o vacío: aviso solo para editores, cadena vacía en el front. */
	if ( ! $svg ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
				 . esc_html__( 'Mapa interactivo de regiones: no se encuentra el activo del mapa (enterprise-eu.svg).', 'enterprise-moto' )
				 . '</p>';
		}
		return '';
	}

	/* #63 (absorbido en #47) — Higiene del marcado emitido. El activo empieza por el
	   prólogo `<?xml version="1.0"?>` (compatibilidad con la herramienta de diseño/GIS
	   que lo genera); en HTML5, emitido inline, se parsea como comentario bogus (marcado
	   inválido). Se retira SOLO del marcado emitido, nunca del activo (invariante D-1,
	   §13.19). Idempotente: si no hay prólogo, no cambia nada. */
	$svg = preg_replace( '/^\s*<\?xml[^>]*\?>\s*/i', '', $svg, 1 );

	if ( ! is_array( $attributes ) ) $attributes = array();

	/* Fondo (canvas) de fuente única (§13.19): el color lo declara el activo —el
	   <rect> del maestro— y lo aplica el tema. Se extrae el fill del primer <rect>
	   y se expone como custom property en el contenedor; el CSS lo consume en el
	   <svg> como background-color, que cubre el área renderizada bajo cualquier
	   viewBox (a diferencia del <rect>, cuyos % se recortan al hacer zoom). Si no
	   se puede leer, el contenedor sale sin la propiedad y el CSS cae al fallback. */
	$canvas = '';
	if ( preg_match( '/<rect\b[^>]*\bfill\s*=\s*"([^"]+)"/i', $svg, $m ) ) {
		$canvas = $m[1];
	}

	$classes     = array( 'ent-region-map' );
	$style_parts = array();
	if ( '' !== $canvas ) {
		$style_parts[] = '--ent-region-canvas: ' . $canvas;
	}

	/* #54 — Preajuste del botón «volver» según el tipo de lienzo declarado por el
	   autor. Es independiente del modo de color (corrige la baja legibilidad del
	   botón sobre el lienzo claro): se emite siempre, con «claro» por defecto. */
	$back      = ( isset( $attributes['backCanvas'] ) && 'dark' === $attributes['backCanvas'] ) ? 'dark' : 'light';
	$classes[] = 'back-' . $back;

	/* #54 — Modo 'theme': marcadores de estado + custom properties de color/grosor/
	   opacidad por nivel y acento de hover. Colores saneados a hex; numéricos
	   acotados. El acento de hover se aplica también en modo 'asset' vía el fallback
	   del CSS (#c9a010); aquí solo se emite el override cuando el autor personaliza. */
	$is_theme = isset( $attributes['colorSource'] ) && 'theme' === $attributes['colorSource'];
	if ( $is_theme ) {
		$classes[] = 'is-color-theme';
		$palette   = isset( $attributes['palette'] ) ? preg_replace( '/[^a-z0-9_-]/', '', (string) $attributes['palette'] ) : '';
		if ( '' !== $palette ) {
			$classes[] = 'palette-' . $palette;
		}

		$color_map = array(
			'landFill'       => '--ent-land-fill',
			'baseStroke'     => '--ent-base-stroke',
			'countryStroke'  => '--ent-t0-stroke',
			'regionFill'     => '--ent-region-fill',
			'regionStroke'   => '--ent-region-stroke',
			'provinceFill'   => '--ent-province-fill',
			'provinceStroke' => '--ent-province-stroke',
			'hoverAccent'    => '--ent-hover-accent',
		);
		foreach ( $color_map as $attr => $prop ) {
			if ( ! isset( $attributes[ $attr ] ) ) continue;
			$hex = sanitize_hex_color( (string) $attributes[ $attr ] );
			if ( $hex ) {
				$style_parts[] = $prop . ': ' . $hex;
			}
		}

		/* prop => [ custom property, min, max ] */
		$num_map = array(
			'baseStrokeWidth' => array( '--ent-base-sw',      0, 20 ),
			't0StrokeWidth'   => array( '--ent-t0-sw',        0, 20 ),
			't1StrokeWidth'   => array( '--ent-t1-sw',        0, 20 ),
			't2StrokeWidth'   => array( '--ent-t2-sw',        0, 20 ),
			'baseOpacity'     => array( '--ent-base-opacity', 0, 1 ),
			't0Opacity'       => array( '--ent-t0-opacity',   0, 1 ),
			't1Opacity'       => array( '--ent-t1-opacity',   0, 1 ),
			't2Opacity'       => array( '--ent-t2-opacity',   0, 1 ),
		);
		foreach ( $num_map as $attr => $cfg ) {
			if ( ! isset( $attributes[ $attr ] ) ) continue;
			$v = (float) $attributes[ $attr ];
			if ( $v < $cfg[1] ) $v = $cfg[1];
			if ( $v > $cfg[2] ) $v = $cfg[2];
			$style_parts[] = $cfg[0] . ': ' . $v;
		}
	}

	$class_attr = esc_attr( implode( ' ', $classes ) );
	$style_attr = '';
	if ( ! empty( $style_parts ) ) {
		$style_attr = ' style="' . esc_attr( implode( '; ', $style_parts ) . ';' ) . '"';
	}

	/* #57 — Inyección aditiva de data-count por región. path.id (=código) → count del
	   término. Sin término → sin atributo; término con 0 → data-count="0". Aditivo: no
	   toca ningún atributo existente del SVG (invariante «SVG tal cual», §13.19). */
	$counts = enterprise_regiones_counts();
	if ( ! empty( $counts ) ) {
		$svg = preg_replace_callback(
			'/<path\b[^>]*\bid="([^"]+)"[^>]*>/i',
			function ( $m ) use ( $counts ) {
				$tag = $m[0];
				$id  = $m[1];
				if ( ! array_key_exists( $id, $counts ) )   return $tag; // sin término → sin atributo
				if ( false !== strpos( $tag, 'data-count' ) ) return $tag; // idempotencia defensiva
				return preg_replace( '/^<path\b/i', '<path data-count="' . (int) $counts[ $id ] . '"', $tag, 1 );
			},
			$svg
		);
	}

	/* #46 (Commit 3) — URL base de la página de destino por región para el enlace del globo.
	   El motor (region-map-frontend.js) le añade `region=<id de la pieza>` por cada unidad
	   TERMINAL con data-count>0. Solo se emite si la Página-destino está configurada y
	   publicada; si no, el globo muestra nombre + conteo pero no enlaza. `region_src` = id de
	   la página que hospeda el mapa (para «← Volver al mapa»), lo estampa el helper. */
	$dest_attr = '';
	$rd_page   = (int) get_theme_mod( 'enterprise_region_dest_page', 0 );
	if ( $rd_page && 'publish' === get_post_status( $rd_page ) && function_exists( 'enterprise_region_destination_url' ) ) {
		$dest_attr = ' data-region-dest="' . esc_url( enterprise_region_destination_url( '', get_queried_object_id() ) ) . '"';
	}

	/* El SVG es un activo GPL de confianza y de origen propio: se emite tal cual,
	   inline, para que el motor interactivo pueda acceder a los nodos <path>. No se
	   pasa por wp_kses_* (eliminaría elementos/atributos SVG). */
	ob_start();
	?>
	<div class="<?php echo $class_attr; ?>"<?php echo $style_attr; echo $dest_attr; ?>>
		<?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — activo SVG GPL de confianza, ver nota superior. ?>
	</div>
	<?php
	return ob_get_clean();
}
