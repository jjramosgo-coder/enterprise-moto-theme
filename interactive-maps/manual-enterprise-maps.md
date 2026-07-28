# Mapas interactivos de Bitácora Enterprise — manual de usuario

Este manual explica los **mapas interactivos** del tema: cómo se estructuran y cómo manejar los ficheros con los que se generan. Está pensado para una persona.

Los mapas los genera **BE Map Studio** (una toolbox en notebooks de Google Colab) a partir de dos ingredientes: un fichero de **datos** (el árbol de regiones por el que se navega) y un fichero de **estilo** (colores, líneas, proyección…). Este manual documenta esos ficheros —qué son, cómo se estructuran y cómo tocarlos— junto con el vocabulario del mapa.

- Los activos versionados de los mapas (este manual, el esquema de estilo y su ejemplo) están en `interactive-maps/`. Los ficheros de datos que produce BE Map Studio son ficheros de trabajo en `claude/res/`.

---

## Índice

- [Estructura del mapa: zoom tiers](#estructura-del-mapa-zoom-tiers)
- [El generador de mapas (BE Map Studio)](#el-generador-de-mapas-be-map-studio)
- [Modelo de datos: ficheros de regiones](#modelo-de-datos-ficheros-de-regiones)
- [Colores del mapa](#colores-del-mapa)
- [Asset: estilo del mapa](#asset-estilo-del-mapa)

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

**Importante para el join:** los códigos de la geometría de origen hay que normalizarlos a nuestro ISO 3166-2 (sin guion) antes del join. Ojo con el `iso_3166_2` con guion de Natural Earth y sus placeholders `-99`. Cuando la discrepancia no es de formato sino de código —una unidad que un país reorganizó o que Natural Earth etiqueta distinto—, se resuelve con un diccionario de alias; ver «Asincronía ISO↔geometría: el diccionario de alias» en [El generador de mapas](#el-generador-de-mapas-be-map-studio).

### Colores por defecto

En crudo, mapshaper saca el SVG **todo negro** (no escribe `fill`, así que se aplica el del navegador). Para darle una paleta por defecto se usa `-style` en la generación (`fill`, `stroke`, `stroke-width`, `opacity`; por `tier` con `where=`). Son solo defaults: al ser atributos de presentación, el estilado del tema los sobreescribe por CSS sin regenerar el mapa. El baseline se alinea con la paleta del proyecto (fondos `#0e0e0e` / `#1a1a1a`, acento dorado `#f2c118`).

[↑ Volver al índice](#índice)

---

## El generador de mapas (BE Map Studio)

BE Map Studio es la toolbox (notebooks de Google Colab) que genera el SVG del mapa a partir de dos ficheros: el de **datos de regiones** (el árbol de navegación) y el de **estilo**. Vive en tu Colab y no se versiona con el tema; el prompt para (re)generar la librería está en `claude/res/svg-generator-library.md`. El SVG que produce cumple lo que describe la sección [Estructura del mapa: zoom tiers](#estructura-del-mapa-zoom-tiers).

### Generar un mapa

1. Indícale tu **fichero de datos de regiones** y tu **fichero de estilo**.
2. Indícale **dónde guardar** el SVG.
3. Ejecútalo (`run()`).
4. Al terminar, revisa el resumen que muestra (viewBox, capas y recuentos) y **descarga** el SVG.

Por defecto busca los ficheros en el directorio de trabajo del notebook; las rutas se fijan con las funciones de configuración que expone la librería.

### Qué hace por dentro (resumen)

Toma tu árbol de regiones y tu estilo, trae geometría de dominio público (Natural Earth), **normaliza sus códigos a los nuestros** (ISO 3166-2 sin guion), une tus datos (`code`, `name`, `admin`, `parent`) con la geometría, arma las capas por `tier` —rellenando la profundidad variable para que al acercarse no queden huecos— y aplica tus colores por defecto. El resultado es un SVG con las capas `tier0` / `tier1` / `tier2`, y cada zona con su `id` (= `code`), `data-admin`, `data-parent` y `data-name`.

### Asincronía ISO↔geometría: el diccionario de alias

El árbol de regiones se puebla de ISO 3166-2 (vía `pycountry`), que está **al día**: en cuanto un país aprueba una reforma administrativa, el código cambia de inmediato. La geometría de Natural Earth va **por detrás** —trazar y validar las fronteras nuevas lleva tiempo—, así que a veces el mapa trae para una zona un código que ya no coincide con el del árbol, y el `-join` no le encuentra pareja. **No es un fallo del pipeline:** es el desajuste clásico entre un estándar tabular, que cambia rápido, y una geometría vectorial, que cambia despacio (le ocurre a cualquiera que cruce ISO con Natural Earth, OSM o GADM). En el documento de diseño se recoge como nota para las fases que consumen el árbol (§13.19): el motor del mapa es *content-agnostic* y la coherencia de códigos se garantiza **aquí, en la generación**, no en el tema.

La solución es una **capa de traducción** (*crosswalk*): el diccionario `NATURAL_EARTH_ALIASES`, que reemplaza el código que trae Natural Earth por **nuestro** código ISO vigente **antes** del `-join`, de modo que el polígono se una al nodo correcto del árbol. Es el patrón estándar en GIS, no un parche temporal. Vive en BE Map Studio (aguas arriba), no en el tema.

Conviene distinguir **dos tipos** de desajuste, porque se mantienen de forma distinta:

- **Asincronía temporal** — Natural Earth conserva unidades que ISO ya **abolió**; se remapea la vieja a la vigente. Ejemplos: la reforma de Cerdeña de 2016 (`ITOT`→`ITSS`, `ITOG`→`ITNU`, `ITVS`/`ITCI`→`ITSU`) o la fusión de regiones francesas de 2016 (`FRA1`/`FRB4`/`FRC1`→`FRGES`, `FRM1`/`FRL1`→`FROCC`).
- **Variante de esquema o de nivel** — el mismo territorio, pero Natural Earth lo etiqueta con un código distinto del que usa nuestro árbol: provincia frente a comunidad (`ESNA`→`ESNC`, `ESPM`→`ESIB`), región frente a provincia (`ITAO`→`IT23`) o departamento frente a colectividad especial (`FR69`→`FR69M`, `FR75`→`FR75C`).

Un apunte que evita un malentendido: cuando el alias colapsa una unidad uniprovincial, su geometría se dibuja **una sola vez** bajo el código canónico y se incluye en dos capas por la [profundidad variable](#estructura-del-mapa-zoom-tiers) (p. ej. Navarra sale como `ESNC` tanto en `tier1` como en `tier2`). Por eso **que un `code` del árbol no aparezca como `id` en el SVG no significa que le falte geometría**: puede estar representado bajo su código canónico.

El diccionario vigente (solo Europa y **no exhaustivo**):

```python
# ---------------------------------------------------------------------
# MAPEO DE EQUIVALENCIAS Y ALIAS EUROPA (Natural Earth GeoJSON -> ISO 3166-2)
# Reemplaza códigos extintos o erróneos de Natural Earth por el código ISO vigente
# ---------------------------------------------------------------------
NATURAL_EARTH_ALIASES = {
    # -----------------------------------------------------------------
    # ITALIA (Reforma de Cerdeña de 2016)
    # -----------------------------------------------------------------
    "ITOT": "ITSS",  # Olbia-Tempio        -> Reabsorbida en Sassari (IT-SS)
    "ITOG": "ITNU",  # Ogliastra           -> Reabsorbida en Nuoro (IT-NU)
    "ITVS": "ITSU",  # Medio Campidano     -> Consolidada en Sud Sardegna (IT-SU)
    "ITCI": "ITSU",  # Carbonia-Iglesias   -> Consolidada en Sud Sardegna (IT-SU)

    # -----------------------------------------------------------------
    # ITALIA (Regiones NUTS vs Provincias ISO)
    # -----------------------------------------------------------------
    "ITAO": "IT23",   # IT-AO (Natural Earth Aosta)   -> IT23 (Árbol: NUTS Valle d'Aosta)

    # -----------------------------------------------------------------
    # ESPAÑA (Variantes de código ISO regional en GeoJSONs de Natural Earth)
    # -----------------------------------------------------------------
    "ESPM": "ESIB",  # Palma / Baleares (PM -> Iles Balears IB)
    "ESNA": "ESNC",  # Navarra (Variante de código provincial vs autonómico)

    # -----------------------------------------------------------------
    # FRANCIA (Consolidación de regiones históricas a regiones ISO NUTS-2)
    # -----------------------------------------------------------------
    "FRA1": "FRGES", # Alsacia            -> Grand Est
    "FRB4": "FRGES", # Lorena             -> Grand Est
    "FRC1": "FRGES", # Champaña-Ardenas   -> Grand Est
    "FRM1": "FROCC", # Midi-Pyrénées      -> Occitania
    "FRL1": "FROCC", # Languedoc-Roussillon -> Occitania

    # -----------------------------------------------------------------
    # FRANCIA (Departamentos / Colectividades especiales)
    # -----------------------------------------------------------------
    "FR69": "FR69M",  # FR-69 (Natural Earth Ródano)  -> FR69M (Árbol: Métropole de Lyon)
    "FR75": "FR75C",  # FR-75 (Natural Earth París)   -> FR75C (Árbol: París Collectivité)

    # -----------------------------------------------------------------
    # GRECIA (Reformas administrativas Kallikratis)
    # -----------------------------------------------------------------
    "GRA":  "GRAI",  # Ática / Attica
}
```

**Cómo ampliarlo.** Es solo-Europa y no cubre todos los casos posibles; se amplía cuando aparece una zona sin representar. Los pasos: (1) identifica la unidad que quedó sin dibujar —el resumen que muestra BE Map Studio al generar marca las *features* con `code` vacío o sin geometría—; (2) averigua con qué código la trae Natural Earth para ese territorio; (3) añade la entrada `"CÓDIGO_NATURAL_EARTH": "NUESTRO_ISO_VIGENTE"` en la categoría que corresponda (temporal o de esquema), con un comentario que explique la equivalencia.

[↑ Volver al índice](#índice)

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

[↑ Volver al índice](#índice)

---

## Colores del mapa

Los colores definidos en el JSON se aplican en **tres capas estructurales**. El script `map_svg.py` (librería BE Interactive maps) lee estas claves y las traduce directamente a atributos nativos de SVG (`fill`, `stroke`) o a elementos de fondo.

### 1. Fondo del mapa (`canvas`)

- **Dónde se usa:** En el fondo global del lienzo SVG.
- **Clave JSON:** `"canvas": { "background": "#e2e2de" }`.

**Cómo se aplica:** Tras generar las geometrías con Mapshaper, el script inyecta automáticamente una etiqueta de fondo al principio del SVG:

```xml
<rect width="100%" height="100%" fill="#e2e2de" />
```

**Uso práctico:** Representa el agua/océano o el lienzo base sobre el que flotan las masas de tierra.

### 2. Relleno y bordes generales (`defaults`)

**Dónde se usa:** Como plantilla de color predeterminada para **todos** los polígonos/países/regiones dibujados.

**Claves JSON:**

- `"fill": "#1a1a1a"`: Color de relleno interior de la tierra.
- `"stroke": "#0e0e0e"`: Color base por defecto para las líneas de frontera.
- `"opacity": 1`: Opacidad general del polígono.

**Cómo se aplica:** Mapshaper asigna estos valores directamente como atributos SVG (`fill="#1a1a1a"`) a cada elemento `<path>` del mapa.

### 3. Jerarquía y grosor de fronteras (`tiers`)

**Dónde se usa:** Para diferenciar visualmente la importancia geopolítica de las fronteras (nivel país vs. nivel regi´ón/comunidad/provincia). Formalmente niveles admin 0/1/2

**Claves JSON:**

- `"tier0"` (Países / Fronteras nacionales): `"stroke": "#f2c118"` (Línea dorada/amarilla).
- `"tier1"` (Comunidades / Estados / Regiones): `"stroke": "#3a3a3a"` (Gris intermedio).
- `"tier2"` (Provincias / Comarcas): `"stroke": "#2a2a2a"` (Gris más tenue).

**Cómo se aplica:** Funciona por **herencia con sobrescritura**. El script combina las propiedades predeterminadas de `defaults` con las específicas de cada nivel:

```python
merged_style = {**style_defaults, **tier_overrides.get(t, {})}
```

Esto significa que el relleno (`fill: "#1a1a1a"`) se mantiene igual para todo el mapa, pero el color y el grosor del borde (`stroke` y `stroke-width`) cambian según la importancia de la frontera:

- Las fronteras internacionales (`tier0`) resaltarán en amarillo (`#f2c118`) con mayor grosor (`0.8`).
- Las fronteras internas (`tier1` y `tier2`) usarán tonos grises más oscuros y trazos más finos (`0.5` y `0.3`) para no recargar la vista.

[↑ Volver al índice](#índice)

---

## Asset: estilo del mapa

Con el fichero de estilo controlas el aspecto del mapa: colores, grosor de las líneas, opacidad, color de fondo y la proyección. Al lado tienes un **esquema** (`map-style.schema.json`), que lista las opciones válidas y valida el fichero mientras lo escribes, y un **ejemplo** listo para copiar (`map-style.json`); ambos en `interactive-maps/`.

### Crear tu fichero de estilo

1. Copia `map-style.json` y renómbralo a tu gusto.
2. Comprueba que la primera línea enlaza el esquema (así tu editor te avisa de errores):
   - si el fichero está en la misma carpeta que el esquema: `"$schema": "./map-style.schema.json"`;
   - si está en otra carpeta, ajusta la ruta (p. ej. `"$schema": "../interactive-maps/map-style.schema.json"`).
3. Cambia los valores (ver abajo) y regenera el mapa con BE Map Studio: verás aplicados esos colores, grosores y la proyección elegida.

### Qué puedes ajustar

- **Color de fondo** — `canvas.background`: el fondo del mapa, en HEX, RGB o HSL.
- **Proyección** — `projection.type`: cómo se representa el mundo en el plano. Elige uno de los valores de la tabla «Proyecciones disponibles».
- **optimization**: permite ajustar el equilibrio entre **precisión visual**, **rendimiento de renderizado** y **peso en disco** del archivo SVG generado.
- **Estilo base de todo el mapa** — `defaults`:
  - `fill`: color de relleno de cada zona.
  - `stroke`: color de la línea (el borde de cada zona).
  - `stroke-width`: grosor de la línea (un número).
  - `opacity`: opacidad, de `0` (transparente) a `1` (opaco).
- **Estilo por paso de zoom** — `tiers` (`tier0`, `tier1`, `tier2`): en cada uno escribe solo lo que quieras que se vea distinto del estilo base.
- `_meta` es opcional: notas para ti; no cambia el mapa.

Para los colores puedes usar la paleta editorial del tema (tabla abajo) o tus propios valores.

### Proyecciones disponibles

| Valor (`type`) | Nombre | Qué hace |
|---|---|---|
| `none` | Sin proyección (WGS84 / Geográfica) | Estado original por defecto. Mapea grados directamente a coordenadas X/Y (Plate Carrée). Produce esa sensación de curvatura o inclinación. |
| `europe_official` | Oficial Unión Europea (ETRS89-extended / LAEA) | Estándar técnico de Eurostat. Preserva el área real de los países (visión cenital equilibrada). |
| `albers` | Albers Equal Area Conic | Proyección cónica. Muy estilizada y limpia para mapas continentales. |
| `mercator` | Web Mercator | Vista plana cartesiana recta, idéntica a Google Maps u OpenStreetMap. |
| `lambert` | Lambert Conformal Conic | Proyección cónica conforme que mantiene fielmente la forma local de las fronteras. |
| `robinson` | Robinson | Curva los bordes para ofrecer una perspectiva global o de gran escala. |
| `ortho` | Ortográfica (efecto globo 3D) | Representa el mapa como si se observara el planeta desde el espacio. |

### La paleta editorial

| Color | HEX | Dónde se usa |
|---|---|---|
| Negro (fondo) | `#0e0e0e` | fondo del mapa (`canvas.background`) y borde base |
| Negro suave (superficie) | `#1a1a1a` | relleno de las zonas (`defaults.fill`) |
| Dorado (acento de marca) | `#f2c118` | borde de los países (`tier0`) |
| Gris oscuro | `#3a3a3a` | borde del nivel intermedio (`tier1`) |
| Gris muy oscuro | `#2a2a2a` | borde del nivel más fino (`tier2`) |

### Ejemplo

```json
{
  "$schema": "./map-style.schema.json",
  "projection": { "type": "europe_official" },
  "canvas":     { "background": "#0e0e0e" },
  "defaults":   { "fill": "#1a1a1a", "stroke": "#0e0e0e", "stroke-width": 0.5, "opacity": 1 },
  "tiers": {
    "tier0": { "stroke": "#f2c118", "stroke-width": 0.8 },
    "tier1": { "stroke": "#3a3a3a", "stroke-width": 0.5 },
    "tier2": { "stroke": "#2a2a2a", "stroke-width": 0.3 }
  }
}
```

Con estos valores verás un mapa con la proyección oficial de la UE, fondo casi negro, zonas gris oscuro, los países con el borde dorado algo grueso y los niveles interiores con líneas grises cada vez más finas.

### Cambiar un color o un grosor

1. Abre tu fichero de estilo.
2. Edita el HEX (color) o el número (grosor) en `defaults`, o en el `tier` concreto si solo quieres cambiar ese paso de zoom.
3. Regenera el mapa con BE Map Studio para ver el cambio.

### Optimización de SVG (`optimization`)

El bloque `"optimization"` dentro de `map-style.json` permite ajustar el equilibrio entre **precisión visual**, **rendimiento de renderizado** y **peso en disco** del archivo SVG generado.

```json
"optimization": {
  "simplify_percent": "20%",
  "min_island_area": "10km2",
  "svg_precision": 0.1
}
```

#### 1. `simplify_percent`

Reducción de la densidad de vértices que componen las fronteras y bordes costeros (mediante el algoritmo de simplificación de Visvalingam).

- **Valores aceptados:**
- Cadena de texto con porcentaje: `"5%"` a `"100%"`.
- `null`: Desactiva la simplificación (mantiene el 100% de los detalles del GeoJSON original).

**Implicación de los valores:**

- **`15%` – `30%` (Recomendado):** Reduce el peso entre un **50% y un 80%**. Elimina ruido e imperfecciones no perceptibles en pantalla conservando la forma reconocible de los países.
- **`< 10%` (Agresivo):** Genera geometrías muy esquemáticas o "poligonales". Ideal solo para mapas estilizados o vistas muy lejanas.
- **`> 50%` (Ligero):** Mantiene casi todo el detalle técnico original, produciendo archivos de gran tamaño.

#### 2. `min_island_area`

Eliminación de pequeñas islas, islotes o polígonos aislados cuya superficie real sea inferior al valor especificado.

- **Valores aceptados:**
- Cadena de texto con valor y unidad de superficie: `"5km2"`, `"10km2"`, `"50km2"`, etc.
- `null`: Conserva todos los polígonos sin importar su tamaño.

**Implicación de los valores:**

- **`10km2` (Recomendado):** Elimina miles de nodos `<path>` imperceptibles que ralentizan la carga del SVG en el navegador, reduciendo el tamaño en un **20% – 40% adicional**.
- **`1km2` – `5km2`:** Mantiene archipiélagos y detalles insulares finos.
- **`> 50km2`:** Puede hacer desaparecer islas o provincias pequeñas completas (ej. Ibiza, Malta o pequeñas islas del Egeo).

#### 3. `svg_precision`

Paso de redondeo absoluto para las coordenadas numéricas dentro del atributo `d="..."` del trazado SVG.

**Valores aceptados:**

- Número flotante positivo: `0.01`, `0.1`, `0.5`, `1.0`.
- `null`: Exporta las coordenadas con la precisión por defecto de Mapshaper (múltiples decimales).


**Implicación de los valores:**

- **`0.1` (Recomendado):** Redondea las coordenadas a 1 decimal (ej. `12.3`). Reduce el peso en bytes entre un **20% y un 30%** simplemente eliminando caracteres de texto sobrantes sin impacto visual perceptible.
- **`0.01` (Alta Fidelidad):** Útil para renders pensados para pantallas 4K/Retina o aplicaciones que permitan hacer gran nivel de zoom.
- **`>= 0.5` (Reducción agresiva):** Puede producir escalonamiento visible en las líneas curvas o pequeñas holguras entre fronteras adyacentes.


#### Perfiles de Configuración Recomendados

| Perfil | `simplify_percent` | `min_island_area` | `svg_precision` | Peso aprox. del SVG | Uso recomendado |
| --- | --- | --- | --- | --- | --- |
| **Balanceado (Por defecto)** | `"20%"` | `"10km2"` | `0.1` | **300 KB – 600 KB** | Entornos web, dashboards y apps empresariales. |
| **Ultra-ligero** | `"10%"` | `"25km2"` | `0.5` | **100 KB – 250 KB** | Listados móviles, miniaturas o carga en conexiones lentas. |
| **Alta Fidelidad** | `"50%"` | `"2km2"` | `0.01` | **1.5 MB – 3 MB** | Impresión de gran formato o mapas interactivos con zoom profundo. |
| **Sin Optimizar** | `null` | `null` | `null` | **5 MB – 10 MB** | Archivo maestro para trabajo cartográfico posterior. |

[↑ Volver al índice](#índice)
