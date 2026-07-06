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

La tabla **«Resueltas»** —con sus bloques «Análisis — #N» y «Notas para
documentación — #N»— se conserva **solo como traza cerrada** de cómo se resolvió
cada ítem: son las entradas que se usaron para cerrarlo, no trabajo pendiente. No es
lista de tareas ni requiere re-verificación al abrir sesión o al releer el fichero,
y la redacción cautelosa dentro de la nota de un ítem resuelto («a confirmar»,
«conviene afinar», «por si…») **no lo reabre**. El backlog vivo es **exclusivamente
la tabla «Pendientes»**; un ítem resuelto solo vuelve a él si se crea un TO-DO
numerado nuevo.

## Pendientes

| # | Tipo | Descripción | Estado |
|---|------|-------------|--------|
| 5 | (sin clasificar) | **(futuro)** Reorientar `page-bitacora-bloques.php` a un propósito propio: publicar colecciones de etapas **no ligadas a un viaje** (p. ej. etapas por la provincia de Tarragona a lo largo de los años), compuestas con bloques Gutenberg. **Nunca** alternativa a `page-cuaderno-de-bitacora.php`. Su metabox/campos se diseñarán aparte y no condicionan el del cuaderno. Concepto y campos a definir cuando se aborde. | pendiente |

## Resueltas

| # | Tipo | Descripción | Resuelto en |
|---|------|-------------|-------------|
| 7 | doc | Reconciliar §4 con el modelo real de filtros del cuaderno (`_filt_*`): reescrita la subsección «Filtro de etapas en el cuaderno» (categorías/etiquetas/fechas por `_filt_category_ids`/`_filt_tag_ids`/`_filt_tag_relation`/`_filt_date_*`, orden/límite por `_filt_orderby`/`_filt_order`/`_filt_limit`, con referencia a §7 y al contrato de navegación §6/§13.1) y marcadas como *legacy* las filas `_exp_categoria`/`_exp_etiquetas` de la tabla de metadatos (solo ticker de «Bitácora con bloques»). Desfase **previo** a #3/#4, procedente de la migración a `_filt_*`. Solo documentación; sin cambio de código ni de versión (entra en el mismo lote 2.4.9). | §4 del design doc (jul 2026) |
| 6 | doc | Documentación de lo construido en #2–#4: §4 «Estadísticas en caliente del cuaderno» (fuente única `enterprise_cuaderno_stats()`, contrato de retorno, tabla progreso/duración por estado, fallbacks); tabla de metadatos actualizada (`_exp_duracion`/`_exp_progreso` retirados del cuaderno, `_exp_fecha_fin` opcional sin «en curso», `_exp_km` como override); decisiones §13.4 (helper km), §13.5 (estadísticas en caliente / fuente única) y §13.6 (metabox consciente de la plantilla). Versión sincronizada 2.4.8 → 2.4.9. | §4/§13 del design doc + bump de versión (jul 2026) |
| 4 | fix | Estadísticas del cuaderno **en caliente** y coherentes en todos los consumidores mediante la fuente única `enterprise_cuaderno_stats()`: grid y cabecera agregada corregidos (resuelto el «punto A» del conteo por `_exp_categoria`), duración/progreso recalculados por `_exp_estado` + fechas (tabla R3, fallbacks R5), subtexto corregido, lista «otras» migrada, y metabox/guardado **conscientes de la plantilla** (cuaderno sin `_exp_duracion`/`_exp_progreso` y fecha de fin sin «en curso»; «Bitácora con bloques» congelada). Implementado en 4 commits troceados. Análisis conservado como referencia más abajo. | commits `6a8045c`, `4355135`, `ed946c0`, `a530b62` (jul 2026) |
| 3 | fix | Helper de presentación compartido `enterprise_km_display()` en `functions.php`, usado en los **cuatro** puntos de pintado de km de una entrada (las dos vistas de «Etapas de ruta» y las dos del cuaderno, `.ps-card-km` / `.exp-tl-km`); retirado el bloque inline de #2 (la regla de formato queda en un único sitio). Solo presentación; no toca datos ni contrato del campo. Análisis conservado como referencia más abajo. | commit `52df77b` (jul 2026) |
| 1 | doc | Sección **«Decisiones de arquitectura»** creada en `bitacora-enterprise-design.md` (§13) y sembrada con las tres decisiones ya validadas: contrato de navegación, contención de floats del cuaderno y estructura de permalinks (cada una autocontenida, con referencia a §6). | Sección §13 del design doc (jul 2026) |
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

### Análisis — #3 · [fix] Unidad «km» defensiva en las tarjetas de entradas filtradas del cuaderno

Redactado para un agente en rol **desarrollador** (que solo dispone del repo, el design doc, las
notas de memoria y este `TODO.md`; no vivió la conversación en que se diagnosticó).

**Decisión (rol arquitecto):** se implementa mediante un **helper de presentación compartido**
`enterprise_km_display()` en `functions.php`, usado en los **cuatro** puntos donde hoy se pinta el km
de una entrada (los dos de «Etapas de ruta» + los dos del cuaderno), **no** con el patrón inline.
Motivo: consolidar la regla de formato de km en un único sitio (separación de responsabilidades —
«cómo se muestra un km» vive en un solo lugar). Esto **re-toca `blocks/post-stages/render.php`, que es
código validado en #2**; Juanjo **asume la re-validación de «Etapas de ruta»** en WordPress tras el
cambio (decisión explícita del arquitecto, no una mejora por iniciativa del desarrollador).

**Síntoma:** en las tarjetas de las entradas filtradas del cuaderno el km sale sin unidad (p. ej.
`1.448` en vez de `1.448 km`). Es el mismo defecto que #2 resolvió en «Etapas de ruta», ahora en
`page-cuaderno-de-bitacora.php`.

**Análisis (verificado en código):**
- Los cuatro puntos pintan el km a partir de `enterprise_get_route_data()['km']`, que es la meta
  `_route_km` en crudo (`functions.php:274`). Es el **mismo dato** en los cuatro.
- `_route_km` **puede venir sin unidad o con ella**: en una entrada tipo *viaje* es un número
  formateado sin unidad (`1.448`, de `enterprise_calculate_viaje_stats()`); en una *etapa* es
  `_post_km`, cuyo placeholder es `Ej: 280 km`, así que puede traer ya el «km». Por eso el añadido
  debe ser **defensivo** (no duplicar: evitar `280 km km`).
- Puntos concretos:
  - `blocks/post-stages/render.php`: vista tarjeta `.ent-card__km` y vista timeline `.ent-tl-km`.
    Hoy resuelto por #2 con un bloque inline `$km_display` (~líneas 165-168) que se pinta en ambas.
  - `page-cuaderno-de-bitacora.php`: vista carrusel `.ps-card-km` y vista timeline `.exp-tl-km`. Hoy
    pintan `$route_e['km']` en crudo; cada vista tiene su propio bucle con su
    `$route_e = enterprise_get_route_data();`.

**Referencia que ya funciona (patrón a consolidar):** la lógica defensiva de #2 y, antes, las
estadísticas del metabox en `functions.php`: la unidad se añade **al pintar**, nunca al guardar.

**Requerimiento — helper de presentación en `functions.php`:**

```php
/**
 * Devuelve el valor de km listo para pintar, añadiendo la unidad «km»
 * de forma defensiva. Solo presentación: no lee metas ni toca datos.
 *
 * @param string $km Valor crudo (_route_km / _exp_km), con o sin unidad.
 * @return string   Cadena para mostrar; '' si la entrada está vacía.
 */
function enterprise_km_display( $km ) {
    $km = (string) $km;
    if ( $km === '' ) {
        return '';
    }
    if ( ! preg_match( '/km\s*$/i', $km ) ) {
        $km .= ' km';
    }
    return $km;
}
```

**Puntos de uso (los cuatro). El guard `if` se mantiene sobre el valor CRUDO; solo cambia el echo:**
- `blocks/post-stages/render.php`:
  - Vista tarjeta: bajo el `if ( $show_km && $route['km'] )` existente →
    `<span class="ent-card__km"><?php echo esc_html( enterprise_km_display( $route['km'] ) ); ?></span>`.
  - Vista timeline (`.ent-tl-km`): idéntico, con `$route['km']`.
  - **Eliminar** el bloque inline `$km_display` introducido por #2 (~líneas 165-168): queda
    sustituido por el helper (no dejar la regla duplicada).
- `page-cuaderno-de-bitacora.php`:
  - Vista carrusel: bajo el `if ( $pres_km && $route_e['km'] )` existente →
    `<div class="ps-card-km"><?php echo esc_html( enterprise_km_display( $route_e['km'] ) ); ?></div>`.
  - Vista timeline (`.exp-tl-km`): idéntico, con `$route_e['km']`.

**Resultado esperado (los cuatro):** `1.448` → `1.448 km`; `280` → `280 km`; `280 km` → `280 km`;
vacío → sin cambios (el guard sobre el valor crudo evita pintar nada).

**No hacer:** no tocar datos guardados, no normalizar `_route_km`, no cambiar el contrato del campo
ni el placeholder. No dejar copias inline de la regla: tras este cambio, la **única** fuente de la
regla de formato de km es `enterprise_km_display()`.

**Consecuencia / validación:** al modificar el `render.php` de «Etapas de ruta» (código de #2),
**re-validar también esa vista** además de las dos del cuaderno. Juanjo asume esa re-validación.
Cambio solo de presentación; no afecta a datos ni al contrato del campo.

### Análisis — #4 · [fix] Estadísticas del cuaderno en caliente, coherentes en todos los consumidores, y rediseño del metabox

Redactado para un agente en rol **desarrollador** (que solo dispone del repo, el design doc, las
notas de memoria y este `TODO.md`; no vivió la conversación en que se diseñó). Todas las referencias
`fichero:línea` son del estado del repo en el momento de redactar; localiza por patrón si han
cambiado. Este ítem toca metabox, guardado, plantilla y `functions.php` a la vez: **antes de empezar,
commit + push como punto de restauración**, e implementa por los commits troceados del final.

#### 0. Decisiones de arquitectura que fija este ítem (contexto)

- El cuaderno calcula sus estadísticas **en caliente** (no cacheadas al guardar, a diferencia del
  post tipo D «viaje»). Motivo: sus etapas cambian a lo largo del tiempo sin re-guardar la página;
  cachear al guardar quedaría obsoleto en cuadernos `activo`. El coste en caliente es asumible
  (ver requisito de cebado de meta) y el volumen real es de 2-3 cuadernos/año.
- El campo de estado canónico es **`_exp_estado`** (`preparando`/`activo`/`finalizado`). Se retira el
  uso del legacy `_exp_en_ruta` **como criterio de lógica** (la barra de progreso hoy lo usa) y la
  semántica «`_exp_fecha_fin` vacío = en curso»: el estado lo dicta `_exp_estado`, no la ausencia de
  fecha. `_exp_en_ruta` se sigue **escribiendo** en el guardado (backward compat) — no se toca.
- Cada plantilla tiene su propio conjunto de campos: lo que se retire del metabox del cuaderno **no**
  se retira del de `page-bitacora-bloques.php`. Invariante duro: **no romper la plantilla bloques**.

#### 1. Estado actual (verificado en código)

- **Cálculo en caliente ya existe en el cuaderno**, `page-cuaderno-de-bitacora.php`:
  - Query de etapas por filtros `_filt_*` (115-159) → `$total_etapas = $etapas_query->found_posts` (161).
  - Suma de km recorriendo `_route_km` de las entradas (167-176), con parseo **entero**
    (`preg_replace('/[^0-9]/','')` + `intval`).
  - `_exp_km` como override manual con fallback al cálculo (62, 178-181): si vacío, se calcula; el
    valor calculado **no se persiste**.
  - Duración/salida/días: autocalculados desde `_exp_fecha_inicio/fin` (70-84); hoy, si falta fin,
    usa `time()` (80) — esa es la vieja semántica «en curso» a retirar.
- **Barra de progreso** hoy: campo manual `_exp_progreso` (67), **disparada por el legacy
  `_exp_en_ruta`** (271), y su subtexto (284-288) está roto (imprime «N de [duración] etapas
  publicadas», mezcla conteo con cadena de duración).
- **Metabox compartido por ambas plantillas**, `functions.php`:
  - Sección 1 «Datos de la expedición» = array `$exp_fields` (511-520), se pinta para **ambas**
    plantillas **antes** de la bifurcación `if ($is_bloques)` (548). Incluye `_exp_fecha_inicio`
    (514), `_exp_fecha_fin` (515, label «(vacío = en curso)»), `_exp_salida` (516), `_exp_duracion`
    (517), `_exp_km` (518), `_exp_progreso` (520).
  - Guardado común: `$text_fields` (722-732) guarda salida/duración/km (+categoría/etiquetas);
    `_exp_progreso` (735-737); `_exp_fecha_*` (740-747); estado + `_exp_en_ruta` (750-753).
- **Consumidores de km/etapas fuera del cuaderno**, `functions.php`:
  - Tarjeta del grid «VIAJES COMPLETADOS» (`.past-grid`/`.past-card`, 2674-2698): km desde `_exp_km`
    **en crudo, sin fallback** (2677, pintado `?: '—'` en 2693); etapas contadas con una query por
    **`_exp_categoria` (slug), campo DEPRECATED** (2679-2685, 2694). **Este es el «punto A»**: si
    `_exp_km` está vacío → «—»; si `_exp_categoria` está vacío (lo normal) → «0», o un conteo que no
    coincide con el listado real del cuaderno.
  - Cabecera agregada (2377-2397): suma `_exp_km` en crudo sobre los cuadernos `finalizado`.
  - Listas «otras»: `page-cuaderno-de-bitacora.php:642` y `page-bitacora-bloques.php:351` leen `_exp_km`.
- **`page-bitacora-bloques.php`** lee `_exp_salida/_exp_duracion/_exp_km/_exp_progreso` como metas
  **estáticas** (24-33) y **no tiene `_filt_*`** (su metabox hace `return` antes de la sección de
  filtros, `functions.php:548-563`). No entra en el cálculo en caliente.

#### 2. Requerimiento R1 — Función compartida `enterprise_cuaderno_stats( $page_id )`

Nueva función en `functions.php`. Centraliza el cálculo en caliente hoy embebido en el template, para
que **todos** los consumidores muestren lo mismo. Contrato:

- **Entrada:** `$page_id` de una página de cuaderno.
- **Salida (array):**
  - `km` → si `_exp_km` tiene contenido, **gana el override manual** (tal cual, incluidos valores
    curados como «~3.200 km»); si vacío, la **suma en caliente** de `_route_km` de las entradas que
    casan los `_filt_*` del cuaderno (misma query que 115-159, mismo parseo entero actual). Devuelve el
    valor **sin** forzar unidad (el pintado usa `enterprise_km_display()` de #3 donde proceda).
  - `etapas` → `found_posts` de esa misma query `_filt_*` (nunca por `_exp_categoria`).
  - `dias_totales` → días de `_exp_fecha_inicio` a `_exp_fecha_fin` si ambas existen; si falta fin, ver
    R5 (fallback por estado).
  - `dias_transcurridos` → días de `_exp_fecha_inicio` a **hoy** (fecha de consulta).
  - (Opcional) exponer también `fecha_inicio`/`fecha_fin` resueltas para que el render no relea metas.
- **Requisito de rendimiento (obligatorio, no opcional): cebado de meta en bloque.** La suma de
  `_route_km` NO debe hacer un `get_post_meta()` por etapa sobre posts no cacheados. Deja que el
  `WP_Query` hidrate los posts (comportamiento por defecto, `update_post_meta_cache` activo) **o**
  ceba con `update_meta_cache( 'post', $ids )` antes del bucle. Con esto el coste es ~2-3 consultas por
  cuaderno y **constante respecto al número de etapas**; sin esto degenera a `nº de etapas` consultas.
  Esto vale aunque el volumen sea bajo: es corrección de patrón, no micro-optimización.
- **Principio:** las estadísticas de un listado salen de la **misma fuente que genera el listado**
  (`_filt_*`), coherente con el «Contrato de navegación» (§6 del design doc). Retira `_exp_categoria`
  de esta ruta.
- El **progreso** no va en esta función necesariamente (depende de `_exp_estado` y de la fecha de hoy,
  y solo lo consume la página del cuaderno); puede vivir en el template o en la función — decisión de
  implementación. Lo que importa es la regla de R3.

#### 3. Requerimiento R2 — El cuaderno usa la función; migrar consumidores

- `page-cuaderno-de-bitacora.php`: sustituir el cálculo embebido (161, 167-181) por
  `enterprise_cuaderno_stats()`. La barra lateral y el hero siguen mostrando lo mismo.
- Tarjeta del grid `.past-card` (`functions.php` 2674-2698): km y etapas desde
  `enterprise_cuaderno_stats()` en vez de `_exp_km` crudo (2677/2693) y del conteo por `_exp_categoria`
  (2679-2685/2694). **Esto resuelve el punto A**: km vacío → se calcula; etapas → conteo real por
  `_filt_*`. `_exp_km` manual sigue ganando si está relleno.
- Cabecera agregada (2377-2397): sumar km vía la función para no quedar incoherente con las tarjetas.
  (Puede ir en su propio commit; ver plan.)
- Lista «otras» del cuaderno (`page-cuaderno-de-bitacora.php:642`): migrar a la función. La de
  `page-bitacora-bloques.php:351` **se deja como está** (plantilla congelada, R4).

#### 4. Requerimiento R3 — Duración y progreso (tabla completa, sin casos abiertos)

Días: `dias_totales` = inicio→fin; `dias_transcurridos` = inicio→hoy. El **estado manda en los
extremos; las fechas solo interpolan en `activo` con fin**. La barra de progreso pasa a dispararse por
**`_exp_estado`** (no por `_exp_en_ruta`, 271). El subtexto roto (284-288) se corrige acorde.

| Estado | Fecha fin | Progreso mostrado | Duración mostrada |
|--------|-----------|-------------------|-------------------|
| `preparando` | — | No se muestra (ni barra ni progreso) | No se muestra |
| `activo` | con fin | Barra de % = `clamp( dias_transcurridos / dias_totales × 100, 0, 100 )` | Días totales (inicio→fin) |
| `activo` | sin fin | **Sin %**: indicador gráfico «día N en ruta» (N = `dias_transcurridos`) | «N días, en curso» |
| `finalizado` | con fin | 100 % (fijo por estado) | Días totales (inicio→fin) |
| `finalizado` | sin fin (heredado) | 100 % (fijo por estado) | Días inicio→(fecha última etapa) — ver R5 |

Notas: en `activo` con fin, hoy < inicio ⇒ 0 %; hoy > fin ⇒ 100 % (lo cubre el `clamp`). En
`finalizado` el progreso es 100 **por definición del estado**, aunque las fechas dieran otra cosa.

#### 5. Requerimiento R4 — Metabox: retirar del cuaderno, congelar en bloques

Invariante «no romper bloques». Hoy `$exp_fields` (511-520) y el guardado (722-747) son comunes; hay
que hacerlos **conscientes de la plantilla** (`$is_bloques` / `$template`):

- **Plantilla `page-cuaderno-de-bitacora.php` (conjunto reducido):**
  - **Quitar del render y del guardado:** `_exp_duracion` (517; se calcula) y `_exp_progreso` (520,
    735-737; se calcula). El dato `_exp_progreso` guardado deja de leerse; no hace falta borrarlo de la
    BD, pero deja de pintarse su input.
  - **Mantener:** `_exp_nombre`, `_exp_subtitulo`, `_exp_salida`, `_exp_km`, `_exp_paises`,
    `_exp_fecha_inicio`, `_exp_fecha_fin`, `_exp_estado`.
  - `_exp_fecha_inicio/fin`: **opcionales** (sin validación dura que impida guardar). **Quitar la
    semántica y el label «(vacío = en curso)»** de `_exp_fecha_fin` (515) — el estado lo da
    `_exp_estado`. Puede existir un viaje con salida conocida y regreso desconocido (fin vacía).
- **Plantilla `page-bitacora-bloques.php` (congelada):** conserva **exactamente** su conjunto de
  campos actual, incluidos `_exp_duracion` y `_exp_progreso`, y su guardado. No se rediseña aquí
  (eso es el futuro #5).
- No tocar la escritura de `_exp_en_ruta` (752): sigue por backward compat.

#### 6. Requerimiento R5 — Fallback defensivo (datos heredados / fechas ausentes)

El render nunca debe romper ni emitir warnings por metas vacías:

- `finalizado` **sin `_exp_fecha_fin`** (cuadernos heredados con la vieja semántica): usar como fin la
  **fecha de la etapa más reciente** del conjunto `_filt_*` (la primera de la query, orden actual). Si
  no hay etapas, degradar: progreso 100 por estado, duración omitida.
- `activo` **sin `_exp_fecha_inicio`**: no calcular progreso ni «día N»; ocultar el widget sin romper.
- Cualquier división usa `dias_totales > 0` como guarda.

#### 7. Qué NO hacer

- **No** eliminar `_exp_salida` (se mantiene como override con autorelleno desde la fecha de inicio).
- **No** eliminar ni tocar `_exp_categoria` / `_exp_etiquetas` como campos: siguen usados por el ticker
  de la plantilla bloques y por backward-compat. Solo se deja de **usar `_exp_categoria`** en la
  tarjeta del grid (R2). Su limpieza es un asunto aparte, acoplado al futuro #5.
- **No** cambiar el contrato del cálculo de km (sigue entero) ni normalizar `_route_km`.
- **No** cachear las estadísticas al guardar (queda descartado imitar al viaje).
- **No** eliminar campos del metabox de forma global (rompería bloques).

#### 8. Validación (Juanjo, en WordPress real) y plan de commits

Commit + push previo. Trocear para validar cada bloque antes de seguir:

1. **`enterprise_cuaderno_stats()`** aislada (sin cambiar consumidores). Validar comparando sus
   números con los que hoy muestra la barra lateral del cuaderno.
2. **Migrar tarjeta del grid + cabecera agregada** a la función (resuelve punto A). Validar el grid
   «VIAJES COMPLETADOS».
3. **Duración/progreso en caliente** + disparador por `_exp_estado` + subtexto corregido. Validar la
   barra en: `activo` con fin, `activo` sin fin, `finalizado`, `preparando`.
4. **Metabox plantilla-consciente**: retirar duración/progreso del cuaderno, quitar «(vacío = en
   curso)» de fecha fin, congelar bloques. Validar el metabox de **ambas** plantillas (el de bloques
   debe quedar idéntico a hoy).

Casos de validación a cubrir: cuaderno `activo` con fin / `activo` sin fin / `finalizado` con fin /
`finalizado` heredado sin fin / `preparando`; `_exp_km` manual relleno vs vacío; página con plantilla
«Bitácora con bloques» (metabox y render intactos).

#### 9. Documentación (AL CIERRE, tras validar — no antes)

- Actualizar design doc §4 «Metadatos de un cuaderno» (199-212): retirar `_exp_duracion` y
  `_exp_progreso` como campos manuales del cuaderno; actualizar `_exp_fecha_fin` (quitar «vacío si
  activo»); reflejar que km/etapas/duración/progreso se calculan en caliente. Actualizar §4 «Filtro de
  etapas» / §4 estados si procede.
- Registrar en «Decisiones de arquitectura» (que crea el #1): cuaderno calcula en caliente; metabox
  por plantilla; progreso por estado + fechas.
- Subir versión en los **tres** sitios (`style.css`, `ENTERPRISE_VERSION`, header del design doc).

### Notas para documentación — #6 · [doc] (para el arquitecto)

Apuntes surgidos al **implementar** #2–#4, para que la documentación refleje lo realmente
construido. Complementan el «Análisis — #4 §9» (centrado en el design doc de #4); no lo repiten.

1. **Formato de km (de #3):** la regla de la unidad «km» vive ahora en un único sitio, el helper
   `enterprise_km_display()` (`functions.php`). Es la **única fuente** de formato de km y la usan los
   cuatro puntos de pintado. Candidata a entrada propia en «Decisiones de arquitectura» (a #3 no se le
   documentó decisión).

2. **Fuente única de estadísticas del cuaderno:** `enterprise_cuaderno_stats( $page_id )` devuelve
   `estado`, `km` (SIN unidad — se pinta con el helper), `etapas`, `dias_totales`, `dias_transcurridos`,
   `fecha_inicio`, `fecha_fin` (resuelta; puede venir de la última etapa por R5) y `fin_heredada`. La
   calculan en caliente todos los consumidores (barra lateral, hero, grid, cabecera agregada, «otras»).
   La query de etapas del template **no** se elimina: el listado necesita los objetos post y de ella
   sale el conteo.

3. **`_exp_en_ruta` retirado como criterio de lógica en TODO el template** (no solo la barra de
   progreso): eyebrow «actualizado hace», badge de última etapa y punto del estado en la lateral pasan
   a `_exp_estado === 'activo'`. `_exp_en_ruta` se sigue **escribiendo** en el guardado (backward
   compat), pero ya no se lee para lógica.

4. **`_exp_duracion` / `_exp_progreso`:** retirados del metabox y del render del cuaderno; se conservan
   como campos SOLO en la plantilla «Bitácora con bloques» (congelada). El dato antiguo no se borra de
   la BD: solo deja de leerse y de editarse en el cuaderno.

5. **Comportamiento a CONFIRMAR y documentar:** un cuaderno `finalizado` sin fecha de fin y sin etapas
   muestra la barra de progreso al **100 %** (R5: «progreso 100 por estado») y **omite** la duración.
   Es el caso «Semana Santa 2025». Conforme al análisis, pero conviene dejarlo explícito por si el
   arquitecto quiere afinar la UX.
