#!/usr/bin/env bash
# Sprint A3 — hubs /tudo-sobre/ + FAQPage em todos os satélites.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-a3-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

for PORTAL_ID in estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== A3 $PORTAL_ID @ $PORTAL_WEB_ROOT ===" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-a3-portal-hubs.php" 2>&1 | tee -a "$LOG"
  portal_wp cache flush 2>/dev/null || true
done

echo "A3 log: $LOG"
