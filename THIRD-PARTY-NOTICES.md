# Avisos de terceros — Bitácora Enterprise

El tema `enterprise-moto` es una obra original, pero **incluye o utiliza** componentes de
terceros que conservan su propia licencia, independiente de la GPL-3.0-or-later del código
del tema. Se listan aquí para cumplir sus condiciones de atribución.

## Componentes empaquetados en el repositorio

| Componente | Ubicación | Licencia | Copyright |
|---|---|---|---|
| **Parsedown** | `enterprise-moto/inc/Parsedown.php` | MIT | © Emanuil Rusev, erusev.com |
| **Leaflet** | `enterprise-moto/assets/vendor/leaflet/` | BSD-2-Clause | © Vladimir Agafonkin, CloudMade y colaboradores de Leaflet |

> **Nota de mantenimiento:** el motor de mapas activo del tema es **OpenLayers** (ver el
> documento de diseño §8 y el TODO #16), no Leaflet. Si se confirma que Leaflet ya no se
> usa, su retirada sería una limpieza (TO-DO nuevo); mientras siga empaquetado, debe
> conservar su licencia. **Pendiente:** ninguna de las dos librerías conserva hoy un
> fichero de licencia junto al código empaquetado; conviene añadir el texto de licencia
> original de cada una en su carpeta (o mantener este aviso como registro).

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
