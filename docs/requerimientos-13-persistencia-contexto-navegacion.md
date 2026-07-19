# Requerimientos de desarrollo — #13 (`fix`) · Persistencia y cobertura del contexto de regreso («Volver») más allá de un nivel de navegación

> Documento del **arquitecto** para el **rol desarrollador**. Es una especificación,
> no código. El desarrollador implementa y entrega **ficheros completos + comandos git**;
> **Juanjo valida en WordPress real** antes de cerrar. Fuente de verdad: el repo
> `jjramosgo-coder/enterprise-moto-theme` (cuenta personal, autor `jjramosgo@gmail.com`).
> Versión base: **2.7.0**. Bump al cierre lo hace **el arquitecto** (por ser `fix`,
> patch: 2.7.0 → 2.7.1 salvo criterio distinto); el desarrollador **no** sube versión.
> Antes de tocar nada: clon fresco + `git ls-remote`, y leer `bitacora-enterprise-design.md`
> (§6 «Contrato de navegación entre entradas», §13.1, §13.10) y `TODO.md` #13.
> **Un commit por topic** (troceado) para validar por partes.

---

## 0. Objetivo y contexto

El contexto de origen del botón «Volver» y de la navegación anterior/siguiente es hoy de
**un solo nivel**: `single.php` lee los parámetros `from_*`, pero resuelve con una cadena
`if/elseif` en la que **gana uno solo**, y el `$nav_suffix` que se propaga en los enlaces es
**ese único parámetro**. No hay memoria de la ascendencia. Consecuencias verificadas:

1. **Pérdida de contexto en anidamiento (el defecto central).** Al bajar de una colección a
   un viaje (tipo D) —contexto `from_col`— y de ahí a una **etapa** del viaje, el bloque
   `post-stages` estampa en la etapa **solo** `from_post` (no propaga `from_col`); y el
   «Volver al viaje» de la etapa es un permalink **plano**. Al regresar al viaje, su URL ya
   no lleva `from_col` → cae al fallback → «Volver» genérico y prev/next por categoría. El
   viaje «olvida» que venía de una colección.

2. **Cobertura desigual del estampado (el mismo problema, más amplio).** La portada estampa
   `from_cat` solo si la sección resuelve categoría; `archive.php` **no estampa nada**
   (enlaces planos), ni siquiera en un archivo de categoría donde `from_cat` claramente
   aplica y la maquinaria ya existe.

Se corrige con una **regla de arrastre de ascendencia** sobre el modelo de parámetros con
nombre ya existente (no una pila opaca): el origen **inmediato** determina «Volver» y
prev/next; el **ancestro** viaja en los enlaces salientes para sobrevivir al regreso. Y se
cierra el único hueco de cobertura en-modelo: `archive.php` estampando `from_cat` en
archivos de categoría. Todo sigue cumpliendo el **contrato §13.1/§6**: cada nivel toma el
orden de la **misma fuente que genera su listado**; el ancestro arrastrado es **memoria de
navegación, no fuente de secuencia**.

**Jerarquía real (acotada).** La única cadena de dos niveles que existe es
*listado → viaje (tipo D) → etapa*: una etapa es hoja (no lista nada), así que no hay
profundidad 3. El «listado» superior puede ser colección (`from_col`), archivo/portada de
categoría (`from_cat`) o —teóricamente— un cuaderno (`from_cuaderno`).

---

## 1. Estado actual (verificado en código, no de memoria — clon `e733518`, v2.7.0)

### 1.1. `single.php` — botón «Volver» (l. 18–53)
Lee y valida los parámetros de contexto (l. 18–36) y decide `$back_url`/`$back_label` con una
cadena `if/elseif` de prioridad **`from_post` > `from_cuaderno` > `from_col` > `from_cat` >
fallback** (l. 37–53):
- `from_post` (l. 37–39): `$back_url = get_permalink( $from_post_id )` — **plano**. **Aquí se
  pierde el ancestro** al volver al viaje.
- `from_cuaderno` (l. 40–42), `from_col` (l. 43–45), `from_cat` (l. 46–49), `else`/referer
  (l. 50–53).

### 1.2. `single.php` — navegación anterior/siguiente (l. 268–512)
`$nav_suffix = ''` (l. 271). Misma cadena de prioridad mutuamente excluyente:
- `from_post` (l. 273–374): `$nav_suffix = array( 'from_post' => $from_post_id )` (l. 284);
  parsea el bloque `post-stages` del viaje y computa prev/next por índice (l. 370–373).
- `from_cuaderno` (l. 376–428), `from_col` (l. 430–466, reconstrucción por
  `enterprise_collection_block_key()` + `enterprise_stage_query()`, intacta de #8),
  `from_cat` (l. 468–478), `else` (l. 480–483).
- **Etiquetas conscientes de contexto (#8, l. 487–491):**
  `$nav_prev_label = $from_col_id ? 'Viaje anterior' : 'Ruta anterior'` (y análogo `next`).
  **Defecto a corregir:** la condición es la **mera presencia** de `from_col`; cuando
  coexiste con `from_post` (etapa alcanzada vía colección→viaje→etapa) mostraría «Viaje»
  siendo el contexto inmediato una etapa dentro de un viaje («Ruta»).
- Enlaces prev/next (l. 497 y 507): `$nav_suffix ? add_query_arg( $nav_suffix,
  get_permalink( … ) ) : get_permalink( … )`.

### 1.3. `blocks/post-stages/render.php` — estampado en las etapas (l. 32–37, 114, 152)
```php
$from_post_id = 0;
$current_id   = get_the_ID();
if ( $current_id && get_post_meta( $current_id, '_post_tipo', true ) === 'viaje' ) {
    $from_post_id = $current_id;
}
$nav_suffix = $from_post_id ? array( 'from_post' => $from_post_id ) : array();
```
Ambos `href` de etapa (l. 114 carrusel, l. 152 timeline) usan `$nav_suffix`. El bloque **no
lee** el contexto de llegada del propio viaje (`$_GET`), así que **no propaga** el ancestro.

### 1.4. `archive.php` — enlaces planos (l. 28–56)
Bucle de tarjetas con tres `href` a la misma entrada: `the_permalink()` en l. 33 (imagen,
`aria-hidden`), l. 47 (título, el accesible) y l. 54 (flecha, `aria-hidden`). **No estampa
ningún `from_*`**. Cabecera con `is_category()` / `is_tag()` / `is_author()` / año / mes
(l. 8–13).

### 1.5. Portada (`functions.php` l. 1858–1863)
`enterprise_home_post_card()` estampa `from_cat` **solo** si la sección resuelve categoría
por slug (`get_category_by_slug`). **No se toca** (ver §5): una sección sin categoría no
tiene destino de regreso en el modelo actual.

> **Corrección post-validación (ampliación §7).** Esta suposición resultó **incorrecta** al
> validar #13: la resolución `get_category_by_slug( sanitize_title( $nombre ) )` reconstruye
> el término desde la **cadena de presentación**, y falla siempre que `sanitize_title(nombre)
> ≠ slug` (subcategoría con nombre ≠ slug, p. ej. «De vacaciones con la moto» → slug real
> `vacaciones`; o secciones con título personalizado). Se aborda en la nueva **§7**.

### 1.6. Reutilizable
- `enterprise_collection_block_key()` (`functions.php` l. 465) — identidad de bloque, intacta.
- `enterprise_collect_stage_blocks()` (l. 499), `enterprise_stage_query()` (l. 388),
  `enterprise_find_first_block()` (l. 256) — intactas.
- Validación de página de colección: `get_page_template_slug( $id ) ===
  'page-templates/template-trip-coleccion.php'` (ya usada en `single.php` l. 33–35).

---

## 2. Artefactos y cambios

| Artefacto | Nombre | Notas |
|---|---|---|
| Helper compartido | `enterprise_nav_origin_params()` en `functions.php` | **Catálogo único** de «qué cuenta como parámetro de origen». Lee y **sanea** (no valida semánticamente) los `from_*` presentes en la request y los devuelve como array. Lo usa `render.php` para re-estampar el ancestro hacia las etapas. |
| Cambio | `blocks/post-stages/render.php` | Cuando el post es un viaje tipo D, `$nav_suffix` incluye, además de `from_post`, el **ancestro** de llegada del propio viaje (todo origen presente salvo `from_post`). |
| Cambio | `single.php` | (a) rama `from_post` del «Volver» arrastra el ancestro (y **descarta** `from_post`); (b) `$nav_suffix` de la rama `from_post` de prev/next incluye el ancestro; (c) etiqueta prev/next atada al **contexto activo** (innermost), no a la mera presencia de `from_col`. |
| Cambio | `archive.php` | En archivos de **categoría** (`is_category()`), estampar `from_cat=slug` en los tres `href` de la tarjeta (permalink calculado una vez por iteración). |

No se crean atributos de bloque ni metadatos. No se introducen tipos de contexto nuevos
(`from_tag`, `from_author`, …): eso es feature aparte (§5).

---

## 3. Decisiones de diseño fijadas (confirmadas con Juanjo — no reabrir sin motivo)

1. **Modelo de contexto: parámetros con nombre + arrastre de ascendencia** (evolución del
   modelo actual, **no** pila opaca). `$nav_suffix` deja de ser «el parámetro ganador» y pasa
   a ser «el del nivel inmediato + los del ancestro que deben sobrevivir». *Principio:* la
   jerarquía de origen aquí es **semánticamente tipada** (colección ≠ viaje ≠ cuaderno ≠
   categoría: cada una con su etiqueta, su validación y su fuente de secuencia); una pila
   homogénea perdería ese tipado. Es además el camino menos invasivo y reutiliza intacto
   `enterprise_collection_block_key()` y la validación por parámetro ya presente.

2. **Prioridad cuando coexisten `from_post` y `from_col`: gana el origen inmediato
   (innermost); el ancestro se arrastra, no se consume.** En una etapa alcanzada desde un
   viaje, `from_post` determina «Volver al viaje» y el prev/next de etapas; `from_col`+`col_key`
   viajan en los enlaces salientes. **No se reordena** la cadena `if/elseif` (ya tiene
   `from_post` antes que `from_col`), así que lo ya validado no cambia. Colaterales obligados:
   (a) el «Volver al viaje» de la etapa lleva el ancestro pero **no** `from_post` (sería
   autorreferente al regresar al viaje); (b) sin ancestro, el «Volver» sigue siendo permalink
   **plano** (arreglo estrictamente **aditivo**); (c) la **etiqueta** prev/next sigue el
   contexto activo, no la mera presencia de `from_col` (ver §1.2).

3. **Qué se propaga hacia abajo viaje→etapa: el contexto de llegada del propio viaje, sea cual
   sea (transparente al tipo), no «solo colección» a fuego.** Como el viaje se alcanza por
   **un** origen, «solo el nivel superior» y «toda la pila» coinciden (hay como mucho un
   ancestro). `post-stages/render.php` lee ese ancestro entrante y lo re-estampa. Hacerlo
   genérico cuesta lo mismo que hacerlo solo para `from_col` y evita **recrear** la cobertura
   desigual que #13 elimina; además cubre archivo/portada-categoría→viaje→etapa y (si se
   diera) cuaderno→viaje→etapa **sin** casos especiales.

4. **Alcance: arreglo de anidamiento/persistencia + el único hueco de cobertura en-modelo —
   `archive.php` estampando `from_cat` en archivos de categoría.** El resto (contexto para
   archivos de etiqueta/autor/fecha, secciones de portada sin categoría, cualquier `from_*`
   nuevo, y la semántica interna de `from_cat` —orden de sección de portada vs. archivo de
   categoría—) queda **fuera** (§5): son features con tipos de contexto y fuentes de secuencia
   nuevos, no un fix.

5. **Contrato §13.1/§6 en cada nivel (invariante explícito).** El ancestro arrastrado es
   **memoria de navegación, no fuente de secuencia**. prev/next se computa siempre desde el
   **origen inmediato**: en la etapa, desde el bloque `post-stages` del viaje (`from_post`),
   **nunca** desde la colección (`from_col`, que lista viajes — universo equivocado). La cadena
   con `from_post` primero ya lo garantiza (refuerza la Decisión 2 de no reordenar).

---

## 4. Requerimientos, agrupados por commit (troceado para validar por partes)

> `git add` por **nombre explícito** (nunca `-A`). Mensajes *conventional commit*. Cada commit
> deja un estado **coherente y validable**: por eso `render.php` y `single.php` van **juntos**
> (estampar el ancestro sin que `single.php` lo consuma introduciría un glitch visible en la
> etiqueta y un «Volver» plano). La cobertura de `archive.php` es un topic independiente.

### Commit 1 — `fix(nav): persistencia del contexto de origen en navegación anidada viaje→etapa`
Tres ficheros: `functions.php`, `blocks/post-stages/render.php`, `single.php`.

**a) `functions.php`** — nuevo helper compartido (catálogo único de origin params):
```php
/**
 * Contexto de navegación presente en la request (#13).
 * Lee y SANEA los parámetros de origen del enlace de entrada; NO valida su
 * semántica (eso lo hace single.php al consumirlos). Fuente única de «qué
 * cuenta como parámetro de origen», para que el estampado del ancestro
 * (render.php) y el consumo (single.php) no diverjan.
 *
 * @return array Subconjunto presente y saneado. p.ej.
 *   array( 'from_col' => 12, 'col_key' => 'a1b2c3d4' ) | array( 'from_cat' => 'italia' ) | array()
 */
function enterprise_nav_origin_params() {
    $out = array();
    if ( isset( $_GET['from_post'] ) && intval( $_GET['from_post'] ) ) {
        $out['from_post'] = intval( $_GET['from_post'] );
    }
    if ( isset( $_GET['from_cuaderno'] ) && intval( $_GET['from_cuaderno'] ) ) {
        $out['from_cuaderno'] = intval( $_GET['from_cuaderno'] );
    }
    if ( isset( $_GET['from_col'] ) && intval( $_GET['from_col'] ) ) {
        $out['from_col'] = intval( $_GET['from_col'] );
        $out['col_key']  = isset( $_GET['col_key'] ) ? sanitize_key( $_GET['col_key'] ) : '';
    }
    if ( isset( $_GET['from_cat'] ) && sanitize_key( $_GET['from_cat'] ) ) {
        $out['from_cat'] = sanitize_key( $_GET['from_cat'] );
    }
    return $out;
}
```

**b) `blocks/post-stages/render.php`** — sustituir la línea `$nav_suffix` (l. 37) para incluir
el ancestro **solo cuando el post es un viaje tipo D** (el resto de casos, sin cambio):
```php
// #13: además del from_post inmediato, propagar hacia las etapas el contexto
// de llegada del propio viaje (el ancestro). El viaje no es «etapa» de nadie,
// así que se excluye from_post por seguridad. Sin viaje → sin estampado.
$ancestor = enterprise_nav_origin_params();
unset( $ancestor['from_post'] );
$nav_suffix = $from_post_id
    ? array_merge( array( 'from_post' => $from_post_id ), $ancestor )
    : array();
```
Los dos `href` (l. 114, l. 152) ya usan `$nav_suffix` → **no** se tocan.

**c) `single.php`** — tres cambios, todos en la rama `from_post` o en las etiquetas:

*(c.1)* Tras el bloque de validación de parámetros (después de l. 36), construir el **ancestro
validado** una sola vez (todo origen validado salvo `from_post`), para reutilizarlo en «Volver»
y en prev/next:
```php
// #13: ancestro = orígenes validados presentes, salvo el inmediato from_post.
// Se construye desde los locales YA validados (no se re-lee $_GET), de modo que
// un from_col/from_cat/from_cuaderno inválido no se arrastra.
$nav_ancestor = array();
if ( $from_cuaderno_id ) { $nav_ancestor['from_cuaderno'] = $from_cuaderno_id; }
if ( $from_col_id )      { $nav_ancestor['from_col'] = $from_col_id; $nav_ancestor['col_key'] = $col_key; }
if ( $from_cat_slug )    { $nav_ancestor['from_cat'] = $from_cat_slug; }
```

*(c.2)* Rama `from_post` del «Volver» (l. 37–39): arrastrar el ancestro; plano si no lo hay.
Además, fijar un `$active_context` en **cada** rama de la cadena para atar la etiqueta al
contexto real (innermost):
```php
if ( $from_post_id ) {
    // #13: al volver al viaje, conservar el ancestro para que el viaje siga
    // sabiendo de dónde vino; from_post no se arrastra (autorreferente al viaje).
    $back_url   = $nav_ancestor
                    ? add_query_arg( $nav_ancestor, get_permalink( $from_post_id ) )
                    : get_permalink( $from_post_id );
    $back_label = esc_html__( '← Volver al viaje', 'enterprise-moto' );
    $active_context = 'post';
} elseif ( $from_cuaderno_id ) {
    // … (sin cambios de URL/label) …
    $active_context = 'cuaderno';
} elseif ( $from_col_id ) {
    // … (sin cambios) …
    $active_context = 'col';
} elseif ( $from_cat_slug ) {
    // … (sin cambios) …
    $active_context = 'cat';
} else {
    // … (sin cambios) …
    $active_context = 'none';
}
```
(`from_cuaderno`/`from_col`/`from_cat` son de nivel superior: no tienen ancestro estampado por
encima, así que su «Volver» **no** cambia. Solo se les añade `$active_context`.)

*(c.3)* Rama `from_post` de prev/next: `$nav_suffix` incluye el ancestro (l. 284):
```php
$nav_suffix = array_merge( array( 'from_post' => $from_post_id ), $nav_ancestor );
```
El resto de ramas (`from_cuaderno`/`from_col`/`from_cat`/`else`) **no** cambian: son de nivel
superior, sin ancestro.

*(c.4)* Etiquetas (l. 487–491): atar al contexto activo, no a la presencia de `from_col`:
```php
$in_col_context = ( 'col' === $active_context );
$nav_prev_label = $in_col_context ? esc_html__( 'Viaje anterior',  'enterprise-moto' )
                                  : esc_html__( 'Ruta anterior',   'enterprise-moto' );
$nav_next_label = $in_col_context ? esc_html__( 'Siguiente viaje', 'enterprise-moto' )
                                  : esc_html__( 'Siguiente ruta',  'enterprise-moto' );
```

**Validación:** ver §6, bloque *Commit 1*.

### Commit 2 — `fix(archive): estampar contexto de categoría (from_cat) en archivos de categoría`
Fichero: `archive.php`. En el bucle (l. 28+), calcular el permalink **una vez** por iteración
y, si el archivo es de categoría, estamparle `from_cat`; usar ese valor en los tres `href`
(l. 33, 47, 54) para que apunten al mismo destino:
```php
$card_permalink = get_permalink();
if ( is_category() ) {
    $cat_obj = get_queried_object();
    if ( $cat_obj instanceof WP_Term ) {
        $card_permalink = add_query_arg( 'from_cat', $cat_obj->slug, $card_permalink );
    }
}
```
y sustituir los tres `the_permalink()` por `echo esc_url( $card_permalink );` (mismo patrón que
la portada, §1.5). Sin cambio en archivos de etiqueta/autor/fecha (guarda `is_category()`).

**Validación:** ver §6, bloque *Commit 2*.

---

## 5. Fuera de alcance (para no inferir requisitos no pedidos)

- **No** introducir tipos de contexto nuevos: `from_tag`, `from_author`, `from_archive`
  (archivos de etiqueta/autor/fecha), ni contexto para secciones de portada **sin** categoría.
  Requieren ramas y fuentes de secuencia nuevas en `single.php`: es feature, TO-DO aparte.
- **No** cambiar la **semántica interna de `from_cat`** (hoy reconstruye adyacencia del archivo
  de categoría; no distingue el orden de una sección de portada del del archivo). Se mantiene
  tal cual.
- **No** tocar la portada (`functions.php`): ya estampa el único contexto que aplica.
  &nbsp;&nbsp;↳ **Levantado por la ampliación §7 (post-validación):** la premisa era incorrecta
  (§1.5, corrección). La cobertura de la portada era, además, lo que §13.10 del design doc
  asignó a #13. Se aborda en §7; el resto de esta lista sigue vigente.
- **No** tocar la navegación ni el «Volver» de `from_cuaderno`, `from_col` ni `from_cat` como
  **origen de nivel superior** (solo se les añade `$active_context` para la etiqueta); ni el
  fallback.
- **No** alterar el render base de `post-stages` más allá de la línea `$nav_suffix`: es
  scaffolding compartido con `trip-collection` (§7/§13.10); el cambio se limita a lo necesario
  para propagar.
- **No** modificar `enterprise_collection_block_key()`, `enterprise_stage_query()` ni la
  reconstrucción de secuencia de #8.
- El desarrollador **no** sube versión ni edita `TODO.md` / el documento de diseño (cierre del
  arquitecto tras validación).

---

## 6. Recordatorios de método y validación

- **Entrega:** ficheros **completos** para descargar + comandos git (`add` por nombre, `commit`
  convencional, `push` a la cuenta personal). Un commit por topic.
- **Red de seguridad:** commit + push del estado actual antes de empezar si se prefiere punto de
  restauración.
- **Validación de Juanjo (en WordPress real):**

  *Commit 1 — anidamiento colección→viaje→etapa→volver:*
  1. En una colección, entrar a un viaje (URL con `?from_col&col_key`). Inspeccionar el HTML del
     viaje: los enlaces de sus **etapas** llevan ahora `?from_post={viaje}&from_col={col}&col_key={hash}`.
  2. Entrar en una etapa. El «Volver» dice **«Volver al viaje»** y su enlace lleva
     `?from_col&col_key` (no `from_post`). La etiqueta prev/next dice **«Ruta»**, no «Viaje»
     (el contexto inmediato es una etapa dentro del viaje).
  3. Pulsar «Volver al viaje»: se regresa al viaje **con** `?from_col&col_key`, y ahí el «Volver»
     vuelve a decir **«Volver a la colección»** y el prev/next recorre los **viajes** de la
     colección (contexto recuperado — el defecto original).
  4. *No regresión (viaje sin ancestro):* un viaje alcanzado directamente → sus etapas llevan
     solo `?from_post`; «Volver al viaje» es permalink **plano**; al volver, el viaje cae al
     fallback como antes.
  5. *No regresión (contexto simple):* «Volver» y prev/next de `from_post` (viaje→etapa sin
     colección), `from_cuaderno` y `from_col` (colección→viaje directo) siguen intactos y con la
     etiqueta correcta («Ruta» salvo colección directa, que dice «Viaje»).

  *Commit 2 — cobertura de `archive.php`:*
  6. En un **archivo de categoría**, las tarjetas llevan `?from_cat={slug}`; al entrar en una
     entrada, «Volver» va al archivo de la categoría (no al referer). Sin cambio visual.
  7. *Anidamiento por categoría:* si la entrada es un viaje, sus etapas heredan `from_cat`
     (gracias al Commit 1) y al volver al viaje se conserva → «Volver» a la categoría.
  8. *No regresión:* en archivos de **etiqueta/autor/fecha** las tarjetas siguen sin estampar
     contexto (enlaces planos), como antes.

---

## 7. Ampliación (post-validación) — cobertura del contexto de regreso en la PORTADA (Commit 3)

> **Encaje (decidido por el arquitecto).** Esta ampliación **no** abre TO-DO nuevo: completa
> lo que §13.10 del documento de diseño ya asignó a #13 («el estampado de contexto es desigual
> entre orígenes —portada, `archive.php`—… se abordan en #13»). El spec original difirió la
> portada en §5 sobre una premisa —«la portada ya estampa el único contexto que aplica»— que la
> validación en WordPress **desmintió**. Por el protocolo de error de diseño del arquitecto, esa
> premisa se **asume como error propio**, se levanta la restricción §5 para esta ampliación y se
> cierra todo en el mismo bump **2.7.0 → 2.7.1**. Los Commits 1 y 2 quedan intactos y validados;
> esto es un tercer topic, **validable por separado**.

### 7.0. Alcance de la ampliación
Dos hallazgos de cobertura en la portada (`index.php` + `functions.php`), **ambos previos a #13**
(no regresión de los commits validados) y **ninguno** pasa por `archive.php`:
- **(a)** las secciones de categoría de la portada no estampan `from_cat` de forma fiable;
- **(b)** la tarjeta destacada «Última ruta publicada» enlaza en plano.

La resolución de (a) es código (Commit 3); la de (b) es una **decisión de no-estampado
documentada** (un comentario, sin lógica nueva).

### 7.1. Estado actual (verificado en código real — clon `273167b`, v2.7.0)

**(a) Resolución del término desde la cadena de presentación.**
`enterprise_home_post_card( $post_id, $num, $section_cat_name = '' )` (`functions.php`, buscar por
nombre de función) usa `$section_cat_name` para **dos** cosas: la **etiqueta visible** de la
tarjeta (`$cat_name = $section_cat_name ?: enterprise_first_category( $post_id )`) y el
**estampado** de `from_cat`:
```php
if ( $section_cat_name ) {
    $section_cat_obj = get_category_by_slug( sanitize_title( $section_cat_name ) );
    if ( $section_cat_obj ) {
        $card_permalink = add_query_arg( 'from_cat', $section_cat_obj->slug, $card_permalink );
    }
}
```
El estampado reconstruye el término **desde una cadena de presentación** con `sanitize_title()`.
Falla siempre que `sanitize_title(nombre) ≠ slug`. `index.php` pasa **el nombre** (no el slug) en
todas las ramas:
- `cat_children` (bucle `$hijos`): pasa `$hijo->name` como `$section_cat` → **falla** con
  «De vacaciones con la moto» (slug real `vacaciones`); las demás subcategorías funcionan **por
  coincidencia** `sanitize_title(nombre)==slug`, no por robustez.
- `cat`: pasa `$cfg['title'] ?: $term->name` → **falla también** si hay **título personalizado**
  en el Personalizador (no solo el caso `vacaciones`).
- `tag`: pasa `$tag_term->name` → `get_category_by_slug()` devuelve `null` (una etiqueta no es
  categoría) → no estampa. Hoy «funciona» por accidente, no por diseño.

**(b) Tarjeta destacada sin contexto.**
`index.php`, bloque `$latest_post` (buscar `$latest_post`): los enlaces (título y botón) usan
`get_permalink( $latest_post->ID )` **plano**. El destacado es «la entrada más reciente **entre
las categorías** de `enterprise_latest_cats`» (`get_theme_mod`), es decir **un único ítem sin un
listado detrás** ni una categoría única.

### 7.2. Decisiones de diseño fijadas (confirmadas por el arquitecto — no reabrir sin motivo)

1. **(a) Decouplar identidad de presentación.** La cadena visible (nombre / título
   personalizado) y la **identidad navegable** (slug real del término) son cosas distintas y
   dejan de compartir variable. Se propaga el **slug real** por un parámetro nuevo, usado **solo**
   para estampar; la etiqueta visible sigue usando el nombre (sin cambio visual). *Principio:* la
   identidad navegable de una sección es el **término real**, no una cadena de presentación
   re-saneada — se lee del término que ya se tiene (`$hijo` / `$term`), no se reconstruye.

2. **(a) Alcance de la corrección:** se aplica a `cat_children` (`$hijo->slug`) y a `cat`
   (`$term->slug`). En `tag` se pasa **`''` explícito**: una etiqueta **no** es categoría y el
   modelo **no** tiene `from_tag` (prohibido por §5); el «no estampado» de las secciones de
   etiqueta pasa a ser **intencionado y documentado**, no accidental.

3. **(b) Dejar el fallback, deliberadamente.** El destacado es **un ítem sin listado** detrás: no
   hay secuencia que el usuario haya visto ni categoría única. Estampar `from_cat` = categoría
   primaria **fabricaría** una secuencia inexistente (violaría §6) y desviaría el «Volver» a un
   archivo de categoría en vez de a la portada. El modelo **no** tiene un contexto «vengo del
   destacado» (un `from_home` sería **tipo nuevo → prohibido por §5** sin decisión mayor). Por
   tanto: **no se estampa**; se conserva el fallback (referer → portada). Se documenta con un
   comentario en el sitio para que no se «arregle» por error más adelante. **No es lógica nueva.**

4. **Contrato §6 (confirmado).** El `from_cat` de portada lo consume la **rama `from_cat` ya
   validada** de `single.php` (adyacencia cronológica de la (sub)categoría; «Volver» por
   `get_term_link`). El **orden** coincide con el de la sección (fecha DESC → misma fuente de
   orden). La diferencia de **subconjunto** (la sección limita a `$cfg['max']`; prev/next recorre
   el archivo completo) es la **semántica interna de `from_cat` ya congelada** en §5, **idéntica**
   a la de `archive.php` (Commit 2, validado): no introduce desviación nueva. El «Volver» de la
   sección `cat_children` cae además en el mismo destino que su CTA «Ver todas las rutas de {sub}»
   (`get_term_link( $hijo )`), coherente.

### 7.3. Requerimientos — Commit 3
`git commit -m "fix(home): cobertura del contexto de regreso en la portada (from_cat por slug real)"`
Dos ficheros: `functions.php` e `index.php`. Un solo commit (topic «cobertura de portada»),
validable de forma independiente de los Commits 1 y 2.

**a) `functions.php` — decouplar el slug para el estampado.**
Añadir un parámetro nuevo `$section_cat_slug` a **ambas** funciones, propagándolo:
```php
function enterprise_home_section( $eyebrow, $title, $posts, $cta_url, $cta_label,
                                  $section_cat = '', $section_cat_slug = '' ) {
    // … idéntico, salvo el paso a la tarjeta:
    foreach ( $posts as $i => $post ) :
        enterprise_home_post_card( $post->ID, $i + 1, $section_cat, $section_cat_slug );
    endforeach;
    // …
}

function enterprise_home_post_card( $post_id, $num, $section_cat_name = '', $section_cat_slug = '' ) {
    // … la ETIQUETA VISIBLE sigue igual (usa $section_cat_name):
    $cat_name = $section_cat_name ?: enterprise_first_category( $post_id );
    // …
    // ESTAMPADO: usar el SLUG REAL directamente; NO reconstruir con sanitize_title($nombre).
    $card_permalink = get_permalink( $post_id );
    if ( $section_cat_slug ) {
        $card_permalink = add_query_arg( 'from_cat', sanitize_key( $section_cat_slug ), $card_permalink );
    }
    // …
}
```
- Se **elimina** la reconstrucción `get_category_by_slug( sanitize_title( $section_cat_name ) )`
  del estampado; el `$section_cat_slug` ya es el slug canónico de un término real (lo garantiza el
  llamante). `sanitize_key()` es higiene defensiva (coincide con cómo `single.php` lee `from_cat`).
- Compatibilidad: sin `$section_cat_slug` → **no** se estampa (fallback seguro; nunca un
  `from_cat` erróneo).

**b) `index.php` — pasar el slug real en cada rama.**
- Inicializar junto a `$section_cat = ''` (rama del bucle de secciones): `$section_cat_slug = '';`
- Rama `cat_children` (llamada a `enterprise_home_section` dentro del `foreach ( $hijos … )`):
  añadir `$hijo->slug` como 7.º argumento.
- Rama `cat`: fijar `$section_cat_slug = $term->slug;` (se usa en la llamada del final del bucle).
- Rama `tag` (llamada dentro del `foreach ( $tag_parts … )`): añadir `''` como 7.º argumento
  **explícito** (documenta el no-estampado; ver decisión 7.2.2).
- Llamada final del bucle (la del `else`/`cat` tras los `continue`): añadir `$section_cat_slug`
  como 7.º argumento.

**c) `index.php` — comentario en el destacado (b), sin lógica.**
En el bloque `$latest_post`, junto a los enlaces `get_permalink( $latest_post->ID )`, añadir un
comentario que fije la decisión 7.2.3, p. ej.:
```php
// #13 (§7): el destacado es un único ítem sin listado detrás; no hay secuencia ni categoría
// única que estampar. Se deja el permalink plano a propósito (fallback → portada). No añadir
// from_cat aquí: fabricaría una secuencia inexistente y desviaría el «Volver» (ver §7.2.3).
```
No se cambia el `href`.

### 7.4. Confirmar al implementar (verificar contra el código real)
- Nombres/aridad reales de `enterprise_home_section()` y `enterprise_home_post_card()` y **todos**
  sus puntos de llamada (que no quede ninguna llamada con la aridad antigua).
- Que las ramas `cat_children` / `cat` / `tag` / final del bucle en `index.php` son exactamente las
  descritas (las líneas se habrán desplazado por el helper de #13; localizar por estructura, no por
  número).
- Que `$hijo` y `$term` exponen `->slug` en el punto de la llamada (son términos de
  `get_categories()` / `get_term_by()`).

### 7.5. Fuera de alcance (de la ampliación)
- **No** introducir `from_tag` / `from_author` / `from_home` ni contexto para secciones de portada
  **sin** categoría: siguen siendo feature aparte (§5 original).
- **No** cambiar la semántica interna de `from_cat` (orden/ subconjunto): congelada (§5, §7.2.4).
- **No** tocar `single.php` (la rama `from_cat` que consume esto ya está validada, Commit 2), ni
  `archive.php`, ni la reconstrucción de #8.
- **No** cambiar la **etiqueta visible** de la tarjeta ni ningún estilo (sin cambio visual).
- El desarrollador **no** sube versión ni edita `TODO.md` / el documento de diseño. El **cierre**
  (design doc §13.10 y, si procede, §6/§9; registro de la decisión 7.2.3 sobre el destacado;
  reconciliación de `TODO.md`; bump 2.7.0 → 2.7.1) lo hace el **arquitecto** tras la validación.

### 7.6. Validación de Juanjo (en WordPress real) — Commit 3
1. **Sección «De vacaciones con la moto»** (subcategoría `vacaciones`): las tarjetas llevan ahora
   `?from_cat=vacaciones`; al entrar en una entrada, «Volver» va al archivo de esa subcategoría
   (no al fallback por referer). Sin cambio visual en la etiqueta de la tarjeta.
2. **Resto de secciones de categoría / con título personalizado:** estampan el `from_cat` correcto
   (slug real), incluso si el título mostrado difiere del nombre de la categoría.
3. **Secciones de etiqueta:** siguen **sin** estampar `from_cat` (enlaces sin `from_cat`), ahora
   por diseño explícito.
4. **Anidamiento portada→viaje→etapa→volver:** si una tarjeta de sección de categoría es un viaje,
   sus etapas heredan `from_cat` (gracias al Commit 1) y al volver al viaje se conserva → «Volver»
   a la (sub)categoría.
5. **Destacado «Última ruta publicada»:** enlace plano a propósito; «Volver» cae al fallback
   (portada vía referer). Sin `from_cat`. Comportamiento inalterado respecto a antes.
6. **No regresión:** los Commits 1 y 2 (colección/archivo de categoría/anidamiento viaje→etapa)
   siguen intactos.
