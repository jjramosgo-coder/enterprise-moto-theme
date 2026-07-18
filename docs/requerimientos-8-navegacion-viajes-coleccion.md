# Requerimientos de desarrollo — #8 (`mejora`) · Navegación anterior/siguiente entre viajes de una «Colección de viajes»

> Documento del **arquitecto** para el **rol desarrollador**. Es una especificación,
> no código. El desarrollador implementa y entrega **ficheros completos + comandos git**;
> **Juanjo valida en WordPress real** antes de cerrar. Fuente de verdad: el repo
> `jjramosgo-coder/enterprise-moto-theme` (cuenta personal, autor `jjramosgo@gmail.com`).
> Versión base: **2.6.1**. Bump al cierre lo hace **el arquitecto** (por ser `mejora`,
> minor: 2.6.1 → 2.7.0 salvo criterio distinto); el desarrollador **no** sube versión.
> Antes de tocar nada: clon fresco + `git ls-remote`, y leer `bitacora-enterprise-design.md`
> (§6, §7, §13) y `TODO.md`. **Un commit por punto** (troceado) para validar por partes.

---

## 0. Objetivo y contexto

Las tarjetas del bloque `enterprise/trip-collection` (en una página «Colección de viajes»,
plantilla `page-templates/template-trip-coleccion.php`) son hoy **enlaces planos**: al
entrar en un viaje desde una colección, `single.php` no sabe que vienes de una colección,
así que (a) el botón «Volver» cae al fallback del referer, (b) la navegación anterior/
siguiente cae al fallback de adyacencia **por categoría** (no el orden ni el conjunto de la
colección) y (c) la etiqueta dice «Ruta».

Se implementa el **contexto de origen `from_col`**, a imagen del `from_post` que ya funciona
para las etapas de un viaje (referencia): la tarjeta propaga el contexto, `single.php`
reconstruye la secuencia **del bloque concreto** de la colección, el «Volver» regresa a la
página de colección y la etiqueta pasa a «Viaje». Cumple el **contrato de navegación
§13.1/§6**: anterior/siguiente recorren la misma fuente que genera el listado, con
**desambiguación por bloque** (una página puede tener varios bloques de filtrado).

**Alcance acotado (decidido con Juanjo).** #8 añade **solo** el origen «colección»
(`from_col`) emitido por `trip-collection`. La cobertura inconsistente de otros orígenes
(portada, `archive.php`) y la pérdida de contexto por navegación anidada (viaje → etapa →
viaje) son un problema más profundo, registrado aparte como **#13** — **fuera de #8**.

---

## 1. Estado actual (verificado en código, no de memoria)

### 1.1. `single.php` — botón «Volver» (l. 17–46)
Lee parámetros GET de contexto y decide `$back_url` / `$back_label`:
- `?from_post` (viaje tipo D padre) → «← Volver al viaje» (**referencia que funciona**).
- `?from_cuaderno` → «← Volver al cuaderno».
- `?from_cat` (slug de categoría) → enlace al archivo de categoría, «← Volver».
- si no hay ninguno → `wp_get_referer()` (fallback), «← Volver». **Aquí cae hoy la
  entrada abierta desde una colección.**

### 1.2. `single.php` — navegación anterior/siguiente (l. 261–413)
Cuatro ramas mutuamente excluyentes que fijan `$prev` / `$next` y un `$nav_suffix` que se
propaga en los enlaces:
- `from_post` (l. 262–360): parsea el contenido del viaje padre, encuentra el bloque
  `enterprise/post-stages` y **replica su query** (mismos atributos) para que la secuencia
  coincida con lo mostrado. `$nav_suffix = array( 'from_post' => id )`.
- `from_cuaderno` (l. 362–406): replica la query `_filt_*` del cuaderno.
- `from_cat` (l. 408 aprox): `get_previous_post()`/`get_next_post()` excluyendo otras
  categorías.
- **`else` (fallback)**: `get_previous_post( true )` / `get_next_post( true )` → adyacencia
  por categoría cronológica. **Aquí cae hoy la entrada de colección** (por eso «funciona»
  solo por coincidencia y puede saltar a entradas ajenas a la colección).

Etiquetas escritas a fuego: «Ruta anterior» (l. 439) y «Siguiente ruta» (l. 449).
Los enlaces prev/next ya propagan el contexto: `$nav_suffix ? add_query_arg( $nav_suffix,
get_permalink( … ) ) : get_permalink( … )` (l. 441–446 y 451–456).

### 1.3. `blocks/trip-collection/render.php`
- La tarjeta es un enlace **plano**: `<a href="<?php echo esc_url( get_permalink() ); ?>"
  class="trip-card">` (l. 118), compuesta una vez (`ob_start()` en l. 79) y envuelta según
  layout en `.ent-stages__slide` (carrusel) o `.ent-tl-item` (timeline).
- Resolución de entradas: guarda `showAll` (l. 27–29: `if ( ! empty( $attributes['showAll'] ) )
  $attributes['postsPerPage'] = -1;`) y luego `$query = enterprise_stage_query( $attributes )`
  (l. 32). **Este es el punto exacto a reutilizar en la navegación.**
- `$uid = 'ent-trips-' . wp_rand( 1000, 9999 )` (l. 75): **aleatorio en cada carga; NO
  sirve** como identificador estable para enlaces.
- Atributos de filtro registrados (query): `categoryIds`, `tagIds`, `tagRelation`,
  `filterDateFrom`, `filterDateTo`, `postsPerPage`, `orderBy`, `order`, `showAll` (más
  `layout`, que es **presentacional** y no afecta al orden).

### 1.4. Funciones reutilizables (`functions.php`)
- `enterprise_stage_query( $attributes )` (l. 388): construye la `WP_Query` por filtros;
  usada por ambos bloques. `posts_per_page => -1` se interpreta nativamente como «todas».
- `enterprise_collect_stage_blocks( $blocks )` (l. 466): recoge los bloques de filtrado de
  una página (reconoce `post-stages` **y** `trip-collection`).
- `enterprise_find_first_block()` (l. 256). `enterprise_collection_post_ids()` (l. 545)
  devuelve la **unión** de todos los bloques (para las cifras del hero, §13.7) — **no** es
  lo que necesita la navegación, que es **por bloque**.
- Detección de página de colección: plantilla `page-templates/template-trip-coleccion.php`
  (patrones ya usados en `functions.php` l. 699, 751, 870; p. ej.
  `get_page_template_slug( $id )`).

---

## 2. Artefactos y cambios

| Artefacto | Nombre | Notas |
|---|---|---|
| Parámetro GET | `from_col` (int) | Id de la página de colección de origen. |
| Parámetro GET | `col_key` (string) | Hash corto que identifica el bloque `trip-collection` concreto dentro de esa página. |
| Helper compartido | `enterprise_collection_block_key( $attributes )` en `functions.php` | Devuelve el hash de identidad del bloque a partir de sus atributos de filtro. Lo usan `render.php` (al pintar la tarjeta) y `single.php` (al casar el bloque). **Única fuente de verdad** del identificador, para que ambos lados no diverjan. |
| Cambio | `blocks/trip-collection/render.php` | Capturar el id de la página + calcular la clave; propagar `?from_col&col_key` en el `href` de la tarjeta. |
| Cambio | `single.php` | Nueva rama `from_col` en el bloque de «Volver» y en el de navegación; propagación de `$nav_suffix`; etiquetas conscientes de contexto. |

No se crean atributos de bloque ni metadatos.

---

## 3. Decisiones de diseño fijadas (no reabrir sin motivo)

1. **Alcance completo (a imagen de `from_post`).** `from_col` hace las tres cosas:
   (a) la tarjeta propaga el contexto; (b) `single.php` reconstruye la secuencia **del
   bloque concreto** de la colección; (c) «Volver» regresa a la página de colección y la
   etiqueta pasa a «Viaje». No se deja el prev/next en el fallback de categoría.

2. **Desambiguación por hash de atributos.** El `col_key` es un hash corto de los
   **atributos de filtro que determinan la secuencia**: `categoryIds` (int, ordenados),
   `tagIds` (int, ordenados), `tagRelation`, `filterDateFrom`, `filterDateTo`, `orderBy`,
   `order`, `postsPerPage`, `showAll`. **No** se incluye `layout` (no afecta al orden). El
   `$uid` del bloque **no** se usa (es `wp_rand`). Es robusto a reordenar bloques; una
   colisión (dos bloques con filtros idénticos) produciría la **misma** secuencia, así que
   es inocua. El helper hashea los **atributos originales** del bloque (los mismos que lee
   `single.php` al parsear la página), no los mutados por la guarda `showAll`.

3. **Reutilizar la query compartida (navegación == listado).** La rama `from_col` de
   `single.php` aplica la **misma guarda** `showAll` (`postsPerPage = -1` si `showAll`) y
   llama a `enterprise_stage_query( $attributes )` para obtener la secuencia ordenada —
   reutiliza la resolución exacta del bloque (§1.3), en lugar de replicar la query a mano
   como hace `from_post`. Así navegación y listado no pueden divergir.

4. **Literales (confirmados por Juanjo).**
   - Etiquetas prev/next en contexto de colección: **`← Viaje anterior`** / **`Siguiente
     viaje →`**. Fuera de ese contexto se conserva «Ruta anterior» / «Siguiente ruta».
   - Botón «Volver» en contexto de colección: **`← Volver a la colección`** (en espejo de
     «Volver al viaje» / «Volver al cuaderno»).

5. **Frontera de alcance.** `from_col` lo emiten **solo** las tarjetas de
   `trip-collection`. Un bloque `post-stages` presente en una página de colección **no** es
   origen `from_col` (pertenece al problema más amplio #13). La navegación de `from_post` /
   `from_cuaderno` / `from_cat` / fallback **no se toca**.

---

## 4. Requerimientos, agrupados por commit (troceado para validar por partes)

> `git add` por **nombre explícito**. Mensajes en formato *conventional commit* (afinar
> según convención de la casa). Orden recomendado: primero que los enlaces lleven el
> contexto (sin cambio de comportamiento visible), luego que `single.php` lo consuma.

### Commit 1 — `feat(trip-collection): propagar contexto de origen (from_col) en las tarjetas`
Dos ficheros.

**a) `functions.php`** — nuevo helper compartido:
```php
/**
 * Clave de identidad de un bloque de filtrado para la navegación (#8).
 * Hash corto y estable de los atributos que determinan la SECUENCIA (no layout),
 * para desambiguar entre varios bloques de la misma página.
 */
function enterprise_collection_block_key( $attributes ) {
    $cat = ( isset( $attributes['categoryIds'] ) && is_array( $attributes['categoryIds'] ) )
             ? array_map( 'intval', $attributes['categoryIds'] ) : array();
    $tag = ( isset( $attributes['tagIds'] ) && is_array( $attributes['tagIds'] ) )
             ? array_map( 'intval', $attributes['tagIds'] ) : array();
    sort( $cat ); sort( $tag );
    $norm = array(
        'cat'   => $cat,
        'tag'   => $tag,
        'trel'  => isset( $attributes['tagRelation'] ) && $attributes['tagRelation'] === 'AND' ? 'AND' : 'IN',
        'dfrom' => isset( $attributes['filterDateFrom'] ) ? (string) $attributes['filterDateFrom'] : '',
        'dto'   => isset( $attributes['filterDateTo'] )   ? (string) $attributes['filterDateTo']   : '',
        'obw'   => isset( $attributes['orderBy'] ) ? (string) $attributes['orderBy'] : 'date',
        'ord'   => isset( $attributes['order'] )   ? (string) $attributes['order']   : 'DESC',
        'ppp'   => isset( $attributes['postsPerPage'] ) ? intval( $attributes['postsPerPage'] ) : 6,
        'all'   => ! empty( $attributes['showAll'] ) ? 1 : 0,
    );
    return substr( md5( wp_json_encode( $norm ) ), 0, 8 );
}
```
(Los valores por defecto deben coincidir con los de `enterprise_stage_query()` / `render.php`;
confirmar contra esa función al implementar.)

**b) `blocks/trip-collection/render.php`** — antes del bucle de tarjetas (p. ej. junto a la
lectura de atributos, **antes** de la mutación `showAll` de la l. 27–29), capturar:
```php
$col_page_id = get_queried_object_id();
$col_key     = enterprise_collection_block_key( $attributes );
```
y cambiar el `href` de la tarjeta (l. 118) para propagar el contexto:
```php
<a href="<?php echo esc_url( add_query_arg(
        array( 'from_col' => $col_page_id, 'col_key' => $col_key ),
        get_permalink()
) ); ?>" class="trip-card">
```
Sin cambio visual. `get_queried_object_id()` es independiente del bucle interno
(`$query->the_post()`), así que es seguro llamarlo aquí.

**Validación:** inspeccionar el HTML de una colección — las tarjetas llevan
`?from_col={id}&col_key={hash}`; el resto igual.

### Commit 2 — `feat(single): navegación y «Volver» conscientes del origen colección (from_col)`
Fichero: `single.php`. Añadir la rama `from_col` en los **dos** bloques, en paralelo a
`from_post`:

**a) Lectura y validación** (junto a la de `from_post`/`from_cuaderno`, l. 17–27):
```php
$from_col_id = isset( $_GET['from_col'] ) ? intval( $_GET['from_col'] ) : 0;
$col_key     = isset( $_GET['col_key'] )  ? sanitize_key( $_GET['col_key'] ) : '';
// Validar que from_col es una página de colección
if ( $from_col_id
     && 'page-templates/template-trip-coleccion.php' !== get_page_template_slug( $from_col_id ) ) {
    $from_col_id = 0;
}
```

**b) Botón «Volver»** — nueva rama en la cadena (l. 28–43), con prioridad análoga a las
demás:
```php
} elseif ( $from_col_id ) {
    $back_url   = get_permalink( $from_col_id );
    $back_label = esc_html__( '← Volver a la colección', 'enterprise-moto' );
}
```

**c) Navegación prev/next** — nueva rama en la cadena (junto a `from_post`, l. 261 en
adelante):
```php
} elseif ( $from_col_id ) {
    $nav_suffix = array( 'from_col' => $from_col_id, 'col_key' => $col_key );

    // Localizar el bloque trip-collection concreto por su clave
    $col_blocks = enterprise_collect_stage_blocks( parse_blocks( get_post_field( 'post_content', $from_col_id ) ) );
    $target_attrs = null;
    foreach ( $col_blocks as $blk ) {
        if ( isset( $blk['blockName'] ) && $blk['blockName'] === 'enterprise/trip-collection'
             && enterprise_collection_block_key( $blk['attrs'] ) === $col_key ) {
            $target_attrs = $blk['attrs'];
            break;
        }
    }
    if ( is_array( $target_attrs ) ) {
        // Misma guarda showAll + misma query que el bloque → misma secuencia
        if ( ! empty( $target_attrs['showAll'] ) ) $target_attrs['postsPerPage'] = -1;
        $col_q   = enterprise_stage_query( $target_attrs );
        $col_ids = wp_list_pluck( $col_q->posts, 'ID' );
        $current_pos = array_search( get_the_ID(), $col_ids, true );
        if ( $current_pos !== false ) {
            $prev_id = $current_pos > 0                     ? $col_ids[ $current_pos - 1 ] : null;
            $next_id = $current_pos < count( $col_ids ) - 1 ? $col_ids[ $current_pos + 1 ] : null;
            $prev    = $prev_id ? get_post( $prev_id ) : null;
            $next    = $next_id ? get_post( $next_id ) : null;
        }
    }
}
```
(Confirmar la forma exacta de obtener los IDs ordenados desde `enterprise_stage_query()`;
si esa función ya devuelve `fields => ids`, adaptar `wp_list_pluck`.)

**d) Etiquetas conscientes de contexto** — sustituir los literales fijos (l. 439 y 449) por
una variante según contexto. P. ej. fijar antes del render de la nav:
```php
$nav_prev_label = $from_col_id ? esc_html__( 'Viaje anterior', 'enterprise-moto' )
                               : esc_html__( 'Ruta anterior', 'enterprise-moto' );
$nav_next_label = $from_col_id ? esc_html__( 'Siguiente viaje', 'enterprise-moto' )
                               : esc_html__( 'Siguiente ruta', 'enterprise-moto' );
```
y usar `← <?php echo $nav_prev_label; ?>` (l. 439) y `<?php echo $nav_next_label; ?> →`
(l. 449). El resto de contextos conservan «Ruta».

**Validación:** ver §6.

---

## 5. Fuera de alcance (para no inferir requisitos no pedidos)

- **No** tocar la navegación ni el «Volver» de `from_post`, `from_cuaderno`, `from_cat` ni
  el fallback.
- **No** cambiar la etiqueta «Ruta anterior/Siguiente ruta» fuera del contexto de colección.
- `from_col` lo emiten **solo** las tarjetas de `trip-collection`; los bloques `post-stages`
  en una página de colección **no** son origen `from_col`.
- **No** abordar #13 (cobertura uniforme de contexto en portada/`archive.php`, ni la
  pérdida por navegación anidada viaje→etapa→viaje): es su propio TO-DO.
- El desarrollador **no** sube versión ni edita `TODO.md` / el documento de diseño (cierre
  del arquitecto).

---

## 6. Recordatorios de método y validación

- **Entrega:** ficheros **completos** para descargar + comandos git (`add` por nombre,
  `commit` convencional, `push` a la cuenta personal). Un commit por punto.
- **Red de seguridad:** commit + push del estado actual antes de empezar si se prefiere
  punto de restauración.
- **Validación de Juanjo (en WordPress real):**
  - *Commit 1:* en el HTML de una colección, las tarjetas llevan `?from_col&col_key`; sin
    cambio visual.
  - *Commit 2, secuencia por bloque:* en una página de colección con **dos** bloques
    `trip-collection` (p. ej. «Mediterráneo» y «Península»), entrar a un viaje del bloque A →
    prev/next recorren **solo** el bloque A en su orden; repetir desde el bloque B → recorre
    B. Que la secuencia coincida con el orden mostrado en la colección.
  - *Commit 2, «Volver» + propagación:* «Volver» regresa a la página de colección; tras usar
    prev/next varias veces, «Volver» sigue yendo a la colección (los parámetros se propagan).
  - *Commit 2, etiqueta:* en contexto de colección dicen «Viaje anterior / Siguiente viaje».
  - *`showAll`:* en un bloque con «sin límite», la navegación recorre **todas** las entradas.
  - *No regresión:* la navegación y el «Volver» de un viaje (`from_post`) y de un cuaderno
    (`from_cuaderno`) siguen intactos, y ahí la etiqueta sigue diciendo «Ruta».
