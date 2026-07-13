#!/usr/bin/env bash
# S7.4 — Rich Results readiness: valida JSON-LD estruturado (amostra 10+ URLs).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

IP="${ESTRATO_VPS_IP:-187.127.12.186}"
PASS=0
FAIL=0
URLS=()

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

fetch_html() {
  local domain="$1"
  local path="$2"
  local web_root="$3"
  local out="$4"
  if [[ -f "${web_root}/wp-config.php" ]]; then
    curl -s --max-time 30 -H "Host: ${domain}" -k \
      "https://${IP}${path}?estrato_rr=$(date +%s)" > "$out" 2>/dev/null || true
  else
    curl -sL --max-time 30 "https://${domain}${path}?estrato_rr=$(date +%s)" > "$out" 2>/dev/null || true
  fi
}

validate_url() {
  local label="$1"
  local domain="$2"
  local path="$3"
  local web_root="$4"
  local expect="$5"
  local tmp result
  tmp=$(mktemp)
  fetch_html "$domain" "$path" "$web_root" "$tmp"
  if [[ ! -s "$tmp" ]]; then
    fail "$label resposta vazia"
    rm -f "$tmp"
    return
  fi
  result=$(python3 - "$tmp" "$expect" "$label" <<'PY'
import json, re, sys
path, expect, label = sys.argv[1], sys.argv[2], sys.argv[3]
html = open(path, encoding="utf-8", errors="ignore").read()
blocks = re.findall(r'<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>', html, re.I | re.S)
if not blocks:
    print(f"FAIL {label} sem blocos JSON-LD")
    sys.exit(1)
types = set()
errors = []
for raw in blocks:
    raw = raw.strip()
    try:
        data = json.loads(raw)
    except json.JSONDecodeError as e:
        errors.append(str(e))
        continue
    stack = [data]
    while stack:
        node = stack.pop()
        if isinstance(node, dict):
            t = node.get("@type")
            if isinstance(t, str):
                types.add(t)
            elif isinstance(t, list):
                types.update(x for x in t if isinstance(x, str))
            graph = node.get("@graph")
            if isinstance(graph, list):
                stack.extend(graph)
            for v in node.values():
                if isinstance(v, (dict, list)):
                    stack.append(v)
        elif isinstance(node, list):
            stack.extend(node)
required = [x.strip() for x in expect.split(",") if x.strip()]
missing = [r for r in required if r not in types]
if errors:
    print(f"FAIL {label} JSON inválido: {errors[0]}")
    sys.exit(1)
if missing:
    print(f"FAIL {label} faltam @type: {','.join(missing)}")
    sys.exit(1)
print(f"OK  {label}")
PY
  ) || true
  echo "$result"
  if [[ "$result" == OK* ]]; then
    PASS=$((PASS+1))
  else
    FAIL=$((FAIL+1))
  fi
  rm -f "$tmp"
}

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  validate_url "$PORTAL_DOMAIN home" "$PORTAL_DOMAIN" "/" "$PORTAL_WEB_ROOT" "WebSite,NewsMediaOrganization"
  URLS+=("https://${PORTAL_DOMAIN}/")
  post_url=$(portal_wp post list --post_status=publish --posts_per_page=1 --field=url 2>/dev/null || true)
  if [[ -n "$post_url" ]]; then
    post_path="${post_url#https://${PORTAL_DOMAIN}}"
    validate_url "$PORTAL_DOMAIN post" "$PORTAL_DOMAIN" "$post_path" "$PORTAL_WEB_ROOT" "NewsArticle"
    URLS+=("$post_url")
  fi
  # hub FAQ (se existir)
  hub=$(portal_wp post list --post_type=page --name=tudo-sobre --field=url 2>/dev/null | head -1 || true)
  if [[ -z "$hub" ]]; then
    hub=$(portal_wp eval 'echo get_permalink(get_page_by_path("tudo-sobre/selic"));' 2>/dev/null || true)
  fi
  if [[ -n "$hub" && "$hub" != "http" ]]; then
    hub_path="${hub#https://${PORTAL_DOMAIN}}"
    validate_url "$PORTAL_DOMAIN hub" "$PORTAL_DOMAIN" "$hub_path" "$PORTAL_WEB_ROOT" "FAQPage"
    URLS+=("$hub")
  fi
done

echo "--- rich results sample: $PASS ok / $FAIL fail (${#URLS[@]} URLs) ---"
[[ "$FAIL" -eq 0 ]]
