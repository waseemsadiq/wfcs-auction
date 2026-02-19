#!/bin/bash
# Build script for Shared Hosting Distribution
# Creates a dist/ folder ready to upload to IONOS (or any LAMP server)

# Ensure we are in the script directory (auction/)
cd "$(dirname "$0")"

echo "Building dist folder for shared hosting..."
echo ""

# --- Build fresh CSS ---
echo "Building Tailwind CSS..."
npm run build:css
echo "CSS built."
echo ""

# Preserve existing configs if the customer already edited them
if [ -f "dist/config/app.php" ]; then
    cp dist/config/app.php /tmp/auction_app_config_backup.php
fi
if [ -f "dist/config/database.php" ]; then
    cp dist/config/database.php /tmp/auction_db_config_backup.php
fi
if [ -f "dist/config/env.php" ]; then
    cp dist/config/env.php /tmp/auction_env_config_backup.php
fi

rm -rf dist
mkdir dist

# Copy app files (only runtime essentials)
# Exclusions are maintained in .distignore — edit that file, not this one
rsync -a . dist/ --exclude-from='.distignore'

# Root .htaccess is excluded by the '.*' rule but is required for routing
cp .htaccess dist/.htaccess

# --- Install production PHP dependencies ---
echo "Installing production dependencies (composer install --no-dev)..."
cp composer.json dist/
cp composer.lock dist/
composer install --no-dev --prefer-dist --quiet --working-dir=dist/
rm dist/composer.json dist/composer.lock
echo "  Installed vendor/ (PHPMailer only — no dev deps)"

# --- Swap Database class for shared hosting (TCP + native prepares) ---
mv dist/core/Database-shared.php dist/core/Database.php
echo "Swapped Database class (TCP + native prepares for standard MySQL)"

# --- Swap config files ---
echo "Applying shared hosting configurations..."

# App config
if [ -f "/tmp/auction_app_config_backup.php" ]; then
    mv /tmp/auction_app_config_backup.php dist/config/app.php
    echo "  Restored existing app.php (preserved your settings)"
elif [ -f "dist/config/app-shared.example.php" ]; then
    mv dist/config/app-shared.example.php dist/config/app.php
    echo "  Applied shared hosting app config template"
fi

# Database config
if [ -f "/tmp/auction_db_config_backup.php" ]; then
    mv /tmp/auction_db_config_backup.php dist/config/database.php
    echo "  Restored existing database.php (preserved credentials)"
elif [ -f "dist/config/database-shared.example.php" ]; then
    mv dist/config/database-shared.example.php dist/config/database.php
    echo "  Applied shared hosting database config template"
fi

# Env config (Stripe keys, SMTP — populates $_ENV on shared hosting)
if [ -f "/tmp/auction_env_config_backup.php" ]; then
    mv /tmp/auction_env_config_backup.php dist/config/env.php
    echo "  Restored existing env.php (preserved your secrets)"
elif [ -f "dist/config/env-shared.example.php" ]; then
    mv dist/config/env-shared.example.php dist/config/env.php
    echo "  Applied shared hosting env config template"
fi

# Clean up shared templates from dist (don't want them on live)
rm -f dist/config/app-shared.example.php
rm -f dist/config/database-shared.example.php
rm -f dist/config/env-shared.example.php


# --- Create protected directories ---
mkdir -p dist/uploads
mkdir -p dist/storage/sessions
mkdir -p dist/logs

# Protect directories with .htaccess
echo "Deny from all" > dist/storage/.htaccess
echo "Deny from all" > dist/logs/.htaccess

# Hotlink protection for uploads and images — allow same-origin and empty referer only
HOTLINK_HTACCESS='Options -Indexes
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_REFERER} !^$
    RewriteCond %{HTTP_REFERER} !^https?://%{SERVER_NAME}[/:]  [NC]
    RewriteRule \.(jpe?g|png|gif|webp|svg|ico)$  -  [F,NC,L]
</IfModule>'

mkdir -p dist/uploads dist/images
printf '%s\n' "$HOTLINK_HTACCESS" > dist/uploads/.htaccess
printf '%s\n' "$HOTLINK_HTACCESS" > dist/images/.htaccess


# --- Database installer ---
echo ""
echo "Database for auction-install.sql:"
echo "  1) Seed data (database/seeds.sql)"
echo "  2) Live snapshot (dump from running Galvani database)"
echo "  3) Schema only (no seeds — safe to roll over live database)"
echo ""
read -r -p "Choose [1/2/3]: " db_choice

SOCKET="../data/mysql.sock"
DB_HOST="127.0.0.1"
DB_PORT="3306"
SCHEMA="database/schema.sql"
SEEDS="database/seeds.sql"

# Helper: run mysqldump with correct connection (socket → TCP fallback)
run_mysqldump() {
    if [ -S "$SOCKET" ]; then
        mysqldump --socket="$SOCKET" -u root --skip-ssl "$@" 2>&1
    else
        mysqldump --host="$DB_HOST" --port="$DB_PORT" -u root --skip-ssl "$@" 2>&1
    fi
}

# Helper: check dump succeeded (non-empty, no error messages)
dump_ok() {
    [ -s "$1" ] && ! grep -q "^mysqldump:" "$1"
}

# Shared preamble for installer (FK checks stay OFF — re-enabled at end of file)
write_preamble() {
    local tables
    tables=$(grep -ioE 'CREATE TABLE (IF NOT EXISTS )?\S+' "$SCHEMA" | awk '{print $NF}' | tr '\n' ',' | sed 's/,$//')
    echo "SET FOREIGN_KEY_CHECKS=0;"
    echo ""
    echo "DROP TABLE IF EXISTS $tables;"
    echo ""
    cat "$SCHEMA"
    echo ""
}

# Shared postamble — re-enable FK checks at the very end
write_postamble() {
    echo ""
    echo "SET FOREIGN_KEY_CHECKS=1;"
}

if [ "$db_choice" = "2" ]; then

    # --- Sync source files with live database ---
    echo "Connecting to live database..."
    if [ -S "$SOCKET" ]; then
        echo "  Via socket: $SOCKET"
    else
        echo "  Via TCP: $DB_HOST:$DB_PORT"
    fi

    # Dump live schema (structure only, clean output)
    run_mysqldump --no-data --skip-comments --skip-add-drop-table \
        --skip-add-locks --skip-lock-tables --compact \
        auction > /tmp/auction_live_schema_raw.sql

    # Dump live data (inserts only)
    run_mysqldump --no-create-info --skip-triggers --skip-comments \
        --complete-insert --skip-add-locks --skip-lock-tables --compact \
        auction > /tmp/auction_live_data.sql

    # Validate both dumps
    if ! dump_ok /tmp/auction_live_schema_raw.sql; then
        echo "  ERROR: schema dump failed:"
        cat /tmp/auction_live_schema_raw.sql
        rm -f /tmp/auction_live_schema_raw.sql /tmp/auction_live_data.sql
        exit 1
    fi
    if ! dump_ok /tmp/auction_live_data.sql; then
        echo "  ERROR: data dump failed:"
        cat /tmp/auction_live_data.sql
        rm -f /tmp/auction_live_schema_raw.sql /tmp/auction_live_data.sql
        exit 1
    fi

    # Clean up schema: add IF NOT EXISTS, strip AUTO_INCREMENT counters, add header
    {
        echo "-- WFCS Auction - Database Schema"
        echo "-- This schema supports both Galvani and LAMP environments"
        echo ""
        sed -E \
            -e 's/CREATE TABLE (`?)/CREATE TABLE IF NOT EXISTS \1/g' \
            -e 's/ AUTO_INCREMENT=[0-9]+//g' \
            /tmp/auction_live_schema_raw.sql
    } > /tmp/auction_live_schema.sql
    rm -f /tmp/auction_live_schema_raw.sql

    # Compare and update schema.sql
    if ! diff -q /tmp/auction_live_schema.sql "$SCHEMA" > /dev/null 2>&1; then
        cp /tmp/auction_live_schema.sql "$SCHEMA"
        echo "  Updated database/schema.sql (live schema differs)"
    else
        echo "  database/schema.sql is up to date"
    fi
    rm -f /tmp/auction_live_schema.sql

    # Compare and update seeds.sql
    if ! diff -q /tmp/auction_live_data.sql "$SEEDS" > /dev/null 2>&1; then
        cp /tmp/auction_live_data.sql "$SEEDS"
        echo "  Updated database/seeds.sql (live data differs)"
    else
        echo "  database/seeds.sql is up to date"
    fi

    # --- Build installer from (now-synced) source files ---
    echo ""
    echo "Building auction-install.sql..."
    {
        echo "-- WFCS Auction - Complete Database Installer (live snapshot)"
        echo "-- Generated: $(date '+%Y-%m-%d %H:%M:%S')"
        echo "-- Safe to re-run: drops and recreates all tables"
        echo ""
        write_preamble
        echo "-- Data"
        echo ""
        cat /tmp/auction_live_data.sql
        write_postamble
    } > dist/auction-install.sql
    rm -f /tmp/auction_live_data.sql
    echo "  Created dist/auction-install.sql"

elif [ "$db_choice" = "3" ]; then

    # --- Schema only (safe for production rollover) ---
    echo ""
    echo "Building auction-install.sql (schema only)..."
    {
        echo "-- WFCS Auction - Database Schema Update"
        echo "-- Generated: $(date '+%Y-%m-%d %H:%M:%S')"
        echo "-- SAFE FOR PRODUCTION: Updates schema without dropping existing data"
        echo "-- All tables use 'IF NOT EXISTS' for safe rollover"
        echo ""
        write_preamble
        write_postamble
    } > dist/auction-install.sql
    echo "  Created dist/auction-install.sql (schema only — no data loss)"

else

    # --- Seed data (default) ---
    if [ -f "$SCHEMA" ] && [ -f "$SEEDS" ]; then
        {
            echo "-- WFCS Auction - Complete Database Installer (seed data)"
            echo "-- Generated: $(date '+%Y-%m-%d %H:%M:%S')"
            echo "-- Safe to re-run: drops and recreates all tables"
            echo ""
            write_preamble
            echo "-- Seed Data"
            echo ""
            cat "$SEEDS"
            write_postamble
        } > dist/auction-install.sql
        echo "  Combined schema + seeds into auction-install.sql"
    fi

fi

# Gzip for phpMyAdmin (accepts .sql.gz natively, bypasses upload size limits)
if [ -f "dist/auction-install.sql" ]; then
    gzip -kf dist/auction-install.sql
    echo "  Created dist/auction-install.sql.gz ($(du -h dist/auction-install.sql.gz | cut -f1) compressed)"
fi

# --- Copy item images (only if not schema-only) ---
if [ "$db_choice" = "3" ]; then
    echo ""
    echo "Skipping uploads (schema-only build)..."
else
    echo ""
    echo "Packaging uploads..."
    if [ -d "uploads" ]; then
        rsync -a --exclude='.gitkeep' uploads/ dist/uploads/
        echo "  Copied uploads/"
    else
        echo "  No uploads/ directory found — skipping"
    fi
fi

echo ""
echo "Build complete!"
echo "Distribution ready in: dist/"
echo ""
echo "Next steps:"
echo "1. Edit dist/config/database.php (set your MySQL credentials)"
echo "2. Edit dist/config/app.php (set APP_KEY, JWT_SECRET, domain)"
echo "3. Edit dist/config/env.php (set Stripe keys, SMTP credentials)"
echo "4. Import dist/auction-install.sql.gz via phpMyAdmin (or .sql via CLI)"
echo "5. Upload contents of dist/ to public_html/bid/ (or your chosen folder)"
echo "6. Ensure uploads/ and storage/ directories are writable (chmod 755)"
echo ""

if [ "$db_choice" = "3" ]; then
    echo "NOTE: You chose schema-only. This updates the database structure"
    echo "without dropping existing data or truncating tables. Perfect for"
    echo "rolling out code updates to production."
    echo ""
elif [ "$db_choice" = "2" ]; then
    echo "NOTE: You chose live snapshot. The database will be recreated from"
    echo "your current Galvani database. All existing data is preserved."
    echo ""
else
    echo "NOTE: You chose seed data. The database will be populated with"
    echo "the default seed data from database/seeds.sql."
    echo ""
fi

echo "IMPORTANT: After importing the database, log in as admin and re-save"
echo "your Stripe keys at Settings > Payments. The database dump contains keys"
echo "encrypted with your LOCAL APP_KEY — the live server needs its own keys."
