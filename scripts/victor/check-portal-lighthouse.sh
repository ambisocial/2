#!/usr/bin/env bash
# S7.3 — Core Web Vitals via PageSpeed Insights (mobile).
# Uso: bash check-portal-lighthouse.sh <url>
# Meta: LCP ≤ 2500ms, CLS ≤ 0.1
set -euo pipefail

URL="${1:?URL obrigatória}"
LCP_MAX="${ESTRATO_LCP_MAX_MS:-2500}"
CLS_MAX="${ESTRATO_CLS_MAX:-0.1}"

result=$(python3 - "$URL" "$LCP_MAX" "$CLS_MAX" <<'PY'
import json
import sys
import urllib.error
import urllib.parse
import urllib.request

url, lcp_max, cls_max = sys.argv[1], float(sys.argv[2]), float(sys.argv[3])
api = (
    "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?"
    + urllib.parse.urlencode(
        {
            "url": url,
            "strategy": "mobile",
            "category": "performance",
        }
    )
)
try:
    with urllib.request.urlopen(api, timeout=120) as resp:
        data = json.loads(resp.read().decode("utf-8"))
except urllib.error.HTTPError as exc:
    print(json.dumps({"ok": False, "url": url, "error": f"PSI HTTP {exc.code}"}))
    raise SystemExit(1)
except Exception as exc:  # noqa: BLE001
    print(json.dumps({"ok": False, "url": url, "error": str(exc)}))
    raise SystemExit(1)

audits = data.get("lighthouseResult", {}).get("audits", {})
lcp = audits.get("largest-contentful-paint", {}).get("numericValue")
cls = audits.get("cumulative-layout-shift", {}).get("numericValue")
score = data.get("lighthouseResult", {}).get("categories", {}).get("performance", {}).get("score")

out = {
    "ok": True,
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
)

echo "$result"
pass=$(echo "$result" | python3 -c "import json,sys; print('yes' if json.load(sys.stdin).get('pass') else 'no')")
[[ "$pass" == "yes" ]]
