#!/usr/bin/env bash
# Adiciona headers de segurança no nginx do Victor (Sprint 7 — AR-PERF-002).
set -euo pipefail

CONF="/etc/nginx/conf.d/domains/estrato.cc.conf"
MARKER="# Estrato security headers (Sprint 7)"

if [[ ! -f "$CONF" ]]; then
  echo "nginx conf não encontrado: $CONF"
  exit 1
fi

if grep -q "$MARKER" "$CONF"; then
  echo "nginx security headers já configurados"
  exit 0
fi

TMP=$(mktemp)
awk -v marker="$MARKER" '
  /client_max_body_size/ && !done {
    print
    print ""
    print "    " marker
    print "    add_header X-Content-Type-Options nosniff always;"
    print "    add_header X-Frame-Options SAMEORIGIN always;"
    print "    add_header Referrer-Policy strict-origin-when-cross-origin always;"
    done=1
    next
  }
  { print }
' "$CONF" > "$TMP"

mv "$TMP" "$CONF"
nginx -t
systemctl reload nginx
echo "nginx security headers installed"
