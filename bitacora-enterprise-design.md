# Bitácora Enterprise — Diseño conceptual e implementación

**Blog:** bitacoraenterprise.com  
**Tema WordPress:** Enterprise Moto v2.6.1  
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
| Archivo de categoría | `from_cat=slug` | Orden del propio archivo | Al archivo de la categoría |
| Sin contexto | — | Adyacentes dentro de la misma categoría (fallback de WordPress) | Referer / página de entradas |

**Regla para funcionalidades análogas:** cualquier listado nuevo que enlace a entradas con navegación anterior/siguiente debe (a) propagar su parámetro de contexto en los enlaces, y (b) reconstruir la secuencia leyendo **la misma fuente** que genera el listado visible (nunca un orden fijado a fuego), de modo que navegación y presentación coincidan siempre.

### Robustez del contenido Gutenberg

Un bloque del contenido Gutenberg de la página (introducción editorial sobre el timeline) alineado con `alignleft` / `alignright` recibe `float` de WordPress. Si el contenedor del contenido no contiene ese float, se desborda por debajo e invade la rejilla `.exp-layout`, comprimiéndola y mandando el timeline/carrusel a la columna lateral.

Para evitarlo, el contenedor del contenido encierra sus propios floats y la rejilla se protege como refuerzo:

```css
.exp-gutenberg-content { display: flow-root; } /* contiene el float en origen */
.exp-layout            { clear: both; }        /* refuerzo defensivo */
```

Con esto la alineación de bloques (cita o imagen flotada) sigue funcionando dentro del propio contenido, pero no puede afectar al timeline. El fallo no era HTML mal formado, sino un float sin contener.

---

## 7. Bloques Gutenberg

Todos los bloques están en la categoría **Enterprise Moto** del insertor de Gutenberg. Para futuros bloques, mantener esta misma estructura: `render.php` en `/blocks/{nombre}/`, JS del editor en `/assets/js/block-{nombre}.js`, CSS en `/assets/css/{nombre}.css` (cargado solo si el bloque está presente en la página).

### Catálogo de bloques

| Bloque | Identificador | Descripción |
|---|---|---|
| Etapas de ruta | `enterprise/post-stages` | Timeline vertical o carrusel horizontal de entradas filtradas. Filtros: categorías (OR), etiquetas (AND/OR), fechas absolutas desde/hasta. Campos visibles, ordenación y cantidad configurables. |
| Colección de viajes | `enterprise/trip-collection` | **Tarjetas de viaje** (una por entrada) para la plantilla «Colección de viajes», con presentación **configurable como `post-stages`**: carrusel horizontal o timeline vertical (atributo `layout`, def. `carousel`; #11, v2.6.0). Reutiliza el scaffolding `.ent-stages--{layout}` y los assets `carousel.js`/`carousel.css` de «Etapas de ruta» **sin tocarlos**, conservando la `.trip-card` (el contenedor lleva ambas clases: `.ent-stages .ent-trip-collection`). Mismos atributos de filtro y **query compartida** con «Etapas de ruta», más el toggle **«sin límite»** (`showAll`, §13.7). Enlaces planos, sin `from_*` (navegación entre viajes: #8). Alta en #5 (v2.5.0). Retoques de presentación de la tarjeta (v2.6.1): flecha-botón con cambio de color al pasar el ratón por la card (coherente con las cards de listado/portada) y celda de «Ferry» oculta si el viaje no tiene conexiones (misma regla «una cifra 0 no se pinta» del hero, §13.7). |
| Mapa de localizaciones | `enterprise/location-map` | Marcadores numerados en mapa OpenLayers con popup de información. |
| Mapa de ruta | `enterprise/route-map` | Trazado GPX con perfil de elevación. Soporta dos ficheros GPX simultáneos. |
| Mapa de ruta animado | `enterprise/animated-route-map` | Trazado GPX con sincronización animada elevación ↔ marcador. |
| Ruta planificada vs realizada | `enterprise/route-comparison` | Dos trazados GPX superpuestos: GPX1 (azul, planificada) y GPX2 (rojo, realizada). Perfil de altitud y sincronización posición ↔ mapa exclusivamente de GPX2. Las altitudes de GPX1 se ignoran. Leyenda automática. |
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

## 8. Sistema de mapas

Los mapas usan **OpenLayers 9.2.4** (CDN: `cdn.jsdelivr.net/npm/ol@9.2.4/dist/ol.js`).

### Tipos de mapa

**Mapa de localizaciones** — puntos de interés numerados con popup de información. Útil para mostrar los lugares visitados en una jornada o un viaje.

**Mapa de ruta** — traza un archivo GPX sobre el mapa. Muestra el perfil de elevación debajo. Soporta dos GPX simultáneos (ruta planificada + ruta real) con colores independientes y leyenda interactiva.

**Mapa de ruta animado** — igual que el mapa de ruta, pero al mover el ratón sobre el perfil de elevación aparece un marcador en la posición correspondiente del mapa. Al salir del gráfico, el marcador desaparece.

**Ruta planificada vs realizada** — superpone dos trazados GPX en el mismo mapa: GPX1 (azul, planificada) y GPX2 (rojo, realizada). Las altitudes de GPX1 se ignoran siempre. El perfil de elevación y la sincronización posición ↔ mapa (igual que el mapa de ruta animado) se generan exclusivamente de GPX2. Incluye leyenda automática con los colores y etiquetas de cada trazado.

### Parser GPX propio

El tema incluye un parser GPX escrito en JavaScript que procesa:
- `<trkseg>` → polilíneas separadas (permite rutas con interrupciones)
- `<rtept>` y `<wpt>` → marcadores en forma de diamante con nombre y descripción
- `<ele>` → datos de elevación para el gráfico

---

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

### Estadística "Días de ruta publicados"

El contador del hero suma los `count` de WordPress de las categorías indicadas en **Apariencia → Personalizar → Hero — Estadísticas → Categorías para «Días de ruta publicados»**.

- El campo acepta slugs de categoría separados por coma. Ej: `cuaderno-etapa, etapa, jornada`
- WordPress mantiene automáticamente el `count` de cada categoría con el número de entradas publicadas asignadas a ella
- Si se indican varias categorías, los conteos se **suman** (puede haber duplicados si un post está en varias categorías de la lista — tenerlo en cuenta al configurar)
- Por defecto: `etapa`

---

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

---

*Documentación generada en Mayo 2026. Para actualizar este documento tras cambios en el tema, revisar especialmente las secciones 3 (tipos de entrada), 4 (cuaderno) y 11 (campos personalizados).*

---

## Elementos pendientes de eliminar

| Elemento | Motivo | Estado |
|---|---|---|
| Patrón "Timeline de etapas de ruta" | El bloque 'Etapas de ruta' ya cubre esta función con modos carrusel y timeline. | ✅ Eliminado en v2.3.1 |

## Notas de uso

| Nota | Detalle |
|---|---|
| Edición rápida de WordPress | Los campos del metabox personalizado (`_post_tipo`, `_post_km`, `_post_tramo`, etc.) **no se guardan** con la edición rápida de WordPress. Siempre abrir el editor completo del post para que los campos del metabox se guarden correctamente. De lo contrario, los posts quedarán sin `_post_tipo` definido y no serán contabilizados en las estadísticas del viaje. |
