# Bitácora Enterprise — Diseño conceptual e implementación
<a id="top"></a>

**Blog:** bitacoraenterprise.com  
**Tema WordPress:** Bitácora Enterprise v2.11.2  
**Última revisión:** Julio 2026

---

## Índice

1. [Principios de diseño](#1-principios-de-diseño)
2. [Modelo de contenidos](#2-modelo-de-contenidos)
3. [Sistema de tipos de entrada](#3-sistema-de-tipos-de-entrada)
4. [Cuaderno de bitácora](#4-cuaderno-de-bitácora)
5. [Flujo editorial](#5-flujo-editorial)
6. [Arquitectura de páginas y URLs](#6-arquitectura-de-páginas-y-urls)
7. [Bloques Gutenberg](#7-bloques-gutenberg)
8. [Sistema de mapas](#8-sistema-de-mapas)
9. [Portada](#9-portada)
10. [Página fuera de ruta](#10-página-fuera-de-ruta)
11. [Referencia de campos personalizados](#11-referencia-de-campos-personalizados)
12. [Referencia de categorías](#12-referencia-de-categorías)
13. [Decisiones de arquitectura](#13-decisiones-de-arquitectura)

---

[↑ Volver arriba](#top)

## 1. Principios de diseño

### Vocabulario base: viaje, ruta, etapa, jornada

Antes de entrar en las dimensiones de contenido conviene fijar cuatro términos que el resto del documento usa como base, porque se confunden con facilidad.

- **Viaje ≡ ruta en moto.** En este blog no hay viaje sin ruta: todo viaje es un recorrido en moto. «Viaje» y «ruta» nombran la misma cosa; no son dos categorías distintas.
- **La variable que distingue los casos es la duración**, no «viaje vs. ruta». Una ruta se hace en un día o en varios:
  - Ruta de **varios días** → se compone de **varias etapas** (y quizá alguna jornada).
  - Ruta de **un día** → es **una sola etapa** (una salida de un día).
- **Etapa.** Un día de conducción: el tramo recorrido en moto en una jornada de ruta. Es la unidad mínima de un recorrido, ya sea un día dentro de una ruta larga o una salida de un día completa (misma naturaleza; ver §3).
- **Jornada.** Un día **sin desplazamiento en moto** dentro de un viaje: visita a una ciudad, descanso, actividad cultural. No aporta kilómetros.

**Matiz — «viaje» (concepto) vs. `viaje` (valor de campo).** El valor `_post_tipo = viaje` no designa el concepto general de «viaje» de este vocabulario, sino específicamente la **ruta de varios días descrita a posteriori** (el **Tipo D** de §3). Una salida de un día, aunque también sea un «viaje» en sentido conceptual, no usa el valor `viaje` sino `etapa` (Tipo B/C). El sistema de valores de `_post_tipo` y su mapa a los tipos A–E vive en §3.

### Separación entre dimensiones de contenido

El blog maneja dos dimensiones de contenido completamente independientes:

- **Tipo de salida** — clasifica el contenido por naturaleza de la actividad (vacaciones, desayuno motero, fin de semana, puente). Usa la jerarquía de categorías `tipo-de-salida`.
- **Cuaderno de bitácora** — narración en directo de un viaje mientras ocurre. Usa su propia categoría `cuaderno-de-bitacora` y una taxonomía de expedición.

Estas dos dimensiones son **ortogonales**: un post nunca pertenece simultáneamente a una subcategoría de `tipo-de-salida` y a `cuaderno-de-bitacora`.

### Narración en directo vs. narración a posteriori

El mismo viaje puede producir dos tipos de contenido distintos y complementarios:

| | Cuaderno de bitácora | Descripción a posteriori |
|---|---|---|
| Cuándo se escribe | Durante el viaje | Después de volver |
| Tono | Apunte en caliente, subjetivo | Relato reflexivo, editado |
| Categoría | `cuaderno-etapa` / `cuaderno-jornada` | `etapa` / `jornada` |
| Tipo de entrada | `etapa` (o `jornada`) | `etapa` (o `jornada`) |
| Pertenece a | Cuaderno de bitácora | Viaje a posteriori (Tipo D) |

Estos posts son **entidades independientes**. El cuaderno no se transforma en descripción a posteriori; conviven como dos documentos sobre el mismo evento.

### Cuadernos de bitácora como documentos de época

Un cuaderno finalizado se conserva exactamente como fue escrito durante el viaje. No se edita, no se resume, no se reestructura. Su URL es permanente desde el momento de su creación.

---

[↑ Volver arriba](#top)

## 2. Modelo de contenidos

### Entidades principales

```
PÁGINA PORTAL
/cuaderno-de-bitacora/
│
├── CUADERNO ACTIVO (página hija)
│   /cuaderno-de-bitacora/sicilia-2026/
│   ├── Etapa 1 (post tipo etapa, cat cuaderno-etapa)
│   ├── Etapa 2 (post tipo etapa, cat cuaderno-etapa)
│   └── Jornada (post tipo jornada, cat cuaderno-jornada)
│
├── CUADERNO FINALIZADO (página hija)
│   /cuaderno-de-bitacora/norte-italia-2025/
│   └── [etapas archivadas]
│
VIAJE A POSTERIORI (post tipo viaje)
│   Categoría: vacaciones (u otra subcategoría de tipo-de-salida)
│   Referencias a etapas mediante filtros (categoría + etiquetas + fechas)
│
ETAPAS A POSTERIORI (posts tipo etapa)
│   Categoría: etapa
│   Vinculadas al viaje mediante filtros del bloque Timeline/Carrusel
```

### Regla de no colisión

```
SI el post tiene categoría cuaderno-de-bitacora
→ usa cuaderno-etapa o cuaderno-jornada
→ NO tiene tipo-de-salida/*

SI el post tiene tipo-de-salida/*
→ usa etapa o jornada
→ NO tiene cuaderno-de-bitacora
```

### Contador de "días de ruta publicados" en portada

El contador solo cuenta posts en la categoría `etapa` (a posteriori). Los posts del cuaderno (`cuaderno-etapa`) no cuentan, evitando doble conteo cuando un mismo día existe en ambas versiones.

---

[↑ Volver arriba](#top)

## 3. Sistema de tipos de entrada

Cada post tiene un campo `_post_tipo` que declara su naturaleza. Este campo activa los campos correspondientes en el metabox y determina qué información se muestra en la ficha del post.

### Tipo A — Jornada

Día sin desplazamiento en moto: visita a ciudad, día de descanso, actividad cultural.

**Metabox visible:** solo Dato extra (etiqueta + valor)  
**Categoría:** `jornada` (a posteriori) o `cuaderno-jornada` (en directo)  
**Franja de datos:** no muestra datos numéricos

### Tipo B/C — Etapa / Salida de un día

Día de conducción. Los tipos B (etapa dentro de un viaje largo) y C (salida autónoma de un día) comparten la misma estructura de metabox porque la naturaleza del día es idéntica; la diferencia está en la categoría.

**Metabox visible:**
- Tramo (origen → destino)
- Kilómetros
- Horas en moto
- Horas en ferry / barco
- Duración total del día
- Dato extra (etiqueta + valor)

**Categoría tipo B:** `etapa` o `cuaderno-etapa`  
**Categoría tipo C:** subcategoría de `tipo-de-salida` de **un solo día** (p. ej. `desayuno-motero`, una excursión de un día). Nunca `fin-de-semana-motero` ni `puente-motero`: al abarcar dos o más días no son salidas de un día.

**Franja de datos:** muestra tramo, km, horas moto, horas ferry, duración

### Tipo D — Viaje de varios días (a posteriori)

Entrada que agrupa y describe un viaje completo escrito después de volver. Es la contraparte editorial del cuaderno de bitácora.

**Metabox visible:**
- Fecha de inicio / Fecha de fin → duración calculada automáticamente
- Países recorridos
- Categoría de las etapas (mismo filtro que los bloques Timeline/Carrusel)
- Etiquetas adicionales (mismo filtro que los bloques)
- Dato extra (etiqueta + valor)
- Panel de estadísticas calculadas al guardar: km totales, nº etapas, nº etapas con ferry

**Cálculo automático al guardar:** el sistema ejecuta la misma query que los bloques Timeline/Carrusel usan dentro de la entrada, suma los km de cada etapa encontrada y calcula ferrys y contadores. Si alguna etapa no tiene km definido, el total aparece con `≈`.

**Franja de datos:** muestra km (calculados o manuales), días, países, nº etapas, etapas en ferry

### Tipo E — Entrada genérica

Contenido que no es una ruta: preparativos, equipación, reflexiones, noticias del blog.

**Metabox visible:** solo el selector de tipo (sin campos de ruta)  
**Franja de datos:** no aparece

---

[↑ Volver arriba](#top)

## 4. Cuaderno de bitácora

### Arquitectura de tres capas

```
CAPA 1 — Portal (URL permanente en el menú)
/cuaderno-de-bitacora/
Responsabilidad: enrutar al visitante según el estado del sistema.
No tiene contenido propio ni estado propio.

CAPA 2 — Cuaderno individual (página hija)
/cuaderno-de-bitacora/sicilia-2026/
Responsabilidad: narrar un viaje concreto.
Tiene estado: activo | finalizado
Tiene metadatos: nombre, fechas, km, países, categoría de etapas, etiquetas

CAPA 3 — Etapas (posts)
Posts vinculados al cuaderno mediante categoría + etiquetas + fechas.
```

### Lógica de enrutamiento del portal

```
El portal lee sus páginas hijas buscando _exp_estado = 'activo'

¿Hay una hija activa?
  SÍ → wp_redirect() a su URL (302)
  NO → muestra la página "Fuera de ruta"
```

El portal redirige silenciosamente. El visitante ve la URL del cuaderno activo, no la del portal.

### Estados del cuaderno

| Estado | _exp_estado | El portal... | La página... | Aparece en listados |
|---|---|---|---|---|
| En preparación | `preparando` | Lo ignora | Accesible por URL directa | No |
| Activo | `activo` | Redirige a este cuaderno | Muestra etapas en tiempo real | No (es el principal) |
| Finalizado | `finalizado` | Lo ignora (busca otro activo) | Permanece accesible en su URL | Sí — "Cuadernos anteriores" |

**Invariante:** como máximo un cuaderno puede estar activo en cualquier momento. Si hubiera dos activos por error, el sistema elige el más reciente.

**Uso de `preparando`:** permite crear y rellenar la página del cuaderno (datos de expedición, etapas de prueba, configuración del ticker) antes de salir. El portal no redirige a él ni lo lista en ningún sitio. Al partir, basta con cambiar el estado a `activo`.

### Metadatos de un cuaderno (página hija)

| Campo | Descripción |
|---|---|
| `_exp_estado` | `preparando` \| `activo` \| `finalizado` |
| `_exp_nombre` | Nombre del viaje (ej: "Sicilia & Cerdeña 2026") |
| `_exp_subtitulo` | Descripción breve de la ruta |
| `_exp_fecha_inicio` | Fecha de salida (AAAA-MM-DD) |
| `_exp_fecha_fin` | Fecha de vuelta (AAAA-MM-DD). **Opcional**: puede quedar vacía si el regreso aún no se conoce. Ya **no** significa «en curso» — el estado lo da `_exp_estado`. |
| `_exp_salida` | Texto de display de la fecha de salida (auto-calculado si hay fechas) |
| `_exp_km` | Kilómetros del viaje. **Override manual opcional**: si tiene valor (incluido uno curado como «~3.200 km») se muestra tal cual; si está vacío, se **calcula en caliente** sumando los km de las etapas. |
| `_exp_paises` | Países recorridos |
| `_exp_categoria` | *(legacy)* Ya **no** filtra el cuaderno; el filtro real usa `_filt_*` (ver §7 y «Filtro de etapas en el cuaderno»). Se conserva solo para el ticker de «Bitácora con bloques». |
| `_exp_etiquetas` | *(legacy)* Ídem — sin uso en el cuaderno; solo ticker de «Bitácora con bloques». |

> **Duración y progreso ya no son campos del cuaderno.** Se calculan en caliente (ver «Estadísticas en caliente del cuaderno», más abajo). Los antiguos campos `_exp_duracion` y `_exp_progreso` solo persisten en la plantilla «Bitácora con bloques», que conserva su propio metabox; en un cuaderno ni se editan ni se leen (el dato antiguo no se borra de la base de datos).

### Filtro de etapas en el cuaderno

El cuaderno selecciona sus etapas mediante el **sistema de filtros unificado** (§7), con los campos `_filt_*` de la página. Muestra los posts **publicados** que cumplen TODOS los criterios definidos:

1. Categorías = `_filt_category_ids` (array de IDs; operador `IN`, es decir OR entre ellas).
2. Etiquetas = `_filt_tag_ids` con relación `_filt_tag_relation` (`IN`/OR o `AND`); la relación entre categorías y etiquetas es siempre `AND`.
3. Fecha de publicación entre `_filt_date_from` y `_filt_date_to` (cada límite es opcional; si faltan ambos, no se filtra por fecha).

El orden y el límite los fijan `_filt_orderby` / `_filt_order` / `_filt_limit`. Ese orden es la fuente del contrato de navegación del cuaderno (§6, §13.1) y de las estadísticas en caliente (`enterprise_cuaderno_stats()`, más abajo): el listado, la navegación anterior/siguiente y el conteo de etapas salen todos de la misma consulta.

Este filtro es idéntico al que usan los bloques Timeline y Carrusel de etapas y el post tipo D (§7).

> **Campos legacy.** Los antiguos `_exp_categoria` / `_exp_etiquetas` **ya no** definen el filtro del cuaderno; se conservan únicamente para el ticker de la plantilla «Bitácora con bloques» (backward compat) y no deben usarse en lógica nueva.

### Estadísticas en caliente del cuaderno

Las estadísticas de un cuaderno (km, nº de etapas, duración, progreso) **se calculan en caliente**, en cada render, a partir de las etapas que casan sus filtros. **No** se cachean al guardar (a diferencia del post tipo D «Viaje de varios días», ver §3): las etapas de un cuaderno cambian a lo largo del tiempo sin re-guardar la página, y cachear al guardar quedaría obsoleto en un cuaderno `activo`.

**Fuente única.** La función `enterprise_cuaderno_stats( $page_id )` (`functions.php`) es el único origen de estas cifras, y la usan **todos** los consumidores (barra lateral y hero del cuaderno, tarjeta del grid de «Viajes completados», cabecera agregada de la página «fuera de ruta» y listas de «otras» expediciones). Devuelve un array con:

| Clave | Contenido |
|---|---|
| `estado` | `_exp_estado` canónico; si está vacío, se deriva del legacy `_exp_en_ruta` **solo** como respaldo. |
| `km` | Valor **sin unidad** (se pinta con `enterprise_km_display()`). Override `_exp_km` si tiene valor; si no, suma en caliente de `_route_km` de las etapas. |
| `etapas` | Nº de etapas (`found_posts` de la query por filtros `_filt_*`, ver §7). |
| `dias_totales` | Días de `_exp_fecha_inicio` a `_exp_fecha_fin`; `0` si no hay fin resoluble. |
| `dias_transcurridos` | Días de `_exp_fecha_inicio` a hoy; `0` si aún no ha empezado o no hay inicio. |
| `fecha_inicio` / `fecha_fin` | Fechas resueltas (`fecha_fin` puede provenir de la última etapa; ver fallback). |
| `fin_heredada` | `true` si la fin se dedujo de la última etapa en lugar de `_exp_fecha_fin`. |

El conteo de etapas sale de esa misma query `_filt_*` (la que genera el listado), nunca del campo `_exp_categoria`, coherente con el contrato de navegación (§6, §13.1). Rendimiento: la query ceba la meta cache en bloque, de modo que la suma de km es de coste ~constante respecto al número de etapas.

**Duración y progreso** se derivan de `estado` + fechas. El estado manda en los extremos; las fechas solo interpolan en `activo` con fin definida:

| Estado | Fecha de fin | Progreso | Duración mostrada |
|---|---|---|---|
| `preparando` | — | No se muestra | No se muestra |
| `activo` | con fin | Barra de %: `clamp( dias_transcurridos / dias_totales × 100, 0, 100 )` | Días totales (inicio→fin) |
| `activo` | sin fin | Sin %: indicador «día N en ruta» (N = `dias_transcurridos`) | «N días, en curso» |
| `finalizado` | con fin | 100 % (fijo por estado) | Días totales (inicio→fin) |
| `finalizado` | sin fin (heredado) | 100 % (fijo por estado) | Días inicio→(fecha de la última etapa) |

**Fallbacks defensivos.** Un cuaderno `finalizado` sin `_exp_fecha_fin` (dato heredado de la antigua semántica «vacío = en curso») usa como fin la **fecha de la etapa más reciente**. Caso límite: un `finalizado` sin fin **y sin etapas** muestra el progreso al **100 %** (por estado) y **omite** la duración. El disparador de la barra de progreso es `_exp_estado` (no el legacy `_exp_en_ruta`); toda división comprueba `dias_totales > 0`.

---

[↑ Volver arriba](#top)

## 5. Flujo editorial

Hay dos formas de documentar un viaje en La Bitácora de la Enterprise:

---

### 1. Cuaderno de Bitácora (viaje de varios días, en tiempo real)

Un cuaderno de bitácora documenta un viaje mientras se realiza. Tiene tres fases:

#### Fase 1 — Preparación del viaje

```
1. WordPress → Páginas → Añadir nueva
2. Título: "Cuaderno · Portugal 2026"
3. Panel lateral → Página padre: "Cuaderno de bitácora" (el portal)
4. Plantilla: "Cuaderno de bitácora"
5. Rellenar metabox de expedición (5 secciones):
   ① Datos de la expedición:
      - Nombre del viaje / Descripción / Fecha inicio y fin
      - Kilómetros / Países / Progreso (0-100)
   ② Estado: 🔧 En preparación
   ③ Filtros:
      - Categorías (checkboxes, OR entre seleccionadas)
        Ej: ☑ cuaderno-etapa  ☑ cuaderno-jornada
      - Etiquetas (checkboxes, relación AND/OR seleccionable)
        Ej: ☑ portugal-2026
      - Fecha desde / Fecha hasta (AAAA-MM-DD, opcionales)
      Relación entre categorías y etiquetas: siempre AND
   ④ Cantidad y orden:
      - Cantidad máxima (vacío = sin límite)
      - Ordenar por: fecha | título | orden manual | modificación | aleatorio
      - Dirección: Descendente | Ascendente
   ⑤ Presentación:
      - Modo: Timeline vertical | Carrusel horizontal
      - Tamaño tarjeta: Normal | Grande
      - Campos visibles: ☑ Extracto  ☑ Kilómetros  ☑ Fecha
6. Publicar

Estado: El cuaderno es accesible por URL directa pero no aparece en ningún
listado ni redirige desde el portal. Los datos del metabox (nombre, fechas,
países) alimentan la sección "Próxima Expedición" de la página "Fuera de ruta".
```

#### Fase 2 — El viaje está activo (publicación de etapas)

```
1. Cambiar estado del cuaderno a ✈ Activo → Actualizar

   Resultado: el portal detecta este cuaderno y redirige a él.
   /cuaderno-de-bitacora/ muestra el cuaderno en tiempo real.

2. Cada día, crear una entrada nueva:
   a. Metabox → Tipo de entrada: 🏍 Etapa / Salida de un día
   b. Rellenar: Tramo, Kilómetros, Horas en moto, Horas en ferry (si aplica)
   c. Categorías: las mismas definidas en los filtros del cuaderno
      Ej: ☑ cuaderno-etapa
   d. Etiquetas: las mismas definidas en los filtros del cuaderno
      Ej: ☑ portugal-2026
   e. Publicar

   Resultado: el cuaderno la recoge automáticamente por los filtros definidos.
```

#### Fase 3 — El viaje finaliza

```
1. Abrir la página del cuaderno
2. Metabox → Estado del cuaderno: ✓ Finalizado → Actualizar

Resultado: el portal ya no detecta ningún cuaderno activo.
/cuaderno-de-bitacora/ muestra la página "Fuera de ruta".
La URL /cuaderno-de-bitacora/portugal-2026/ sigue accesible para siempre.
El cuaderno aparece en "Cuadernos anteriores" en la página "Fuera de ruta".
```

---

### 2. Post de viaje a posteriori (cualquier duración)

Se usa cuando el viaje ya ha terminado y se publica de una sola vez o por etapas
sin restricción de cuándo. Es una entrada normal de WordPress. Lo único que varía
es cómo rellenar el metabox según la duración del viaje.

#### Salida de un día (desayuno motero, ruta corta, etc.)

```
1. Crear una entrada nueva
2. Metabox → Tipo de entrada: 🏍 Etapa / Salida de un día
3. Rellenar: Kilómetros, Horas en moto, Horas en ferry (si aplica), Duración
4. Categoría: la que corresponda (ej: desayuno-motero, ruta-corta)
   No usar las categorías reservadas para el cuaderno de bitácora
5. Publicar
```

#### Viaje de varios días (a posteriori)

```
1. Publicar primero las etapas individuales del viaje (ver "Salida de un día"
   pero con las categorías y etiquetas del viaje a posteriori)

2. Crear una entrada resumen del viaje completo:
   a. Metabox → Tipo de entrada: 📋 Viaje de varios días (a posteriori)
   b. Rellenar datos generales: Fecha inicio/fin, Países recorridos
   c. Filtros (mismos que en el cuaderno de bitácora):
      - Categorías (checkboxes): las mismas usadas en las etapas
      - Etiquetas (checkboxes, AND/OR): las mismas usadas en las etapas
      - Fecha desde / Fecha hasta: el rango del viaje
   d. Guardar → el sistema calcula automáticamente km totales, nº etapas, ferrys
   e. En el editor de bloques, añadir el bloque "Etapas de ruta" con los
      mismos filtros → muestra exactamente las etapas contabilizadas
   f. Categoría del post resumen: la que corresponda (ej: viajes, vacaciones)
      No usar las categorías reservadas para el cuaderno de bitácora

Nota: los filtros del metabox y del bloque "Etapas de ruta" son idénticos
(mismo sistema de checkboxes por ID) para garantizar coherencia en las estadísticas.
```

---

[↑ Volver arriba](#top)

## 6. Arquitectura de páginas y URLs

### Estructura de URLs

```
/                                    → Portada (index.php)
/las-rutas/                          → Archivo de todas las rutas
/cuaderno-de-bitacora/               → Portal del cuaderno (enrutador)
  ↳ si hay activo → redirige a él
  ↳ si no → muestra "Fuera de ruta"
/cuaderno-de-bitacora/sicilia-2026/  → Cuaderno individual (página hija)
/cuaderno-de-bitacora/norte-italia-2025/ → Cuaderno finalizado (URL permanente)
```

### Estructura de permalinks (ajuste de WordPress)

Los enlaces permanentes del sitio están configurados como **«Nombre de la entrada»** (`%postname%`) en *Ajustes → Enlaces permanentes*. **No** debe usarse la opción «Simple» (`?p=123`).

**Motivo:** con permalinks «Simple», la REST API de WordPress solo queda expuesta en su forma de consulta (`?rest_route=/wp/v2/...`) y la app móvil (Jetpack / app de WordPress) no lograba conectar. Al pasar a una estructura de enlaces amigables se activan los endpoints REST en su forma de ruta (`/wp-json/wp/v2/...`), que es la que la app espera. La conectividad de la app móvil depende, por tanto, de mantener este ajuste.

**Implicaciones al tocar permalinks:**

- Cualquier cambio en *Ajustes → Enlaces permanentes* debe ir seguido de volver a guardar la configuración para regenerar las reglas de reescritura (`flush_rewrite_rules`). Si tras un cambio las URLs de los cuadernos o la app dejan de funcionar, ese suele ser el motivo.
- Las URLs de los cuadernos finalizados son permanentes; cambiar el slug de una página rompería sus enlaces entrantes.

**Contexto de dominio:** el sitio sirve desde `bitacoraenterprise.com`. El dominio antiguo `jjramosgo.blog` sigue vivo y **redirige con 301** al nuevo, de modo que los enlaces previos no se pierden. La cabecera del tema (`style.css`: `Theme URI` / `Author URI`) apunta al dominio nuevo.

### Plantillas de página

| Plantilla | Archivo | Uso |
|---|---|---|
| Cuaderno de bitácora | `page-cuaderno-de-bitacora.php` | Portal Y cuadernos individuales |
| Colección de viajes | `page-templates/template-trip-coleccion.php` | Página curada que publica colecciones de viajes/rutas ya cerrados (p. ej. «De vacaciones»), compuesta con bloques Gutenberg. Alta en #5 (v2.5.0); procede de renombrar la antigua «Bitácora con bloques» (`page-bitacora-bloques.php`) conservando su historia. Ver §7 y §13.7. |
| Por defecto | `page.php` | Páginas genéricas |

La plantilla "Cuaderno de bitácora" detecta automáticamente si la página actual es el portal (sin `_exp_estado` ni página padre) o un cuaderno individual, y se comporta de manera diferente en cada caso.

### Lógica de detección en la plantilla

```php
$es_individual = !empty(_exp_estado) || tiene_pagina_padre

SI NO es_individual (= es el portal):
  buscar hija con _exp_estado = 'activo'
  SI existe → wp_redirect() a su URL
  SI NO    → enterprise_render_off_route()

SI es_individual (= cuaderno concreto):
  mostrar el cuaderno (timeline de etapas)
```

### Contrato de navegación entre entradas

Los botones **«ruta anterior» / «ruta siguiente»** de una entrada (`single.php`) recorren la secuencia de entradas en el **mismo orden con el que la produjo el listado de origen** desde el que se llegó a ella. No imponen ninguna lectura cronológica.

Definición rigurosa:

- **anterior** = elemento de índice−1 en la secuencia (hacia la cabeza).
- **siguiente** = elemento de índice+1 en la secuencia (hacia la cola).
- El criterio de orden (fecha ASC/DESC, título A-Z/Z-A, modificación…) lo fija el origen; «anterior/siguiente» solo se mueven por el índice, sea cual sea ese criterio. Por tanto **no** equivalen a «más antiguo / más reciente».

El orden se toma siempre de **la misma fuente que generó el listado mostrado**, según el contexto (parámetro de la URL):

| Contexto | Parámetro | Fuente del orden y los filtros | «Volver» |
|---|---|---|---|
| Cuaderno de bitácora | `from_cuaderno=ID_página` | Metadatos `_filt_orderby` / `_filt_order` (+ `_filt_*`) de la página del cuaderno | A la página del cuaderno |
| Viaje tipo D | `from_post=ID_post` | **Atributos del bloque «Etapas de ruta»** (`orderBy` / `order` + filtros) leídos del contenido del post | Al post del viaje |
| Colección de viajes | `from_col=ID_página` + `col_key=hash` | **Atributos del bloque `trip-collection` concreto** (identificado por `col_key`, hash de `enterprise_collection_block_key()`), reconstruidos con `enterprise_stage_query()` (misma guarda `showAll`) | A la página de colección |
| Rutas por localización | `from_loc=ID_página` + `loc_cat` + `loc_tag` | **Carrusel de esa categoría del destino**, reconstruido con `enterprise_stage_query({categoryIds:[loc_cat], tagIds:loc_tag})` (mismos atributos que la plantilla por carrusel) | A la vista de esa categoría del destino |
| Archivo de categoría | `from_cat=slug` | Orden del propio archivo | Al archivo de la categoría |
| Sin contexto | — | Adyacentes dentro de la misma categoría (fallback de WordPress) | Referer / página de entradas |

Las etiquetas de los botones dependen del **contexto activo** (el de nivel inmediato, ver «Persistencia» más abajo): solo cuando el contexto activo es la colección (`from_col`) se muestran **«Viaje anterior» / «Siguiente viaje»**; el resto de contextos —incluida una etapa alcanzada *a través* de un viaje que a su vez venía de una colección— conservan «ruta» (#8, v2.7.0; matiz de contexto activo, #13, v2.7.1).

**Regla para funcionalidades análogas:** cualquier listado nuevo que enlace a entradas con navegación anterior/siguiente debe (a) propagar su parámetro de contexto en los enlaces, y (b) reconstruir la secuencia leyendo **la misma fuente** que genera el listado visible (nunca un orden fijado a fuego), de modo que navegación y presentación coincidan siempre.

**Persistencia del contexto en navegación anidada (#13, v2.7.1).** El contexto no es de un solo nivel: la única cadena anidada real es *listado → viaje (tipo D) → etapa*. El origen **inmediato** (el más interior) gobierna el «Volver», el prev/next y la etiqueta; el origen **ancestro** (aquel desde el que se llegó al viaje) **se arrastra** en los enlaces salientes para sobrevivir al regreso, pero **no** es fuente de secuencia. En concreto: al bajar de un viaje a una etapa, los enlaces de etapa llevan `from_post` (inmediato) **más** el ancestro del propio viaje (`from_col`+`col_key`, `from_cat` o `from_cuaderno`, según cómo se llegó al viaje); el «Volver al viaje» de la etapa arrastra ese ancestro pero **descarta** `from_post` (sería autorreferente); al regresar, el viaje recupera su contexto y el «Volver» vuelve a apuntar a la colección/categoría/cuaderno de origen. El ancestro es **memoria de navegación, no secuencia**: el prev/next de la etapa siempre se computa desde el bloque «Etapas de ruta» del viaje (el listado que el usuario vio), nunca desde el ancestro. Sin ancestro (viaje alcanzado directamente), el «Volver al viaje» es un permalink plano, como antes.

**Puntos de estampado del contexto (#13, v2.7.1).** El estampado quedó **uniforme** en los orígenes que existen en el modelo: el bloque `trip-collection` estampa `from_col`+`col_key`; el bloque «Etapas de ruta» dentro de un viaje estampa `from_post` (+ ancestro); la plantilla del cuaderno estampa `from_cuaderno`; el **archivo de categoría** (`archive.php`, solo `is_category()`) y las **secciones de categoría de la portada** (`index.php` → `enterprise_home_post_card()`) estampan `from_cat` con el **slug real del término** (nunca reconstruido desde la cadena de presentación, que fallaba con nombre ≠ slug o título personalizado). **No** se estampa contexto —a propósito— en ítems **sin un listado ni una categoría única detrás**: la tarjeta destacada «Última ruta publicada» y la sección «Mientras tanto» de la portada (ver §13.11); ni en las secciones de **etiqueta** de la portada (una etiqueta no es categoría y el modelo no tiene `from_tag`). Con #18 (v2.9.0), la **página-destino de «Rutas por localización»** estampa `from_loc`+`loc_cat`+`loc_tag` en las tarjetas de sus carruseles por categoría; se reconoce en `enterprise_nav_origin_params()` y participa en el arrastre de ancestro como los demás orígenes (§13.13).

### Robustez del contenido Gutenberg

Un bloque del contenido Gutenberg de la página (introducción editorial sobre el timeline) alineado con `alignleft` / `alignright` recibe `float` de WordPress. Si el contenedor del contenido no contiene ese float, se desborda por debajo e invade la rejilla `.exp-layout`, comprimiéndola y mandando el timeline/carrusel a la columna lateral.

Para evitarlo, el contenedor del contenido encierra sus propios floats y la rejilla se protege como refuerzo:

```css
.exp-gutenberg-content { display: flow-root; } /* contiene el float en origen */
.exp-layout            { clear: both; }        /* refuerzo defensivo */
```

Con esto la alineación de bloques (cita o imagen flotada) sigue funcionando dentro del propio contenido, pero no puede afectar al timeline. El fallo no era HTML mal formado, sino un float sin contener.

---

[↑ Volver arriba](#top)

## 7. Bloques Gutenberg

Todos los bloques están en la categoría **Enterprise Moto** del insertor de Gutenberg. Para futuros bloques, mantener esta misma estructura: `render.php` en `/blocks/{nombre}/`, JS del editor en `/assets/js/block-{nombre}.js`, CSS en `/assets/css/{nombre}.css` (cargado solo si el bloque está presente en la página).

### Catálogo de bloques

| Bloque | Identificador | Descripción |
|---|---|---|
| Etapas de ruta | `enterprise/post-stages` | Timeline vertical o carrusel horizontal de entradas filtradas. Filtros: categorías (OR), etiquetas (AND/OR), fechas absolutas desde/hasta. Campos visibles, ordenación y cantidad configurables. |
| Colección de viajes | `enterprise/trip-collection` | **Tarjetas de viaje** (una por entrada) para la plantilla «Colección de viajes», con presentación **configurable como `post-stages`**: carrusel horizontal o timeline vertical (atributo `layout`, def. `carousel`; #11, v2.6.0). Reutiliza el scaffolding `.ent-stages--{layout}` y los assets `carousel.js`/`carousel.css` de «Etapas de ruta» **sin tocarlos**, conservando la `.trip-card` (el contenedor lleva ambas clases: `.ent-stages .ent-trip-collection`). Mismos atributos de filtro y **query compartida** con «Etapas de ruta», más el toggle **«sin límite»** (`showAll`, §13.7). Navegación anterior/siguiente **entre los viajes de la colección**: cada tarjeta propaga `?from_col&col_key` y `single.php` reconstruye la secuencia del bloque concreto, con «← Volver a la colección» y etiqueta «Viaje» (#8, v2.7.0; §6, §13.10). Alta en #5 (v2.5.0). Retoques de presentación de la tarjeta (v2.6.1): flecha-botón con cambio de color al pasar el ratón por la card (coherente con las cards de listado/portada) y celda de «Ferry» oculta si el viaje no tiene conexiones (misma regla «una cifra 0 no se pinta» del hero, §13.7). |
| Mapa de localizaciones | `enterprise/location-map` | Marcadores numerados en mapa OpenLayers con popup de información. |
| Mapa de ruta | `enterprise/route-map` | Trazado GPX con perfil de elevación. Soporta dos ficheros GPX simultáneos. |
| Mapa de ruta animado | `enterprise/animated-route-map` | Trazado GPX con sincronización animada elevación ↔ marcador. |
| Ruta planificada vs realizada | `enterprise/route-comparison` | Dos trazados GPX superpuestos: GPX1 (azul, planificada) y GPX2 (rojo, realizada). Perfil de altitud y sincronización posición ↔ mapa exclusivamente de GPX2. Las altitudes de GPX1 se ignoran. Leyenda automática. |
| Mapa de rutas por localización | `enterprise/routes-by-location` | Variante de `location-map` (que queda intacto) para **descubrimiento**: cada marcador (localización) guarda nombre, coordenadas, descripción opcional y un **filtro compuesto** sobre las taxonomías existentes —**(cat OR…) AND (tag AND…)**, vocabulario §7, resuelto con `enterprise_stage_query()`—; el popup lleva el enlace **«→ Entradas relacionadas»** a una página que auto-compone el grid de las entradas (viaje/etapa/jornada) que casan el filtro. Alta con buscador Nominatim y con clic en el mapa; gestión de decenas/cientos en un **Modal** con mapa (OpenLayers cargado bajo demanda al abrir) + lista propia buscable/paginada (10/pág.) —no usa DataViews/DataForm porque no están disponibles como globales en este WordPress (§13.12)—. Almacenamiento **por-bloque** (atributos); reutilización = copiar el bloque. Destino: la Página con la plantilla `template-routes-by-location.php` (elegida en el Customizer, `enterprise_rbl_dest_page`), que presenta las entradas en **un carrusel de `.post-card` por categoría** del marcador —reutilizando la librería de carrusel del tema (`.ent-stages` + `carousel.js`/`carousel.css`, §13.13)—, con enlace **«← Volver al mapa»** (param `rbl_src`) y contexto de navegación prev/next propio **`from_loc`** (§6). Alta en #17 (v2.8.0); rediseño definitivo del destino en #18 (v2.9.0). |
| Carrusel de fotos | `enterprise/photo-gallery` | Carrusel de imágenes seleccionadas manualmente. Ratio del contenedor configurable (16:9, 4:3, 1:1, 3:4, 9:16, adaptativo). Autoplay, 4 tamaños de imagen, lightbox integrado, botón ▶/⏸. |
| Stories | `enterprise/stories` | Historias verticales estilo WhatsApp/Instagram. Imágenes o vídeos con barra de progreso animada, pausa al mantener pulsado, bucle opcional. Visor fullscreen. |
| Tip / Aviso | `enterprise/tip-box` | Aviso destacado en 4 variantes: Consejo (verde), Nota (azul), Atención (ámbar), Peligro (rojo). Icono auto o selección manual (20 opciones). Etiqueta personalizable. Texto editable con RichText (negrita, cursiva, enlace). |
| YouTube Vídeo | `enterprise/youtube-video` | Un único vídeo o Short de YouTube con contenedor estilizado (fondo oscuro, cabecera con canal y duración, botón de play propio). Carga diferida del iframe hasta que el usuario hace click. Ratio configurable: 16:9, 9:16, 4:3. |
| YouTube Reels | `enterprise/youtube-reels` | Galería de YouTube Shorts. En móvil: scroll-snap horizontal con swipe, una tarjeta por pantalla, dots indicadores. En desktop: grid de N columnas configurable. Misma estética de contenedor que YouTube Vídeo. Carga diferida de iframes. |
| Markdown | `enterprise/markdown` | Renderiza contenido Markdown como HTML heredando los estilos del tema. Sin opciones de estilo adicionales. Ideal para contenido editorial que debe integrarse con el resto del post. Motor: Parsedown (PHP servidor). |
| Markdown con estilo | `enterprise/markdown-styled` | Igual que Markdown simple pero con opciones de presentación en el inspector: familia tipográfica (Bebas Neue / DM Sans / DM Serif Display), tamaño de texto, colores de texto y fondo, padding interior y borde lateral configurable. |

### Sistema de filtros unificado

Los tres puntos donde se filtran entradas usan exactamente el mismo sistema:

| Punto de uso | Campos |
|---|---|
| Bloque `post-stages` (Etapas de ruta) | `categoryIds` (array IDs), `tagIds` (array IDs), `tagRelation` (AND/OR), `filterDateFrom`, `filterDateTo` |
| Metabox post tipo D (Viaje de varios días) | `_post_viaje_cat_ids`, `_post_viaje_tag_ids`, `_post_viaje_tag_rel`, `_post_fecha_inicio`, `_post_fecha_fin` |
| Plantilla Cuaderno de Bitácora | `_filt_category_ids`, `_filt_tag_ids`, `_filt_tag_relation`, `_filt_date_from`, `_filt_date_to` |

La lógica de `tax_query` es idéntica en los tres: categorías con `operator IN` (OR entre ellas), etiquetas con `IN` u `AND` según `tagRelation`, relación entre categorías y etiquetas siempre `AND`.

**Query compartida `post-stages` ↔ `trip-collection`.** La construcción de la query por filtros (atributos → `WP_Query`) vive en una función única, `enterprise_stage_query( $attributes )` (`functions.php`), que usan **ambos** bloques; «Colección de viajes» reutiliza los mismos atributos de filtro que «Etapas de ruta». Lo compartido es la resolución de entradas (filtros → IDs); el **render** puede divergir. El helper `enterprise_collect_stage_blocks()` reconoce los dos identificadores como «bloques de filtrado» de una página. Es la base del cálculo de cifras de la colección (§13.7). Desde #11, ambos bloques comparten además el **scaffolding de presentación** (`.ent-stages--{layout}` + `carousel.js`/`carousel.css`): `trip-collection` lo reutiliza conservando su `.trip-card`, sin duplicar la lógica de layout.

### Mapas — comportamiento en scroll

Los bloques de mapa implementan protección contra desplazamiento accidental:

- **Desktop:** rueda del ratón sin Ctrl → hint "🖱 Ctrl + scroll para hacer zoom" — el mapa no se mueve
- **Móvil:** arrastrar con un dedo → hint "☝️ Usa dos dedos para mover el mapa" — el mapa no se mueve; dos dedos → pinch-zoom funciona

La implementación usa interceptación de eventos en fase capture antes de que OpenLayers los procese.

---

[↑ Volver arriba](#top)

## 8. Sistema de mapas

Los mapas usan **OpenLayers 9.2.4** (CDN: `cdn.jsdelivr.net/npm/ol@9.2.4/dist/ol.js`).

### Tipos de mapa

**Mapa de localizaciones** — puntos de interés numerados con popup de información. Útil para mostrar los lugares visitados en una jornada o un viaje.

**Mapa de ruta** — traza un archivo GPX sobre el mapa. Muestra el perfil de elevación debajo. Soporta dos GPX simultáneos (ruta planificada + ruta real) con colores independientes y leyenda interactiva.

**Mapa de ruta animado** — igual que el mapa de ruta, pero al mover el ratón sobre el perfil de elevación aparece un marcador en la posición correspondiente del mapa. Al salir del gráfico, el marcador desaparece.

**Ruta planificada vs realizada** — superpone dos trazados GPX en el mismo mapa: GPX1 (azul, planificada) y GPX2 (rojo, realizada). Las altitudes de GPX1 se ignoran siempre. El perfil de elevación y la sincronización posición ↔ mapa (igual que el mapa de ruta animado) se generan exclusivamente de GPX2. Incluye leyenda automática con los colores y etiquetas de cada trazado.

**Mapa de rutas por localización** — como el mapa de localizaciones, pero cada marcador es un punto de **descubrimiento**: en vez de un popup informativo con enlace a una entrada suelta, guarda un **filtro compuesto** (categorías OR + etiquetas AND, AND entre grupos) y su popup enlaza **«→ Entradas relacionadas»** a una página que compone el grid de todas las entradas que casan ese filtro (viaje/etapa/jornada). En el editor las localizaciones se gestionan en un `Modal` (mapa OpenLayers cargado bajo demanda al abrir el Modal, buscador Nominatim y lista propia buscable/paginada); **no** usa DataViews/DataForm porque no están disponibles como globales en este WordPress (ver §13.12). El destino es una página con plantilla propia que agrupa las entradas en **un carrusel de `.post-card` por categoría** del marcador, con «← Volver al mapa» (`rbl_src`) y navegación prev/next propia (`from_loc`); ver §13.13 (#18, v2.9.0).

### Parser GPX propio

El tema incluye un parser GPX escrito en JavaScript que procesa:
- `<trkseg>` → polilíneas separadas (permite rutas con interrupciones)
- `<rtept>` y `<wpt>` → marcadores en forma de diamante con nombre y descripción
- `<ele>` → datos de elevación para el gráfico

### Popup del mapa — enlace clicable (#15, v2.7.3)

El popup de un marcador (usado por `location-map`) se pinta en un contenedor `.ent-ol-popup` (un `ol.Overlay` creado en `assets/js/map-frontend.js`) que lleva `pointer-events: none` en su estilo inline, de modo que los clics **atraviesan** el popup hacia el mapa (click-through: poder seleccionar otro marcador o cerrar el popup pulsando el fondo). Cuando un marcador tiene `postUrl`, el popup incluye el enlace «→ Leer la entrada» (`.ent-map-popup__link`). Como `pointer-events` se hereda, ese enlace heredaba el `none` del contenedor y **no era objetivo de clic** (el ancla se pintaba pero no navegaba, y su `:hover` tampoco reaccionaba).

La solución reactiva el puntero **solo en el enlace**: `.ent-map-popup__link` recibe `pointer-events: auto` en `assets/css/maps.css`, mientras el contenedor `.ent-ol-popup` conserva su `pointer-events: none`. Un descendiente con `auto` es objetivo de eventos aunque su ancestro esté en `none` (comportamiento estándar de la propiedad), así que el enlace vuelve a navegar y el resto del popup mantiene el click-through al mapa. Es un arreglo de la capa de presentación (CSS), sin tocar el JS ni la capa de interacción de OpenLayers. En el mismo lote se corrigió el comentario de cabecera de `blocks/location-map/render.php`, que aún nombraba «Leaflet» como motor cuando el real es OpenLayers 9.2.4.

### Numeración de pines — leyenda y pin (#20, v2.9.1)

El interruptor **«Mostrar numeración»** (`showNumbers`, por defecto activado) de los dos mapas de localización —`location-map` y `routes-by-location`— gobierna la leyenda **y el pin**. Antes de #20 el flag solo llegaba a la leyenda: el pin iba siempre numerado porque `showNumbers` no se emitía al frontend y `olPinStyle()` se llamaba siempre con número. El fix lo emite como `data-show-numbers` en `.ent-map` (ambos `render.php`); cada init (`initLocationMap` / `initRoutesByLocationMap`) lo lee (`container.dataset.showNumbers !== '0'`, seguro ante ausencia = numerado) y pasa `olPinStyle(showNumbers ? i+1 : null)`. Con el toggle **activado** el pin muestra el número; **desactivado**, un **punto centrado** (`<circle>` blanco en el centro del pin). Cambio aditivo: geometría, colores y leyenda intactos. (El Desarrollador fusionó `map-frontend.js` completo en el primer commit, con el visto bueno de Juanjo, en lugar de trocearlo entre los dos commits; comportamiento idéntico al especificado y cada commit validado por separado.)

---

[↑ Volver arriba](#top)

## 9. Portada

### Secciones configurables

La portada tiene hasta 6 secciones configurables desde **Apariencia → Personalizar → 🏍 Configuración de la portada → Sección 1-6**.

Cada sección tiene:
- **Tipo de agrupación:** Desactivada / Categoría / Etiqueta / Hijos de categoría (auto)
- **Nombre o slug** del término (con autocompletado AJAX)
- **Título personalizado** de la sección (vacío = usa el nombre del término)
- **Eyebrow** (texto pequeño sobre el título)
- **Máximo de entradas** (1-8)

El tipo **"Hijos de categoría (auto)"** genera automáticamente una subsección por cada categoría hija que tenga entradas publicadas — útil para `tipo-de-salida`.

Con el tipo **Etiqueta**, si se especifican varias etiquetas separadas por coma, se genera una sección independiente por cada etiqueta.

### "Última ruta publicada"

El hero de la portada muestra la última ruta publicada. Se puede configurar qué categorías se usan para esta selección en **Apariencia → Personalizar → Hero — Estadísticas → Categorías para "Última ruta publicada"** (slugs separados por coma, por defecto: `etapa`).

El rótulo dorado **«Última ruta»** sobre la imagen del hero (`hero-photo-tag`) es un **enlace ancla real** a la sección destacada «Última ruta publicada» situada justo debajo (`<section class="featured-section" id="ultima-ruta">`): un `<a href="#ultima-ruta">` que aprovecha el `scroll-behavior: smooth` global del tema, **sin JS**. El destino lleva `scroll-margin-top: calc(var(--nav-h) + 24px)` para que su arranque no quede oculto bajo el header fijo (mismo idiom que los elementos `sticky` del tema, que ya se separan de la barra con `calc(var(--nav-h) + …)`). El rótulo conserva su aspecto de pastilla dorada; la interactividad la señalan el cursor y el enlace real, no un reestilado. Tanto el rótulo como la sección destino se renderizan bajo la misma guarda `if ( $latest_post )`, de modo que el enlace nunca apunta a un ancla inexistente. (#14, v2.7.2)

### Estadística "Días de ruta publicados"

El contador del hero suma los `count` de WordPress de las categorías indicadas en **Apariencia → Personalizar → Hero — Estadísticas → Categorías para «Días de ruta publicados»**.

- El campo acepta slugs de categoría separados por coma. Ej: `cuaderno-etapa, etapa, jornada`
- WordPress mantiene automáticamente el `count` de cada categoría con el número de entradas publicadas asignadas a ella
- Si se indican varias categorías, los conteos se **suman** (puede haber duplicados si un post está en varias categorías de la lista — tenerlo en cuenta al configurar)
- Por defecto: `etapa`

### Contexto de navegación de la portada (#13, v2.7.1)

Las tarjetas de las secciones de **categoría** y de **hijos de categoría** estampan `from_cat` con el **slug real del término** de la sección (no reconstruido desde el título/nombre mostrado), de modo que el «Volver» y el prev/next de la entrada respeten el contrato §6. Las secciones de **etiqueta** no estampan contexto (una etiqueta no es categoría; el modelo no tiene `from_tag`). La tarjeta destacada **«Última ruta publicada»** y la sección **«Mientras tanto»** **no** estampan contexto **a propósito**: son ítems sin un listado ni una categoría única detrás, y estampar uno fabricaría una secuencia inexistente (ver §6 «Puntos de estampado» y la decisión §13.11).

---

[↑ Volver arriba](#top)

## 10. Página fuera de ruta

Se muestra en `/cuaderno-de-bitacora/` cuando no hay ningún cuaderno activo.

### Secciones

1. **Hero** — "La moto espera". Imagen del garaje + texto configurables en el Personalizador.
2. **Próxima expedición** — título, descripción, fecha, países, días estimados, km estimados, imagen de la ruta, cuenta atrás en tiempo real.
3. **Mientras tanto** — posts filtrados por categorías o etiqueta configurables en el Personalizador. Usa la misma presentación de cards que la portada.
4. **Cuadernos anteriores** — páginas hijas del portal con `_exp_estado = finalizado`.

### Configuración en el Personalizador

**Apariencia → Personalizar → 🏍 Configuración de la portada → Fuera de ruta — Hero**
- Foto del garaje
- Texto del hero

**Apariencia → Personalizar → 🏍 Configuración de la portada → Fuera de ruta — Próxima expedición**
- Título, subtítulo, descripción
- Fecha de salida (formato AAAA-MM-DD)
- Países, días, km estimados
- Imagen de la ruta
- Categorías "Mientras tanto" (nombres separados por coma)
- Etiqueta "Mientras tanto" (slug, fallback si no hay categorías)

---

[↑ Volver arriba](#top)

## 11. Referencia de campos personalizados

### Posts — campos tipados (`_post_*`)

| Campo | Tipos | Descripción |
|---|---|---|
| `_post_tipo` | todos | `etapa` \| `viaje` \| `jornada` \| `generica` |
| `_post_tramo` | B/C | Origen → Destino |
| `_post_km` | B/C | Kilómetros de la etapa |
| `_post_horas_moto` | B/C | Horas de conducción |
| `_post_horas_ferry` | B/C | Horas en ferry/barco |
| `_post_duracion` | B/C | Duración total del día |
| `_post_custom_label` | A/B/C/D | Dato extra — etiqueta |
| `_post_custom_value` | A/B/C/D | Dato extra — valor |
| `_post_fecha_inicio` | D | Fecha de inicio del viaje (AAAA-MM-DD) |
| `_post_fecha_fin` | D | Fecha de fin del viaje (AAAA-MM-DD) |
| `_post_paises` | D | Países recorridos |
| `_post_viaje_categoria` | D | Categoría de las etapas (filtro igual que los bloques) |
| `_post_viaje_etiquetas` | D | Etiquetas adicionales (filtro igual que los bloques) |
| `_post_km_calculado` | D | Km totales calculados automáticamente al guardar |
| `_post_km_incompleto` | D | `1` si alguna etapa no tiene km |
| `_post_ferry_count` | D | Nº de etapas con ferry |
| `_post_etapas_count` | D | Nº total de etapas encontradas |
| `_post_ticker_name` | A/B/C/D | Nombre corto de la entrada en el ticker de una «Colección de viajes». Texto libre, sin unidad ni transformación; oculto en el tipo E. Si está vacío, el ticker usa el título. Alta en #5 (v2.5.0). |

### Posts — campos legacy (`_route_*`, backward compatible)

Los campos `_route_*` son los originales del tema y siguen siendo leídos. Al guardar una entrada con el nuevo metabox tipado, los valores se sincronizan automáticamente en los campos `_route_*` para que las entradas antiguas y los templates existentes sigan funcionando.

| Campo legacy | Equivalente nuevo |
|---|---|
| `_route_km` | `_post_km` (etapa) o `_post_km_calculado` (viaje) |
| `_route_etapa` | `_post_tramo` |
| `_route_paises` | `_post_paises` |
| `_route_dias` | calculado de fechas |
| `_route_ferrys` | `_post_ferry_count` |
| `_route_custom1_label` | `_post_custom_label` |
| `_route_custom1_value` | `_post_custom_value` |

### Páginas de cuaderno (`_exp_*`)

Ver sección 4 — Metadatos de un cuaderno.

### Páginas de colección (`_col_*`)

Metadatos **de página** de la plantilla «Colección de viajes». No los edita nadie a mano: se **calculan y cachean al guardar** (§13.7), nunca en caliente. Alta en #5 (v2.5.0).

| Campo | Descripción |
|---|---|
| `_col_stats` | Array serializado con las cifras del hero, computadas sobre el conjunto único deduplicado de entradas de la página. Claves: `viajes` (int), `km` (int), `km_incompleto` (bool), `etapas` (int), `paises` (int — conteo de la unión de países), `ferrys` (int). |
| `_col_stats_updated` | Texto de fecha ya formateado del último recálculo (alimenta la línea «actualizadas …» del hero). |

El contrato de `_col_stats` está sembrado también en la cabecera de `page-templates/template-trip-coleccion.php`.

---

[↑ Volver arriba](#top)

## 12. Referencia de categorías

### Categorías de tipo de entrada (posts)

| Categoría | Slug | Uso |
|---|---|---|
| Tipo de salida | `tipo-de-salida` | Categoría padre (no se usa directamente) |
| → Vacaciones | `vacaciones` | Viajes de varios días a posteriori |
| → Fin de semana motero | `fin-de-semana-motero` | Salidas de fin de semana |
| → Puente motero | `puente-motero` | Salidas de puente |
| → Desayuno motero | `desayuno-motero` | Salidas de un día matutinas |
| Etapa | `etapa` | Día de ruta en moto (a posteriori) |
| Jornada | `jornada` | Día sin moto (a posteriori) |

### Categorías del cuaderno de bitácora

| Categoría | Slug | Uso |
|---|---|---|
| Cuaderno de bitácora | `cuaderno-de-bitacora` | Identifica posts del cuaderno |
| Cuaderno-etapa | `cuaderno-etapa` | Día de ruta escrito en directo |
| Cuaderno-jornada | `cuaderno-jornada` | Día sin moto escrito en directo |

Las categorías `cuaderno-etapa` y `cuaderno-jornada` se crean automáticamente al activar el tema si no existen.

### Regla de exclusión mutua

```
Un post tiene cuaderno-de-bitacora
  → usa cuaderno-etapa o cuaderno-jornada
  → NUNCA tiene tipo-de-salida/*

Un post tiene tipo-de-salida/*
  → usa etapa o jornada
  → NUNCA tiene cuaderno-de-bitacora
```

Los descriptores `etapa` y `jornada` son compartidos pero no colisionan porque identifican el tipo de día, no la pertenencia a un sistema u otro. El contador de portada solo suma `etapa`, nunca `cuaderno-etapa`.

---

[↑ Volver arriba](#top)

## 13. Decisiones de arquitectura

Registro de decisiones de arquitectura del tema. Cada entrada es **autocontenida** (contexto, decisión, consecuencias); el **mecanismo concreto** vive en la sección de referencia que se cita, y no se duplica aquí. Este registro recoge el *porqué* de una decisión, para que sea extrapolable y no se reabra sin motivo.

### 13.1 Contrato de navegación entre entradas

**Contexto.** Una misma entrada se alcanza desde listados distintos (la página de un cuaderno, un post de viaje tipo D, el archivo de una categoría), y cada listado tiene su propio criterio de orden. Se produjeron dos errores que motivaron fijar el contrato: (1) describir la navegación en términos cronológicos («anterior = más antiguo») en lugar de por índice de secuencia, y (2) un bug en `single.php` donde `order=ASC` estaba fijado a fuego, que invertía anterior/siguiente cuando el bloque «Etapas de ruta» usaba orden descendente.

**Decisión.** «Anterior» = índice−1 y «siguiente» = índice+1 dentro de la secuencia del listado de origen. El criterio de orden lo fija **la misma fuente que genera el listado visible** (los metadatos `_filt_*` de la página del cuaderno; los atributos del bloque «Etapas de ruta» para el viaje tipo D, leídos con `parse_blocks()`; el orden del propio archivo para una categoría), nunca un orden fijado a fuego. La definición rigurosa y la tabla por contexto están en §6 «Contrato de navegación entre entradas».

**Consecuencias.** «Anterior/siguiente» **no** equivalen a «más antiguo/más reciente». Cualquier listado nuevo que enlace a entradas con navegación anterior/siguiente debe (a) propagar su parámetro de contexto (`from_*`) en los enlaces y (b) reconstruir la secuencia leyendo la misma fuente que produce el listado, de modo que navegación y presentación coincidan siempre.

### 13.2 Contención de floats del contenido Gutenberg del cuaderno

**Contexto.** El timeline/carrusel del cuaderno se colapsaba dentro de la columna lateral. El primer diagnóstico —HTML mal formado— era erróneo: el HTML renderizado estaba bien formado. La causa real era un `float` de WordPress (un bloque de la introducción editorial alineado con `alignleft`/`alignright`) que su contenedor no contenía, y que se desbordaba e invadía la rejilla `.exp-layout`, comprimiéndola.

**Decisión.** Contener el float **en origen** con `.exp-gutenberg-content { display: flow-root }`, y proteger la rejilla con `.exp-layout { clear: both }` como refuerzo defensivo. Se evaluó y descartó `force_balance_tags()` por inaplicable (no había HTML mal formado que reparar). El detalle está en §6 «Robustez del contenido Gutenberg».

**Consecuencias.** La alineación de bloques (citas o imágenes flotadas) sigue funcionando dentro del propio contenido, pero ya no puede afectar al timeline. Principio de método que deja esta decisión: diagnosticar sobre el HTML renderizado real y por comparación, distinguiendo «HTML mal formado» de «float sin contener», en lugar de teorizar la causa.

### 13.3 Estructura de permalinks («Nombre de la entrada»)

**Contexto.** Con los enlaces permanentes en «Simple» (`?p=123`), la app móvil (Jetpack / app de WordPress) no lograba conectar: la REST API solo quedaba expuesta en su forma de consulta (`?rest_route=/wp/v2/...`) y no en la forma de ruta (`/wp-json/wp/v2/...`) que la app espera.

**Decisión.** Fijar los enlaces permanentes como **«Nombre de la entrada»** (`%postname%`) en *Ajustes → Enlaces permanentes*; no usar «Simple». El detalle está en §6 «Estructura de permalinks».

**Consecuencias.** La conectividad de la app móvil depende de mantener este ajuste. Todo cambio en *Ajustes → Enlaces permanentes* debe ir seguido de volver a guardar la configuración para regenerar las reglas de reescritura (`flush_rewrite_rules`). Los slugs de los cuadernos finalizados son URLs permanentes: cambiarlos rompe sus enlaces entrantes. El sitio sirve desde `bitacoraenterprise.com`, con el dominio antiguo `jjramosgo.blog` redirigiendo con 301.

### 13.4 Formato de presentación de km centralizado

**Contexto.** El valor `_route_km` de una entrada puede venir **sin unidad** (en un post tipo D «viaje», es un número formateado por `enterprise_calculate_viaje_stats()`, p. ej. `1.448`) o **ya con ella** (en una etapa, `_post_km` admite «280 km»). Se pintaba en crudo en varios sitios, de modo que unas veces salía «1.448» y otras «280 km». El primer arreglo (bloque «Etapas de ruta») añadía la unidad de forma inline, duplicando la regla.

**Decisión.** La regla de formato de km vive en un **único** helper de presentación, `enterprise_km_display( $km )` (`functions.php`), que añade « km» de forma **defensiva** (no duplica si ya termina en «km») y no toca datos. Lo usan los cuatro puntos donde se pinta el km de una entrada (las dos vistas de «Etapas de ruta» y las dos del cuaderno). Se retiró el añadido inline anterior.

**Consecuencias.** «Cómo se muestra un km» se cambia en un solo lugar. Es formato de presentación: nunca se persiste la unidad ni se normaliza `_route_km`.

### 13.5 Estadísticas del cuaderno en caliente y fuente única

**Contexto.** Las cifras de un cuaderno (km, etapas, duración, progreso) se resolvían de forma dispersa e incoherente: la barra lateral las calculaba en el template, mientras que la tarjeta del grid leía `_exp_km` en crudo (sin fallback → «—» si estaba vacío) y contaba etapas por el campo **deprecado** `_exp_categoria` (→ «0» o un conteo que no coincidía con el listado real). Además la barra de progreso era un campo manual (`_exp_progreso`) disparado por el legacy `_exp_en_ruta`.

**Decisión.** Cálculo **en caliente** (no cacheado al guardar, a diferencia del post tipo D, cuyo dominio asume finalización; un cuaderno `activo` publica etapas sin re-guardarse), centralizado en una **fuente única** `enterprise_cuaderno_stats()` que usan todos los consumidores. El conteo de etapas sale de los filtros `_filt_*` (la misma fuente que genera el listado), retirando `_exp_categoria` de esa ruta. El progreso y la duración se derivan de `_exp_estado` + fechas (tabla en §4). Se retira `_exp_en_ruta` **como criterio de lógica** en todo el template (badge, eyebrow, punto de estado y barra pasan a `_exp_estado`); `_exp_en_ruta` se sigue **escribiendo** al guardar (backward compat) y solo se lee como respaldo del estado cuando `_exp_estado` está vacío. El mecanismo y el contrato de retorno están en §4 «Estadísticas en caliente del cuaderno».

**Consecuencias.** Todos los consumidores muestran las mismas cifras y siempre al día. Un `_exp_km` curado sigue ganando como override. Se descartó explícitamente cachear al guardar (imitar al viaje tipo D), porque introduciría obsolescencia en cuadernos activos. Coste en caliente asumible por el cebado de meta en bloque y el volumen real (pocos cuadernos).

### 13.6 Metabox del cuaderno consciente de la plantilla

**Contexto.** Las plantillas «Cuaderno de bitácora» y «Bitácora con bloques» compartían el mismo metabox y guardado. Al calcularse duración y progreso en caliente (§13.5), sus campos manuales (`_exp_duracion`, `_exp_progreso`) sobraban en el cuaderno, pero **no** podían eliminarse en bloque: «Bitácora con bloques» los lee como datos estáticos y no tiene filtros `_filt_*` de los que derivarlos.

**Decisión.** El conjunto de campos del metabox y su guardado son **conscientes de la plantilla**. En «Cuaderno de bitácora» se retiran `_exp_duracion` y `_exp_progreso` (se calculan) y `_exp_fecha_inicio`/`_exp_fecha_fin` quedan **opcionales**, sin la semántica «vacío = en curso». En «Bitácora con bloques» el conjunto de campos queda **congelado** tal como estaba, hasta que esa plantilla reciba su propio propósito (ver `TODO.md` #5). El dato antiguo de los campos retirados no se borra de la base de datos: solo deja de editarse y leerse en el cuaderno.

**[Actualización v2.5.0 — #5]** Esa espera terminó: la plantilla se renombró a «Colección de viajes» (`page-templates/template-trip-coleccion.php`) con un propósito distinto del que se anticipó aquí (colecciones de **viajes** cerrados, no «etapas no ligadas a un viaje»), y en su desacople se retiró el conjunto de campos `_exp_*` congelado — la colección **no** usa el metabox de expedición. Ver §13.7.

**Consecuencias.** Cada plantilla evoluciona con su propio conjunto de campos; el rediseño del metabox del cuaderno no rompe «Bitácora con bloques». Invariante para cambios futuros del metabox: no eliminar campos de forma global.

### 13.7 Cifras de la colección de viajes cacheadas al guardar

**Contexto.** La plantilla «Colección de viajes» (§6, #5) muestra en su hero cifras agregadas (viajes, km, etapas, países, ferrys) de un conjunto de entradas curado con **varios bloques de filtrado** por página (`enterprise/post-stages` y/o `enterprise/trip-collection`, con la query compartida de §7). El dominio es de **viajes ya cerrados**: a diferencia de un cuaderno `activo`, una colección no gana entradas sin que se re-guarde algo. Calcular en caliente (como §13.5) sería trabajo repetido en cada visita sin ganancia de frescura.

**Decisión.** Cifras **cacheadas al guardar, nunca en caliente** — la política **opuesta** a §13.5, y correcta aquí por el dominio cerrado. La **fuente** de las cifras es la **unión deduplicada** de las entradas de **todos** los bloques de filtrado de la página (`enterprise_collection_post_ids()`): se construye el conjunto de IDs únicos y **todas** las cifras se derivan de ese mismo conjunto (un post presente en dos bloques cuenta una sola vez en todas ellas). Por entrada se leen las cachés ya frescas del tipo D (`_post_km_calculado` / `_post_etapas_count` / `_post_ferry_count`) o, para una salida de un día, `_post_km` y 1 etapa; los países por unión de `_post_paises`. El resultado se persiste en `_col_stats` (+ `_col_stats_updated`, §11). El recálculo se dispara en la **escritura del post**: al guardar la página de esta plantilla, y al guardar cualquier entrada relevante, sendos hooks `save_post` recomputan las páginas afectadas (sigue siendo cacheado, no en render). El mecanismo (`enterprise_compute_collection_stats()`) vive en `functions.php`.

**Consecuencias.** El hero no queda obsoleto pese a no calcularse en render: publicar o editar una entrada que casa un bloque refresca las páginas de colección sin re-guardarlas. La deduplicación es **única y global** a todas las cifras. Edge conocido: enviar una entrada a la papelera no dispara `save_post`, así que la cifra se corrige al re-guardar la página (volumen bajo, coste trivial). **Presentación del hero** (decisiones de Juanjo, validadas al implementar y registradas aquí): (a) los km del hero se pintan **sin unidad** —número, con prefijo `≈` si hay km incompletos— porque la etiqueta ya dice «Kilómetros»; las **tarjetas** de viaje sí usan `enterprise_km_display()` (§13.4); (b) una cifra cuyo valor sea **0 no se pinta** (p. ej. «Ferrys» si no hay ninguno), y la fila de cifras es `flex` para repartirse según cuántas queden visibles. Principio general: cada dominio elige su política de frescura según si su fuente cambia sin re-guardado (cuaderno activo → en caliente; colección cerrada → cacheo al guardar).

**«Sin límite» (`showAll`, #11).** El bloque `trip-collection` ofrece un toggle que, al activarse, fuerza `postsPerPage = -1` (todas) **a nivel de bloque**, antes de la query compartida —que ya mapea `-1` nativamente—, sin tocar `enterprise_stage_query()`. El mismo ajuste se aplica en los **dos puntos de resolución** del bloque: su render y `enterprise_collection_post_ids()`, de modo que las cifras cacheadas del hero (y el ticker) cuentan **todas** las entradas cuando el toggle está activo — coherente con «la fuente es lo que resuelven los bloques», no un efecto colateral. La guarda actúa solo con `showAll` presente, atributo que únicamente emite `trip-collection`; los `post-stages` que pasan por esa función quedan intactos.

### 13.8 Anchura del slide del carrusel en móvil (especificidad de media queries solapadas)

**Contexto.** El scaffolding compartido `carousel.css` (usado por `enterprise/post-stages` y `enterprise/trip-collection`, §7) fija la anchura del slide del carrusel por breakpoints. A ≤640px se cumplen **a la vez** las media queries `max-width: 900px` y `max-width: 640px`. Para el caso no-`large` competían la regla @900 (`.ent-stages--carousel:not(.ent-stages--large) .ent-stages__slide`, 50%, especificidad (0,3,0)) y la @640 (`.ent-stages--carousel .ent-stages__slide`, 85%, especificidad (0,2,0)). Las media queries **no aportan especificidad**, así que ganaba la @900 (50%) pese a ir antes en el fichero: en el móvil se veían **2 tarjetas estrechas** en vez de ~1 con asomo. Afectaba a **ambos** bloques por ser fichero compartido. Diagnosticado sobre captura real de móvil (iPhone, viewport CSS 402px) y confirmado por comparación en código (v2.6.1).

**Decisión.** Igualar la especificidad de la regla @640 no-`large` a la de la @900 (`.ent-stages--carousel:not(.ent-stages--large) .ent-stages__slide`, (0,3,0)); al ir el bloque @640 **después** en el fichero, gana por orden de fuente y se aplica el 85%. Cambio de **una sola línea** en `carousel.css`, sin tocar `carousel.js` ni la tarjeta. El caso `large` ya funcionaba (su regla @640 era (0,3,0)).

**Consecuencias.** En ≤640px (móvil) el carrusel muestra ~1 tarjeta con asomo en `post-stages` y `trip-collection`; la franja 641–900px (tablet) se mantiene en 2 tarjetas, no afectada. Principio transferible: en scaffolding responsive con **breakpoints solapados** (varias media queries que se cumplen a la vez), decide la **especificidad**, no el orden de fuente; la regla del breakpoint más estrecho debe **igualar o superar** la especificidad de la del más ancho. La visibilidad de este arreglo en producción dependía además del cache-busting (§13.9).

### 13.9 Cache-busting de assets de bloque por `filemtime`

**Contexto.** El arreglo de §13.8 era correcto en el CSS pero **no se veía** en el móvil tras el push. Causa raíz verificada: `enterprise_carousel_assets()` (`functions.php`) encolaba `carousel.css` y `carousel.js` con `ENTERPRISE_VERSION` como parámetro de versión (`?ver=`), mientras que el resto de assets de bloque del tema (`coleccion.css`, `blocks-media.css`, `tip-box.css`…) usan `filemtime()`. Con versión fija y sin bump, la URL seguía en `?ver=2.6.0` y el navegador/caché servía el fichero antiguo; el fix solo se habría hecho visible al subir la versión. La inconsistencia era **previa** a este lote (no la introdujo §13.8).

**Decisión.** Alinear el encolado de `carousel.css`/`carousel.js` al patrón ya presente en el tema: `file_exists( $path ) ? filemtime( $path ) : ENTERPRISE_VERSION`. Solo se cambió el **encolado** en `functions.php`; los ficheros CSS/JS no se tocaron. **Contrato:** todos los assets de bloque (CSS/JS servidos condicionalmente según `has_block()`) se encolan con `filemtime()`, no con `ENTERPRISE_VERSION`, de modo que cualquier cambio del fichero invalida la caché sin depender de un bump de versión.

**Consecuencias.** Un cambio de CSS/JS de bloque es visible tras el push **sin** necesidad de subir la versión — decisivo porque esos cambios se **validan** en el WordPress real y una caché obsoleta enmascararía el resultado (como ocurrió con §13.8). El bump de versión queda para lo que es —marcar el cierre de un lote— desacoplado de la invalidación de caché. Invariante para bloques nuevos: encolar sus assets con `filemtime()`, coherente con §7 («CSS cargado solo si el bloque está presente en la página»).

### 13.10 Contexto de navegación «colección» (`from_col`) con desambiguación por bloque

**Contexto.** Una página «Colección de viajes» (§6, §7) lista viajes (tipo D) con uno o **varios** bloques `enterprise/trip-collection`. Sus tarjetas eran enlaces planos: al entrar en un viaje, `single.php` no sabía que se venía de una colección, así que el «Volver» caía al fallback del referer y la navegación anterior/siguiente caía a la adyacencia por categoría (no al orden ni al conjunto de la colección), incumpliendo el contrato §13.1/§6. El `id` que emite el bloque en el render es aleatorio (`wp_rand`), inservible como identificador estable en un enlace; y como una página puede tener varios bloques de filtrado, hacía falta desambiguar **cuál** generó la tarjeta.

**Decisión.** Añadir el contexto de origen **`from_col`** (id de la página) + **`col_key`** (hash de identidad del bloque), a imagen del `from_post` de las etapas. La identidad del bloque la da un helper único, `enterprise_collection_block_key( $attributes )`, que hashea los **atributos que determinan la secuencia** (categorías, etiquetas, relación, fechas, orden, `postsPerPage`, `showAll` — **no** `layout`); lo usan tanto la tarjeta al estampar el enlace como `single.php` al casar el bloque, de modo que no puedan divergir. La navegación reconstruye la secuencia **del bloque concreto** reutilizando `enterprise_stage_query()` con la misma guarda `showAll` (no se replica la query a mano, a diferencia de `from_post`), garantizando que navegación == listado. El «Volver» regresa a la página de colección y las etiquetas pasan a «Viaje». El detalle del contexto y su tabla están en §6 «Contrato de navegación entre entradas».

**Consecuencias.** Se cumple el contrato §13.1/§6 también para las colecciones, con desambiguación por bloque robusta a reordenar bloques (una colisión de hash implica bloques con filtros idénticos → misma secuencia, inocua). Principio: la **identidad navegable** de un bloque de filtrado es su conjunto de filtros de secuencia, no un id volátil de render. **Límite conocido en #8 (resuelto en #13, v2.7.1 — ver §13.11):** el contexto de origen era de **un solo nivel** —al bajar de un viaje a una etapa (`from_post`) y volver, se perdía el `from_col`— y el estampado era **desigual** entre orígenes (portada, `archive.php`). Ambos excedían #8 y se abordaron en #13.

### 13.11 Persistencia y cobertura uniforme del contexto de navegación (arrastre de ancestro)

**Contexto.** Tras #8 (§13.10) el contexto de origen (`from_*`) era de **un solo nivel** y de cobertura **desigual**. Dos síntomas validados: (1) al bajar de un viaje (tipo D) —alcanzado desde una colección— a una de sus etapas y **volver**, el viaje «olvidaba» la colección: la etapa solo estampaba `from_post` y su «Volver al viaje» era un permalink plano, así que al regresar no había `from_col` → fallback → «Volver» genérico; (2) el estampado era desigual entre orígenes: `archive.php` no estampaba nada y la portada resolvía la categoría de sección **desde la cadena de presentación** (`get_category_by_slug( sanitize_title( $nombre ) )`), que fallaba siempre que `sanitize_title(nombre) ≠ slug` (subcategoría con nombre ≠ slug —p. ej. «De vacaciones con la moto» → slug real `vacaciones`— o sección con título personalizado). La jerarquía de anidamiento real está **acotada** a dos niveles: una etapa es hoja, así que no hay profundidad 3.

**Decisión.** Modelo de **parámetros con nombre + arrastre de ancestro** (no una pila opaca), porque la jerarquía de origen es **semánticamente tipada** (colección ≠ viaje ≠ cuaderno ≠ categoría, cada una con su etiqueta, validación y fuente de secuencia). El origen **inmediato** gobierna «Volver», prev/next y etiqueta; el **ancestro** se arrastra en los enlaces salientes y sobrevive al regreso, pero **no** es fuente de secuencia (invariante del contrato §13.1/§6). Mecánica construida y validada: (a) `single.php` construye `$nav_ancestor` desde los orígenes ya validados **excluyendo** `from_post`; la rama `from_post` del «Volver» lo arrastra (plano si no hay ancestro) y el `$nav_suffix` del prev/next hace `array_merge` con él; cada rama fija un `$active_context` (`post`/`cuaderno`/`col`/`cat`/`none`) que gobierna la **etiqueta** («Viaje…» solo si `active_context === 'col'`), corrigiendo el que la etiqueta de #8 dependiera de la mera presencia de `from_col`. (b) El bloque «Etapas de ruta» (`post-stages/render.php`) propaga hacia las etapas, **solo en un viaje tipo D**, el `from_post` inmediato **más** el ancestro de llegada del propio viaje —tocando únicamente la línea `$nav_suffix`, sin alterar el scaffolding compartido con `trip-collection`—. (c) La cobertura se hizo **uniforme** para los orígenes del modelo: `archive.php` estampa `from_cat` en `is_category()`, y la portada estampa `from_cat` con el **slug real del término** (nuevo parámetro `$section_cat_slug`: `cat_children` → `$hijo->slug`, `cat` → `$term->slug`), desacoplando la **identidad navegable** (slug real) de la **presentación** (nombre/título, que sigue rotulando la tarjeta). Las secciones de **etiqueta** de la portada pasan `''` explícito (una etiqueta no es categoría; no hay `from_tag`).

**No-estampado deliberado (subdecisión).** La tarjeta destacada **«Última ruta publicada»** y la sección **«Mientras tanto»** de la portada son **ítems sin un listado ni una categoría única detrás** (agregan «la más reciente / lo de entre expediciones» de varias categorías). Estampar `from_cat` con la categoría primaria **fabricaría una secuencia que el usuario nunca vio** (violando §6) y desviaría el «Volver» a un archivo de categoría en vez de a la portada. Como el modelo no tiene un contexto «vengo del destacado/portada» (un `from_home` sería un **tipo nuevo**, fuera de alcance), la decisión correcta **dentro del modelo** es **no estampar** y conservar el fallback (referer → portada). Es una ausencia intencionada, fijada con un comentario en `index.php` para que no se «arregle» por error; «Mientras tanto» comparte exactamente esta razón. Un eventual contexto propio para estos slots sería una decisión mayor y su propio TO-DO.

**Consecuencias.** El contexto sobrevive a la navegación anidada viaje→etapa→viaje en los tres orígenes que pueden anidar (`from_col`, `from_cat`, `from_cuaderno`), y el estampado es coherente entre portada, archivo de categoría y bloques. El contrato §13.1/§6 se mantiene en cada nivel porque el ancestro es memoria, no secuencia. Principio transferible: la **identidad navegable** de una sección es su **término real**, no una cadena de presentación re-saneada; y un ítem **sin listado detrás no debe fabricar** un contexto de secuencia. Límite deliberado que permanece: los archivos de **etiqueta/autor/fecha** y las secciones de portada sin categoría siguen sin contexto propio (requerirían tipos `from_*` nuevos); la **semántica interna de `from_cat`** (adyacencia del archivo de categoría; el orden coincide con el de la sección de portada, no su subconjunto acotado) se mantiene tal cual.

### 13.12 «Localización» como filtro guardado (no taxonomía) y descubrimiento por filtro compuesto (bloque `routes-by-location`)

**Contexto.** Se necesitaba, a partir de una localización de un mapa, llegar a las entradas (viaje/etapa/jornada) que la incluyen; una relación que **evoluciona** (una localización gana entradas con el tiempo) y **atraviesa tipos** (una misma localización aparece en una etapa y en un viaje, categorías distintas §12). El contenido ya está clasificado (categorías/etiquetas), así que introducir una taxonomía de «localización» duplicaría esa clasificación y obligaría a re-etiquetar.

**Decisión.** Una localización **no es una taxonomía**: es un **filtro guardado** sobre las taxonomías existentes, con semántica **(cat_1 OR … OR cat_n) AND (tag_1 AND … AND tag_m)** — exactamente el sistema de filtros unificado §7, resuelto con `enterprise_stage_query()`. El bloque nuevo `enterprise/routes-by-location` (deja `location-map` **intacto**) guarda por marcador nombre, coordenadas, descripción opcional y ese filtro (IDs de término, **globales del sitio**); el popup enlaza («→ Entradas relacionadas») a una página que auto-compone el grid con ese filtro. Almacenamiento **por-bloque** (atributos); la reutilización entre entradas es **copiar el bloque** (copy/paste conserva atributos), sin fuente compartida. El editor mueve la gestión a un `wp.components.Modal` con mapa (OpenLayers cargado **bajo demanda** al abrir) + buscador Nominatim + **lista propia buscable/paginada** (10/pág., con edición, borrado y borrado múltiple): se comprobó (check de solo lectura en la consola del editor) que en este WordPress **`wp.dataviews`/`wp.dataform` no existen como globales**, así que la solución **no** usa DataViews/DataForm sino ese fallback propio (los selectores de término leen categorías/etiquetas por REST con `apiFetch`, límite 100 — suficiente hoy; paginarlos si el sitio crece es #19). El destino es una **página provisional** (`page-templates/template-routes-by-location.php` + Página elegida en el Customizer, control `dropdown-pages` `enterprise_rbl_dest_page`; la URL por marcador la deriva `enterprise_rbl_destination_url()` con parámetros `rbl_cat`/`rbl_tag`); su rediseño definitivo —estética, URL/enrutado limpio y contexto de navegación prev/next— es **#18**.

**Consecuencias.** La relación localización↔entradas es **dinámica** (una entrada nueva que casa el filtro aparece sola) y **transversal a los tipos**, sin taxonomía nueva ni re-etiquetado. `map-frontend.js` se amplió de forma **100 % aditiva** (rama `data-map-type="routes-by-location"`; la rama `"location"`, `popupHtml` y la capa de interacción originales quedan intactas), y `location-map` byte-idéntico en ese hito (hasta #20, v2.9.1, que ya modifica su pin; ver §8). Semántica del Modal: «Guardar y cerrar» confirma; la ✕/Esc cancela (cierre al clicar fuera desactivado, para evitar pérdidas). Límite que se resolvió en **#18** (§13.13, v2.9.0): el destino ganó **contexto de navegación prev/next propio** (`from_loc`) y su enrutado nativo quedó **ratificado** (Página con plantilla + Customizer + parámetros); la URL sigue llevando el filtro por **parámetros** a propósito (un filtro compuesto de IDs de término no tiene slug legible), descartándose el rewrite. Invariante para bloques nuevos: encolar assets con `filemtime()` y solo si el bloque está presente (§7/§13.9).

**Corrección de la relación entre etiquetas (fix #22, v2.9.2).** El filtro compuesto de cada marcador es **(categoría_1 OR … OR categoría_n) AND (etiqueta_1 AND … AND etiqueta_m)**: las categorías se combinan con OR, las etiquetas con **AND** (una entrada casa solo si lleva **todas** las etiquetas del marcador). La relación no se guarda por marcador ni viaja en la URL —el marcador solo almacena listas de IDs de término— sino que se fija al resolver, en los dos únicos puntos que resuelven el filtro: `page-templates/template-routes-by-location.php` (el listado mostrado) y `single.php` (la reconstrucción del prev/next del contrato de navegación §6/§13.13). Ambos deben usar la misma relación para que navegación y listado no diverjan. La especificación original de #17/#18 fijó OR entre etiquetas por error; #22 lo corrige a AND.

### 13.13 Destino de «Rutas por localización»: carruseles por categoría, contexto `from_loc` y enrutado nativo

**Contexto.** El destino del bloque `enterprise/routes-by-location` (§7, §8, §13.12) nació **provisional** en #17: un grid plano de la unión del filtro compuesto del marcador, sin cabecera de regreso ni navegación prev/next, pensado solo para validar el bloque. #18 es su rediseño definitivo. Restricción de partida (verificada en el WordPress real por Juanjo): la página-destino es una **Página real** con la plantilla `template-routes-by-location.php` elegida en el Customizer (`enterprise_rbl_dest_page`); `/las-rutas/` es la página que **hospeda el bloque-mapa**, no el destino.

**Decisión.**

- **Layout: un carrusel por categoría.** Las entradas se agrupan en **una sección por cada categoría del marcador** (`rbl_cat`, en su orden; se omite la categoría sin coincidencias). Cada sección resuelve `enterprise_stage_query({ categoryIds:[cat_i], tagIds: rbl_tag, tagRelation:'IN', postsPerPage:-1, orderBy:'date', order:'DESC' })` y la pinta como **carrusel horizontal de `.post-card`** (la tarjeta de portada, reutilizada tal cual). La unión de secciones equivale al filtro compuesto; una entrada en dos categorías del marcador aparece en ambas (intencionado).
- **Mecanismo por reutilización, no a medida.** Se reutiliza la **librería de carrusel ya presente en el tema** —andamiaje `.ent-stages` + `carousel.js`/`carousel.css`— **sin modificarla** (una `.post-card` por `.ent-stages__slide`, a imagen de la `.trip-card` de `trip-collection`), encolándola en esta plantilla al ampliar `enterprise_carousel_assets()` con la condición de `get_page_template_slug()`. Aplica el orden de prioridad del método (nativo → composición → **librería ya presente** → a medida): un carrusel propio se descartó por innecesario, tras analizar el encaje (§1.3): plantilla PHP en vez de bloque, `.post-card` en vez de `.ent-card`/`.trip-card`, y N carruseles por página; ninguna diferencia impedía la reutilización.
- **«← Volver al mapa» (`rbl_src`).** Como el bloque-mapa es reutilizable (puede vivir en varias páginas), el enlace del marcador estampa `rbl_src` = id de la página que lo hospeda (`enterprise_rbl_destination_url()`, tercer parámetro, desde `get_queried_object_id()`); la plantilla pinta «← Volver al mapa» a ese permalink, con *fallback* al referer y, si no, se oculta. **No** se codifica `/las-rutas/` a fuego. **Propagación en el viaje de ida y vuelta (fix #21, v2.9.1).** `rbl_src` viaja también por el camino de vuelta: las tarjetas del destino lo estampan como `loc_src`, `single.php` lo conserva en prev/next y en el ancestro, y al pulsar «← Volver» lo repone como `rbl_src` en la URL del destino; así «← Volver al mapa» sigue apuntando al mapa tras abrir una entrada y navegar por el carrusel, y el referer queda solo como último recurso. **Ampliación (`bc2d00a`):** la spec original solo contemplaba el salto directo destino → etapa (vía `single.php`) y no el viaje de vuelta que atraviesa una **colección** (destino → colección → etapa → vuelta), donde el botón se perdía; se cerró añadiendo `loc_src` a `enterprise_nav_origin_params()` (`functions.php`) —fuente única del arrastre de ancestro, consumida por `post-stages/render.php`—, además de los tres puntos ya previstos en `single.php` y las tarjetas del destino. Antes del fix, volver desde una entrada perdía `rbl_src` y el botón caía al referer (la entrada recién dejada).
- **Contexto de navegación nuevo `from_loc`.** El destino es un **listado de origen**, así que por el contrato §6/§13.1 cada tarjeta propaga `from_loc` (id de la página-destino) + `loc_cat` (la categoría de ESE carrusel) + `loc_tag` (las etiquetas del marcador). `single.php` reconstruye el prev/next re-ejecutando **la misma query del carrusel** (`enterprise_stage_query({categoryIds:[loc_cat], tagIds:loc_tag})`), el «Volver» regresa a la **vista de esa categoría** del destino (`rbl_cat=loc_cat`+`rbl_tag=loc_tag`) y las etiquetas quedan «Ruta anterior/siguiente» (`$active_context='loc'`, nunca «Viaje»). Se eligió `from_loc` —tipado, que lleva categoría **y** etiquetas— y **no** un `from_tag` genérico (que ignoraría la categoría y el AND, y que §13.11 dejó fuera): `from_loc` reconstruye exactamente el carrusel y subsume esa necesidad. `enterprise_nav_origin_params()` lo reconoce, de modo que **participa en el arrastre de ancestro** (viaje→etapa→volver) sin tocar `post-stages` ni el andamiaje compartido.
- **Enrutado nativo ratificado; rewrite descartado.** La URL = permalink de la Página-destino + `?rbl_cat`/`?rbl_tag` (+`rbl_src`). Un filtro compuesto de **IDs de término** no tiene slug legible; un path solo codificaría IDs opacos o exigiría inventar un slug por localización (dato nuevo + trabajo de edición), fuera de alcance. Se mantiene el mecanismo nativo (Página + plantilla + Customizer).

**Consecuencias.** El destino deja de ser provisional: descubre por categoría con la estética del tema, permite volver al mapa y navegar prev/next dentro de cada carrusel respetando el contrato §6/§13.1, y no introduce taxonomía ni assets nuevos (reutiliza `.post-card` y la librería de carrusel; §13.9 sigue rigiendo su encolado). Se cierra el «límite deliberado» de §13.12. Estado «sin parámetros» conservado (aviso «accede desde un marcador»). Retirado «(provisional)» de la cabecera de la plantilla y del control del Customizer. Implementado en 4 commits (`adfa000`, `bf337e2`, `1a50aea`, `270e7cf`), validados por Juanjo en WordPress real. Principio transferible: **el mecanismo de una funcionalidad nueva lo fija el orden de prioridad (reutilizar antes que a medida), tras analizar el encaje (§1.3)**; y un listado de origen nuevo necesita su contexto `from_*` **tipado** que reconstruya la secuencia desde la misma fuente que el listado.

### 13.14 Tematización de los enlaces de columna del footer por contenedor (`.footer-widget-area a`)

**Contexto.** Las columnas de widgets del footer (`footer.php`, áreas `footer-1`/`footer-2` registradas en `functions.php`) se pintan en **tres formas** distintas: el *fallback* hardcodeado (cuando el área de widgets está vacía), el menú nativo de la ubicación `footer` («Secciones», `wp_nav_menu` con `ul.menu`) y un **widget** (p. ej. la columna «Blog» resuelta con un bloque HTML). La regla de enlace `.footer-widget-area .widget ul li a` / `:hover` exigía un elemento con clase `.widget` **anidado dentro** de `.footer-widget-area`. Confirmado al 100% sobre el HTML renderizado: **ninguna** de las tres formas tiene ese `.widget` anidado —el *fallback* cuelga el `<ul>` como hijo directo, el menú «Secciones» pinta `ul.menu` como hijo directo, y el widget de bloque lleva `widget_block footer-widget-area` en el **mismo** wrapper, no en un descendiente—, así que la regla específica no casaba y los enlaces caían a la **global** `a` / `a:hover` (`style.css` l. 63-64: dorado `--gold-dk` → negro `--black`), invisibles sobre el footer oscuro al pasar el ratón. Bug latente **preexistente** que afectaba a **las dos** columnas, aflorado al poblar `footer-2` con un widget.

**Decisión.** Tematizar los enlaces de columna del footer por su **contenedor** —`.footer-widget-area a` / `:hover`, conservando intacta la paleta del spec (reposo `rgba(255,255,255,.5)`, hover `rgba(255,255,255,.85)`)—, no por el marcado interno del widget. La especificidad (0,1,1) gana a la global `a` (0,0,1) y queda **confinada** a las dos columnas del footer. Se mantiene, **deliberadamente**, la divergencia respecto de la barra de copyright `.footer-bottom` (dorado → blanco): se **descartó** unificar ambas en dorado/blanco. No se tocan ni la `a`/`a:hover` global (correcta para el contenido sobre fondo claro) ni `.footer-bottom`. Cambio de una sola línea de selector en `style.css` (l. 761-762); l. 759 (`.widget-title`) y l. 760 (reset de lista) intactas.

**Consecuencias.** Los enlaces de las dos columnas del footer son legibles en reposo y en hover **con independencia de la forma del marcado** (fallback / menú nativo / widget clásico / widget de bloque); `.footer-bottom` no se ve afectada (conserva su propio hover). Principio transferible: las decisiones de **presentación** de una columna del footer viven en el **contenedor estable que el tema controla** (`.footer-widget-area`), no en las formas de marcado que WordPress puede variar (widget clásico vs. de bloque, menú nativo vs. hardcodeado). **Colateral pendiente (limpieza #24):** la regla de reset de lista `.footer-widget-area .widget ul` (l. 760) arrastra el mismo fallo latente de no-match; no se tocó por spec, queda como TO-DO de limpieza. (#23, v2.9.3)

### 13.15 Identidad de marca emitida desde el tema (favicon, app-icons y logo del header)

**Contexto.** El tema no emitía **ningún** favicon propio: el icono del navegador dependía por completo del **Site Icon** nativo de WordPress (Apariencia → Personalizar → Identidad del sitio), y el logo de la cabecera (`header.php`, `.site-logo .logo-icon`) se resolvía con `has_site_icon()` → `get_site_icon_url( 40 )`, cayendo al emoji 🏍️ cuando no había Site Icon configurado. Esa era, verificado por grep, la **única** dependencia del tema respecto del Site Icon. El Site Icon tiene dos límites que chocan con el set de marca producido por Branding: **no acepta SVG** (subida bloqueada por seguridad) y **emite su propio marcado fijo** vía `wp_site_icon()` en `wp_head`, que el tema no controla. Además vive como **opción en la base de datos** —configuración de instancia—, no como activo versionado en el repositorio, que es la única fuente de verdad del proyecto.

**Decisión.** La identidad de marca (favicon, app-icons y logo del header) es un **activo del tema versionado en el repositorio**, no configuración de instancia en la BD. En consecuencia:

- **Fuente única = el tema.** La función `enterprise_emit_brand_icons()` (`functions.php`) engancha en `wp_head` y emite el **conjunto mínimo moderno** de `<link>` leyendo de `enterprise-moto/assets/images/`: `favicon.ico` (`rel="icon" sizes="32x32"`), `favicon.svg` (`rel="icon" type="image/svg+xml"`), `apple-touch-icon.png` (180) y `site.webmanifest` (`rel="manifest"`; estático, con los iconos 192/512 en rutas **relativas** al propio manifest). Para que sea realmente fuente única y no haya doble emisión si algún día se configura un Site Icon, se retira la emisión nativa del `head` del frontend con `remove_action( 'wp_head', 'wp_site_icon', 99 )`. El favicon del **admin/login** (chrome de WordPress) queda **fuera de alcance** y conserva el comportamiento por defecto.
- **Logo del header desacoplado del Site Icon.** `header.php` sirve `assets/images/favicon-monograma.svg` (el monograma «BE» con aguja, más expresivo a 40 px) vía `get_theme_file_path()` / `get_theme_file_uri()`, con el emoji 🏍️ como **fallback defensivo** solo si el fichero faltara. Desaparecen del tema `has_site_icon()` y `get_site_icon_url()`.
- **Dos SVG con papeles distintos** (confirmado por Juanjo): `favicon.svg` (BE simplificado, sin aguja) es el favicon del navegador, legible a 16-32 px; `favicon-monograma.svg` (con aguja) es el logo de la cabecera. Ambos autocontenidos, con las letras trazadas desde Bebas Neue (sin dependencia de fuente).
- **Cache-busting por `filemtime()`** en cada URL emitida (iconos y logo), coherente con §13.9: un cambio de un fichero de icono invalida la caché del navegador sin depender del bump de versión.
- **`enterprise-moto/assets/images/`** se crea como **hogar de las imágenes propias** del tema (© reservado, `COPYRIGHT.md`), donde se colocan los seis activos del set. Se descartaron del cableado los PNG sueltos 16/32/48 (redundan con el `.ico`) y un `favicon-512.png` byte-idéntico al `android-chrome-512x512.png`.

**Consecuencias.** El favicon y el logo son ahora **idénticos en cualquier despliegue** y no dependen de una opción de la BD que se puede perder o divergir entre entornos; el tema sirve el SVG que el Site Icon no admite y controla el marcado exacto. El logo de la cabecera muestra el monograma de marca aunque no haya Site Icon configurado (antes caía al emoji). Principio transferible: **la identidad de marca es código del tema versionado, no configuración de instancia** —el mismo principio por el que el repositorio es la única fuente de verdad—; un activo de marca nuevo se emite desde el tema y se cachea por `filemtime()` (§13.9), no se delega en una opción de WordPress. **Nota operativa (no es TO-DO):** el servidor sirve `.webmanifest` como `application/octet-stream` por falta de mapeo MIME, lo que solo molesta en Safari; es configuración de servidor, no del tema. En la misma tanda se retiró la **carpeta espuria** `assets/{css,js,images}` (nombre literal, resto de un `mkdir` sin expansión de llaves; vacía y no versionada, borrado local sin commit). (#27/#28, v2.10.0)

### 13.16 Marca anclada en el footer (lockup servido como SVG único)

**Contexto.** El footer (`footer.php`, `.footer-grid`) mostraba en su columna izquierda el **nombre del sitio** como título de texto (`.footer-brand-name`, `bloginfo('name')` → «Diario de ruta de la Enterprise») con la descripción del sitio (`bloginfo('description')`) debajo (`.footer-brand-desc`). La marca «Bitácora Enterprise» no tenía presencia en el footer.

**Decisión.** La columna izquierda del footer **ancla la marca** colocando el **lockup horizontal** (insignia BE con marco + wordmark «BITÁCORA ENTERPRISE») como **activo de marca servido: un SVG único autocontenido** que el tema **solo coloca**, nunca recompone. En consecuencia:

- El título de texto `.footer-brand-name` se sustituye por `<img class="footer-brand-lockup" src="assets/images/lockup-footer.svg" alt="Bitácora Enterprise">`, servido con el **mismo idiom que el logo del header** (§13.15): `get_theme_file_path()` (guarda de existencia) + `get_theme_file_uri()` + cache-busting por `filemtime()` (`?ver=`, §13.9) + `esc_url()`. **Sin fallback de emoji**: si el fichero faltara, la guarda `file_exists()` simplemente no emite nada (el lockup no es un icono funcional). CSS `.footer-brand-lockup { display:block; width:300px; max-width:100%; height:auto; margin-bottom:16px; }` (ancho controlado, relación de aspecto preservada, no desborda en móvil).
- El **tagline** va como **texto HTML** (no incrustado en el SVG), reutilizando `.footer-brand-desc`: «Viajar en moto por asfalto, disfrutar del camino y contarlo después.» (con punto; literal de marca confirmado por Juanjo, manual §2.1).
- Se retiran las reglas CSS muertas `.footer-brand-name` y `.footer-brand-name span`. El padding vertical de `.site-footer` se compacta `56/32` → `40/24` (horizontal 40 intacto).
- **Intactos**: las columnas SECCIONES/BLOG, la barra inferior `.footer-bottom` (el copyright conserva el **nombre del sitio** «Diario de ruta de la Enterprise» enlazado a «Licencia de contenido», #26) y el apilado móvil (`@media max-width:900px`).

**Consecuencias.** La marca «Bitácora Enterprise» queda anclada en el footer como activo versionado del tema, coherente con §13.15 (la identidad de marca es código del tema versionado, no configuración de instancia). La marca visible de esa esquina (el lockup, «Bitácora Enterprise») es **deliberadamente distinta** del nombre del sitio que sigue en el copyright («Diario de ruta de la Enterprise»), decisión de Juanjo. Principio transferible: un **lockup de marca** se entrega como **arte final (SVG único)** y el tema **solo lo coloca** —nunca recompone insignia+wordmark por HTML/CSS—; el mockup de referencia (`claude/mockups/footer-rediseno-mockup.html`) fija el **resultado y el layout**, no el método de composición. **Fuera de alcance** (posibles TO-DOs si se piden): marca de agua tenue del monograma en monitores anchos, botón «volver arriba» del mockup, y variantes fondo claro / una tinta del lockup (ligadas al manual, #31). (#30, v2.11.0)

### 13.17 Tarjeta de previsualización al compartir emitida desde el tema (Open Graph / Twitter Card)

**Contexto.** El tema **no emitía ninguna** etiqueta Open Graph (verificado por grep en `functions.php`): al compartir una URL del blog (WhatsApp, redes) la plataforma tomaba una imagen de *fallback* arbitraria y el tema no controlaba la tarjeta de previsualización.

**Decisión.** El tema **emite la tarjeta de previsualización** vía `enterprise_emit_og_tags()` (`functions.php`, hook `wp_head` prioridad 5), espejo de `enterprise_emit_brand_icons()` (§13.15). En consecuencia:

- **`og:image` de marca única para todo el sitio**: `assets/images/og-image.png` (1200×630), URL **absoluta** (`get_theme_file_uri()`) con cache-busting `?ver=filemtime` (§13.9), acompañada de `og:image:type` (`image/png`), `og:image:width`/`height` (1200/630) y `og:image:alt` («Bitácora Enterprise»). Misma imagen en cada URL (decisión de Juanjo: la imagen es para la previsualización al compartir la URL del blog).
- **Título y descripción siguen a la página, la imagen no**: portada/blog → `bloginfo('name')` / `bloginfo('description')`, `og:type=website`; singular → `wp_get_document_title()` / extracto (`get_the_excerpt()`, o recorte del contenido a 40 palabras si no hay), `og:type=article`; resto → título del documento / descripción del sitio. `og:site_name` = `bloginfo('name')`; `og:locale` = `get_locale()`; `og:url` = permalink en singular, `home_url('/')` en el resto.
- **Twitter Card** `summary_large_image` reflejando título/descripción/imagen. Escapado: `esc_url()` para imagen y URL, `esc_attr()` para el resto; `og:*` con `property=`, `twitter:*` con `name=`.
- **Puerta previa sostenida**: se confirmó (comprobación de solo lectura de Juanjo) que **ningún plugin** (Yoast/Rank Math/Jetpack…) emite ya `og:`, de modo que el tema es la fuente y no hay etiquetas duplicadas.

**Consecuencias.** Compartir cualquier URL del blog muestra una tarjeta de marca coherente y controlada por el tema, no una imagen de *fallback* arbitraria; coherente con §13.15 (la marca es código del tema versionado). Principio transferible: la **tarjeta social de marca** es un activo del tema versionado emitido desde `wp_head`, no configuración de instancia ni delegación en un plugin. **Fuera de alcance** (posibles TO-DOs si se piden): `og:image` **por-entrada** (foto de portada de la ruta, con *fallback* a la de marca), `og:url` canónica por-archivo, y schema.org/JSON-LD / etiquetas `article:*`. (#29, v2.11.0)

---

*Documentación generada en Mayo 2026. Para actualizar este documento tras cambios en el tema, revisar especialmente las secciones 3 (tipos de entrada), 4 (cuaderno) y 11 (campos personalizados).*

---

[↑ Volver arriba](#top)

## Elementos pendientes de eliminar

| Elemento | Motivo | Estado |
|---|---|---|
| Patrón "Timeline de etapas de ruta" | El bloque 'Etapas de ruta' ya cubre esta función con modos carrusel y timeline. | ✅ Eliminado en v2.3.1 |

[↑ Volver arriba](#top)

## Notas de uso

| Nota | Detalle |
|---|---|
| Edición rápida de WordPress | Los campos del metabox personalizado (`_post_tipo`, `_post_km`, `_post_tramo`, etc.) **no se guardan** con la edición rápida de WordPress. Siempre abrir el editor completo del post para que los campos del metabox se guarden correctamente. De lo contrario, los posts quedarán sin `_post_tipo` definido y no serán contabilizados en las estadísticas del viaje. |

[↑ Volver arriba](#top)