#!/usr/bin/env bash
# Helpers compartilhados — setup-portal-full-stack.sh e scripts Victor.
# shellcheck disable=SC2034
set -euo pipefail

# Resolve portal_id → domain, web_root, preset, title
portal_resolve() {
  local portal_id="${1:?portal_id obrigatório}"
  local repo="${ESTRATO_REPO:-/var/www/estrato/repo}"
  local yaml="$repo/portals/${portal_id}.yaml"

  if [[ ! -f "$yaml" ]]; then
    echo "ERRO: YAML não encontrado: $yaml" >&2
    return 1
  fi

  PORTAL_ID="$portal_id"
  PORTAL_DOMAIN=$(grep -E '^domain:' "$yaml" | head -1 | awk '{print $2}')
  PORTAL_TITLE=$(grep -E '^title:' "$yaml" | head -1 | sed 's/^title: //' | tr -d '"')
  PORTAL_PRESET=$(grep -E 'rss_preset:' "$yaml" | head -1 | awk '{print $2}')
  PORTAL_WEB_ROOT=$(grep -E 'web_root:' "$yaml" | head -1 | awk '{print $2}')

  if [[ -z "$PORTAL_WEB_ROOT" ]]; then
    PORTAL_WEB_ROOT="/var/www/${PORTAL_DOMAIN}"
  fi

  PORTAL_VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
  export PORTAL_ID PORTAL_DOMAIN PORTAL_TITLE PORTAL_PRESET PORTAL_WEB_ROOT PORTAL_VPS_IP
}

portal_wp() {
  sudo -u www-data wp --path="${PORTAL_WEB_ROOT:?}" "$@"
}

portal_log() {
  echo "[$(date -Iseconds)] [$PORTAL_ID] $*"
}

portal_curl_check() {
  local path="$1"
  local expect="${2:-200}"
  local code
  code=$(curl -sk -o /dev/null -w "%{http_code}" \
    -H "Host: ${PORTAL_DOMAIN}" \
    "https://${PORTAL_VPS_IP}${path}" 2>/dev/null || echo "000")
  if [[ "$code" == "$expect" ]]; then
    portal_log "OK $path → HTTP $code"
    return 0
  fi
  portal_log "FAIL $path → HTTP $code (esperado $expect)"
  return 1
}

portal_sync_plugins() {
  local repo="${ESTRATO_REPO:-/var/www/estrato/repo}"
  local web="${PORTAL_WEB_ROOT}"
  for slug in estrato-portal-bootstrap estrato-rss-bootstrap estrato-publisher-bridge; do
    rsync -a --delete "$repo/$slug/" "$web/wp-content/plugins/$slug/"
  done
  mkdir -p "$web/wp-content/estrato-portals"
  rsync -a "$repo/portals/" "$web/wp-content/estrato-portals/"
  cp "$repo/estrato-portal-bootstrap/estrato-news-sitemap.php" "$web/estrato-news-sitemap.php" 2>/dev/null || true
  portal_wp plugin activate estrato-portal-bootstrap estrato-rss-bootstrap estrato-publisher-bridge wordpress-seo contact-form-7 --quiet 2>/dev/null || true
}

portal_ensure_permalinks() {
  portal_wp option update permalink_structure '/%postname%/' 2>/dev/null || true
  portal_wp rewrite flush 2>/dev/null || true
}
