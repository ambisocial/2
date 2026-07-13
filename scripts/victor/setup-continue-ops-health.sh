#!/usr/bin/env bash
# Continuidade ops: repara OG/thumbs, RSS health+import, gate satélites, regression.
# Uso (no Victor): bash scripts/victor/setup-continue-ops-health.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(
  estrato-finance
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-sustain
  estrato-culture
)

LOG_DIR="${ESTRATO_REPORT_DIR:-$REPO/logs}"
mkdir -p "$LOG_DIR"
LOG="$LOG_DIR/continue-ops-health-$(date +%Y%m%d-%H%M%S).log"

echo "=== Continue ops health $(date -Iseconds) ===" | tee "$LOG"

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  export ESTRATO_REPO="$REPO"
  echo "" | tee -a "$LOG"
  echo "──────── $PORTAL_ID ($PORTAL_DOMAIN) ────────" | tee -a "$LOG"

  portal_sync_plugins 2>&1 | tee -a "$LOG" || true

  echo "--- repair OG + thumbs ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/repair-og-default-and-thumbs.php" 2>&1 | tee -a "$LOG" || true

  if [[ "$PORTAL_ID" != "estrato-finance" ]]; then
    echo "--- RSS health + import ---" | tee -a "$LOG"
    portal_wp eval-file "$REPO/scripts/victor/setup-portal-rss-health.php" 2>&1 | tee -a "$LOG" || true
    echo "--- fix gate satellites ---" | tee -a "$LOG"
    portal_wp eval-file "$REPO/scripts/victor/fix-gate-satellites.php" 2>&1 | tee -a "$LOG" || true
  fi

  echo "--- publish counts ---" | tee -a "$LOG"
  pub=$(portal_wp post list --post_type=post --post_status=publish --format=count 2>/dev/null || echo err)
  echo "publish=$pub" | tee -a "$LOG"
done

echo "" | tee -a "$LOG"
echo "=== Regression all --strict ===" | tee -a "$LOG"
set +e
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG"
rc=$?
set -e

echo "Log: $LOG" | tee -a "$LOG"
exit "$rc"
