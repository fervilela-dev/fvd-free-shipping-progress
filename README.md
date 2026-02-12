# fvd-free-shipping-progress
Free shipping progress bar.

## Changelog

- **1.0.4** — Auto‑actualización nativa: el plugin consulta el último release en GitHub y muestra la actualización directamente en WordPress (sin depender de plugins externos). Versions cacheadas 6h.
- **1.0.3** — Añadido metadato `Primary Branch: main` en el header del plugin para compatibilidad con actualizadores GitHub.
- **1.0.2** — Añadido encabezado `GitHub Plugin URI` apuntando al repositorio para facilitar updates vía GitHub Updater.
- **1.0.1** — Añadida integración con Xootix Side Cart (`xoo-wsc`) y carga obligatoria de assets para mini-cart; estilos específicos para el modal.
- **1.0.0** — Versión inicial.

## Cómo publicar una actualización

1. Genera un release en GitHub con tag `vX.Y.Z` (ej: `v1.0.4`) en `fervilela-dev/fvd-free-shipping-progress`.  
2. Almacenará el zip automático de GitHub; WordPress lo leerá y mostrará la actualización en Plugins → Actualizaciones.  
3. El checado se cachea por 6 h; si necesitas forzar, presiona “Buscar actualizaciones” en la pantalla de plugins o ejecuta `wp transient delete --all` vía WP-CLI.
