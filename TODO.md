# TODO — Enterprise Moto

Fuente **persistente** de pendientes del tema. Es código versionado en el
repositorio (se consolida en git como el resto). La lista de trabajo en memoria de
cada sesión se sincroniza con este fichero mediante los comandos `create` / `add` /
`list` / `export` / `clear TO-DOs`.

Cada pendiente lleva un **tipo** que indica su propósito: `mejora`, `fix`, `doc` u `otro`.
Los que aún no tienen propósito decidido quedan como `(sin clasificar)`.

Cada TO-DO lleva además un **número (`#`)** que es su identificador **permanente y
único**: se asigna una sola vez al crearlo, es independiente del tipo y del estado, y **el
ítem lo conserva al pasar de «Pendientes» a «Resueltas»** (así se mantiene la traza con su
«Análisis — #N», cuando lo tiene). El número es un **contador global monótono**: el
siguiente TO-DO toma *(el mayor `#` usado en cualquiera de las dos tablas) + 1*. Los números
**no se reciclan ni se renumeran**; si un ítem se abandona, su número se retira, para que una
referencia a `#N` nunca apunte a dos cosas distintas. (El `#0` de «Resueltas» es una
asignación retroactiva a la entrada que ya existía antes de introducir la numeración.)

## Pendientes

| # | Tipo | Descripción | Estado |
|---|------|-------------|--------|
| 1 | doc | Crear la sección **"Decisiones de arquitectura"** en `bitacora-enterprise-design.md` y sembrarla con las tres decisiones ya tomadas: contrato de navegación (§6), contención de floats del cuaderno (§6) y estructura de permalinks (§6). | pendiente |

## Resueltas

| # | Tipo | Descripción | Resuelto en |
|---|------|-------------|-------------|
| 2 | mejora | Unidad «km» defensiva al pintar los km en las **dos** vistas del bloque «Etapas de ruta» (`blocks/post-stages/render.php`): tarjeta `.ent-card__km` y timeline `.ent-tl-km`. Solo presentación; no toca datos ni contrato del campo. Análisis conservado como referencia más abajo. | commit `0fd7c1c` (jul 2026) |
| 0 | otro | Trasladar el seguimiento de TO-DOs a este `TODO.md` independiente y versionado (antes vivía en la sección "Mejoras pendientes" del design doc) y crear aquí la sección "Resueltas" como destino de lo completado. | Reorganización de TO-DOs (jul 2026) |

### Análisis — #2 · [mejora] Unidad «km» defensiva en las tarjetas de «Etapas de ruta»

Redactado para un agente en rol **desarrollador** (que solo dispone del repo, el design doc, las
notas de memoria y este `TODO.md`; no vivió la conversación en que se diagnosticó).

**Fichero:** `blocks/post-stages/render.php`. Dos puntos, **ambos** (localizar por el patrón
`echo esc_html( $route['km'] )`, no por número de línea, que puede cambiar): la vista tarjeta
(`<span class="ent-card__km">`) y la vista timeline (`<span class="ent-tl-km">`).

**Síntoma:** en la tarjeta y en la timeline el km sale sin unidad (p. ej. `1.448`) en lugar de
`1.448 km`.

**Análisis (verificado en código):**
- La tarjeta pinta `$route['km']`, que es `enterprise_get_route_data()['km']` = meta `_route_km`.
- En una entrada tipo *viaje* («Viaje de varios días (a posteriori)»), `_route_km` **no es texto del
  usuario**: al guardar, `enterprise_calculate_viaje_stats()` suma el `_post_km` de las etapas
  filtradas y devuelve `number_format($km_total, 0, ',', '.')` (número formateado, **sin unidad**),
  que se sincroniza a `_route_km` dentro del bloque `if ( $tipo === 'viaje' )` de
  `enterprise_post_stage_save()`. De ahí sale `1.448`.
- En una etapa, `_route_km` = `_post_km`, cuyo campo de metabox tiene placeholder `Ej: 280 km`: el
  valor **puede** venir ya con la unidad. Por eso el añadido debe ser defensivo (evitar `280 km km`).

**Referencia que ya funciona (replicar el patrón):** las estadísticas del propio metabox ya muestran
`1.448 km` añadiendo la unidad **al pintar**, no al guardar: `echo esc_html( $km_calc ) . ' km'` en
`functions.php` (bloque «Estadísticas calculadas al último guardado»). Se guarda el número, se añade
la unidad en el render.

**Regla a implementar (solo presentación):** al pintar, si `$route['km']` tiene contenido y **no
termina ya en «km»** (comparación insensible a mayúsculas y a espacios finales), añadir `' km'`. Si
ya termina en «km», dejarlo tal cual. Si está vacío, no cambiar nada (respetar el
`if ( $show_km && $route['km'] )` existente y la lógica de etiqueta «Detalles»). Resultado esperado:
`1.448` → `1.448 km`; `280` → `280 km`; `280 km` → `280 km`; vacío → sin cambios.

**No hacer:** no tocar los datos guardados, no normalizar `_route_km`, no cambiar el contrato del
campo ni el placeholder (si se quisiera eso, es decisión del rol de **arquitecto**). Es un cambio
solo de presentación, de pocas líneas, en un único fichero, sin efectos colaterales fuera del bloque.
