# Icontec Maintenance Mode

Plugin de **Ventana de Mantenimiento** para WordPress con personalización visual, control por roles, programación automática, donaciones y multilenguaje.

## Estructura

- Archivo principal: `maintenance-mode.php`
- Módulos: `includes/class-im-settings.php`, `class-im-admin.php`, `class-im-frontend.php`, `class-im-cron.php`
- Plantilla de mantenimiento: `template/maintenance-template.php`
- Assets: `assets/css/*` y `assets/js/*`
- Traducciones: `languages/` (`.po`, `.mo` y `.l10n.php` para es_ES)

## Instalación

1. Copiar la carpeta `maintenance-mode` en `wp-content/plugins/`
2. Activar el plugin desde el panel de *Plugins*.
3. Ir al menú **Ventana de Mantenimiento** en el admin.

## Funcionalidades

### Apariencia
- Título y mensaje editables.
- Colores personalizables (fondo, texto, acento/botones) con selector de color.
- Logo corporativo e imagen de fondo desde la biblioteca de medios.
- Mostrar fechas de mantenimiento a los visitantes.
- Selector de idioma del plugin (Español / Inglés / predeterminado del sitio).

### Roles
- Selección de roles que pueden seguir navegando el sitio durante el mantenimiento.
- Los administradores siempre tienen acceso.
- Rol dedicado `maintenance-mode` creado en la activación.

### Programación (cron jobs)
- Fecha/hora de inicio y fin usando WP Cron (`wp_schedule_single_event`).
- Activación y desactivación automáticas en la zona horaria del sitio.
- Activación/desactivación manual instantánea desde el panel (solo administradores, con nonce).
- Indicador "Maintenance ON" en la barra de administración mientras está activo.

### Donaciones ☕
- Sección "Invítame un café" con animación.
- Integración con PayPal (JS SDK / REST): configura tu Client ID desde
  [developer.paypal.com/api/rest](https://developer.paypal.com/api/rest).
- Montos rápidos y selección de moneda (USD, EUR, MXN, COP).

## Comportamiento técnico

- Bloquea las páginas públicas cuando el mantenimiento está activo (manual o programado).
- Devuelve `503 Service Unavailable` con `Retry-After` calculado hasta el fin programado.
- No bloquea `wp-login.php`, AJAX, REST ni el panel de administración.
- Seguridad: nonces (`wp_nonce_field` / `check_admin_referer`), sanitización (`sanitize_*`, `esc_*`) y comprobación de capacidades en cada acción.
- Configuración almacenada en una sola opción (`im_settings`); migra automáticamente las opciones de la v1.
