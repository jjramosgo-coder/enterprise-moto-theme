# Requerimientos de desarrollo — correcciones estéticas · tarjeta de viaje (`trip-collection`) y carrusel compartido

> Documento del **arquitecto** para el **rol desarrollador**. Es una especificación,
> no código. El desarrollador implementa y entrega **ficheros completos + comandos git**;
> **Juanjo valida en WordPress real** antes de cerrar. Fuente de verdad: el repo
> `jjramosgo-coder/enterprise-moto-theme` (cuenta personal, autor `jjramosgo@gmail.com`).
> Versión base: **2.6.0** (verificada en las tres fuentes canónicas en HEAD `30657ff`);
> objetivo al cierre: **2.6.1** (bump de parche; lo hace **el arquitecto tras validar**,
> no el desarrollador). Antes de tocar nada: clon fresco + `git ls-remote`, y leer
> `bitacora-enterprise-design.md` y `TODO.md`. **Un commit por punto** (troceado) para
> validar por partes.

---

## 0. Objetivo y contexto

Cuatro correcciones estéticas menores. **Tres** son cosméticas de la tarjeta de viaje
(`.trip-card`) del bloque `enterprise/trip-collection`; la **cuarta** es un **fix real de
especificidad** en el scaffolding **compartido** `carousel.css`, que afecta **también** a
`enterprise/post-stages`.

1. **Padding en las celdas de cifras** de la card: el texto queda pegado a la línea gris
   que separa las celdas. Falta padding horizontal.
2. **Celda de ferry condicional + etiqueta en singular:** hoy la celda «Ferrys» sale
   siempre, aunque el viaje no tenga ferries. Ocultarla si `ferrys == 0` y, cuando salga,
   dejar la etiqueta en **singular** («Ferry»).
3. **Flecha-botón con hover:** añadir un botón simulado que cambia de color al pasar el
   ratón por la card, replicando el patrón de las cards de listado/portada
   (`.post-card-arrow`), por coherencia del sistema de diseño.
4. **Carrusel en móvil:** muestra **2 tarjetas estrechas** en vez de 1. Causa raíz: bug de
   especificidad en `carousel.css` (detallado en §1.4). Se corrige **en origen** (camino A,
   decidido por Juanjo). Afecta a los dos bloques que usan el scaffolding.

**Nota de trazabilidad.** Estas correcciones **no** están numeradas como TO-DOs (1–3 son
cosmética fuera de backlog). El punto 4, por su alcance (fix en fichero compartido), podría
registrarse como `fix` con número propio si Juanjo lo desea; queda a su criterio al cierre.

---

## 1. Estado actual (verificado en código, no de memoria)

### 1.1. Padding de las celdas
`assets/css/coleccion.css`, líneas 105–110:

```css
.ent-trip-collection .trip-meta-i {
  flex: 1;
  padding: 10px 0;                 /* vertical sí, horizontal NO */
  border-right: 1px solid var(--border);
}
.ent-trip-collection .trip-meta-i:last-child { border-right: none; }
```

El contenido va alineado a la izquierda, sin padding horizontal, por lo que las celdas 2ª y
3ª pegan su texto al separador que tienen a la izquierda. **Patrón de referencia ya en el
tema** (a imitar): `.f-stat` en `style.css` (~l. 421–426): `padding: 16px 0;` +
`padding-left: 20px;` + `.f-stat:first-child { padding-left: 0; }`.

### 1.2. Celda de ferry siempre presente
`blocks/trip-collection/render.php`, líneas 144–147 (la 3ª `.trip-meta-i`):

```php
<div class="trip-meta-i">
    <div class="trip-meta-n"><?php echo intval( $data['ferrys'] ); ?></div>
    <div class="trip-meta-l"><?php esc_html_e( 'Ferrys', 'enterprise-moto' ); ?></div>
</div>
```

**Importante:** la card se compone **una sola vez** (`ob_start()` en l. 117, `$card` en
l. 152) y se reutiliza en **carrusel** (`.ent-stages__slide`, l. 155) **y timeline**
(`.ent-tl-item`, l. 157–164). Por tanto cualquier cambio en la card (puntos 1, 2 y 3)
aplica a **ambos layouts** (ver §3.3).

### 1.3. Patrón de flecha-botón existente
`style.css`, l. 428–433 y l. 397:

```css
.post-card-arrow {
  width: 32px; height: 32px; background: var(--black); color: var(--white);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; transition: background .2s, color .2s; flex-shrink: 0;
}
.post-card:hover .post-card-arrow { background: var(--gold); color: var(--black); }
```

La `.trip-card` es un `<a>` (`render.php` l. 118) que envuelve **toda** la card; su nombre
accesible lo aporta el título (`.trip-title`). No hay flecha en la card hoy.

### 1.4. Bug de especificidad del carrusel (causa raíz del punto 4)
`assets/css/carousel.css`:

- Base (l. 118–119): `large` → `calc(50% - 8px)`; **no-`large`** → `calc(33.333% - 12px)`.
- `@media (max-width: 900px)` (l. 300–303): `large` → 70%; **no-`large`** → **50%**
  (selector `.ent-stages--carousel:not(.ent-stages--large) .ent-stages__slide`,
  especificidad **(0,3,0)**).
- `@media (max-width: 640px)` (l. 305–311): **todos** → 85%
  (selector `.ent-stages--carousel .ent-stages__slide`, especificidad **(0,2,0)**);
  `large` → 90% (0,3,0).

A ≤640px se cumplen **las dos** media queries a la vez. Para el caso **no-`large`**
compiten la regla @900 (50%, especificidad 0,3,0) y la @640 (85%, especificidad 0,2,0).
Las media queries no suman especificidad, así que **gana la de mayor especificidad (50%)**
pese a ir después en el fichero → **2 tarjetas estrechas en el móvil**. La regla de ≤640px
pensada para ~1 tarjeta nunca llega a aplicarse en no-`large`. El caso `large` funciona
(su regla @640 es 0,3,0 y va después).

`trip-collection` se emite **siempre** como no-`large` (`render.php` l. 78, sin clase
`--large`), por eso siempre falla en el teléfono. **Juanjo ha confirmado** que
`post-stages` en modo carrusel no-`large` sufre exactamente lo mismo (fichero compartido).

---

## 2. Artefactos y cambios

| Fichero | Punto | Cambio |
|---|---|---|
| `assets/css/carousel.css` | 4 | **Compartido.** Elevar la especificidad de la regla @640px no-`large` (una línea). |
| `assets/css/coleccion.css` | 1, 3 | Padding de `.trip-meta-i`; estilos nuevos `.trip-foot` / `.trip-arrow` + hover. |
| `blocks/trip-collection/render.php` | 2, 3 | Celda de ferry condicional + etiqueta singular; markup del footer con flecha. |

Clases nuevas (escopadas a `.ent-trip-collection`, en `coleccion.css`): `.trip-foot`,
`.trip-arrow`. No se crean atributos de bloque ni metadatos.

---

## 3. Decisiones de diseño fijadas (no reabrir sin motivo)

1. **Flecha (punto 3):** fila-footer fina, alineada a la derecha, **debajo** de la tira de
   cifras (visto bueno de Juanjo). Se replica el **aspecto** de `.post-card-arrow` pero con
   **clases propias escopadas** (`.trip-foot` / `.trip-arrow`) en `coleccion.css`, disparadas
   por `.trip-card:hover` — **no** se reutiliza la clase `.post-card-arrow` de `style.css`
   (su hover depende de un ancestro `.post-card` que aquí no existe, y así no se acopla a la
   portada ni se arriesga a afectarla). Flecha **decorativa** (`aria-hidden="true"`), un
   `<span>` (no un `<a>` anidado: la card ya es el enlace).
2. **Ferry (punto 2):** etiqueta en **singular** («Ferry»); celda **oculta** si
   `ferrys == 0`. **Solo** la de ferry: km y etapas se mantienen siempre (km ya pinta «—»
   si falta el dato).
3. **Ambos layouts:** los cambios 1–3 aplican a la card en **carrusel y timeline** porque
   `$card` es único. Es coherente (la card es enlace en los dos modos); **confirmar en
   validación** que la flecha y las cifras se ven bien también en timeline.
4. **Carrusel (punto 4):** se corrige **en origen** (camino A, decidido por Juanjo):
   elevar la especificidad de la regla @640px no-`large` a (0,3,0) para que gane por **orden
   de fuente** (va después de la @900). Es el **único** cambio en fichero compartido; los
   ficheros propios de `post-stages` **no se tocan** y `carousel.js` **no se toca**. Con esto
   el móvil (≤640px) muestra ~1 tarjeta con asomo en **ambos** bloques; la tablet (641–900px)
   se mantiene en 2 tarjetas (comportamiento no afectado, ver §5).

---

## 4. Requerimientos, agrupados por commit (troceado para validar por partes)

> Orden recomendado: primero el ancho (deja ver las cards a tamaño correcto en móvil) y
> luego los retoques de la card. `git add` por **nombre explícito** (nunca `-A`). Mensajes
> en formato *conventional commit* (afinar redacción según convención de la casa).

### Commit 1 — `fix(carousel): corregir especificidad del slide no-large en ≤640px`
Fichero: `assets/css/carousel.css`. **Cambiar únicamente la línea 306.**

Antes:
```css
  .ent-stages--carousel .ent-stages__slide { width: calc(85% - 8px); }
```
Después:
```css
  .ent-stages--carousel:not(.ent-stages--large) .ent-stages__slide { width: calc(85% - 8px); }
```
No tocar la línea 307 (`large` → 90%) ni ninguna otra regla del fichero.

### Commit 2 — `style(trip-collection): padding horizontal en las celdas de cifras`
Fichero: `assets/css/coleccion.css`, reglas de `.trip-meta-i` (l. 105–110).

Antes:
```css
.ent-trip-collection .trip-meta-i {
  flex: 1;
  padding: 10px 0;
  border-right: 1px solid var(--border);
}
.ent-trip-collection .trip-meta-i:last-child { border-right: none; }
```
Después:
```css
.ent-trip-collection .trip-meta-i {
  flex: 1;
  padding: 10px 0 10px 12px;
  border-right: 1px solid var(--border);
}
.ent-trip-collection .trip-meta-i:first-child { padding-left: 0; }
.ent-trip-collection .trip-meta-i:last-child { border-right: none; }
```
(`12px` es punto de partida, ajustable en validación; replica el patrón de `.f-stat`: la
primera celda se alinea con el borde de contenido de la card.)

### Commit 3 — `fix(trip-collection): ocultar celda de ferry sin conexiones y etiqueta singular`
Fichero: `blocks/trip-collection/render.php`, 3ª `.trip-meta-i` (l. 144–147).

Antes:
```php
                        <div class="trip-meta-i">
                            <div class="trip-meta-n"><?php echo intval( $data['ferrys'] ); ?></div>
                            <div class="trip-meta-l"><?php esc_html_e( 'Ferrys', 'enterprise-moto' ); ?></div>
                        </div>
```
Después:
```php
                        <?php if ( $data['ferrys'] > 0 ) : ?>
                        <div class="trip-meta-i">
                            <div class="trip-meta-n"><?php echo intval( $data['ferrys'] ); ?></div>
                            <div class="trip-meta-l"><?php esc_html_e( 'Ferry', 'enterprise-moto' ); ?></div>
                        </div>
                        <?php endif; ?>
```
(Con 2 celdas, `flex: 1` reparte al 50% y `:last-child { border-right: none }` recoloca el
borde solo. No se requiere CSS adicional.)

### Commit 4 — `feat(trip-collection): flecha-botón con hover en la card`
Dos ficheros.

**a) `blocks/trip-collection/render.php`** — insertar el footer **dentro de `.trip-body`,
justo después del cierre de `.trip-meta`** (tras l. 148, antes del `</div>` que cierra
`.trip-body` en l. 149):

```php
                    <div class="trip-foot">
                        <span class="trip-arrow" aria-hidden="true">→</span>
                    </div>
```

**b) `assets/css/coleccion.css`** — añadir (junto al resto de reglas de la card):

```css
.ent-trip-collection .trip-foot {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}
.ent-trip-collection .trip-arrow {
  width: 32px; height: 32px;
  background: var(--black); color: var(--white);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; flex-shrink: 0;
  transition: background .2s, color .2s;
}
.ent-trip-collection .trip-card:hover .trip-arrow {
  background: var(--gold); color: var(--black);
}
```

### Cierre (arquitecto, SOLO tras validar — no lo hace el desarrollador)
Tras la validación de Juanjo en WordPress real: **bump de parche 2.6.0 → 2.6.1** en las
**tres** fuentes canónicas a la vez (`style.css` header `Version`, `ENTERPRISE_VERSION` en
`functions.php`, cabecera de `bitacora-enterprise-design.md`) y nota breve del fix de
especificidad del carrusel en la documentación. Reconciliación de `TODO.md` si Juanjo decide
numerar el punto 4 como `fix`.

---

## 5. Fuera de alcance (para no inferir requisitos no pedidos)

- **No** ocultar las celdas de km ni de etapas (solo la de ferry).
- **No** tocar `assets/js/carousel.js` ni los ficheros propios de `post-stages`
  (`blocks/post-stages/*`).
- **No** re-estilizar `post-stages` más allá de la única línea corregida en `carousel.css`.
- **No** cambiar el comportamiento en **tablet**: la franja 641–900px se mantiene en 2
  tarjetas; el fix solo corrige ≤640px.
- El desarrollador **no** sube versión ni edita `TODO.md` / el documento de diseño (eso es
  del arquitecto, al cierre).

---

## 6. Recordatorios de método y validación

- **Entrega:** ficheros **completos** para descargar + comandos git (`add` por nombre
  explícito, `commit` convencional, `push` a la cuenta personal). Nada de fragmentos para
  empalmar a mano. Un commit por punto.
- **Red de seguridad:** el repo está limpio en `30657ff`; aun así, commit + push del estado
  actual antes de empezar si se prefiere punto de restauración.
- **Validación de Juanjo (en WordPress real):**
  - *Commit 1:* en el móvil, abrir una página con `trip-collection` (carrusel) **y** una
    entrada con `post-stages` (carrusel no-`large`): confirmar **1 tarjeta con asomo** en
    ambos. En tablet (~700px) siguen viéndose 2 (esperado).
  - *Commit 2:* el texto se separa de los separadores; la primera celda queda alineada con
    el borde de contenido de la card.
  - *Commit 3:* un viaje con 0 ferries **no** muestra la celda; con ferries muestra «Ferry»
    en singular y las 2 celdas restantes se reparten bien.
  - *Commit 4:* flecha negra que pasa a **dorada** al situar el ratón sobre la card; visible
    y correcta en **carrusel y timeline**.
