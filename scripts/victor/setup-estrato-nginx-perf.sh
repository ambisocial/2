#!/usr/bin/env bash
# S7 — cache estático nginx em todos os domínios estrato.cc (LCP/assets).
set -euo pipefail

MARKER="# Estrato static cache (S7 perf)"
shopt -s nullglob
CONFS=( /etc/nginx/conf.d/domains/estrato.cc.conf /etc/nginx/conf.d/domains/*.estrato.cc.conf )
changed=0

for CONF in "${CONFS[@]}"; do
  [[ -f "$CONF" ]] || continue
  if grep -qF "$MARKER" "$CONF"; then
    echo "skip $(basename "$CONF")"
    continue
  fi
  TMP=$(mktemp)
  awk -v marker="$MARKER" '
    /client_max_body_size/ && !done {
      print
      print ""
      print "    " marker
      print "    location ~* ^.+\\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2?|ttf|eot)$ {"
      print "        expires 30d;"
      print "        add_header Cache-Control \"public, max-age=2592000, immutable\";"
      print "        access_log off;"
      print "    }"
      done=1
      next
    }
    { print }
  ' "$CONF" > "$TMP"
  mv "$TMP" "$CONF"
  echo "patched $(basename "$CONF")"
  changed=$((changed + 1))
done

if [[ "$changed" -gt 0 ]]; then
  nginx -t
  systemctl reload nginx
  echo "nginx perf cache: $changed configs"
else
  echo "nginx perf cache: nada a alterar"
fi
