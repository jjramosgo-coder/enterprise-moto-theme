# Enterprise Moto — Guía de instalación

## Instalación en WordPress

### Preparar el paquete del tema
El código del tema vive en la carpeta **`enterprise-moto/`** del repositorio; el resto de la raíz son documentos del proyecto y no forman parte del tema. Según cómo instales:

- **Por FTP (Opción A):** no necesitas comprimir nada; sube directamente la carpeta `enterprise-moto/` del repositorio.
- **Por el panel de WordPress (Opción B):** genera el `.zip` comprimiendo esa carpeta desde la raíz del repositorio, de modo que el archivo contenga la carpeta `enterprise-moto/` en su interior:

  ```
  zip -r enterprise-moto.zip enterprise-moto
  ```

### Opción A — Subir por FTP (recomendada)
1. Sube la carpeta `enterprise-moto/` (del repositorio) a `wp-content/themes/`
2. Ve a **Apariencia → Temas** y activa **Enterprise Moto**

### Opción B — Panel de WordPress
1. Ve a **Apariencia → Temas → Añadir nuevo → Subir tema**
2. Sube el archivo `enterprise-moto.zip`
3. Haz clic en **Instalar ahora** y luego **Activar**

---

## Configuración inicial (10 minutos)

### 1. Menú de navegación
1. **Apariencia → Menús → Crear nuevo menú**
2. Añade las páginas/categorías que quieras
3. Asígnalo a la posición **Menú principal**
4. Guarda

El último ítem del menú recibe automáticamente el estilo de botón CTA oscuro.

### 2. Página de inicio
- Si usas la **página de inicio estática**: asigna una página en **Ajustes → Lectura**
- Si usas las **entradas** como portada (blog estándar): el tema funciona directamente

### 3. Título y descripción del blog
Ve a **Ajustes → General**:
- **Título del sitio** → aparece en el logo (ej: `Diario de ruta de la Enterprise`)
- **Descripción** → aparece bajo el logo (ej: `Juanjo & María José`)

### 4. Widgets del sidebar y footer
Ve a **Apariencia → Widgets**:
- **Sidebar de ruta** → aparece a la derecha de cada entrada
- **Footer — Secciones** → segunda columna del footer
- **Footer — Blog** → tercera columna del footer

Sin widgets configurados, el tema muestra contenido automático.

---

## Datos de ruta por post

Cada entrada tiene una caja de metadatos **"Datos de la ruta"** en el editor:

| Campo | Descripción | Ejemplo |
|---|---|---|
| Kilómetros totales | Se muestra en cards y data strip | `2.800 km` |
| Días de ruta | Número de días | `12` |
| Países recorridos | Número de países | `4` |
| Etapa / Tramo | Descripción del trayecto | `Porto Torres → BCN` |
| Ferrys | Número de ferrys | `3` |
| Dato extra | Etiqueta y valor personalizables | `Ferry` / `Grimaldi Lines` |

Estos datos aparecen en:
- La **franja de datos** bajo el título del post
- La **card** en el grid de inicio
- La **sección destacada** del último post

---

## Imágenes destacadas

Asigna siempre una **imagen destacada** a cada entrada.
El tema registra tres tamaños automáticamente:
- `enterprise-hero` — 1600×900 (hero y portada)
- `enterprise-card` — 800×600 (grid de posts)
- `enterprise-wide` — 1200×500 (cabecera del post)

Para regenerar tamaños en posts existentes:
```
Plugins → Regenerate Thumbnails (plugin gratuito)
```

---

## Ticker de destinos

El ticker dorado bajo el hero muestra las **categorías del blog**.
Para editarlo: añade o renombra categorías en **Entradas → Categorías**.

---

## Destinos en la sección "Destinos recorridos"

Puedes añadir emoji de bandera y nombre del país a cada categoría:
1. Ve a **Entradas → Categorías**
2. Edita la categoría (ej: "Sicilia")
3. Rellena los campos personalizados `_dest_flag` (🇮🇹) y `_dest_country` (Sicilia, Italia)

> Si no usas campos custom, el tema muestra las categorías con el emoji 📍 por defecto.
> Para campos custom en categorías, instala el plugin **Advanced Custom Fields (ACF)** gratuito.

---

## Comentarios

El tema incluye plantilla propia de comentarios.
Activa/desactiva comentarios en **Ajustes → Comentarios**.

---

## Compatibilidad

- WordPress 6.0+
- PHP 8.0+
- Gutenberg (editor de bloques): soporte completo
- Classic Editor: compatible
- Plugins testados: Yoast SEO, WP Super Cache, Jetpack, Contact Form 7

---

## Solución de problemas

**El menú no aparece:**
Ve a Apariencia → Menús y asigna un menú a "Menú principal".

**Las fuentes no cargan:**
Verifica que el servidor tiene acceso a `fonts.googleapis.com`.

**Los datos de ruta no aparecen:**
Guarda el post de nuevo después de rellenar la caja de metadatos.

**El ticker va muy rápido/lento:**
Edita la duración en `style.css`, busca `animation: ticker-scroll 30s`.

---

## Bloque «Etapas de ruta» (enterprise/post-stages)

### Qué hace
Bloque de Gutenberg que permite **seleccionar posts manualmente** y mostrarlos en dos layouts:
- **Carrusel horizontal** — tarjetas deslizables con swipe, botones prev/next y dots de posición
- **Timeline vertical** — mismo diseño que el cuaderno automático pero con posts elegidos a mano

### Cómo usarlo
1. En cualquier página o entrada, haz clic en **+** para añadir un bloque
2. Busca **"Etapas de ruta"** (o la categoría **Enterprise Moto**)
3. En el **panel lateral derecho** → *Selección de posts*: escribe el título del post y selecciónalo
4. Repite para cada etapa. Usa las flechas ▲▼ para reordenar
5. En *Presentación* elige **Carrusel** o **Timeline** y el tamaño de tarjeta
6. En *Campos visibles* activa/desactiva extracto, km y fecha

### Patrones predefinidos
En el selector de bloques, dentro de la pestaña **Patrones → Enterprise Moto** encontrarás:
- **Carrusel de etapas de ruta** — carrusel listo para configurar
- **Timeline de etapas de ruta** — timeline listo para configurar

---

## Plantilla «Bitácora con bloques»

### Cuándo usarla
Cuando quieres el **diseño completo del cuaderno de bitácora** (hero oscuro, estadísticas, barra de progreso, sidebar de resumen) pero elegir tú mismo qué posts aparecen y en qué orden, usando bloques de Gutenberg.

### Diferencia con el cuaderno automático

| | Cuaderno de bitácora | Bitácora con bloques |
|---|---|---|
| Posts | Automático por categoría | Tú los seleccionas |
| Orden | Cronológico | El que tú definas |
| Layouts | Solo timeline | Carrusel y/o timeline |
| Editor | Sin contenido editable | Gutenberg completo |

### Cómo crear una página con esta plantilla
1. **Páginas → Añadir nueva**
2. En el panel lateral, despliega **Plantilla** y elige **Bitácora con bloques**
3. Guarda la página — aparecerá el metabox **"Datos de la expedición"**
4. Rellena: nombre del viaje, ruta, km, países, progreso y marca "En ruta" si estás en marcha
5. En el editor añade uno o varios bloques **"Etapas de ruta"**
6. Publica

### Estructura del editor Gutenberg para esta plantilla
Ejemplo de contenido recomendado:

```
[ Encabezado: "Etapas — semana 1" ]
[ Bloque Etapas de ruta — Layout: Carrusel — Posts: días 1-5 ]

[ Encabezado: "Etapas — semana 2" ]
[ Bloque Etapas de ruta — Layout: Timeline — Posts: días 6-10 ]

[ Párrafo: "El viaje en números..." ]
[ Bloque Etapas de ruta — Layout: Carrusel grande — Posts: favoritas ]
```

---

## Archivos nuevos en esta versión

```
enterprise-moto/
├── assets/
│   ├── css/
│   │   └── carousel.css              ← Carrusel + timeline del bloque
│   └── js/
│       ├── block-post-stages.js      ← Editor Gutenberg del bloque (sin build tools)
│       └── carousel.js               ← Prev/next, swipe, dots
├── blocks/
│   └── post-stages/
│       └── render.php                ← Renderizado PHP del bloque (frontend)
└── page-bitacora-bloques.php         ← Plantilla "Bitácora con bloques"
```
