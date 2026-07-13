#!/usr/bin/env bash
# Onda 0 — RSS feed + autores por subcategoria (Estrato Victor)
set -euo pipefail

WP="${WP_CLI_PATH:-wp}"
ROOT="${ESTRATO_WP:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
SCRIPT="$REPO/scripts/victor/setup-estrato-authors-wave0.php"

echo "=== Estrato Onda 0: autores + RSS ==="

if [[ ! -f "$SCRIPT" ]]; then
  echo "ERRO: script ausente: $SCRIPT"
  exit 1
fi

sudo -u www-data "$WP" --path="$ROOT" eval-file "$SCRIPT"

# Limpar OPcache após deploy de plugins (evita feed 500 com bytecode antigo).
if systemctl is-active php8.3-fpm >/dev/null 2>&1; then
  systemctl reload php8.3-fpm 2>/dev/null || systemctl restart php8.3-fpm 2>/dev/null || true
fi

echo "--- validar feed ---"
CODE=$(curl -sS -o /tmp/estrato-feed.xml -w '%{http_code}' "https://estrato.cc/feed/" || echo 000)
if [[ "$CODE" != "200" ]]; then
  echo "FALHA: feed HTTP $CODE"
  exit 1
fi
if grep -q 'internal_server_error' /tmp/estrato-feed.xml; then
  echo "FALHA: feed contém erro WordPress"
  exit 1
fi
if ! grep -q '<dc:creator>' /tmp/estrato-feed.xml; then
  echo "AVISO: feed sem dc:creator (pode estar vazio)"
fi
echo "OK: feed HTTP 200, XML limpo ($(wc -c < /tmp/estrato-feed.xml) bytes)"

echo "=== Onda 0 concluída ==="
