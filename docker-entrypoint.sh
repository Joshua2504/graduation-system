#!/bin/bash
set -e

# Fix ownership on bind-mounted directories
chown -R www-data:www-data /var/lib/php/sessions
chown -R www-data:www-data /var/www/html/public/uploads

# Run the default Apache entrypoint
exec apache2-foreground
