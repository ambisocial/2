#!/usr/bin/env bash
# Empacota plugins da fábrica de portais para deploy manual ou CI.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

rm -f estrato-portal-bootstrap.zip estrato-publisher-bridge.zip
zip -r estrato-portal-bootstrap.zip estrato-portal-bootstrap/
zip -r estrato-publisher-bridge.zip estrato-publisher-bridge/
node scripts/build-estrato-rss-zip.mjs

echo "Built:"
ls -lh estrato-portal-bootstrap.zip estrato-publisher-bridge.zip estrato-rss-bootstrap.zip
