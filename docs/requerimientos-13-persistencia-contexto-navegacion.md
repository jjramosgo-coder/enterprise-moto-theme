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
