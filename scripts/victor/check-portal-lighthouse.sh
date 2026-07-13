#!/usr/bin/env bash
# S7.3 — Core Web Vitals (Lighthouse local ou PSI fallback).
# Uso: bash check-portal-lighthouse.sh <url>
# Meta: LCP ≤ 2500ms, CLS ≤ 0.1
set -euo pipefail

URL="${1:?URL obrigatória}"
LCP_MAX="${ESTRATO_LCP_MAX_MS:-2500}"
CLS_MAX="${ESTRATO_CLS_MAX:-0.1}"
MODE="${ESTRATO_LIGHTHOUSE_MODE:-local}"

run_local() {
  local tmp
  tmp=$(mktemp)
  if ! command -v lighthouse >/dev/null 2>&1; then
    return 1
  fi
  if ! lighthouse "$URL" \
    --chrome-flags="--headless --no-sandbox --disable-gpu" \
    --only-categories=performance \
    --output=json \
    --output-path="$tmp" \
    --quiet \
    --max-wait-for-load=90000 2>/dev/null; then
    rm -f "$tmp"
    return 1
  fi
  python3 - "$tmp" "$LCP_MAX" "$CLS_MAX" "$URL" <<'PY'
import json, sys
path, lcp_max, cls_max, url = sys.argv[1], float(sys.argv[2]), float(sys.argv[3]), sys.argv[4]
data = json.load(open(path, encoding="utf-8"))
audits = data.get("audits", {})
lcp = audits.get("largest-contentful-paint", {}).get("numericValue")
cls = audits.get("cumulative-layout-shift", {}).get("numericValue")
score = data.get("categories", {}).get("performance", {}).get("score")
out = {
    "ok": True,
    "source": "lighthouse-cli",
    "url": url,
    "score": round((score or 0) * 100),
    "lcp_ms": round(lcp) if lcp is not None else None,
    "cls": round(cls, 3) if cls is not None else None,
    "lcp_pass": lcp is not None and lcp <= lcp_max,
    "cls_pass": cls is not None and cls <= cls_max,
}
out["pass"] = bool(out["lcp_pass"] and out["cls_pass"])
print(json.dumps(out))
PY
  rm -f "$tmp"
}

run_psi() {
  python3 - "$URL" "$LCP_MAX" "$CLS_MAX" <<'PY'
import json, sys, urllib.parse, urllib.request
url, lcp_max, cls_max = sys.argv[1], float(sys.argv[2]), float(sys.argv[3])
api = (
    "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?"
    + urllib.parse.urlencode({"url": url, "strategy": "mobile", "category": "performance"})
)
with urllib.request.urlopen(api, timeout=120) as resp:
    data = json.loads(resp.read().decode("utf-8"))
if "error" in data:
    print(json.dumps({"ok": False, "url": url, "error": data["error"].get("message", "PSI error")}))
    raise SystemExit(1)
audits = data.get("lighthouseResult", {}).get("audits", {})
lcp = audits.get("largest-contentful-paint", {}).get("numericValue")
cls = audits.get("cumulative-layout-shift", {}).get("numericValue")
score = data.get("lighthouseResult", {}).get("categories", {}).get("performance", {}).get("score")
out = {
    "ok": True,
    "source": "psi-api",
    "url": url,
    "score": round((score or 0) * 100),
    "lcp_ms": round(lcp) if lcp is not None else None,
    "cls": round(cls, 3) if cls is not None else None,
    "lcp_pass": lcp is not None and lcp <= lcp_max,
    "cls_pass": cls is not None and cls <= cls_max,
}
out["pass"] = bool(out["lcp_pass"] and out["cls_pass"])
print(json.dumps(out))
PY
}

result=""
if [[ "$MODE" == "local" ]] || [[ "$MODE" == "auto" ]]; then
  result=$(run_local || true)
fi
if [[ -z "$result" ]] && [[ "$MODE" == "psi" || "$MODE" == "auto" ]]; then
  result=$(run_psi || true)
fi
if [[ -z "$result" ]]; then
  echo '{"ok":false,"url":"'"$URL"'","error":"lighthouse indisponível"}'
  exit 1
fi

echo "$result"
pass=$(echo "$result" | python3 -c "import json,sys; d=json.load(sys.stdin); print('yes' if d.get('pass') else 'no')")
[[ "$pass" == "yes" ]]
