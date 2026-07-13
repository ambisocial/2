#!/usr/bin/env bash
# Instala location nginx para news-sitemap.xml no Victor.
set -euo pipefail

CONF="/etc/nginx/conf.d/domains/estrato.cc.conf"
BOOT="/var/www/estrato.cc/estrato-news-sitemap.php"
MARKER="# Estrato news-sitemap (Sprint 1)"

if [[ ! -f "$CONF" ]]; then
  echo "nginx conf não encontrado: $CONF"
  exit 1
fi

if [[ ! -f "$BOOT" ]]; then
  echo "bootstrap ausente: $BOOT"
  exit 1
fi

if grep -q "$MARKER" "$CONF"; then
  echo "nginx news-sitemap já configurado"
  exit 0
fi

TMP=$(mktemp)
awk -v marker="$MARKER" '
  /location \/ \{/ && !done {
    print "    " marker
    print "    location = /news-sitemap.xml {"
    print "        include fastcgi_params;"
    print "        fastcgi_param SCRIPT_FILENAME /var/www/estrato.cc/estrato-news-sitemap.php;"
    print "        fastcgi_pass unix:/run/php/php8.3-fpm-estrato.cc.sock;"
    print "    }"
    print ""
    done=1
  }
  { print }
' "$CONF" > "$TMP"

mv "$TMP" "$CONF"
nginx -t
systemctl reload nginx
echo "nginx news-sitemap location installed"
