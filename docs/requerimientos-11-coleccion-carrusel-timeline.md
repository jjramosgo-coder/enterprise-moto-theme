# Requerimientos de desarrollo — #11 (`mejora`) · «Colección de viajes»: layout carrusel/timeline + sin límite

> Documento del **arquitecto** para el **rol desarrollador**. Es una especificación,
> no código. El desarrollador implementa, y **Juanjo valida en WordPress real** antes
> de cerrar. Fuente de verdad: el repo `jjramosgo-coder/enterprise-moto-theme`.
> Versión base: **2.5.0**. Antes de tocar nada: clon fresco + `git ls-remote`, y leer
> `bitacora-enterprise-design.md` y `TODO.md`.

---

## 0. Objetivo y contexto

El bloque `enterprise/trip-collection` (alta en #5, v2.5.0) pinta hoy las entradas
filtradas como un **grid fijo** de tarjetas de viaje. Debe pasar a **comportarse como
`enterprise/post-stages`**: la misma tarjeta de viaje, pero con la **presentación
configurable** entre **carrusel horizontal** y **timeline vertical**, reutilizando el
mecanismo de `layout` que `post-stages` ya tiene. Además, se añade la capacidad de
listar **sin límite** de entradas.

**Contexto (por qué existe este ítem).** En #5, el documento de requerimientos del
arquitecto especificó el render como «tarjetas de viaje según la maqueta» y descartó el
modo carrusel/timeline de `post-stages`, sin que Juanjo lo pidiera. El desarrollador
implementó fielmente esa spec (grid). Es, por tanto, una **corrección de un fallo de
diseño del arquitecto**, no un incumplimiento del desarrollador. La **tarjeta** actual
(`.trip-card`) es correcta y se conserva; lo que cambia es su **contenedor/disposición**.

---

## 1. Estado actual (verificado en código, no de memoria)

- **`post-stages` ya resuelve el layout.** `blocks/post-stages/render.php`: atributo
  `layout` (`carousel` | `timeline`, por defecto `carousel`, línea 21). Emite un
  contenedor `.ent-stages .ent-stages--{layout}` con `data-layout` y una pista
  `.ent-stages__track`. En **carrusel**: slides `.ent-stages__slide` > `.ent-card`,
  cabecera con nav `.ent-stages__nav` (botones prev/next + contador) y `.ent-stages__dots`
  al pie (solo con `total > 1`). En **timeline**: `.ent-tl-item` > `.ent-tl-dot-col`
  (`.ent-tl-dot` + `.ent-tl-connector`) + tarjeta `.ent-tl-card`.
- **Comportamiento del carrusel:** `assets/js/carousel.js`. Autoinicializa en
  `DOMContentLoaded` todos los `.ent-stages--carousel` del documento, y sus botones
  externos por `data-target` (id del contenedor). No hay que tocarlo.
- **CSS del scaffolding:** `assets/css/carousel.css` (`.ent-stages*`, `.ent-card*`,
  `.ent-tl-*`).
- **Encolado hoy:** `carousel.css` + `carousel.js` se encolan **solo** si
  `has_block( 'enterprise/post-stages', $post )` (`functions.php:1664` y siguientes).
- **Control de layout en el editor (patrón a copiar):** `assets/js/block-post-stages.js`
  líneas ~234-240: un `SelectControl` «Diseño» con opciones «⟵ Carrusel horizontal»
  (`carousel`) y «↓ Timeline vertical» (`timeline`).
- **Estado de `trip-collection`:**
  - `blocks/trip-collection/render.php`: contenedor `.ent-trip-collection` >
    `.trip-grid` (role=list) > `.trip-card` (enlace plano). Datos por entrada de
    `enterprise_trip_card_data()`. **Sin** atributo `layout`.
  - Atributos registrados (`functions.php`, `register_block_type( 'enterprise/trip-collection' … )`):
    `categoryIds`, `tagIds`, `filterDateFrom`, `filterDateTo`, `tagRelation`,
    `postsPerPage` (def. 6), `orderBy`, `order`. **Sin** `layout` ni `showAll`.
  - Editor `assets/js/block-trip-collection.js`: `InspectorControls` con `PanelBody`
    «Filtros» y «Cantidad y orden» (un `RangeControl` «Número de viajes», min 1 / max 24;
    dos `SelectControl` de orden). Importa `PanelBody`, `SelectControl`, `RangeControl`
    (no `ToggleControl`).
- **Query compartida:** `enterprise_stage_query( $attributes )` (`functions.php:388`)
  pasa `posts_per_page => intval( postsPerPage )` (líneas 397/403). `WP_Query` interpreta
  **nativamente** `posts_per_page => -1` como «todas»; `no_found_rows => true` (línea 407)
  es compatible con traer todas.

---

## 2. Artefactos y atributos nuevos

| Artefacto | Nombre | Notas |
|---|---|---|
| Atributo de bloque | `layout` (string, def. `carousel`) | En `enterprise/trip-collection`. Valores `carousel` \| `timeline`, idénticos a `post-stages`. |
| Atributo de bloque | `showAll` (boolean, def. `false`) | En `enterprise/trip-collection`. `true` ⇒ sin límite de entradas. |
| CSS puente | en `assets/css/coleccion.css` | Reglas mínimas para que `.trip-card` encaje en el slide del carrusel y en la ranura de tarjeta del timeline. |

No se crean metadatos (`layout`/`showAll` son atributos de bloque, no `post`/página).

---

## 3. Decisiones de diseño fijadas (no reabrir sin motivo)

1. **Se conserva la tarjeta `.trip-card`** tal cual (visualmente correcta). Cambia su
   **contenedor**, no su contenido.
2. **Reutilizar el mecanismo de layout de `post-stages`**, no reinventarlo: el render de
   `trip-collection` emite el **mismo scaffolding** `.ent-stages--{layout}` (pista, slides
   y nav/dots en carrusel; `.ent-tl-item`/`.ent-tl-dot-col` en timeline), colocando
   `.trip-card` como contenido de cada slide / ranura de timeline. Así se reutilizan
   `carousel.js` y `carousel.css` sin tocarlos. **Alternativa descartada:** namespace
   propio `.trip-carousel` + generalizar `carousel.js` — más código y toca JS compartido.
3. **`layout` por defecto = `carousel`** (coherente con `post-stages` y con la intención
   de la maqueta: un grid de 3 ≈ carrusel con 3 visibles en escritorio). El grid fijo
   actual **se retira**; los dos modos son carrusel y timeline (Juanjo no pidió conservar
   un tercer modo grid).
4. **«Sin límite» por toggle booleano** `showAll`. Cuando está activo, el bloque pasa
   `postsPerPage => -1` a `enterprise_stage_query()` (a nivel de bloque); **no se toca la
   query compartida** (ya mapea `-1` sola), de modo que `post-stages` queda intacto. El
   `RangeControl` numérico se oculta/inhabilita mientras `showAll` esté activo.
5. **Sin `post-stages` tocado.** No se modifica ningún fichero de `post-stages` ni
   `enterprise_stage_query()`. Su render debe seguir **byte-idéntico** (Juanjo lo valida
   comparando el HTML de una página con `post-stages` antes/después).

---

## 4. Requerimientos, agrupados por commit (troceado para validar por partes)

> El **bump de versión** y la **documentación** van al final, tras validar (Fase 3).
> El desarrollador **no** sube versión por su cuenta.

### Fase 1 — «Sin límite» (`showAll`)

- **R1.** Añadir atributo `showAll` (boolean, def. `false`) a `enterprise/trip-collection`
  (`register_block_type` en `functions.php`).
- **R2.** Editor (`block-trip-collection.js`): importar `ToggleControl`; en el panel
  «Cantidad y orden», añadir un toggle **«Sin límite (mostrar todas)»** enlazado a
  `showAll`. Cuando `showAll` sea `true`, **no** renderizar el `RangeControl` «Número de
  viajes» (o inhabilitarlo).
- **R3.** Render (`blocks/trip-collection/render.php`): antes de llamar a
  `enterprise_stage_query( $attributes )`, si `showAll` es `true`, forzar
  `$attributes['postsPerPage'] = -1`. Nada más: la query ya trae todas. Aplicar la misma
  regla **en todos los puntos** donde el bloque resuelve sus entradas si hubiera más de
  uno (verificar en el código).

*Validación Fase 1:* con el toggle activo, la colección lista todas las entradas del
filtro; con él inactivo, respeta el número. El hero de la página (cifras §13.7) pasa a
contar todas las entradas del bloque sin límite — conducta correcta, no un fallo.

### Fase 2 — Layout carrusel/timeline

- **R4.** Añadir atributo `layout` (string, def. `carousel`) a `enterprise/trip-collection`.
- **R5.** Editor: añadir un `SelectControl` **«Diseño»** con opciones «⟵ Carrusel
  horizontal» (`carousel`) y «↓ Timeline vertical» (`timeline`), **copiando el patrón** de
  `block-post-stages.js` (~234-240). Actualizar el texto de vista previa del bloque para
  reflejar el modo elegido.
- **R6.** Render: sustituir el `.trip-grid` fijo por el scaffolding de `post-stages`
  según `layout`:
  - **carrusel:** `.ent-stages .ent-stages--carousel` con `id` único y `data-layout`,
    `.ent-stages__track`, un `.ent-stages__slide` por entrada con la `.trip-card` dentro,
    y —si `total > 1`— la cabecera de nav (`.ent-stages__nav`, prev/next + contador) y
    `.ent-stages__dots` al pie. Reproducir la estructura de `post-stages/render.php` para
    que `carousel.js` funcione sin cambios.
  - **timeline:** `.ent-stages .ent-stages--timeline` con `.ent-tl-item` por entrada
    (`.ent-tl-dot-col` con `.ent-tl-dot` numerado + `.ent-tl-connector` salvo en el
    último) y la `.trip-card` como tarjeta de la fila.
  - En ambos, la `.trip-card` conserva su markup interno actual (`.trip-thumb`,
    badges, `.trip-body`, `.trip-meta`). Siguen siendo **enlaces planos** (sin `from_*`;
    la navegación entre viajes es el TO-DO #8, fuera de alcance).
- **R7.** Encolar `carousel.css` + `carousel.js` también para este bloque: extender la
  condición de `functions.php:1664` (y su bloque de `wp_enqueue_style`/`_script`) para que
  dispare también con `has_block( 'enterprise/trip-collection', $post )`. `carousel.js`
  autoinicializa `.ent-stages--carousel`, así que no requiere más cableado.
- **R8 (puente CSS).** En `assets/css/coleccion.css`, añadir solo las reglas mínimas para
  que `.trip-card` ocupe correctamente el ancho del `.ent-stages__slide` (carrusel) y la
  ranura de tarjeta del timeline. No duplicar la lógica de layout: esa vive en
  `carousel.css`. Ajustar únicamente el encaje de la tarjeta.

*Validación Fase 2:* el bloque se puede alternar entre carrusel (con nav/dots y scroll,
como `post-stages`) y timeline vertical, manteniendo la tarjeta; una página con
`post-stages` sigue idéntica (HTML sin cambios); ambos bloques pueden convivir.

### Fase 3 — Documentación y versión (SOLO tras validar; la hace el arquitecto)

- Design doc: **§7** actualizar la descripción de `enterprise/trip-collection`
  (ahora carrusel/timeline como `post-stages`, y «sin límite»); nota de que reutiliza
  `carousel.js`/`carousel.css`. Revisar si procede una nota en **§13.7**.
- **Bump de versión 2.5.0 → 2.6.0** (nueva capacidad configurable) en los TRES sitios a la
  vez: `style.css`, `ENTERPRISE_VERSION` y header del design doc.
- `TODO.md`: mover #11 a «Resueltas» (tipo `mejora`) conservando su `#`.

---

## 5. Fuera de alcance

- **Navegación anterior/siguiente entre los viajes de la colección** (TO-DO #8): las
  tarjetas siguen siendo enlaces planos.
- **Tocar `post-stages` o `enterprise_stage_query()`**: no se modifican.
- **Conservar un modo grid** además de carrusel/timeline: no se pide.
- **Llevar el «sin límite» a `post-stages`**: si se quisiera, mismo patrón, pero es otro
  ítem.

---

## 6. Recordatorios de método

- Antes de operaciones de riesgo: **commit + push** como punto de restauración.
- Entregar cada fichero versionado modificado **completo** + los comandos git (add /
  commit conventional / push a la cuenta personal), aunque el cambio sea de una línea.
- Cualquier comando de inspección que pidas a Juanjo es de **solo lectura** y se confirma
  como tal.
