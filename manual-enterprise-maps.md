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
- [Map structure: zoom tiers](#map-structure-zoom-tiers)
- [Asset: `map-levels.json`](#asset-map-levelsjson)
- [Asset: `map-regions.json`](#asset-map-regionsjson)
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

## Map structure: zoom tiers

How the interactive map (Phase 51.2, not yet built) is organised, and the vocabulary that
keeps it unambiguous. Recorded here as the design contract the map generation must follow.

### Two axes, never conflated: admin level vs zoom tier

- **admin level** (`0` / `1` / `2`) — the *canonical, intrinsic* administrative level of a
  place, defined in [conventions](#project-wide-conventions-locked) and in
  [`map-levels.json`](#asset-map-levelsjson), and carried by every node of
  [`map-regions.json`](#asset-map-regionsjson). A Portuguese *distrito* is admin-1, always.
- **zoom tier** (`tier0` / `tier1` / `tier2`) — a *presentation* concept: which layer (zoom
  step) a feature is drawn at.

They usually coincide, but diverge under variable depth (below). Name things by the axis they
belong to: SVG layers are **tiers**; a feature's level is its **admin**.

### Separation of concerns (the contract)

- **Geometry** → the SVG.
- **Hierarchy** (parent/child, drill-in and back) → the tree in
  [`map-regions.json`](#asset-map-regionsjson). The SVG does **not** nest DOM groups to
  represent the tree; the JSON is the tree.
- **Editorial style and interaction** → the theme (CSS/JS), handled by their own TO-DOs. The
  generated SVG carries only a default colour baseline plus the keys that link each feature to
  the tree.

### SVG layer layout

- One `<g>` layer per zoom tier: `tier0` (countries), `tier1`, `tier2` (flat sibling groups —
  mapshaper does not nest).
- Each `<path>` carries:
  - `id` = the **canonical ISO 3166-2 code without the hyphen**, identical to the node's
    `code` in [`map-regions.json`](#asset-map-regionsjson). This is the **join key** between
    geometry and tree.
  - `data-admin` = the feature's **canonical** admin level.
  - `data-parent` = its parent's code (back-navigation without walking the tree).
- Rendering "a level" = showing/hiding a tier layer as the user zooms.

### Variable depth: a tier is filled with the deepest level available

Not every country reaches admin-2. A tier layer includes, **per country, the deepest level it
has at or above that tier** — so zooming in never leaves a hole:

- `ES` / `IT` / `FR`: `tier2` = admin-2 (provinces / province / départements).
- `PT` / `AD`: `tier2` = admin-1 (distritos / parroquias), the same features as their `tier1`.

Rules that keep this from corrupting the model:

- **`data-admin` stays the feature's true canonical level** even when it appears in a higher
  tier: a PT *distrito* is `data-admin="1"` in both `tier1` and `tier2`. The tier is the layer;
  the admin is the fact.
- **Layers are named by tier, never by admin** — precisely because `tier2` may hold admin-1
  features.
- **Duplication is generated from a single source** (the country's admin-1 geometry), merely
  included in two tier layers at export. It is never authored twice and cannot diverge.
- **Consistent with the tree:** a node that is a leaf in
  [`map-regions.json`](#asset-map-regionsjson) (no `children`) cannot drill deeper; its
  appearance in `tier2` is terminal. The renderer must treat "leaf at this tier" as the end of
  drill-in.

### How the SVG is generated (mapshaper)

mapshaper emits one `<g>` per data layer, sets the path `id` from `id-field`, emits `data-*`
from `svg-data`, computes fields with `-each`, and attaches external attributes by key with
`-join`. Pipeline: source admin-0/1/2 geometry → `-join`
[`map-regions.json`](#asset-map-regionsjson) attributes (code, admin, parent, name) on the ISO
code → split into tier layers → export with `id-field=code svg-data=admin,parent,name`.

**Join-key requirement:** the geometry source's codes must be normalised to our canonical
ISO 3166-2 (no hyphen) before the join. Watch Natural Earth's hyphenated `iso_3166_2` and its
`-99` placeholders.

### Default colours

Raw mapshaper SVG renders **all-black** (it writes no `fill`, so the browser default applies).
Set a default palette at generation with **`-style`** (`fill`, `stroke`, `stroke-width`,
`opacity`; per-tier with `where=`). Because these are SVG **presentation attributes**, they are
only defaults: the theme's later styling overrides them via CSS **without regenerating the
map** (a CSS rule beats a presentation attribute). Align the baseline with the project palette
(backgrounds `#0e0e0e` / `#1a1a1a`, gold accent `#f2c118`).

[↑ Back to top](#table-of-contents)

---

## Asset: `map-levels.json`

### Purpose

Canonical dictionary of the map's navigation levels: **what each level is**, its stable
identifiers, and **its concrete name per country**. It is the single source of truth other
map assets and the theme's map code refer to when they need to name or order a level.

### Location & format

- File: `claude/res/map-levels.json`
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

## Asset: `map-regions.json`

### Purpose

The actual **navigation tree**: the concrete admin-0/1/2 nodes the map drills through. Where
`map-levels.json` defines *what each level is*, this asset holds *the nodes themselves* and
their parent→child nesting, so the map can drill in (`children`) and go back (ancestor in the
tree).

### Location & format

- File: `claude/res/map-regions.json`
- Format: JSON (`json_decode` in PHP, `fetch` / `import` in JS — no build step).

### Provenance

Populated from **ISO 3166-2** (official authority: ISO Online Browsing Platform), the same
code system the nodes use. ISO 3166-2 is what supplies the parent→child link (each admin-2
declares its admin-1 parent). Names are in Spanish (Castilian). Validated on load:
counts per country match ISO 3166-2, no duplicate codes, every admin-2 nested under an
existing admin-1.

### Schema

Top-level keys: `_meta`, `tree`.

- **`_meta`** — self-documentation (`purpose`, `levels_ref`, `node_shape`, `source`). Not
  consumed at runtime.
- **`tree`** — ordered array of **admin-0 (country) nodes**. Every node — at any level — has
  the **same shape**:
  - `code` *(string, required)* — identifier. Country = ISO 3166-1 alpha-2 (`ES`);
    sub-national = ISO 3166-2 **without the hyphen** (`ES-AN` → `ESAN`, `ES-SE` → `ESSE`).
  - `name` *(string, required)* — Spanish name.
  - `admin` *(number, required)* — canonical level, base 0 (see conventions). Redundant with
    tree depth on purpose: it makes the tree trivially **flattenable** (depth-first traversal
    → `[{code, name, admin, parent}]`, with `parent` taken from the ancestor).
  - `children` *(array, optional)* — child nodes. **Omitted** on leaves (never an empty `[]`).

Depth is data, not schema: `ES`/`IT`/`FR` go to admin-2; `PT`/`AD` stop at admin-1; a country
with no sub-national data would be an admin-0 leaf.

### How to update

- **Add a country:** append an admin-0 node to `tree` (`code` = alpha-2, `admin: 0`), with its
  `children` if it has sub-national data.
- **Add / move a sub-national node:** place it inside its parent's `children`, set its `admin`
  (`parent.admin + 1`), and give it `children` only if it drills deeper.
- **Source of truth for codes and parent links is ISO 3166-2.** Do not invent a parent from a
  code's shape; confirm it against ISO 3166-2 (ISO OBP, or a faithful derivative).
- **Autonomous cities / single-node cases** (e.g. `ESCE` Ceuta, `ESML` Melilla): admin-1 with
  no `children`.
- **Validate** after editing: (1) valid JSON; (2) every node's `admin` equals its tree depth;
  (3) leaves have no `children` key (not an empty array); (4) no duplicate `code` anywhere;
  (5) codes are ISO 3166-2 without the hyphen (country in alpha-2).

### Do not

- Do not store `parent` on nodes — it is redundant with nesting and is recovered on flatten.
- Do not use hyphenated ISO codes (`ES-AN`) or reintroduce `level`/`nivel` numbering.
- Do not nest a node deeper than its real admin level, nor leave an empty `children: []`.
- Do not assign a parent by guessing from the code; verify against ISO 3166-2.

### Current contents

Five admin-0 countries — `ES`, `IT`, `FR`, `PT`, `AD` (337 nodes total). `ES`/`IT`/`FR` drill
to admin-2 (provinces / province / départements); `PT`/`AD` stop at admin-1. Counts verified
against ISO 3166-2: `ES` 19/50, `IT` 20/107, `FR` 13/96 (metropolitan France, Corsica
included, overseas excluded), `PT` 20 admin-1, `AD` 7 admin-1. Residual: exhaustive
parent→child correctness across all ~255 second-level nodes was not machine-verified (counts
and sampling give high confidence).

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

- File: `claude/res/map-style.json`
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
