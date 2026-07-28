<?php
/**
 * Bitácora Enterprise — blocks/interactive-region-map/render.php
 * Mapa interactivo de regiones — render estático e inerte del maestro georreferenciado
 * (assets/maps/enterprise-eu.svg). Solo HTML: el SVG se emite inline en el DOM.
 * Sin <script> inline.
 * (El motor interactivo —hover, zoom, drill-down— es la sub-fase siguiente de #51;
 *  el color/descripción por región y la UX de editor son #47.)
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_interactive_region_map_block( $attributes, $content = '', $block = null ) {

	$svg_path = get_template_directory() . '/assets/maps/enterprise-eu.svg';
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

	/* El SVG es un activo GPL de confianza y de origen propio: se emite tal cual,
	   inline, para que el motor interactivo (sub-fase siguiente de #51) pueda acceder
	   directamente a los nodos <path>. No se pasa por wp_kses_* (eliminaría
	   elementos/atributos SVG). */
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

	ob_start();
	?>
	<div class="ent-region-map"<?php if ( '' !== $canvas ) : ?> style="--ent-region-canvas: <?php echo esc_attr( $canvas ); ?>;"<?php endif; ?>>
		<?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — activo SVG GPL de confianza, ver nota superior. ?>
	</div>
	<?php
	return ob_get_clean();
}
