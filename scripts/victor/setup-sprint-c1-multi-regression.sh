#!/usr/bin/env bash
# Sprint C1 — Anti-regressão multi-portal (rede Estrato).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"

echo "=== C1.1 CI estático ==="
bash "$REPO/scripts/victor/check-portal-regression-ci.sh"

echo ""
echo "=== C1.2 Gate strict — 6 portais ==="
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict

echo ""
echo "=== C1 concluído ==="
