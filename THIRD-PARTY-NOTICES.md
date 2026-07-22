# Avisos de terceros — Bitácora Enterprise

El tema `enterprise-moto` es una obra original, pero **incluye o utiliza** componentes de
terceros que conservan su propia licencia, independiente de la GPL-3.0-or-later del código
del tema. Se listan aquí para cumplir sus condiciones de atribución.

## Componentes empaquetados en el repositorio

| Componente | Ubicación | Licencia | Copyright |
|---|---|---|---|
| **Parsedown** | `enterprise-moto/inc/Parsedown.php` | MIT | © Emanuil Rusev, erusev.com |

> **Estado (verificado, jul 2026):**
>
> - **Parsedown — en uso.** Lo requieren e instancian los bloques propios
>   `enterprise/markdown` y `enterprise/markdown-styled` (`inc/Parsedown.php`, con
>   `setSafeMode(true)`) como conversor de Markdown → HTML. Se mantiene mientras esos
>   bloques existan; conserva su aviso MIT (© Emanuil Rusev), presente en la cabecera del
>   propio fichero.

## Componentes cargados desde CDN (no empaquetados)

Estos se sirven al navegador desde terceros y no se redistribuyen en el repositorio, pero
se documentan por transparencia (y por su relevancia para la política de privacidad):

| Componente | Origen | Licencia |
|---|---|---|
| **OpenLayers** | `cdn.jsdelivr.net` | BSD-2-Clause |
| **Fuentes** (Bebas Neue, DM Sans, DM Serif Display) | `fonts.googleapis.com` (Google Fonts) | SIL Open Font License 1.1 |

## WordPress

El tema está diseñado para ejecutarse sobre **WordPress** (GPL-2.0-or-later) y es una obra
derivada de este a efectos de la parte PHP, lo que motiva su licencia GPL.
