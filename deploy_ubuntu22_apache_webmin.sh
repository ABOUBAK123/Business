#!/usr/bin/env bash
set -euo pipefail

# ============================================================================
# Laravel deploy script - Ubuntu 22.04 + Apache (Webmin friendly)
# Usage:
APP_DIR="/var/www/html/e-administration"
REPO_URL="https://github.com/ABOUBAK123/E-parapheur.git"
REPO_BRANCH="main"
USE_GIT="1"                               # 1=clone/pull depuis Git, 0=code déjà copié sur le serveur
DOMAIN="e-administration.gedsante.ci"
DOMAIN_WWW="www.e-administration.gedsante.ci"

#   1) Edit variables in "CONFIGURATION" section
#   2) sudo bash deploy_ubuntu22_apache_webmin.sh
# ============================================================================

# ----------------------------- CONFIGURATION ---------------------------------
APP_NAME="e-administration"
DB_CONNECTION="mysql"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_DATABASE="e_parapheur"
DB_USERNAME="e_parapheur_user"         # <-- à renseigner ou garder root en dev
DB_PASSWORD="change_me"                # <-- à changer

# Set to 1 to request Let's Encrypt certificate automatically
ENABLE_SSL="1"
LETSENCRYPT_EMAIL="jjkam555@yahoo.ci"  # <-- ton email pour les alertes SSL
# -----------------------------------------------------------------------------

if [[ "$EUID" -ne 0 ]]; then
  echo "Run as root: sudo bash $0"
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "[1/11] Installing system packages..."
apt-get update -y
apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https curl git unzip
add-apt-repository ppa:ondrej/php -y
apt-get update -y
apt-get install -y apache2 libapache2-mod-fcgid php8.2 php8.2-cli php8.2-common php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd php8.2-soap

echo "[1b/11] Configuring PHP upload limits..."
cat >/etc/php/8.2/fpm/conf.d/99-e-administration-upload.ini <<'PHPINI'
upload_max_filesize = 20M
post_max_size = 64M
max_file_uploads = 20
memory_limit = 512M
PHPINI

cat >/etc/php/8.2/cli/conf.d/99-e-administration-upload.ini <<'PHPINI'
upload_max_filesize = 20M
post_max_size = 64M
max_file_uploads = 20
memory_limit = 512M
PHPINI

if ! command -v composer >/dev/null 2>&1; then
  echo "[2/11] Installing Composer..."
  curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
fi

if [[ "$USE_GIT" == "1" ]]; then
  if [[ "$REPO_URL" == "https://github.com/your-org/your-repo.git" ]]; then
    echo "REPO_URL is still the placeholder. Set REPO_URL or set USE_GIT=0."
    exit 1
  fi

  if [[ ! -d "$APP_DIR/.git" ]]; then
    echo "[3/11] Cloning repository..."
    mkdir -p "$(dirname "$APP_DIR")"
    git clone --branch "$REPO_BRANCH" "$REPO_URL" "$APP_DIR"
  else
    echo "[3/11] Pulling latest code..."
    git -C "$APP_DIR" fetch --all --prune
    git -C "$APP_DIR" checkout "$REPO_BRANCH"
    git -C "$APP_DIR" pull --ff-only origin "$REPO_BRANCH"
  fi
else
  echo "[3/11] Git step skipped (USE_GIT=0)."
  if [[ ! -f "$APP_DIR/artisan" ]]; then
    echo "No Laravel project found in $APP_DIR (missing artisan)."
    exit 1
  fi
fi

cd "$APP_DIR"

echo "[4/11] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[4b/11] Ensuring PDF.js assets..."
mkdir -p "$APP_DIR/public/vendor/pdfjs"
if [[ ! -f "$APP_DIR/public/vendor/pdfjs/pdf.min.js" ]]; then
  curl -fsSL https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js -o "$APP_DIR/public/vendor/pdfjs/pdf.min.js"
fi
if [[ ! -f "$APP_DIR/public/vendor/pdfjs/pdf.worker.min.js" ]]; then
  curl -fsSL https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js -o "$APP_DIR/public/vendor/pdfjs/pdf.worker.min.js"
fi

if [[ ! -f .env ]]; then
  echo "[5/11] Creating .env from .env.example..."
  cp .env.example .env
fi

echo "[5/11] Updating .env values..."
DEPLOY_APP_URL="https://${DOMAIN}" \
DEPLOY_DB_CONNECTION="$DB_CONNECTION" \
DEPLOY_DB_HOST="$DB_HOST" \
DEPLOY_DB_PORT="$DB_PORT" \
DEPLOY_DB_DATABASE="$DB_DATABASE" \
DEPLOY_DB_USERNAME="$DB_USERNAME" \
DEPLOY_DB_PASSWORD="$DB_PASSWORD" \
DEPLOY_APP_LOCALE="fr" \
DEPLOY_APP_FALLBACK_LOCALE="fr" \
php -r '
$envPath = ".env";
$vars = [
  "APP_ENV" => "production",
  "APP_DEBUG" => "false",
  "APP_URL" => getenv("DEPLOY_APP_URL"),
  "APP_LOCALE" => getenv("DEPLOY_APP_LOCALE"),
  "APP_FALLBACK_LOCALE" => getenv("DEPLOY_APP_FALLBACK_LOCALE"),
  "DB_CONNECTION" => getenv("DEPLOY_DB_CONNECTION"),
  "DB_HOST" => getenv("DEPLOY_DB_HOST"),
  "DB_PORT" => getenv("DEPLOY_DB_PORT"),
  "DB_DATABASE" => getenv("DEPLOY_DB_DATABASE"),
  "DB_USERNAME" => getenv("DEPLOY_DB_USERNAME"),
  "DB_PASSWORD" => getenv("DEPLOY_DB_PASSWORD"),
];
$c = file_get_contents($envPath);
foreach ($vars as $k => $v) {
  if ($v === false || $v === null || $v === "") continue;
  $line = $k . "=" . $v;
  if (preg_match("/^" . preg_quote($k, "/") . "=.*/m", $c)) {
    $c = preg_replace("/^" . preg_quote($k, "/") . "=.*/m", $line, $c);
  } else {
    $c .= "\n" . $line;
  }
}
file_put_contents($envPath, $c);
'

if ! grep -q '^APP_KEY=' .env || grep -q '^APP_KEY=$' .env; then
  php artisan key:generate --force
fi

echo "[6/11] Laravel optimize/migrate..."
php artisan migrate --force
php artisan storage:link || true
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Keep OnlyOffice public URL in sync with APP_URL
DEPLOY_APP_URL="https://${DOMAIN}" php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();
\App\Models\AppSetting::updateOrCreate(
  ["key" => "app_public_url"],
  ["value" => getenv("DEPLOY_APP_URL"), "description" => "URL publique application (OnlyOffice callback/docUrl)"]
);
echo "app_public_url updated\n";
'

echo "[7/11] Fixing permissions..."
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R ug+rwx "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "[8/11] Configuring Apache vhost..."
cat >/etc/apache2/sites-available/${APP_NAME}.conf <<APACHECONF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias ${DOMAIN_WWW}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${APP_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${APP_NAME}-access.log combined
</VirtualHost>
APACHECONF

a2enmod rewrite headers proxy_fcgi setenvif
a2enconf php8.2-fpm || true
a2ensite "${APP_NAME}.conf"
a2dissite 000-default.conf || true
apache2ctl -t
systemctl restart php8.2-fpm
systemctl reload apache2

echo "[9/11] Configuring scheduler cron..."
if ! crontab -u www-data -l 2>/dev/null | grep -q 'schedule:run'; then
  (crontab -u www-data -l 2>/dev/null; echo "* * * * * cd ${APP_DIR} && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
fi

if [[ "$ENABLE_SSL" == "1" ]]; then
  echo "[10/11] Enabling Let's Encrypt SSL..."
  apt-get install -y certbot python3-certbot-apache
  certbot --apache --non-interactive --agree-tos -m "$LETSENCRYPT_EMAIL" -d "$DOMAIN" -d "$DOMAIN_WWW" --redirect
else
  echo "[10/11] SSL step skipped (ENABLE_SSL=0)."
fi

echo "[11/11] Running post-install checks..."

DB_CHECK="FAIL"
if php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();
\Illuminate\Support\Facades\DB::connection()->getPdo();
echo "DB_OK\n";
' >/tmp/eparapheur_db_check.log 2>&1; then
  DB_CHECK="OK"
  echo "[CHECK] Database connection: OK"
else
  echo "[CHECK] Database connection: FAIL"
  cat /tmp/eparapheur_db_check.log || true
fi

APP_HTTP_CODE="000"
if command -v curl >/dev/null 2>&1; then
  APP_HTTP_CODE="$(curl -k -s -o /dev/null -w "%{http_code}" "https://${DOMAIN}" || echo 000)"
fi

if [[ "$APP_HTTP_CODE" =~ ^(200|301|302)$ ]]; then
  echo "[CHECK] Public URL https://${DOMAIN}: OK (HTTP ${APP_HTTP_CODE})"
else
  echo "[CHECK] Public URL https://${DOMAIN}: WARN (HTTP ${APP_HTTP_CODE})"
fi

OO_URL="$(php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap();
echo (string) (\App\Models\AppSetting::where("key", "onlyoffice_server_url")->value("value") ?: "");
' 2>/dev/null || true)"

if [[ -n "$OO_URL" ]]; then
  OO_HEALTH_CODE="$(curl -k -s -o /dev/null -w "%{http_code}" "${OO_URL%/}/healthcheck" || echo 000)"
  if [[ "$OO_HEALTH_CODE" == "200" ]]; then
    echo "[CHECK] OnlyOffice healthcheck ${OO_URL%/}/healthcheck: OK (HTTP 200)"
  else
    echo "[CHECK] OnlyOffice healthcheck ${OO_URL%/}/healthcheck: WARN (HTTP ${OO_HEALTH_CODE})"
  fi
else
  echo "[CHECK] OnlyOffice healthcheck: SKIPPED (onlyoffice_server_url not set)"
fi

echo "Done. Deployment completed successfully."
echo "Open: https://${DOMAIN}"
