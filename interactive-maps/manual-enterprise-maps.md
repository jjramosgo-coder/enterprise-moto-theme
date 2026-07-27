# Enterprise Maps — asset manual (for AI agents)

This manual documents the **map data assets** of the Enterprise Moto theme. Its reader is
**another AI agent**: read this manual first, then you can locate an asset in the folder
(`claude/res/`) and **update its content** without breaking the agreed conventions.

- One entry per asset. Assets live in `claude/res/`.
- These are **working assets**, not versioned theme code. Like the rest of `claude/`, they are
  not tracked in git. Carrying an asset into the versioned theme (`enterprise-moto/assets/`)
  is a separate step, outside this manual.
- **Golden rule:** the manual is the method; the asset is the data. When you change an asset,
  keep it consistent with the conventions below and with the asset's own `_meta` block.

---

## Table of contents

- [Project-wide conventions (locked)](#project-wide-conventions-locked)
- [Estructura del mapa: zoom tiers](#estructura-del-mapa-zoom-tiers)
- [The map generator (Colab library)](#the-map-generator-colab-library)
- [Asset: `map-levels.json`](#asset-map-levelsjson)
- [Modelo de datos: ficheros de regiones](#modelo-de-datos-ficheros-de-regiones)
- [Asset: `map-style.json`](#asset-map-stylejson)

---

## Project-wide conventions (locked)

These apply to every level-based asset. They exist to kill a real ambiguity that appeared
in the legacy `region-codes.json`, where the word "level" meant two different things.

### Canonical level numbering: `admin`, base 0

The **only** valid level numbering is the canonical GIS one, base 0, as used by
Natural Earth and GADM:

| `admin` | `key`       | What it is                | Example (ES)         |
|--------:|-------------|---------------------------|----------------------|
| `0`     | `country`   | Country (national border) | Spain                |
| `1`     | `region`    | 1st sub-national division | Comunidad autónoma   |
| `2`     | `subregion` | 2nd sub-national division | Provincia            |

- **`admin`** is the machine identity of a level. It is a number, base 0.
- **`key`** is a stable text identifier (`country` / `region` / `subregion`). Prefer it in
  code so logic does not depend on the number.

### Forbidden

- **Never** use `levelN` / `nivelN` as a **data** identifier. That homonymy (legacy
  `level1` = country vs `admin1` = región) is exactly what this model removes.
- Do not introduce a second, competing numbering alongside `admin`.

### UI ordinal (presentation only)

If the interface needs to show "nivel 1 / 2 / 3", that is a **navigation ordinal**, derived
at presentation time as `admin + 1`. It is **never stored** in an asset.

### Supra-national scope

Continent is out of the model for now (decided: not relevant). The hierarchy starts at
`admin: 0` (country). Do not add a continent field to these assets unless a future decision
reopens it.

[↑ Back to top](#table-of-contents)

---

## Estructura del mapa: zoom tiers

Cómo se organiza el mapa interactivo y el vocabulario que lo mantiene claro. Es la referencia que la generación del mapa debe respetar.

### El nivel de un lugar (`admin`) y el paso de zoom (`tier`)

Conviene no confundir dos cosas:

- **`admin`** — *qué es* un lugar administrativamente: país, región o provincia (`0`, `1`, `2`…). Es un hecho fijo: un distrito portugués es admin-1, se mire como se mire.
- **`tier`** — *cómo se muestra* en el mapa: en qué paso de zoom se dibuja (`tier0`, `tier1`, `tier2`).

Suelen coincidir, pero no siempre (ver «profundidad variable»). Por eso las capas del SVG se nombran por su `tier`, mientras que el nivel real de cada lugar es su `admin`.

### La navegación: entrar (drill-in) y volver (back)

El usuario navega **entrando** por niveles (`0→1→2…`) y **volviendo**. Qué contiene qué —y cuál es el ascendiente al que se vuelve— está en el árbol del [modelo de datos de regiones](#modelo-de-datos-ficheros-de-regiones): `children` para entrar, el ascendiente para volver. Cada paso de zoom se corresponde con mostrar el `tier` siguiente. El comportamiento en pantalla (la animación del zoom, el icono de volver…) lo pone el motor del mapa (#51); aquí solo se documenta la estructura sobre la que se apoya.

### Quién se encarga de qué

- La **geometría** vive en el SVG.
- La **jerarquía** (padre/hijo, entrar y volver) vive en el árbol del [modelo de datos de regiones](#modelo-de-datos-ficheros-de-regiones). El SVG no representa el árbol anidando grupos; el árbol es el JSON.
- El **estilo editorial y la interacción** son del tema (CSS/JS), en sus propios TO-DOs. El SVG generado lleva solo un color por defecto y las claves que enlazan cada elemento con el árbol.

### Cómo se organizan las capas del SVG

- Una capa `<g>` por `tier`: `tier0` (países), `tier1`, `tier2` (grupos hermanos, planos — mapshaper no anida).
- Cada `<path>` lleva:
  - `id` = el código ISO 3166-2 sin guion, igual que el `code` del nodo en el modelo de datos. Es la **clave de join** entre geometría y árbol.
  - `data-admin` = el `admin` real del elemento.
  - `data-parent` = el `code` de su padre (para volver sin recorrer el árbol).
- Mostrar «un nivel» es enseñar u ocultar la capa del `tier` correspondiente según el zoom.

### Profundidad variable: cada tier se rellena con el nivel más profundo disponible

No todos los países llegan a admin-2. Una capa de `tier` incluye, **por país, el nivel más profundo que ese país tiene en ese `tier` o por encima**, para que al acercarse no quede un hueco:

- `ES` / `IT` / `FR`: `tier2` = admin-2 (provincias / province / départements).
- `PT` / `AD`: `tier2` = admin-1 (distritos / parroquias), los mismos elementos que su `tier1`.

Detalles que lo mantienen coherente:

- El `data-admin` sigue siendo el nivel real del elemento aunque aparezca en un `tier` superior: un distrito de PT es `data-admin="1"` tanto en `tier1` como en `tier2`. El `tier` es la capa; el `admin` es el hecho.
- Las capas se nombran por `tier`, nunca por `admin`, precisamente porque un `tier2` puede contener elementos admin-1.
- La duplicación sale de una única fuente (la geometría admin-1 del país), incluida en dos capas al exportar; no se crea dos veces.
- Un elemento que es hoja en el árbol (sin `children`) no profundiza más: su aparición en `tier2` es el final del recorrido.

### Cómo se genera el SVG (mapshaper)

La generación usa mapshaper: emite un `<g>` por capa, fija el `id` de cada path desde `id-field`, escribe los `data-*` desde `svg-data`, calcula campos con `-each` y añade atributos externos por clave con `-join`. En resumen: geometría de origen admin-0/1/2 → `-join` de los atributos del modelo de datos (`code`, `admin`, `parent`, `name`) por el código ISO → separar en capas de `tier` → exportar con `id-field=code svg-data=admin,parent,name`.

**Importante para el join:** los códigos de la geometría de origen hay que normalizarlos a nuestro ISO 3166-2 (sin guion) antes del join. Ojo con el `iso_3166_2` con guion de Natural Earth y sus placeholders `-99`.

### Colores por defecto

En crudo, mapshaper saca el SVG **todo negro** (no escribe `fill`, así que se aplica el del navegador). Para darle una paleta por defecto se usa `-style` en la generación (`fill`, `stroke`, `stroke-width`, `opacity`; por `tier` con `where=`). Son solo defaults: al ser atributos de presentación, el estilado del tema los sobreescribe por CSS sin regenerar el mapa. El baseline se alinea con la paleta del proyecto (fondos `#0e0e0e` / `#1a1a1a`, acento dorado `#f2c118`).

[↑ Back to top](#table-of-contents)

---

## The map generator (Colab library)

A **model-driven** Python library, run in Google Colab, that reads the map model assets and generates the SVG defined by [Map structure: zoom tiers](#map-structure-zoom-tiers) using mapshaper. That section is the contract; this library is what produces an SVG that satisfies it. It is a **working generator**, not versioned theme code.

The Gemini prompt that pilots the generation of this library lives in [`svg-generator-library.md`](claude/res/svg-generator-library.md).

### Configuration: model and output paths

The library holds its input and output paths in **module-level global variables**, so a Colab cell can point it at the model assets and the output file without editing the library's body:

- one global for each **data-model file** it reads — `map-regions.json` (the navigation tree: `code`, `name`, `admin`, `parent`) and `map-style.json` (per-tier default colours);
- one global for the **output SVG** file to write.

The library exposes functions to set these globals:

- a function to set the **data-model file paths**;
- a function to set the **output SVG path**;
- `run()`, which executes the whole pipeline using whatever the globals currently hold.

Defaults assume the files sit in the notebook's working directory.

### What `run()` does (the pipeline)

1. **Ensure mapshaper** is available.
2. **Load the model.** Read the regions file and flatten the tree depth-first into records of `code`, `name`, `admin`, `parent` (parent from the ancestor). Read the style file.
3. **Fetch public-domain geometry** (Natural Earth admin-0/1/2).
4. **Normalise the source codes to the join key.** Convert Natural Earth's `iso_3166_2` and resolve its `-99` / missing-code placeholders into our `code`, **before** the join; countries key on ISO 3166-1 alpha-2.
5. **Join our attributes.** Join the flattened tree onto the geometry by `code`, so `name`, `admin` and `parent` come from the model, not from Natural Earth's own fields.
6. **Resolve variable depth.** Where a country's canonical level is coarser than the source geometry, derive that level rather than relabelling finer geometry; `data-admin` always stays the feature's true canonical level.
7. **Split into tier layers** `tier0` / `tier1` / `tier2`, filling each tier per country with the deepest level available at or above it, so zoom-in never leaves a hole.
8. **Apply default style.** For each tier, merge the style file's `defaults` with that tier's overrides and pass the result to mapshaper `-style`; apply `canvas.background` as the backdrop.
9. **Export SVG** with `id-field=code svg-data=admin,parent,name` → one `<g>` per tier; each `<path>` carries `id` (= `code`), `data-admin`, `data-parent`, `data-name`.
10. **Verify and download.** Report the viewBox, the tier layers and their counts, flag any feature with an empty `code`, render inline, and trigger the Colab download of the output file.

### Contract (must hold)

- Layers named by **tier** (`tier0/1/2`), never by admin.
- Each `<path>`: `id` = the tree's `code`; `data-admin` = the feature's canonical level; `data-parent` = parent code.
- Colours are defaults only, overridable later by the theme's CSS without regenerating the map.

[↑ Back to top](#table-of-contents)

---

## Asset: `map-levels.json`

### Purpose

Canonical dictionary of the map's navigation levels: **what each level is**, its stable
identifiers, and **its concrete name per country**. It is the single source of truth other
map assets and the theme's map code refer to when they need to name or order a level.

### Location & format

- File: [`map-levels.json`](claude/res/map-levels.json)
- Format: JSON (loaded with `json_decode` in PHP, `fetch` / `import` in JS — no build step).

### Schema

Top-level keys: `_meta`, `levels`, `names_by_country`.

- **`_meta`** — self-documentation of the asset. Not consumed at runtime.
  - `purpose` — one line, what the asset is for.
  - `glossary` — meaning of each field (`admin`, `key`, `label`, `names_by_country`).
  - `conventions` — the locked rules (`forbidden`, `ui_ordinal`) restated inside the asset.
- **`levels`** — ordered array, one object per level. Each object:
  - `admin` *(number, required)* — canonical level, base 0.
  - `key` *(string, required)* — stable text id: `country` / `region` / `subregion`.
  - `label` *(string, required)* — generic UI name in Spanish (e.g. `"Región"`).
- **`names_by_country`** — per-country concrete names. Key = ISO 3166-1 alpha-2 country
  code (uppercase). Value = object mapping a level `key` to its concrete Spanish name in
  that country. `country` (admin-0) is not repeated here; only the sub-national levels.

### How to update

- **Add / edit a per-country name:** under `names_by_country`, add or edit the country's
  entry, keyed by level `key`. Example — adding Germany's regional name:
  `"DE": { "region": "Bundesland" }`.
- **A country with only one sub-national level:** include only `region`, omit `subregion`.
  This is how the asset encodes that a country's depth stops at admin-1 (e.g. `PT`
  distritos, `AD` parroquias, which have no admin-2 in this model).
- **Country codes** must be ISO 3166-1 alpha-2, uppercase, to stay consistent with
  `region-codes.json`.
- **Spanish literals** (`label`, `names_by_country` values) are interface text: write them
  in Spanish, correctly accented.
- **Validate** after editing: (1) the file is valid JSON; (2) every `key` used in
  `names_by_country` exists in `levels`; (3) `admin` values stay base-0 and contiguous
  (`0, 1, 2`); (4) `levels` stays ordered by `admin`.

### Do not

- Do not reintroduce `level` / `nivel` numbering, nor rename `admin` to a 1-based scheme.
- Do not store a UI ordinal (`admin + 1`) in the file.
- Do not add levels deeper than the country actually has (no empty `subregion`).
- Do not add a continent / supra-national field (see conventions).

### Current contents

Levels `country` / `region` / `subregion` (admin 0/1/2) and per-country names for the five
countries currently in scope: `ES`, `IT`, `FR`, `PT`, `AD`. `PT` and `AD` carry only
`region` (single sub-national level). Extend as new countries or specifications arrive.

[↑ Back to top](#table-of-contents)

---

## Modelo de datos: ficheros de regiones

### Qué es

El **árbol de navegación** del mapa: los lugares (un país, sus regiones, sus provincias…) y su anidamiento padre→hijo, para que el mapa pueda entrar (`children`) y volver (ascendiente en el árbol).

Estos ficheros son **output** de la herramienta de BE Map Studio que construye el árbol a partir de la **lista de países**, e **input** de la herramienta que genera el SVG. Por eso aquí se documenta su **estructura**, no un fichero concreto: cualquier fichero que la respete puede ser el activo de producción del mapa. No se editan a mano; los produce BE Map Studio.

### Cómo está hecho por dentro

Dos claves de primer nivel: `_meta` y `tree`.

- **`_meta`** — información sobre el propio fichero (`purpose`, `levels_ref`, `node_shape`, `source`). No se usa en ejecución.
- **`tree`** — la lista de países (nodos admin-0). Todo nodo, a cualquier nivel, tiene la misma forma:
  - `code` — el identificador. País = ISO 3166-1 alpha-2 (`ES`); subdivisiones = ISO 3166-2 **sin el guion** (`ES-AN` → `ESAN`). Es la **clave de join** con la geometría del SVG.
  - `name` — el nombre a mostrar (ver Notas).
  - `admin` — el nivel, base 0. Va repetido con la profundidad del árbol a propósito, para poder aplanarlo con facilidad.
  - `children` — los nodos hijo. Se omite en las hojas (no se deja un `[]` vacío).

**Profundidad variable:** cada país baja tanto como ISO 3166-2 tenga para él (se ha visto hasta **admin-3**); un país sin subdivisiones sería una hoja admin-0.

### De dónde salen los datos

Se pueblan desde **ISO 3166-2** (vía `pycountry`), el mismo sistema de códigos que usan los nodos; de ahí sale también el enlace padre→hijo.

### Notas

- **Nombre bilingüe (nivel país).** El nombre del país viene en dos idiomas: el del propio país y el español (`Nombre [Español]`, p. ej. `France [Francia]`). Cómo se usan los dos se concretará en los TO-DOs pendientes.
- **Territorios de ultramar.** El pipeline de generación de BE Map Studio tiene un flag para que decidas si se incluyen o no.

### Alcance actual

Cinco países: `ES`, `IT`, `FR`, `PT`, `AD`. Se amplía cambiando la lista de países de entrada y regenerando con BE Map Studio.

[↑ Back to top](#table-of-contents)

---

## Asset: `map-style.json`

### Purpose

Non-cartographic **decoration input** for map generation: the default colours and stroke
weights the generator bakes into the SVG so it does not come out all-black (see
[Default colours](#map-structure-zoom-tiers)). Also the home for any future non-cartographic
generation data, kept separate from geometry and from the tree. Exchanged with the map
generator (Gemini / mapshaper) as structured data.

### Location & format

- File: [`map-style.json`](claude/res/map-style.json)
- Format: JSON.

### Consumer contract (how the generator applies it)

- For each zoom tier (see [Map structure: zoom tiers](#map-structure-zoom-tiers)), merge
  `defaults` with that tier's overrides in `tiers`, and pass the result to mapshaper `-style`
  on the tier's layer. Keys map 1:1 to SVG presentation attributes (`fill`, `stroke`,
  `stroke-width`, `opacity`).
- `canvas.background` is the intended backdrop — applied as a background `<rect>` at
  generation, or by the theme container.
- Everything here is a **default baseline**: presentation attributes, overridable by the
  theme's CSS at runtime **without regenerating the map**.

### Schema

Top-level keys: `_meta`, `canvas`, `defaults`, `tiers`.

- **`_meta`** — self-documentation. Not consumed at generation.
- **`canvas.background`** *(string)* — page backdrop colour (hex).
- **`defaults`** *(object)* — base style applied to every feature. Keys are **exact SVG
  presentation-attribute names**: `fill`, `stroke`, `stroke-width`, `opacity`.
- **`tiers.tierN`** *(object)* — per-tier overrides of the same keys, keyed by zoom tier
  (`tier0` = countries, `tier1`, `tier2`). Merged **over** `defaults`; only list what differs.

### How to update

- **Change a colour / weight:** edit the hex or number under `defaults` or a specific
  `tiers.tierN`.
- **Add a styleable property:** use its **exact SVG presentation-attribute name** (e.g.
  `fill-opacity`) so it maps straight to mapshaper `-style`.
- **Tier keys** stay `tier0` / `tier1` / `tier2` — never `admin0/1/2` (see
  [Map structure: zoom tiers](#map-structure-zoom-tiers)).
- **Non-style generation data:** add a new top-level section; do not overload `tiers`.

### Do not

- Do not put interaction states (hover / active) here — those are runtime theme styling (their
  own TO-DOs), not generation defaults.
- Do not put cartographic / geometry parameters here (projection, `simplify`, clip bbox):
  those belong with the generation geometry, not with decoration.
- Do not key styles by admin level; key by tier.

### Current contents

Dark editorial baseline aligned to the project palette: backdrop `#0e0e0e`, land fill
`#1a1a1a`, country outline (`tier0`) in gold `#f2c118`, sub-national tiers with progressively
finer grey strokes. Values are a starting default, meant to be tuned.

[↑ Back to top](#table-of-contents)
