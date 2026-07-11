#!/usr/bin/env python3
"""
Auditoria consolidada — portais Estrato (taxonomia + feeds RSS).

Uso: python3 scripts/victor/audit-portals.py [--feeds]
"""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def inventory() -> list[dict]:
    script = ROOT / "scripts/victor/audit-taxonomy-inventory.php"
    raw = subprocess.check_output(["php", str(script)], text=True, cwd=ROOT)
    return json.loads(raw)


def feed_summary() -> dict:
    proc = subprocess.run(
        [sys.executable, str(ROOT / "scripts/victor/validate-rss-feeds.py"), "--all"],
        capture_output=True,
        text=True,
        cwd=ROOT,
    )
    out = proc.stdout + proc.stderr
    portals: dict[str, dict] = {}
    current = None
    for line in out.splitlines():
        if line.startswith("=== ") and line.endswith(" ==="):
            name = line.split("=== ")[1].split(" (")[0]
            current = name
            portals[current] = {"ok": 0, "warn": 0, "fail": 0, "fails": [], "warns": []}
        elif current and line.strip().startswith("Resumo:"):
            parts = line.split("Resumo:")[1]
            for chunk in parts.split():
                if chunk.startswith("OK="):
                    portals[current]["ok"] = int(chunk.split("=")[1])
                elif "WARN" in chunk:
                    portals[current]["warn"] = int(chunk.split("=")[1])
                elif chunk.startswith("FAIL="):
                    portals[current]["fail"] = int(chunk.split("=")[1])
        elif current and line.strip().startswith("FAIL"):
            portals[current]["fails"].append(line.strip())
        elif current and ("WARN" in line or "STALE" in line) and "HTTP" in line:
            portals[current]["warns"].append(line.strip())
    return {"exit_code": proc.returncode, "portals": portals}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--feeds", action="store_true", help="Inclui validação HTTP de feeds")
    args = parser.parse_args()

    inv = inventory()
    total_editorias = sum(len(p["editorias"]) for p in inv)
    total_subs = sum(p["total_subs"] for p in inv)
    total_feeds = sum(p["total_feeds"] for p in inv)

    print("=== AUDITORIA ESTRATO — TAXONOMIA ===")
    print(f"Portais: {len(inv)} | Editorias: {total_editorias} | Subcategorias: {total_subs} | Feeds (refs): {total_feeds}")
    for p in inv:
        print(f"\n[{p['portal']}] editorias={len(p['editorias'])} subs={p['total_subs']} feeds={p['total_feeds']}")
        for e in p["editorias"]:
            print(f"  · {e['slug']}: {e['name']} ({len(e['subs'])} subs)")
            for s in e["subs"]:
                flag = "⚠️ sem feeds" if s["feeds"] == 0 else f"feeds={s['feeds']}"
                print(f"      - {s['slug']}: {flag}, kw={s['keywords']}")

    if args.feeds:
        print("\n=== AUDITORIA ESTRATO — FEEDS HTTP ===")
        fs = feed_summary()
        for name, data in fs["portals"].items():
            status = "✅" if data["fail"] == 0 else "❌"
            print(f"{status} {name}: OK={data['ok']} WARN={data['warn']} FAIL={data['fail']}")
            for f in data["fails"]:
                print(f"    {f}")
        return fs["exit_code"]

    return 0


if __name__ == "__main__":
    sys.exit(main())
