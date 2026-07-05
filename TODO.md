# TODO — Enterprise Moto

Fuente **persistente** de pendientes del tema. Es código versionado en el
repositorio (se consolida en git como el resto). La lista de trabajo en memoria de
cada sesión se sincroniza con este fichero mediante los comandos `create` / `add` /
`list` / `export` / `clear TO-DOs`.

Cada pendiente lleva un **tipo** que indica su propósito: `mejora`, `fix`, `doc` u `otro`.
Los que aún no tienen propósito decidido quedan como `(sin clasificar)`.

## Pendientes

| # | Tipo | Descripción | Estado |
|---|------|-------------|--------|
| 1 | doc | Crear la sección **"Decisiones de arquitectura"** en `bitacora-enterprise-design.md` y sembrarla con las tres decisiones ya tomadas: contrato de navegación (§6), contención de floats del cuaderno (§6) y estructura de permalinks (§6). | pendiente |

## Resueltas

| Tipo | Descripción | Resuelto en |
|------|-------------|-------------|
| otro | Trasladar el seguimiento de TO-DOs a este `TODO.md` independiente y versionado (antes vivía en la sección "Mejoras pendientes" del design doc) y crear aquí la sección "Resueltas" como destino de lo completado. | Reorganización de TO-DOs (jul 2026) |
