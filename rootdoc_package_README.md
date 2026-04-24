# Vehiclescheduler root/subdir compatibility package

## Files

- `glpi-root.conf.example`: Apache vhost example for GLPI at the web root.
- `glpi-subdir.conf.example`: Apache vhost example for GLPI under `/glpi`.
- `plugin_vehiclescheduler_url.php`: central helper for GLPI base-path-safe URLs.
- `vehiclescheduler-rootdoc-compat.patch`: minimal PHP patch for `front/management.php`.

## Suggested plugin placement

Copy the helper to:

- `inc/url.php`

## Suggested patch application

From the plugin root:

```bash
git apply vehiclescheduler-rootdoc-compat.patch
```

## Validation

```bash
php -l inc/url.php
php -l front/management.php
sudo apachectl -t
sudo systemctl reload httpd
```
