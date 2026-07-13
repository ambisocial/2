#!/usr/bin/env bash
# Sprint 7 — amostra schema JSON-LD (substituto leve ao Rich Results Test).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

IP="${ESTRATO_VPS_IP:-187.127.12.186}"
PASS=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

check_html() {
  local label="$1"
  local host="$2"
  local path="$3"
  local body
  body=$(curl -sk --max-time 20 -H "Host: ${host}" "https://${IP}${path}" 2>/dev/null || true)
  if echo "$body" | grep -qi 'application/ld+json'; then
    ok "$label JSON-LD"
  else
    fail "$label sem JSON-LD"
  fi
  if [[ "$path" != "/" ]]; then
    if echo "$body" | grep -qiE 'NewsArticle|"@type"[[:space:]]*:[[:space:]]*"Article"'; then
      ok "$label NewsArticle"
    else
      fail "$label sem NewsArticle"
    fi
  else
    if echo "$body" | grep -qiE 'WebSite|Organization|NewsMediaOrganization'; then
      ok "$label Organization/WebSite"
    else
      fail "$label home schema"
    fi
  fi
}

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  check_html "$PORTAL_DOMAIN" "$PORTAL_DOMAIN" "/"
  post_path=$(portal_wp post list --post_status=publish --posts_per_page=1 --field=url 2>/dev/null \
    | sed "s#https://${PORTAL_DOMAIN}##" || true)
  if [[ -n "$post_path" ]]; then
    check_html "$PORTAL_DOMAIN post" "$PORTAL_DOMAIN" "$post_path"
  fi
done

echo "--- schema sample: $PASS ok / $FAIL fail ---"
[[ "$FAIL" -eq 0 ]]
