#!/usr/bin/env bash
# Sprint 7 — amostra schema JSON-LD (alinhado ao gate anti-regressão).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

IP="${ESTRATO_VPS_IP:-187.127.12.186}"
PASS=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

fetch_body() {
  local domain="$1"
  local path="$2"
  local web_root="$3"
  local nocache="?estrato_check=$(date +%s)"
  if [[ "$path" != "/" && "$path" == *"?"* ]]; then
    nocache="&estrato_check=$(date +%s)"
  elif [[ "$path" != "/" ]]; then
    nocache="?estrato_check=$(date +%s)"
  fi
  if [[ -f "${web_root}/wp-config.php" ]]; then
    curl -s --max-time 25 -H "Cache-Control: no-cache" -A "EstratoRegressionCheck/1.0" \
      -H "Host: ${domain}" -k "https://${IP}${path}${nocache}" 2>/dev/null || true
  else
    curl -sL --max-time 25 -H "Cache-Control: no-cache" -A "EstratoRegressionCheck/1.0" \
      "https://${domain}${path}${nocache}" 2>/dev/null || true
  fi
}

check_html() {
  local label="$1"
  local domain="$2"
  local path="$3"
  local web_root="$4"
  local body
  body=$(fetch_body "$domain" "$path" "$web_root")
  if [[ -z "$body" ]]; then
    fail "$label resposta vazia"
    return
  fi
  if echo "$body" | grep -Fq 'application/ld+json'; then
    ok "$label JSON-LD"
  else
    fail "$label sem JSON-LD"
  fi
  if [[ "$path" == "/" ]]; then
    if echo "$body" | grep -Fq 'WebSite'; then ok "$label WebSite"; else fail "$label sem WebSite"; fi
    if echo "$body" | grep -Fq 'NewsMediaOrganization' || echo "$body" | grep -Fq 'Organization'; then
      ok "$label Organization"
    else
      fail "$label sem Organization"
    fi
  else
    if echo "$body" | grep -Fq 'NewsArticle' || echo "$body" | grep -Fq '@type":"Article"'; then
      ok "$label NewsArticle"
    else
      fail "$label sem NewsArticle"
    fi
    if echo "$body" | grep -qE 'rel=["'\'']canonical["'\'']'; then ok "$label canonical"; else fail "$label sem canonical"; fi
  fi
}

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  check_html "$PORTAL_DOMAIN home" "$PORTAL_DOMAIN" "/" "$PORTAL_WEB_ROOT"
  post_url=$(portal_wp post list --post_status=publish --posts_per_page=1 --field=url 2>/dev/null || true)
  if [[ -n "$post_url" ]]; then
    post_path="${post_url#https://${PORTAL_DOMAIN}}"
    check_html "$PORTAL_DOMAIN post" "$PORTAL_DOMAIN" "$post_path" "$PORTAL_WEB_ROOT"
  fi
done

echo "--- schema sample: $PASS ok / $FAIL fail ---"
[[ "$FAIL" -eq 0 ]]
