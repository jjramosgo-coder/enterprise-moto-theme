<?php
/**
 * Bitácora Enterprise — blocks/interactive-region-map/render.php
 * Mapa interactivo de regiones (nivel-1, Europa) — render estático inline del SVG.
 * Solo HTML: el SVG se emite inline en el DOM. Sin <script> inline.
 * (La interactividad —hover, zoom, drill-down— es #44; el color/descripción por
 *  región y la UX de editor son #47.)
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_interactive_region_map_block( $attributes, $content = '', $block = null ) {

	$svg_path = get_template_directory() . '/assets/maps/europe.svg';
	$svg      = @file_get_contents( $svg_path );

	/* Activo ausente o vacío: aviso solo para editores, cadena vacía en el front. */
	if ( ! $svg ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p style="padding:16px;background:#fff8e1;border-left:3px solid #f2c118;font-size:14px;color:#555;">'
				 . esc_html__( 'Mapa interactivo de regiones: no se encuentra el activo del mapa (europe.svg).', 'enterprise-moto' )
				 . '</p>';
		}
		return '';
	}

	/* El SVG es un activo GPL de confianza y de origen propio: se emite tal cual,
	   inline, para que la interactividad de #44 pueda acceder directamente a los
	   nodos <path>. No se pasa por wp_kses_* (eliminaría elementos/atributos SVG). */
	ob_start();
	?>
	<div class="ent-region-map" data-map-level="1" data-maps-base="<?php echo esc_url( get_template_directory_uri() . '/assets/maps/' ); ?>">
		<?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — activo SVG GPL de confianza, ver nota superior. ?>
	</div>
	<?php
	return ob_get_clean();
}
