#!/usr/bin/env bash
# Sequência pós-S6: hubs satélites + autores E-E-A-T + gate.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/seq-followup-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

echo "=== A3 hubs satélites ===" | tee -a "$LOG"
bash "$SCRIPT_DIR/setup-sprint-a3-all-portals.sh" 2>&1 | tee -a "$LOG"

echo "=== A2 autores todos os portais ===" | tee -a "$LOG"
bash "$SCRIPT_DIR/setup-sprint-a2-all-portals.sh" 2>&1 | tee -a "$LOG"

echo "=== Wave0 finance (retratos + reatribuição) ===" | tee -a "$LOG"
portal_resolve estrato-finance
export ESTRATO_PORTAL=estrato-finance
portal_wp eval-file "$REPO/scripts/victor/setup-estrato-authors-wave0.php" 2>&1 | tee -a "$LOG"

echo "=== Deploy plugins + gate ===" | tee -a "$LOG"
bash "$SCRIPT_DIR/setup-audit-sprints-all.sh" 2>&1 | tee -a "$LOG"
bash "$SCRIPT_DIR/setup-gate-all-portals.sh" 2>&1 | tee -a "$LOG"

echo "Seq followup log: $LOG"
