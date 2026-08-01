<?php
/**
 * Bitácora Enterprise — blocks/route-metadata-map/render.php
 * Bloque «Mapa de ruta con metadatos» (enterprise/route-metadata-map).
 * Dibuja la ruta planificada (opcional) y la registrada (track) como
 * route-comparison, y muestra el espejo de métricas del fichero de metadatos.
 *
 * Commit 1 (#56 · Fase 2 del plan #45): esqueleto. Render mínimo y seguro;
 * el render real (mapa + estadísticas + info) llega en el Commit 4.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function enterprise_render_route_metadata_map_block( $attributes ) {

    // Commit 1 — esqueleto: contenedor vacío placeholder-safe.
    // Sin datos aún: no se emite mapa ni estadísticas (Commit 4).
    return '<div class="ent-map-block ent-route-metadata-map"></div>';
}
