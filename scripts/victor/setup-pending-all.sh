#!/usr/bin/env bash
# Fecha pendências: sync pipeline, higiene drafts, boost satélites, CSS, gate.
# Uso (Victor): bash scripts/victor/setup-pending-all.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG_DIR="${ESTRATO_REPORT_DIR:-$REPO/logs}"
mkdir -p "$LOG_DIR"
LOG="$LOG_DIR/pending-all-$(date +%Y%m%d-%H%M%S).log"

echo "=== Pending all $(date -Iseconds) ===" | tee "$LOG"

# 1) Pipeline versionado → disco do Victor
PIPELINE_SRC="$REPO/scripts/victor/pipeline"
PIPELINE_DST="${ESTRATO_PIPELINE_DIR:-/var/www/estrato/pipeline}"
if [[ -d "$PIPELINE_SRC" && -d "$PIPELINE_DST" ]]; then
  echo "── sync pipeline → $PIPELINE_DST ──" | tee -a "$LOG"
  for f in wp_bridge.py writer.py publisher.py; do
    if [[ -f "$PIPELINE_SRC/$f" ]]; then
      cp -a "$PIPELINE_SRC/$f" "$PIPELINE_DST/$f"
      echo "  synced $f" | tee -a "$LOG"
    fi
  done
fi

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID" ESTRATO_REPO="$REPO"
  echo "" | tee -a "$LOG"
  echo "──────── $PORTAL_ID ────────" | tee -a "$LOG"
  portal_sync_plugins 2>&1 | tee -a "$LOG" || true

  echo "--- cleanup bleed + repair thumbs ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/cleanup-pipeline-bleed.php" 2>&1 | tee -a "$LOG" || true
  portal_wp eval-file "$REPO/scripts/victor/repair-og-default-and-thumbs.php" 2>&1 | tee -a "$LOG" || true

  echo "--- drafts hygiene ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/drafts-hygiene.php" 2>&1 | tee -a "$LOG" || true

  if [[ "$PORTAL_ID" != "estrato-finance" ]]; then
    export ESTRATO_HARD_GUID_PRUNE=1
    echo "--- RSS health + hard boost ---" | tee -a "$LOG"
    portal_wp eval-file "$REPO/scripts/victor/setup-portal-rss-health.php" 2>&1 | tee -a "$LOG" || true
    portal_wp eval-file "$REPO/scripts/victor/boost-satellite-rss.php" 2>&1 | tee -a "$LOG" || true
    portal_wp eval-file "$REPO/scripts/victor/fix-gate-satellites.php" 2>&1 | tee -a "$LOG" || true
    unset ESTRATO_HARD_GUID_PRUNE
  fi
done

echo "" | tee -a "$LOG"
echo "── pre2024 + pipeline_primary ──" | tee -a "$LOG"
bash "$REPO/scripts/victor/fix-pre2024-and-rss-mode.sh" 2>&1 | tee -a "$LOG" || true

echo "" | tee -a "$LOG"
echo "── publish counts ──" | tee -a "$LOG"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  pub=$(portal_wp post list --post_type=post --post_status=publish --format=count 2>/dev/null || echo err)
  draft=$(portal_wp post list --post_type=post --post_status=draft --format=count 2>/dev/null || echo err)
  echo "$PORTAL_ID publish=$pub draft=$draft" | tee -a "$LOG"
done

echo "" | tee -a "$LOG"
echo "=== Regression --strict ===" | tee -a "$LOG"
set +e
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG"
rc=$?
set -e
echo "Log: $LOG" | tee -a "$LOG"
exit "$rc"
