# Requerimientos de desarrollo — #5 (`mejora`) · Plantilla «Colección de viajes»

> Documento del **arquitecto** para el **rol desarrollador**. Es una especificación,
> no código. El desarrollador implementa, y **Juanjo valida en WordPress real** antes
> de cerrar. Fuente de verdad: el repo `jjramosgo-coder/enterprise-moto-theme`.
> Versión base: **2.4.9**. Antes de tocar nada: clon fresco + `git ls-remote`, y leer
> `bitacora-enterprise-design.md` y `TODO.md`.

---

## 0. Objetivo

Reconvertir la plantilla en desuso `page-bitacora-bloques.php` en una **plantilla de
página curada** para publicar **colecciones de viajes/rutas ya cerrados** (p. ej. «De
vacaciones», «Rutas de puente»), como alternativa a la presentación por defecto que
WordPress hace de un archivo de categoría. En ella:

1. Compones el cuerpo con **bloques Gutenberg** (varios por página), controlando orden,
   decoración y segmentación de las entradas que cumplen tus filtros.
2. Un **hero** muestra cifras agregadas de la colección, **cacheadas al guardar** (no en
   caliente), sin fechas ni duración ni progreso ni estado «en vivo».
3. Un **ticker** se alimenta de un identificador curado por entrada.

Maqueta de referencia aprobada: `pagina-curada-prototipo.html` (en los ficheros del
proyecto; no versionada).

### Concepto (vocabulario base, para no confundir términos)

- **Viaje ≡ ruta en moto**: no hay viaje sin ruta. Una ruta se hace en **un día o en
  varios**.
- Ruta de varios días → **varias etapas** (y quizá alguna jornada). Ruta de un día →
  **una sola etapa** (una salida de un día), quizá con jornada.
- Esta plantilla lista **rutas/viajes enteros** (cada tarjeta = una salida completa, de 1
  o N días). El bloque `post-stages` («Etapas de ruta») lista las **etapas dentro de** una
  ruta. Son cosas distintas.
- Cuidado con el solaje: `_post_tipo = viaje` es el **valor de campo** del tipo D (ruta de
  varios días a posteriori), distinto del **concepto** general «viaje».

---

## 1. Estado actual (verificado en código, no de memoria)

- **`enterprise_calculate_viaje_stats( $post_id )`** (`functions.php:2408`): ejecuta la
  query de etapas por filtros (categorías/etiquetas/fechas) y devuelve `km_total`,
  `km_incompleto`, `ferry_count`, `etapas_count`. En `enterprise_post_stage_save()`
  (`:2510`) el tipo D **cachea** esas cifras en `_post_km_calculado`, `_post_km_incompleto`,
  `_post_ferry_count`, `_post_etapas_count`. **Este es el precedente a reutilizar.**
- **`enterprise_collect_stage_blocks( $blocks )`** (dentro de `page-bitacora-bloques.php`,
  ~`:123`): recorre recursivamente los bloques y devuelve los `enterprise/post-stages`.
  Reutilizable y a generalizar (debe reconocer también el bloque nuevo).
- **`enterprise_km_display( $km )`**: helper único de presentación de km (§13.4). Usarlo.
- **Filtros del bloque `post-stages`** (§7): `categoryIds` (array), `tagIds` (array),
  `tagRelation` (AND/OR), `filterDateFrom`, `filterDateTo`, `orderBy`, `order`,
  `postsPerPage`. La `tax_query`: categorías `IN` (OR), etiquetas `IN`/`AND`, relación
  categoría↔etiqueta siempre `AND`.
- **La plantilla `page-bitacora-bloques.php` está en desuso**: ninguna página del blog la
  referencia (confirmado por Juanjo). Renombrarla no rompe asignaciones.
- **Cableado funcional del nombre `page-bitacora-bloques.php`** que hay que tratar al
  renombrar (verificado): `functions.php:487` (lista de plantillas con metabox de
  expedición), `:497` (`$is_bloques` en el render del metabox), `:775` (`$is_bloques` en el
  guardado), `:1391` y `:1424` (hooks gateados a esta plantilla), `:1487` (check «es
  plantilla de expedición»). Además comentarios en `functions.php:588,1482` y
  `page-cuaderno-de-bitacora.php:84`.
- **No tocar** `page-cuaderno-de-bitacora.php`: su prefijo `page-` es correcto (el portal
  vive en el slug fijo `cuaderno-de-bitacora`).

---

## 2. Artefactos (nombres y espacios de nombres ya acordados)

| Artefacto | Nombre / ruta | Notas |
|---|---|---|
| Plantilla | `page-templates/template-trip-coleccion.php` | Crear subcarpeta `page-templates/`. `Template Name: Colección de viajes`. Global (sin prefijo `page-`). |
| Bloque nuevo | `enterprise/trip-collection` | Rótulo «Colección de viajes». Categoría del insertor «Enterprise Moto». |
| Campo de entrada | `_post_ticker_name` | Meta **de post** (namespace `_post_*`, §11). Fallback al título si vacío. |
| Cifras cacheadas (página) | `_col_stats` (array serializado) | Namespace **nuevo `_col_*`** para esta plantilla. Claves: `viajes`, `km`, `km_incompleto`, `etapas`, `ferrys`, `paises`. |
| Marca de tiempo | `_col_stats_updated` | Fecha del último recálculo (para la línea «actualizadas …» del hero). |
| CSS de la plantilla | `assets/css/coleccion.css` | Cargado solo si la plantilla está activa (§7). Clases del prototipo: `.col-*`, `.trip-*`, `.ticker`. |

> Si prefieres otro prefijo distinto de `_col_*` para los campos de página, indícalo antes
> de empezar; el resto de la spec no depende del literal.

---

## 3. Decisiones de diseño fijadas (no reabrir sin motivo)

1. **Cifras cacheadas al guardar, NUNCA en caliente.** Es lo contrario a §13.5 (cuaderno),
   y es correcto porque el dominio aquí es de viajes cerrados. Se documentará como decisión
   §13.7 (contraste explícito con §13.5).
2. **La fuente de las cifras es la unión de bloques, no un filtro de página.** No hay
   `_filt_*` en esta plantilla. Se admiten **varios bloques de filtrado** por página.
3. **Deduplicación única.** Se construye el conjunto de **IDs únicos** de la unión de todos
   los bloques de filtrado de la página; **todas** las cifras se derivan de ese mismo
   conjunto (un post en dos bloques cuenta una sola vez en todas las estadísticas).
4. **Estadísticas del hero** (etiquetas y cómputo, sobre el conjunto único):
   - **Viajes** = nº de entradas del conjunto.
   - **Etapas** = suma por entrada de sus etapas (tipo D: `_post_etapas_count`; salida de un
     día: 1).
   - **Kilómetros** = suma de km por entrada (tipo D: `_post_km_calculado`; salida de un
     día: `_post_km`). Se pinta con `enterprise_km_display()`. Si alguna entrada no aporta km,
     marcar incompleto (prefijo `≈`).
   - **Ferrys** = suma (tipo D: `_post_ferry_count`; salida de un día: 1 si tiene
     `_post_horas_ferry`).
   - **Países** = parseo y **unión** de `_post_paises` de cada entrada (ver R6).
   - Se asume que los bloques listan **entradas de salida completa** (tipo C/D), no etapas
     sueltas (tipo B).
5. **Ticker** alimentado por `_post_ticker_name` de las entradas del conjunto (fallback al
   título), deduplicado, en orden de aparición en la página, con **tope** N.
6. **Metabox de la plantilla: mínimo.** El texto del hero sale del **título** y el
   **extracto** nativos de la página (no crear campos de texto nuevos). Las cifras son
   automáticas. No hay filtros de página. (El desacoplado del metabox `_exp_*` es R7.)
7. **Bloque nuevo aditivo + lógica compartida.** El contenido guardado con `post-stages`
   no se migra. Se comparte la **query** (filtros → IDs); el **render** puede divergir
   (tarjetas de viaje vs. timeline de etapas).

---

## 4. Requerimientos, agrupados por commit (troceado para validar por partes)

> Convención de commits: conventional commits con scope. Cada fase debe poder validarse en
> WordPress antes de seguir. El **bump de versión** y la **documentación** van al final,
> tras validar (fase 5). El desarrollador **no** sube versión por su cuenta.

### Fase 1 — Lógica de query compartida + bloque `enterprise/trip-collection`

- **R1.** Leer primero `blocks/post-stages/block.json` y `blocks/post-stages/render.php`.
  Extraer la construcción de la **query por filtros** (attrs → `WP_Query`/IDs) a una
  **función compartida** (p. ej. `enterprise_stage_query( array $attrs ): WP_Query|array`),
  y hacer que `post-stages` la use. **Garantía:** el render de `post-stages` debe quedar
  **byte-idéntico** para el contenido existente (Juanjo lo valida comparando el HTML
  renderizado de una página con `post-stages` antes/después).
- **R2.** Registrar el bloque `enterprise/trip-collection` (block.json + editor JS +
  `render.php`), en la categoría «Enterprise Moto». Reutiliza:
  - la **query compartida** (R1) para resolver las entradas;
  - los **controles de filtro** del inspector de `post-stages` (categorías/etiquetas/fechas/
    orden/cantidad) — mismos atributos.
  - Render propio: **tarjetas de viaje** según la maqueta (`.trip-card` con badge de tipo,
    año, y pie de 3 datos por entrada). Km con `enterprise_km_display()`.
- **R3.** Generalizar `enterprise_collect_stage_blocks()` para reconocer **ambos**
  identificadores (`enterprise/post-stages` y `enterprise/trip-collection`) como «bloques de
  filtrado». Moverla a `functions.php` (deja de vivir dentro de la plantilla) para que sea
  reutilizable por la plantilla y por el cálculo de stats.

*Validación fase 1:* el bloque nuevo aparece en el insertor, filtra y pinta tarjetas en una
página de prueba; las páginas con `post-stages` no cambian (HTML idéntico).

### Fase 2 — Campo `_post_ticker_name`

- **R4.** Añadir al metabox de entrada el campo **«Nombre en el ticker»** →
  `_post_ticker_name` (texto). Visible para los tipos que se listan en colecciones (tipo D
  «viaje» y tipo C «salida de un día»); seguir el patrón del metabox existente. Guardado con
  las guardas habituales (nonce, autosave, capacidad). Sin unidad ni transformación.

*Validación fase 2:* el campo aparece/guarda; vacío → se usará el título como fallback.

### Fase 3 — Plantilla `template-trip-coleccion.php` (renombrado + desacople + maqueta)

- **R5.** `git mv page-bitacora-bloques.php page-templates/template-trip-coleccion.php`
  (conserva historia) y **reescribir** su contenido según la maqueta:
  - Header con `Template Name: Colección de viajes`.
  - **Hero**: título = título de la página; subtítulo = extracto de la página; badge
    «Colección · N viajes»; cinco cifras leídas de `_col_stats` (ver Fase 4); línea
    «actualizadas …» desde `_col_stats_updated`. **Sin** fechas, duración, progreso ni
    estado en vivo.
  - **Ticker**: `_post_ticker_name` (fallback título) de las entradas del conjunto único,
    en orden de aparición, deduplicado, tope N (constante del tema, p. ej. `16`). Si el
    conjunto supera N, tomar los primeros N por ese orden.
  - **Cuerpo**: renderizar el contenido Gutenberg de la página (bloques). Mantener la
    contención de floats (§13.2): el contenedor del contenido con `display: flow-root`.
  - **Sin** columna resumen sticky ni caja de suscripción del antiguo cuaderno (la
    colección no es un viaje único).
- **R6 (contención del desacople).** Retirar `template-trip-coleccion.php` de la lógica de
  **expedición** compartida y darle la suya:
  - Quitar la plantilla de la lista/checks de metabox de expedición (`functions.php:487`,
    `:497`, `:775`, `:1487`) — la colección **no** usa el metabox `_exp_*`.
  - Reapuntar/duplicar los hooks de enqueue (`:1391`, `:1424`) para que carguen
    `assets/css/coleccion.css` cuando la plantilla activa sea la nueva.
  - Eliminar del código el ticker antiguo por `_exp_categoria`/`_exp_etiquetas` (queda
    sustituido por `_post_ticker_name`) y la query `otras` que referenciaba nombres de
    plantilla (`page-bitacora-bloques.php:333,341`).
  - Actualizar comentarios que citan el nombre viejo.

*Validación fase 3:* asignar la plantilla a una página de prueba con 2 bloques; se ven hero
(cifras aún a 0/vacías), ticker y cuerpo; `post-stages` y el cuaderno siguen intactos.

### Fase 4 — Cálculo y cacheo de las cifras

- **R7.** Función que, dada la página, (a) parsea su contenido, (b) recoge los bloques de
  filtrado (R3), (c) resuelve cada uno con la query compartida (R1) a IDs, (d) **une y
  deduplica** los IDs, y (e) computa las cifras del §3.4 sobre ese conjunto único. Reutilizar
  el patrón de `enterprise_calculate_viaje_stats()` para leer los cacheados por entrada.
  Persistir en `_col_stats` (+ `_col_stats_updated`).
- **R8.** Disparar el cálculo al **guardar la página** de esta plantilla (guardas
  habituales).
- **R9 (frescura sin cálculo en caliente).** Hook `save_post` que, al guardar cualquier
  entrada `post` publicada relevante, **recache** las páginas de esta plantilla (localizar
  las páginas con `_wp_page_template = page-templates/template-trip-coleccion.php` y
  recomputar su `_col_stats`). Volumen bajo → coste trivial. Guardas: saltar autosave,
  revisiones y comprobar capacidad; evitar recursión. El recálculo ocurre en **escritura del
  post**, no en render de la página (sigue siendo cacheado, no en caliente).
- **R6-países.** Parseo de `_post_paises`: separar por el separador de display usado en el
  tema (verificar en el código real cómo se guarda; p. ej. « · » o «,»), normalizar
  (trim/mayúsc. inicial), y **unir sin duplicados** entre entradas. El nº de países del hero
  = tamaño de esa unión.

*Validación fase 4:* las cinco cifras cuadran con las entradas mostradas (deduplicadas); al
publicar/editar una entrada que casa un bloque y re-guardarla, el hero se actualiza sin
re-guardar la página.

### Fase 5 — Documentación y versión (SOLO tras validar todo lo anterior)

- Design doc: **§1** nueva subsección «Vocabulario base: viaje, ruta, etapa, jornada»
  (texto que aportará el arquitecto); **§6** tabla de plantillas (nueva fila «Colección de
  viajes» + retirar «Bitácora con bloques»); **§7** catálogo (bloque `trip-collection` +
  nota de query compartida con `post-stages`); **§11** alta de `_post_ticker_name` y de los
  `_col_*`; **§13.7** nueva decisión (cacheado al guardar en la colección; contraste con
  §13.5; unión deduplicada de bloques como fuente).
- **Bump de versión 2.4.9 → 2.5.0** (feature nueva) en los TRES sitios a la vez: `style.css`,
  `ENTERPRISE_VERSION` y header del design doc.
- `TODO.md`: mover #5 a «Resueltas» (tipo `mejora`) conservando su `#`.

---

## 5. Fuera de alcance (para no inferir requisitos no pedidos)

- **Navegación anterior/siguiente entre las entradas de la colección.** Las tarjetas son
  **enlaces planos**; no se inyecta contexto `from_*`. Si más adelante se quiere navegar
  entre los viajes de una colección, será un **TODO nuevo** que deberá cumplir §13.1/§6
  (propagar contexto y reconstruir la secuencia desde el mismo bloque que la genera, con
  desambiguación por bloque al haber varios en la página). No implementar ahora.
- **Retirada del campo legacy `_exp_categoria`.** Al desaparecer el ticker viejo, deja de
  tener consumidor y queda **desbloqueado** para retirarse, pero es limpieza aparte:
  **crear un TODO nuevo**, no hacerlo dentro de #5. El dato no se borra de la BD.
- **Renombrar `page-cuaderno-de-bitacora.php`.** No se toca (ver §1).
- **Flag «incluir en las cifras» por bloque.** No se incluye: **todos** los bloques de
  filtrado cuentan para el hero. Posible refinamiento futuro si aparece la necesidad de un
  bloque decorativo que no deba sumar.

---

## 6. Recordatorios de método

- Antes de operaciones de riesgo (renombrado/`git mv`, migraciones): **commit + push** como
  punto de restauración.
- Entregar cada fichero versionado modificado **completo** + los comandos git (add / commit
  conventional / push a la cuenta personal), aunque el cambio sea de una línea.
- Cualquier comando de inspección que pidas a Juanjo es de **solo lectura** y se confirma
  como tal.
