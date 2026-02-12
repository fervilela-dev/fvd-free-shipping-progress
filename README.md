# fvd-free-shipping-progress
Free shipping progress bar.

## Changelog

- **1.0.7** — El auto-update ahora usa el asset ZIP del release (`fvd-free-shipping-progress.zip`) en lugar del `zipball_url` para evitar carpetas con hash que rompen el plugin. Si el asset falta, no se ofrece actualización.
- **1.0.6** — El checker limpia caché cuando pulsas “Buscar actualizaciones” y reduce el cacheo del release a 1h para detectar tags nuevos enseguida.
- **1.0.5** — La barra ahora se muestra en Woocommerce Side Cart Premium (xoo-wsc) enganchándonos a varios hooks y evitando duplicados.
- **1.0.4** — Auto‑actualización nativa: el plugin consulta el último release en GitHub y muestra la actualización directamente en WordPress (sin depender de plugins externos). Versions cacheadas 6h.
- **1.0.3** — Añadido metadato `Primary Branch: main` en el header del plugin para compatibilidad con actualizadores GitHub.
- **1.0.2** — Añadido encabezado `GitHub Plugin URI` apuntando al repositorio para facilitar updates vía GitHub Updater.
- **1.0.1** — Añadida integración con Xootix Side Cart (`xoo-wsc`) y carga obligatoria de assets para mini-cart; estilos específicos para el modal.
- **1.0.0** — Versión inicial.

## Cómo publicar una actualización

1. Genera un release en GitHub con tag `vX.Y.Z` (ej: `v1.0.7`) en `fervilela-dev/fvd-free-shipping-progress`.  
2. Adjunta un asset ZIP llamado **`fvd-free-shipping-progress.zip`** que contenga la carpeta del plugin en la raíz. Ese es el paquete que WordPress descargará.  
3. El checado se cachea por 1 h; si necesitas forzar, pulsa “Buscar actualizaciones” en la pantalla de plugins o borra el transient `fvd_freeship_update_payload` (WP-CLI: `wp transient delete --network fvd_freeship_update_payload` o `wp transient delete fvd_freeship_update_payload`).
