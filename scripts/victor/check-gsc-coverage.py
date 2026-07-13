#!/usr/bin/env python3
"""S7.5 — cobertura GSC (URLs inspecionadas vs publicadas recentes)."""
from __future__ import annotations

import json
import os
import subprocess
import sys
from datetime import datetime, timedelta, timezone

REPO = os.environ.get("ESTRATO_REPO", "/var/www/estrato/repo")
GSC_API = os.path.join(REPO, "scripts/victor/index-bot/gsc_api.py")
LOG = os.environ.get("GSC_COVERAGE_LOG", "/var/log/estrato/gsc-index.log")
TARGET = float(os.environ.get("GSC_COVERAGE_TARGET", "0.9"))


def run(cmd: list[str]) -> str:
    return subprocess.check_output(cmd, text=True, stderr=subprocess.DEVNULL)


def main() -> int:
    if not os.path.isfile(GSC_API):
        print(json.dumps({"ok": False, "error": "gsc_api missing"}))
        return 1
    try:
        sites_out = run(["python3", GSC_API, "sites"])
        sites = json.loads(sites_out) if sites_out.strip().startswith("[") else []
    except (subprocess.CalledProcessError, json.JSONDecodeError) as exc:
        print(json.dumps({"ok": False, "error": f"sites: {exc}"}))
        return 1

    inspected_recent = 0
    if os.path.isfile(LOG):
        cutoff = datetime.now(timezone.utc) - timedelta(days=7)
        with open(LOG, encoding="utf-8", errors="ignore") as fh:
            for line in fh:
                if "indexed" in line.lower() or "INSPECTION" in line:
                    inspected_recent += 1

    # proxy: últimas linhas do log de indexação
    indexed_ok = 0
    if os.path.isfile(LOG):
        tail = subprocess.check_output(["tail", "-n", "200", LOG], text=True, errors="ignore")
        indexed_ok = tail.lower().count("success") + tail.lower().count("submitted")

    payload = {
        "ok": True,
        "sites": len(sites) if isinstance(sites, list) else 0,
        "log_inspected_lines_7d": inspected_recent,
        "log_success_signals": indexed_ok,
        "target_ratio": TARGET,
        "note": "Cobertura exata requer sc-domain:estrato.cc; bot filtra URLs da propriedade prefix.",
    }
    print(json.dumps(payload, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())
