# TODO — Enterprise Moto

Fuente **persistente** de pendientes del tema. Es código versionado en el
repositorio (se consolida en git como el resto). La lista de trabajo en memoria de
cada sesión se sincroniza con este fichero mediante los comandos `create` / `add` /
`list` / `export` / `clear TO-DOs`.

Cada pendiente lleva un **tipo** que indica su propósito: `mejora`, `fix`, `limpieza`, `doc` u `otro` (`limpieza` = retirar código obsoleto o muerto sin cambiar el comportamiento; se distingue de `fix`, que corrige un fallo observable).
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
| 9 | limpieza | Retirada del campo legacy `_exp_categoria`. Quedó **desbloqueada** al eliminar #5 el ticker antiguo que lo consumía. Limpieza de código (dejar de escribirlo y de leerlo); **no borrar el dato de la BD**. Verificar antes que ningún otro consumidor lo use. | pendiente |
| 10 | limpieza | Limpieza trivial: en `enterprise_post_stage_save()` hay **dos `update_post_meta( _post_paises )` idénticos seguidos**; redundante e inocuo. Eliminar la duplicación. | pendiente |
| 12 | limpieza | CSS muerto en `assets/css/coleccion.css`: las reglas `.ent-trip-collection .trip-grid` (el `grid` y sus media queries) ya no aplican tras #11, porque el bloque dejó de emitir `.trip-grid`. Retirarlas; inocuo, no cambia comportamiento. Surgido al cerrar #11. | pendiente |
| 16 | limpieza | CSS muerto en `assets/css/maps.css`: las reglas `.leaflet-*` (≈l. 69-88, cabecera «Override estilos Leaflet para que encaje con el tema») ya no aplican — el motor de mapas es **OpenLayers 9.2.4**, no Leaflet. Retirarlas; inocuo, no cambia comportamiento. Surgido al cerrar #15. | pendiente |
| 19 | mejora | **Paginar los selectores de términos del editor de «Mapa de rutas por localización» (#17).** Los pickers de categorías y etiquetas del gestor de marcadores leen las **primeras 100** categorías y 100 etiquetas por REST (`apiFetch`, `per_page=100`, orden alfabético). Suficiente hoy; si el sitio superase 100 categorías o 100 etiquetas, las restantes no aparecerían como opción al definir el filtro de un marcador. Paginar o buscar los selectores por REST cuando haga falta. **No** afecta al número de localizaciones (marcadores), que no tiene límite. Surgido al cerrar #17. | pendiente |
| 23 | fix | **Los enlaces de las columnas de widgets del footer quedan ilegibles al pasar el ratón.** Sobre el footer oscuro caen a la regla global `a`/`a:hover` (`style.css` l. 63-64: dorado `--gold-dk` → negro `--black`) porque la regla específica `.footer-widget-area .widget ul li a(:hover)` (l. 761-762) exige un `.widget` **anidado** que ni el widget de bloques, ni el widget clásico, ni el fallback hardcodeado, ni el menú «Secciones» producen. Bug latente **preexistente** que afecta a ambas columnas («Secciones» y «Blog»), aflorado al poblar `footer-2` con un bloque HTML. Fix propuesto: tematizar por contenedor (`.footer-widget-area a`), robusto ante widgets clásicos/de bloques, fallback y menú nativo; sin tocar la `a`/`a:hover` global ni `.footer-bottom`. **El Desarrollador debe confirmar la causa al 100% sobre el HTML renderizado (estructura sin `.widget` anidado + regla ganadora en reposo/hover) antes de implementar.** Requerimientos: `claude/requirements/requirements-23-fix-footer-widget-link-hover.md` (no versionado). | pendiente |

## Resueltas

| # | Tipo | Descripción | Resuelto en |
|---|------|-------------|-------------|
| 22 | fix | la relación entre las etiquetas de una localización del bloque «Mapa de rutas por localización» pasa de OR a AND (una entrada casa solo si lleva todas las etiquetas del marcador); cambio en los dos puntos de resolución (plantilla-destino y `single.php`), sin tocar `enterprise_stage_query()`, el editor ni los datos guardados. Commits `f3a0d7c` (fix), `0e6593e` (docs). Requerimientos: `claude/requirements/closed/requirements-22-fix-tags-and-routes-by-location.md` (no versionado). | §7/§8/§13.12 del design doc + bump 2.9.1 → 2.9.2 (jul 2026) |
| 21 | fix | **«← Volver al mapa» del destino RBL conserva el mapa tras navegar por las entradas filtradas.** Causa: al volver desde una entrada la URL del destino no llevaba `rbl_src`, así que el botón caía al `wp_get_referer()` (la entrada recién dejada); `rbl_src` se perdía en la frontera destino→tarjeta→entrada. Fix: propagar el id del mapa como `loc_src` por toda la cadena —tarjetas del destino, `single.php` (lectura, `$nav_ancestor`, `$back_url`→`rbl_src`, `$nav_suffix`) y el helper único `enterprise_nav_origin_params()` (`functions.php`, consumido por `post-stages/render.php`)— y reponerlo como `rbl_src` al pulsar «← Volver». **Ampliación (`bc2d00a`):** la spec original solo cubría el salto directo destino → etapa; el viaje de vuelta que atraviesa una **colección** (destino → colección → etapa → vuelta) perdía `loc_src`, cerrado con el `loc_src` del helper de ancestro. Commits `be0f5e5`, `bc2d00a`. Requerimientos: `claude/requirements/closed/requirements-21-fix-volver-al-mapa-rbl-src.md` (no versionado). | §13.13 del design doc + bump 2.9.0 → 2.9.1 (jul 2026) |
| 20 | fix | **«Mostrar numeración» ahora gobierna también el pin (punto cuando está desactivado) en `location-map` y `routes-by-location`.** Causa: el flag `showNumbers` solo llegaba a la leyenda; el pin iba siempre numerado porque no se emitía al frontend y `olPinStyle()` se llamaba siempre con número. Fix: el `render.php` de ambos bloques emite `data-show-numbers` en `.ent-map`; cada init (`initLocationMap`/`initRoutesByLocationMap`) lo lee (`dataset.showNumbers !== '0'`, seguro ante ausencia = numerado) y pasa `olPinStyle(showNumbers ? i+1 : null)`; `olPinStyle(null)` dibuja un punto (`<circle>` blanco centrado). Aditivo (geometría/colores/leyenda intactos). El Desarrollador fusionó `map-frontend.js` completo en el Commit 1 (con visto bueno de Juanjo) y dejó `location-map/render.php` en el Commit 2; comportamiento idéntico al especificado, cada commit validado por separado. Commits `5345ebe`, `3a3c286`. Requerimientos: `claude/requirements/closed/requirements-20-fix-numeracion-pines-mapas.md` (no versionado). | §8 del design doc + bump 2.9.0 → 2.9.1 (jul 2026) |
| 18 | mejora | **Rediseño definitivo de la página-destino de «Mapa de rutas por localización» (#17).** Sustituye el grid provisional por **una sección-carrusel por categoría** del marcador: cada sección resuelve `enterprise_stage_query({categoryIds:[cat_i], tagIds:rbl_tag})` y la pinta como **carrusel de `.post-card`** reutilizando la **librería de carrusel del tema** (`.ent-stages` + `carousel.js`/`carousel.css`, sin tocarla; encolado ampliado por `enterprise_carousel_assets()` a la plantilla). Cabecera **«← Volver al mapa»** vía `rbl_src` (id de la página que hospeda el bloque, estampado por `enterprise_rbl_destination_url()`). Contexto de navegación prev/next nuevo **`from_loc`**+`loc_cat`+`loc_tag` (no un `from_tag` genérico): `single.php` reconstruye el carrusel de esa categoría y participa en el arrastre de ancestro. **Enrutado nativo ratificado** (Página + plantilla + Customizer + parámetros); **rewrite descartado** (filtro de IDs sin slug legible). Retirado «(provisional)» de plantilla y Customizer. `.post-card`, `carousel.*`, `location-map` y `post-stages`/`trip-collection` intactos. Implementado en 4 commits (`adfa000`, `bf337e2`, `1a50aea`, `270e7cf`), validados por Juanjo en WordPress real. Requerimientos: `claude/requirements/closed/requirements-18-routes-by-location-destination.md` (no versionado). | §6/§7/§8/§13.12+§13.13 del design doc + bump 2.8.0 → 2.9.0 (jul 2026) |
| 17 | mejora | **Bloque nuevo «Mapa de rutas por localización»** (`enterprise/routes-by-location`; mejora sustancial; `location-map` **intacto byte a byte**). Cada marcador guarda nombre, coordenadas, descripción **opcional** y un **filtro compuesto** sobre las taxonomías existentes **(cat_1 OR … OR cat_n) AND (tag_1 OR … OR tag_m)** (vocabulario §7, resuelto con `enterprise_stage_query()`). El popup —misma estructura que `location-map`— lleva el enlace **«→ Entradas relacionadas»** a la página-destino que auto-compone el grid de las entradas (viaje/etapa/jornada) que casan el filtro; la URL por marcador la deriva `enterprise_rbl_destination_url()` (params `rbl_cat`/`rbl_tag`). Alta con buscador Nominatim + clic en el mapa; gestión en un `wp.components.Modal` con mapa (OpenLayers **bajo demanda**) + **lista propia buscable/paginada** (10/pág.) — **DataViews/DataForm no disponibles** como globales en este WP → fallback (§13.12); los pickers leen términos por REST (`per_page=100` → #19). `map-frontend.js` **100 % aditivo** (rama `routes-by-location`). Almacenamiento **por-bloque**; reutilización = copiar el bloque. Destino **provisional** (`page-templates/template-routes-by-location.php` + Página del Customizer `enterprise_rbl_dest_page`), rediseño definitivo en **#18**. Implementado en 4 commits (`affa4f5`, `4fb1980`, `0443466`, `9dc6711`), validados por Juanjo en WordPress real. Requerimientos: `claude/requirements/closed/requirements-17-routes-by-location.md` (no versionado). Ver «Análisis — #17» y «Notas para documentación — #17» más abajo. | §7/§8/§13.12 del design doc + bump 2.7.3 → 2.8.0 (jul 2026) |
| 15 | fix | **`location-map`: el enlace del popup no navegaba + comentario de cabecera desfasado (mismo componente, un solo TO-DO).** **Causa (verificada en código):** el contenedor del popup `.ent-ol-popup` (un `ol.Overlay` creado en `assets/js/map-frontend.js`) lleva `pointer-events:none` inline para el click-through al mapa; el enlace `.ent-map-popup__link` («→ Leer la entrada»), al ser descendiente, **heredaba** ese `none` y no era objetivo de clic (el `<a>` se pintaba pero no navegaba, ni reaccionaba su `:hover`). El `<a>` estaba bien emitido por `popupHtml()`; el fallo era de la capa de presentación, no de generación del enlace ni de OpenLayers. **Solución (opción A del arquitecto):** `pointer-events:auto` **solo** en `.ent-map-popup__link` (`assets/css/maps.css`), conservando el `none` del contenedor — un descendiente con `auto` es objetivo de eventos aunque el ancestro esté en `none` (comportamiento estándar). CSS-only, sin tocar JS ni la capa de interacción. Commit 1 `fix(location-map)`: la declaración en `maps.css`. Commit 2 `docs(location-map)`: corregido el comentario de cabecera de `blocks/location-map/render.php` (l. 5) «La lógica Leaflet está en…» → **OpenLayers 9.2.4** (§8); solo comentario. Validado por Juanjo en WordPress real (traba única: caché del navegador; confirmado en ventana privada). **Fuera de alcance** (TO-DO nuevo si se pide): retirada de las reglas `.leaflet-*` muertas de `maps.css` (l. 69-88). Implementación por el desarrollador; cierre (doc §8 + bump) por el arquitecto. Requerimientos: `claude/requirements/closed/requirements-15-fix-enlace-popup-location-map.md` (no versionado). | §8 del design doc + bump 2.7.2 → 2.7.3 (jul 2026) |
| 14 | mejora | **Botón real «Última ruta» en el hero de la portada.** El rótulo dorado «Última ruta» del hero (`hero-photo-tag`), antes una pastilla decorativa inerte, pasa a ser un **enlace ancla real** a la sección destacada «Última ruta publicada» de más abajo. Commit `ea11135` `feat(home)`: en `index.php`, `<span class="hero-photo-tag">` → `<a href="#ultima-ruta" class="hero-photo-tag">` (misma clase y literal «Última ruta») e `id="ultima-ruta"` en `<section class="featured-section">`; en `style.css`, `.hero-photo-tag` recibe `display:inline-block; text-decoration:none; cursor:pointer;` (conserva su aspecto de pastilla) y `.featured-section` recibe `scroll-margin-top: calc(var(--nav-h) + 24px)`. **Nativo, sin JS** (reutiliza el `scroll-behavior: smooth` global); el offset del ancla reutiliza el idiom `calc(var(--nav-h) + …)` ya presente en el tema (elementos `sticky`), para que el header fijo no tape el arranque del destino. Texto «Última ruta» confirmado por Juanjo. **Fuera de alcance** (serían TO-DOs nuevos si se piden): `aria-label` y estados hover/focus. Implementación por el desarrollador; cierre (doc §9 + bump) por el arquitecto. Requerimientos: `claude/requirements/closed/requirements-14-boton-ultima-ruta-hero.md` (no versionado). | §9 del design doc + bump 2.7.1 → 2.7.2 (jul 2026) |
| 13 | fix | **Persistencia y cobertura del contexto de regreso («Volver») más allá de un nivel de navegación.** Modelo de **parámetros con nombre + arrastre de ancestro** (jerarquía semánticamente tipada, no pila opaca): el origen **inmediato** gobierna «Volver»/prev-next/etiqueta y el **ancestro** se arrastra en los enlaces (memoria, no secuencia). Commit 1 `7977184` `fix(nav)`: `single.php` construye `$nav_ancestor` (orígenes validados salvo `from_post`), la rama `from_post` lo arrastra en el «Volver» y en `$nav_suffix`, y las etiquetas se atan a `$active_context` (corrige que #8 dependiera de la mera presencia de `from_col`); `post-stages/render.php` propaga `from_post` + ancestro solo en viaje tipo D (solo la línea `$nav_suffix`; scaffolding intacto). Commit 2 `273167b` `fix(archive)`: `from_cat` en `is_category()`. Commit 3 `f0d641d` `fix(home)`: cobertura de portada con **slug real** del término (`$section_cat_slug`; `cat_children`→`$hijo->slug`, `cat`→`$term->slug`; `tag`→`''`), desacoplando identidad de presentación. **No-estampado deliberado** (decisión §13.11): la tarjeta destacada «Última ruta publicada» y la sección «Mientras tanto» son ítems sin listado ni categoría única detrás → estampar fabricaría una secuencia inexistente y violaría §6 → fallback intencionado (comentario en `index.php`). Ampliación de portada acordada al validar (el spec §5 la había excluido sobre una premisa que el runtime desmintió; §13.10 ya la asignaba a #13). Fuera de alcance: tipos `from_*` nuevos (`from_tag`/`from_home`…) y la semántica interna de `from_cat`. Verificación de integridad post-push (byte a byte) hecha por el desarrollador. Requerimientos: [docs/requerimientos-13-persistencia-contexto-navegacion.md](docs/requerimientos-13-persistencia-contexto-navegacion.md) (incl. §7, ampliación de portada). | §6/§9/§13.10/§13.11 del design doc + bump 2.7.0 → 2.7.1 (jul 2026) |
| 8 | mejora | Navegación anterior/siguiente entre los viajes de una «Colección de viajes» (`enterprise/trip-collection`): contexto de origen **`from_col`** (id de la página) + **`col_key`** (hash de identidad del bloque, nuevo helper `enterprise_collection_block_key()`), estampado en el `href` de cada tarjeta (`render.php`); `single.php` reconstruye la secuencia **del bloque concreto** reutilizando `enterprise_stage_query()` con la guarda `showAll`, el «Volver» regresa a la página de colección («← Volver a la colección») y la etiqueta pasa a «Viaje anterior / Siguiente viaje». Cumple el contrato §13.1/§6 con **desambiguación por bloque** por hash de atributos (el `$uid` aleatorio del bloque no sirve). **Acotado** al origen «colección»; la pérdida de contexto por navegación anidada (viaje→etapa→viaje) y la cobertura desigual (portada/`archive.php`) quedaron **fuera** y son #13. Fases 1–2 por el desarrollador; cierre (doc §6/§7/§13.10 + bump) por el arquitecto. Requerimientos: [docs/requerimientos-8-navegacion-viajes-coleccion.md](docs/requerimientos-8-navegacion-viajes-coleccion.md). | §6/§7/§13.10 del design doc + bump 2.6.1 → 2.7.0 (jul 2026) |
| 11 | mejora | «Colección de viajes» (`enterprise/trip-collection`): presentación **configurable carrusel horizontal \| timeline vertical** (atributo `layout`, def. `carousel`) como `post-stages`, **reutilizando** su scaffolding `.ent-stages--{layout}` y `carousel.js`/`carousel.css` **sin tocarlos** y conservando la `.trip-card` (contenedor con ambas clases `.ent-stages .ent-trip-collection`); encolado extendido a `has_block('enterprise/trip-collection')`; puente CSS mínimo en `coleccion.css`. Toggle **«sin límite»** (`showAll` ⇒ `postsPerPage = -1` a nivel de bloque, aplicado en los dos puntos de resolución: render y `enterprise_collection_post_ids()`; sin tocar `enterprise_stage_query()`). Retirada la rejilla fija. **Corrige el fallo de diseño del arquitecto en #5** (había especificado grid en vez de carrusel/timeline). `post-stages` byte-idéntico (validado por Juanjo). Fases 1–2 por el desarrollador; Fase 3 (doc §7/§13.7 + bump) por el arquitecto. Notas de traza (`Notas para documentación — #11`) más abajo. Requerimientos: [docs/requerimientos-11-coleccion-carrusel-timeline.md](docs/requerimientos-11-coleccion-carrusel-timeline.md). | §7/§13.7 del design doc + bump 2.5.0 → 2.6.0 (jul 2026) |
| 5 | mejora | Plantilla curada **«Colección de viajes»** (`page-templates/template-trip-coleccion.php`, renombrada desde `page-bitacora-bloques.php` conservando historia) para publicar colecciones de viajes/rutas ya cerrados compuestas con bloques Gutenberg. Incluye: bloque nuevo `enterprise/trip-collection` (tarjetas de viaje) con **query compartida** con `post-stages` (`enterprise_stage_query()` + `enterprise_collect_stage_blocks()` generalizada); campo por entrada `_post_ticker_name` (ticker); hero con cifras agregadas **cacheadas al guardar** (`_col_stats`/`_col_stats_updated`, sobre la **unión deduplicada** de todos los bloques de filtrado; `save_post` de página y de entrada), **sin** fechas/duración/progreso/estado en vivo; desacople del metabox `_exp_*`. Redescripción y reclasificación (`(sin clasificar)` → `mejora`) respecto de la definición original «colecciones de etapas no ligadas a un viaje». Fases 1–4 por el desarrollador; Fase 5 (documentación §1/§6/§7/§11/§13.7 + bump) por el arquitecto. Notas de traza (`Notas para documentación — #5`) más abajo. Requerimientos: [docs/requerimientos-5-coleccion-viajes.md](docs/requerimientos-5-coleccion-viajes.md). | §1/§6/§7/§11/§13 del design doc + bump 2.4.9 → 2.5.0 (jul 2026) |
| 7 | doc | Reconciliar §4 con el modelo real de filtros del cuaderno (`_filt_*`): reescrita la subsección «Filtro de etapas en el cuaderno» (categorías/etiquetas/fechas por `_filt_category_ids`/`_filt_tag_ids`/`_filt_tag_relation`/`_filt_date_*`, orden/límite por `_filt_orderby`/`_filt_order`/`_filt_limit`, con referencia a §7 y al contrato de navegación §6/§13.1) y marcadas como *legacy* las filas `_exp_categoria`/`_exp_etiquetas` de la tabla de metadatos (solo ticker de «Bitácora con bloques»). Desfase **previo** a #3/#4, procedente de la migración a `_filt_*`. Solo documentación; sin cambio de código ni de versión (entra en el mismo lote 2.4.9). | §4 del design doc (jul 2026) |
| 6 | doc | Documentación de lo construido en #2–#4: §4 «Estadísticas en caliente del cuaderno» (fuente única `enterprise_cuaderno_stats()`, contrato de retorno, tabla progreso/duración por estado, fallbacks); tabla de metadatos actualizada (`_exp_duracion`/`_exp_progreso` retirados del cuaderno, `_exp_fecha_fin` opcional sin «en curso», `_exp_km` como override); decisiones §13.4 (helper km), §13.5 (estadísticas en caliente / fuente única) y §13.6 (metabox consciente de la plantilla). Versión sincronizada 2.4.8 → 2.4.9. | §4/§13 del design doc + bump de versión (jul 2026) |
| 4 | fix | Estadísticas del cuaderno **en caliente** y coherentes en todos los consumidores mediante la fuente única `enterprise_cuaderno_stats()`: grid y cabecera agregada corregidos (resuelto el «punto A» del conteo por `_exp_categoria`), duración/progreso recalculados por `_exp_estado` + fechas (tabla R3, fallbacks R5), subtexto corregido, lista «otras» migrada, y metabox/guardado **conscientes de la plantilla** (cuaderno sin `_exp_duracion`/`_exp_progreso` y fecha de fin sin «en curso»; «Bitácora con bloques» congelada). Implementado en 4 commits troceados. Análisis conservado como referencia más abajo. | commits `6a8045c`, `4355135`, `ed946c0`, `a530b62` (jul 2026) |
| 3 | fix | Helper de presentación compartido `enterprise_km_display()` en `functions.php`, usado en los **cuatro** puntos de pintado de km de una entrada (las dos vistas de «Etapas de ruta» y las dos del cuaderno, `.ps-card-km` / `.exp-tl-km`); retirado el bloque inline de #2 (la regla de formato queda en un único sitio). Solo presentación; no toca datos ni contrato del campo. Análisis conservado como referencia más abajo. | commit `52df77b` (jul 2026) |
| 1 | doc | Sección **«Decisiones de arquitectura»** creada en `bitacora-enterprise-design.md` (§13) y sembrada con las tres decisiones ya validadas: contrato de navegación, contención de floats del cuaderno y estructura de permalinks (cada una autocontenida, con referencia a §6). | Sección §13 del design doc (jul 2026) |
| 2 | mejora | Unidad «km» defensiva al pintar los km en las **dos** vistas del bloque «Etapas de ruta» (`blocks/post-stages/render.php`): tarjeta `.ent-card__km` y timeline `.ent-tl-km`. Solo presentación; no toca datos ni contrato del campo. Análisis conservado como referencia más abajo. | commit `0fd7c1c` (jul 2026) |
| 0 | otro | Trasladar el seguimiento de TO-DOs a este `TODO.md` independiente y versionado (antes vivía en la sección "Mejoras pendientes" del design doc) y crear aquí la sección "Resueltas" como destino de lo completado. | Reorganización de TO-DOs (jul 2026) |

### Análisis — #17 · [mejora] Bloque «Mapa de rutas por localización»

Realidad de la implementación, verificada en el cierre (clon `9dc6711`; base 2.7.3 → 2.8.0). Se conserva como traza cerrada.

**Commits (4, base→HEAD):** `affa4f5` registro + modelo de marcador + render · `4fb1980` editor Modal con mapa, geocoder y **lista buscable** (fallback, no DataViews) · `0443466` popup con enlace de destino derivado · `9dc6711` página-destino provisional.

**Ficheros:** `functions.php` (+56/−1: registro del bloque, editor script con dep `wp-api-fetch`, `require_once` del render, `$has_rbl` en el guard del enqueue frontend, sección de Customizer `enterprise_rbl_dest_page` tipo `dropdown-pages`) · `blocks/routes-by-location/render.php` (nuevo; helper `enterprise_rbl_destination_url()`) · `assets/js/block-routes-by-location.js` (nuevo) · `assets/js/map-frontend.js` (**+91/−0**, aditivo: rama `data-map-type="routes-by-location"`) · `page-templates/template-routes-by-location.php` (nuevo, provisional).

**Decisión clave (verificada por check de solo lectura en consola del editor):** `wp.dataviews`/`wp.dataform` **no existen** como globales en este WordPress (`window.wp` expone `data`, `coreData`, `viewport`, `formatLibrary`… y `wp.components.Modal`, pero no DataViews/DataForm) → se aplicó el **fallback** del spec: lista propia buscable + paginada (10/pág.) con edición, borrado y borrado múltiple, dentro de `Modal`. (La nota de memoria del proyecto ya lo recoge.)

**Decisiones de implementación ratificadas por el arquitecto al cerrar:** (a) Modal: «Guardar y cerrar» confirma, ✕/Esc cancela, clic-fuera desactivado (evita pérdidas); (b) sin `wp_localize_script` — la URL de destino se deriva en servidor (`render.php`), el editor no la necesita; (c) selectores de término limitados a 100 categorías/100 etiquetas por REST → nuevo #19; (d) leyenda del bloque en texto plano, enlace «→ Entradas relacionadas» solo en el popup.

**Integridad:** `location-map` byte-idéntico (diff vacío en el rango); `map-frontend.js` con 0 líneas borradas (rama `"location"`, `popupHtml` y capa de interacción intactas).

### Notas para documentación — #17 · [mejora] (aplicadas al cerrar)

- **§7 catálogo:** fila del bloque `enterprise/routes-by-location` añadida tras la familia de mapas.
- **§8 Sistema de mapas:** nuevo tipo «Mapa de rutas por localización».
- **§13.12:** decisión de arquitectura (localización = filtro guardado sobre taxonomías existentes, no taxonomía nueva; fallback por ausencia de DataViews; `map-frontend.js` aditivo; destino provisional → #18).
- **Bump** 2.7.3 → 2.8.0 en `style.css`, `ENTERPRISE_VERSION` y cabecera del design doc.

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

### Notas para documentación — #5 · [doc] (para el arquitecto)

Apuntes surgidos al **implementar** #5 (Fases 1–4), para que la documentación refleje lo
realmente construido. Insumo para la Fase 5 (design doc §1/§6/§7/§11/§13.7). **#5 sigue
pendiente**: estas notas no son cierre, solo material para documentar y validar.

1. **Query y recolección compartidas (§7):** `enterprise_stage_query( $attrs ): WP_Query`
   extraída de `blocks/post-stages/render.php` (render **byte-idéntico** para el contenido
   existente) y usada por `post-stages` y `trip-collection`. `enterprise_collect_stage_blocks()`
   generalizada para reconocer **ambos** bloques y movida a `functions.php` (deja de vivir en la
   plantilla). `enterprise_collection_post_ids( $page_id )` devuelve la **unión deduplicada** de
   IDs de todos los bloques de filtrado de la página, en orden de aparición; es la fuente única
   del ticker (Fase 3) y del cálculo de cifras (Fase 4). Un post en dos bloques cuenta una vez.

2. **Bloque `enterprise/trip-collection` (§7):** registrado en PHP (`register_block_type`,
   api_version 3) + `render.php` + editor JS vanilla; **sin `block.json`** (patrón del tema).
   Reutiliza la query compartida y los **mismos atributos de filtro** que `post-stages`
   (categorías/etiquetas/fechas/orden/cantidad). Render propio: tarjetas `.trip-card` con badge
   de tipo, año y pie de datos, como **enlaces planos** (sin contexto `from_*`).

3. **Vocabulario y tipos de entrada (`_post_tipo`) (§1 / §11):** el `select` tiene **cuatro**
   valores: `etapa`, `viaje` (el «tipo D»), `jornada` (sin moto), `generica`. **No existe un
   «tipo C» como valor de campo**; la «salida de un día» es cualquier no-`viaje`. Mapeo en la
   colección: `viaje` → cachés `_post_km_calculado` / `_post_etapas_count` / `_post_ferry_count`
   / `_post_km_incompleto`, badge «Viaje»; no-`viaje` → `_post_km`, 1 etapa, ferry si
   `_post_horas_ferry`, badge «Salida»; `jornada` = «Salida» / 0 km. Año del badge:
   `_post_fecha_inicio`; si falta, año de publicación.

4. **Campo `_post_ticker_name` (alta en §11):** texto, en el metabox de entrada; visible en
   `viaje` / `etapa` / `jornada`, oculto en `generica`. Guardado con las guardas habituales
   (nonce/autosave/capacidad), sin unidad ni transformación. Alimenta el ticker de la colección
   (fallback al título, deduplicado, orden de aparición, tope `ENTERPRISE_COLECCION_TICKER_MAX = 16`).

5. **Metadatos `_col_*` (alta en §11):** `_col_stats` = array con `viajes` (int), `km` (int),
   `km_incompleto` (bool), `etapas` (int), `paises` (int, **conteo** de la unión), `ferrys` (int);
   `_col_stats_updated` = texto de fecha ya formateado. Se **cachean al guardar**, no en caliente.
   El contrato está sembrado también en la **cabecera de `template-trip-coleccion.php`**; conviene
   reflejarlo en §11.

6. **Km del hero SIN unidad (contrasta con la spec §3.4):** el hero pinta número + `≈` si es
   incompleto, **sin** « km» (la etiqueta ya dice «Kilómetros»). Las **tarjetas** sí usan
   `enterprise_km_display()`. Decisión validada por Juanjo.

7. **Km con separador de miles y criterio de «incompleto»:** `enterprise_calculate_viaje_stats()`
   guarda `_post_km_calculado` con `number_format(…, ',', '.')` («1.448» = 1448) y `_post_km` se
   guarda **crudo**; el cómputo de la colección normaliza con el helper **`enterprise_km_to_int()`**
   antes de sumar (un `(int)` directo daba 1). **Matiz importante:** a nivel de **etapa**,
   `enterprise_calculate_viaje_stats()` **ya normaliza bien** (usa `floatval(str_replace(',', '.', …))`);
   el fallo estaba solo al **consumir el total ya formateado**, no en la suma por etapas. **Km
   incompleto** = alguna entrada con km vacío o sin dígitos (un 0 numérico sí cuenta), o un viaje
   con `_post_km_incompleto`.

8. **Países (R6-países):** `enterprise_parse_paises()` separa `_post_paises` por « · » o coma,
   hace trim y pone la inicial en mayúscula; la **unión** entre entradas se deduplica
   case-insensitive. El nº de países del hero = tamaño de esa unión.

9. **Ocultar cifras a 0 (matiza la maqueta de 5 cifras fijas; candidata a §13.7):** el hero **no**
   pinta una cifra cuyo valor sea 0 (p. ej. «Ferrys» cuando no hay ferrys). `.col-stats` pasó de
   `grid` fijo de 5 columnas a **flex** (`flex:1 1 0; min-width:140px; flex-wrap`) para repartirse
   según el nº de cifras visibles; se retiraron los overrides `grid-template-columns` de las media
   queries. Decisión de Juanjo.

10. **Cacheo al guardar (decisión §13.7; contraste con §13.5):** `enterprise_compute_collection_stats()`
    computa sobre la unión deduplicada. Recache en `save_post` de la **página** (R8) y de **cualquier
    entrada** (R9, prioridad 20 para leer las cachés de viaje ya frescas, actualizadas a prioridad 10).
    Es cacheado **en escritura**, no en render — a diferencia de las estadísticas del cuaderno **en
    caliente** de §13.5. Edge conocido: el vaciado a papelera no dispara `save_post` (se corrige al
    re-guardar la página).

11. **Desacople `_exp_*` (Fase 3b; §11 / §13.7):** la colección **no** usa el metabox de expedición
    (no se registra en su plantilla). Retirados: el set de campos «congelado», el ticker antiguo por
    `_exp_categoria` / `_exp_etiquetas` del metabox y su guardado, los dos hooks de encolado de la
    plantilla vieja y el filtro muerto `enterprise_show_expedition_metabox`. **`_exp_categoria` queda
    desbloqueado para su retirada, pero eso es un TO-DO nuevo, fuera de #5.**

12. **Encolado de `coleccion.css` (§7):** vía el `'style'` del bloque `trip-collection` + un hook de
    plantilla gateado a `page-templates/template-trip-coleccion.php` (matiza «solo si la plantilla
    está activa»). El carrusel se auto-encola por `has_block`. Bugfix: `var(--display)` inexistente →
    `var(--font-display)` en `coleccion.css`.

13. **Plantillas (§6):** alta de la fila «Colección de viajes»
    (`page-templates/template-trip-coleccion.php`, `git mv` desde `page-bitacora-bloques.php`
    conservando historia) y retirada de la fila «Bitácora con bloques».

14. **Fuera de alcance de #5 (para no reintroducirlo por error):** (a) **navegación anterior/siguiente
    entre los viajes de la colección** — las tarjetas son enlaces planos; si se aborda, será un TO-DO
    nuevo que deberá cumplir §13.1/§6 (propagar contexto y reconstruir la secuencia desde el mismo
    bloque que la genera, con desambiguación por bloque al haber varios); (b) **retirada del legacy
    `_exp_categoria`** (TO-DO nuevo, ya desbloqueado); (c) **renombrar `page-cuaderno-de-bitacora.php`**;
    (d) **flag «incluir en cifras» por bloque**.
15. **La Fase 5** definida en el documento de requerimientos no has sido ejecutada por el Desarrollador a petición de 
    Juanjo. Deberá completarla por tanto el Arquitecto.

**Observación al margen (posible TO-DO nuevo, fuera de #5):** en `enterprise_post_stage_save` hay
**dos `update_post_meta` idénticos de `_post_paises`** seguidos; redundante e inocuo, candidato a
limpieza trivial aparte.

### Notas para documentación — #11 · [doc] (para el arquitecto)

Apuntes surgidos al **implementar** #11 (Fases 1–2), para que la documentación refleje lo realmente
construido. Insumo para la Fase 3 (design doc **§7**; revisar **§13.7**; + bump 2.5.0 → 2.6.0; mover
#11 a «Resueltas»). **#11 sigue pendiente**: estas notas no son cierre, solo material para documentar.

1. **La celda de `enterprise/trip-collection` en §7 queda desfasada.** Hoy dice «Rejilla de tarjetas de
   viaje… el render diverge (tarjetas vs. timeline)». Tras #11 el bloque **ya no es una rejilla fija**:
   presentación **configurable** carrusel horizontal | timeline vertical (atributo `layout`, def.
   `carousel`), como `post-stages`. Además, toggle **«sin límite»** (`showAll`). Conviene reescribir esa
   celda: reutiliza el scaffolding y los assets de `post-stages` conservando la `.trip-card`; sigue con
   enlaces planos (sin `from_*`; navegación entre viajes = #8). Capacidad añadida en #11 (v2.6.0).

2. **Reutilización del layout, no reimplementación (§7).** El render emite el contenedor con **ambas**
   clases: `.ent-stages .ent-stages--{layout}` (el layout lo gobierna `carousel.css`) **y**
   `.ent-trip-collection` (mantiene el estilado de la `.trip-card` en `coleccion.css`). La `.trip-card`
   se compone **una vez** y se envuelve en `.ent-stages__slide` (carrusel: cabecera con nav prev/next +
   contador y `.ent-stages__dots`, solo si hay más de una entrada) o en `.ent-tl-item` / `.ent-tl-dot-col`
   numerado (timeline). `carousel.js` autoinicializa `.ent-stages--carousel` **sin cableado nuevo**.

3. **Encolado (§7).** La condición de `enterprise_carousel_assets()` se extendió para disparar también con
   `has_block('enterprise/trip-collection')` (antes solo `post-stages`), de modo que `carousel.css` /
   `carousel.js` se cargan también en páginas con el bloque de colección.

4. **Puente CSS mínimo (§7).** En `coleccion.css`, solo el **encaje** de la tarjeta, escopado a
   `.ent-trip-collection.ent-stages--{layout}`: `height:100%` en carrusel (slide) y `margin-bottom:12px`
   en timeline (fila). La lógica de layout **no** se duplica: vive en `carousel.css`.

5. **«Sin límite» y su efecto en las cifras (§13.7).** El toggle `showAll` fuerza `postsPerPage = -1` **a
   nivel de bloque**, antes de la query compartida (que ya mapea `-1` nativamente); **no** se tocó
   `enterprise_stage_query()`. Punto fino para §13.7: el mismo ajuste `showAll → -1` se aplica **también**
   en `enterprise_collection_post_ids()` (**segundo punto de resolución**), así el hero y el ticker de la
   colección cuentan **todas** las entradas cuando el toggle está activo (conducta correcta, no un fallo).
   La guarda solo actúa con `!empty($attrs['showAll'])`, atributo que solo emite `trip-collection`; los
   bloques `post-stages` que pasan por esa función quedan **intactos**.

6. **`post-stages` byte-idéntico.** No se tocó ningún fichero de `post-stages` ni `enterprise_stage_query()`;
   su render sigue idéntico (validado por Juanjo comparando el HTML antes/después).

7. **Resto sin uso (posible TO-DO trivial aparte, fuera de #11).** Al retirar la rejilla fija, las reglas
   `.ent-trip-collection .trip-grid` (el `grid` y sus media queries) de `coleccion.css` quedan **muertas**
   (ya no se emite ningún `.trip-grid`). Se dejaron en su sitio para acotar el cambio a lo pedido (R8); su
   limpieza sería un TO-DO trivial independiente.

8. **La Fase 3** definida en el documento de requerimientos (doc §7 / revisar §13.7 + bump 2.5.0 → 2.6.0 +
   mover #11 a «Resueltas») **no** la ejecuta el Desarrollador, a petición de Juanjo. La completa el Arquitecto.
