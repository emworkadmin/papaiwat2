#!/bin/bash
set -e

# Replace DB placeholders in application.ini files with environment variables
# Work on copies so the mounted source files are not modified
for ini_file in /var/www/html/applicationfront/configs/application.ini /var/www/html/applicationadmin/configs/application.ini; do
    if [ -f "$ini_file" ]; then
        # Restore placeholders first (idempotent for repeated restarts)
        sed -i 's|resources.db.params.host="[^"]*"|resources.db.params.host=DB_HOST|g' "$ini_file"
        sed -i 's|resources.db.params.username="[^"]*"|resources.db.params.username=DB_USERNAME|g' "$ini_file"
        sed -i 's|resources.db.params.password="[^"]*"|resources.db.params.password=DB_PASSWORD|g' "$ini_file"
        sed -i 's|resources.db.params.dbname="[^"]*"|resources.db.params.dbname=DB_DATABASE|g' "$ini_file"
        # Remove any previously injected port line
        sed -i '/resources.db.params.port=/d' "$ini_file"

        # Now replace placeholders with env values
        sed -i "s|resources.db.params.host=DB_HOST|resources.db.params.host=\"${DB_HOST:-localhost}\"|g" "$ini_file"
        sed -i "s|resources.db.params.username=DB_USERNAME|resources.db.params.username=\"${DB_USERNAME:-root}\"|g" "$ini_file"
        sed -i "s|resources.db.params.password=DB_PASSWORD|resources.db.params.password=\"${DB_PASSWORD}\"|g" "$ini_file"
        sed -i "s|resources.db.params.dbname=DB_DATABASE|resources.db.params.dbname=\"${DB_DATABASE:-d2ms_papaiwat_db}\"|g" "$ini_file"

        # Add port
        if [ -n "$DB_PORT" ]; then
            sed -i "/resources.db.params.charset/a resources.db.params.port=\"${DB_PORT}\"" "$ini_file"
        fi
    fi
done

exec "$@"
