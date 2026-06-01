# Bitácora Enterprise — Diseño conceptual e implementación

**Blog:** bitacoraenterprise.com  
**Tema WordPress:** Enterprise Moto v2.4.5  
**Última revisión:** Mayo 2026

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

---

## 1. Principios de diseño

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
**Categoría tipo C:** subcategoría de `tipo-de-salida` (desayuno-motero, fin-de-semana-motero, etc.)  
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
| `_exp_fecha_fin` | Fecha de vuelta (AAAA-MM-DD) — vacío si activo |
| `_exp_salida` | Texto de display de la fecha de salida (auto-calculado si hay fechas) |
| `_exp_duracion` | Duración (auto-calculada si hay fechas) |
| `_exp_km` | Kilómetros del viaje |
| `_exp_paises` | Países recorridos |
| `_exp_progreso` | 0-100, para la barra de progreso |
| `_exp_categoria` | Slug de la categoría que agrupa las etapas |
| `_exp_etiquetas` | Etiquetas adicionales para filtrar etapas (nombres, separados por coma) |

### Filtro de etapas en el cuaderno

El cuaderno muestra los posts que cumplen TODOS los criterios:

1. Categoría = `_exp_categoria`
2. Etiquetas = cualquiera de `_exp_etiquetas` (operador AND con la categoría)
3. Fecha publicación entre `_exp_fecha_inicio` y `_exp_fecha_fin` (si ambas están definidas)

Este filtro es idéntico al que usan los bloques Timeline y Carrusel de etapas.

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

### Plantillas de página

| Plantilla | Archivo | Uso |
|---|---|---|
| Cuaderno de bitácora | `page-cuaderno-de-bitacora.php` | Portal Y cuadernos individuales |
| Bitácora con bloques | `page-bitacora-bloques.php` | Cuaderno con bloques Gutenberg |
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

---

## 7. Bloques Gutenberg

Todos los bloques están en la categoría **Enterprise Moto** del insertor de Gutenberg. Para futuros bloques, mantener esta misma estructura: `render.php` en `/blocks/{nombre}/`, JS del editor en `/assets/js/block-{nombre}.js`, CSS en `/assets/css/{nombre}.css` (cargado solo si el bloque está presente en la página).

### Catálogo de bloques

| Bloque | Identificador | Descripción |
|---|---|---|
| Etapas de ruta | `enterprise/post-stages` | Timeline vertical o carrusel horizontal de entradas filtradas. Filtros: categorías (OR), etiquetas (AND/OR), fechas absolutas desde/hasta. Campos visibles, ordenación y cantidad configurables. |
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

*Documentación generada en Mayo 2026. Para actualizar este documento tras cambios en el tema, revisar especialmente las secciones 3 (tipos de entrada), 4 (cuaderno) y 11 (campos personalizados).*

---

## Elementos pendientes de eliminar

| Elemento | Motivo | Estado |
|---|---|---|
| Patrón "Timeline de etapas de ruta" | El bloque 'Etapas de ruta' ya cubre esta función con modos carrusel y timeline. | ✅ Eliminado en v2.3.1 |

## Mejoras pendientes

*(ninguna pendiente actualmente)*




## Notas de uso

| Nota | Detalle |
|---|---|
| Edición rápida de WordPress | Los campos del metabox personalizado (`_post_tipo`, `_post_km`, `_post_tramo`, etc.) **no se guardan** con la edición rápida de WordPress. Siempre abrir el editor completo del post para que los campos del metabox se guarden correctamente. De lo contrario, los posts quedarán sin `_post_tipo` definido y no serán contabilizados en las estadísticas del viaje. |
