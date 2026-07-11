#!/usr/bin/env bash
# setup-portal-full-stack.sh — Fase 3: automatiza Sprints 0–11 por portal.
#
# Uso:
#   bash scripts/victor/setup-portal-full-stack.sh estrato-mind
#   bash scripts/victor/setup-portal-full-stack.sh estrato-lifestyle --from 3 --dry-run
#   bash scripts/victor/setup-portal-full-stack.sh estrato-finance --strict-gate
#   ESTRATO_PORTAL=estrato-science bash scripts/victor/setup-portal-full-stack.sh
#
# Variáveis:
#   ESTRATO_REPO      default /var/www/estrato/repo
#   ESTRATO_VPS_IP    default 187.127.12.186
#   WEB_ROOT          override (senão lê do YAML)
#   SKIP_LEGACY_MERGE 1 para satélites (sem merge política/tecnologia)
#   SYNC_FIRESFERA    1 para estrato-mind
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTAL_ID="${1:-${ESTRATO_PORTAL:-}}"
FROM_SPRINT="${FROM_SPRINT:-0}"
DRY_RUN=0
STRICT_GATE=0
SKIP_CONTENT=0

shift || true
while [[ $# -gt 0 ]]; do
  case "$1" in
    --from) FROM_SPRINT="${2:-0}"; shift 2 ;;
    --dry-run) DRY_RUN=1; shift ;;
    --strict-gate) STRICT_GATE=1; shift ;;
    --skip-content) SKIP_CONTENT=1; shift ;;
    -h|--help)
      sed -n '2,20p' "$0"
      exit 0
      ;;
    *) echo "Argumento desconhecido: $1" >&2; exit 1 ;;
  esac
done

if [[ -z "$PORTAL_ID" ]]; then
  echo "Uso: $0 <portal_id> [--from N] [--dry-run] [--strict-gate] [--skip-content]" >&2
  echo "Portais: estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture" >&2
  exit 1
fi

portal_resolve "$PORTAL_ID"
export ESTRATO_PORTAL="$PORTAL_ID"
export WEB_ROOT="$PORTAL_WEB_ROOT"
export ESTRATO_REPO="$REPO"

LOG_DIR="${REPO}/logs/full-stack"
mkdir -p "$LOG_DIR"
LOG_FILE="${LOG_DIR}/${PORTAL_ID}-$(date +%Y%m%d-%H%M%S).log"

run_step() {
  local sprint="$1"
  local label="$2"
  shift 2
  if [[ "$sprint" -lt "$FROM_SPRINT" ]]; then
    portal_log "SKIP Sprint $sprint — $label"
    return 0
  fi
  portal_log "═══ Sprint $sprint — $label ═══"
  if [[ "$DRY_RUN" -eq 1 ]]; then
    portal_log "[dry-run] $*"
    return 0
  fi
  "$@" 2>&1 | tee -a "$LOG_FILE"
}

portal_log "Full stack: $PORTAL_ID @ $PORTAL_DOMAIN ($PORTAL_WEB_ROOT)"
portal_log "Preset: $PORTAL_PRESET | from=$FROM_SPRINT | log=$LOG_FILE"

# ─── Sprint 0: Pré-requisitos ───────────────────────────────────
run_step 0 "Pré-requisitos" bash -c '
  portal_sync_plugins
  portal_ensure_permalinks
  portal_wp option update blog_public 1 2>/dev/null || true
  portal_wp option update timezone_string America/Sao_Paulo 2>/dev/null || true
  portal_wp option update WPLANG pt_BR 2>/dev/null || true
  portal_wp post delete $(portal_wp post list --post_type=page --name=pagina-exemplo --field=ID 2>/dev/null) --force 2>/dev/null || true
'

# ─── Sprint 1: SEO ──────────────────────────────────────────────
run_step 1 "SEO (robots, Yoast, news-sitemap)" bash -c "
  export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
  export ESTRATO_REPO=\"$REPO\"
  if [[ -f \"$REPO/scripts/victor/setup-estrato-seo.sh\" ]]; then
    # Yoast titles genéricos por portal
    bash \"$REPO/scripts/victor/setup-estrato-seo.sh\"
    portal_wp option patch update wpseo_titles title-home-wpseo '${PORTAL_TITLE} | ${PORTAL_DOMAIN}'
    portal_wp option patch update wpseo_titles title-tax-category '%%term_title%%: notícias %%page%% | ${PORTAL_TITLE}'
    portal_wp option patch update wpseo_titles title-post '%%title%% | ${PORTAL_TITLE}'
  fi
  portal_ensure_permalinks
"

# ─── Sprint 2: Schema ─────────────────────────────────────────────
run_step 2 "Schema & OG" bash -c "
  export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
  export ESTRATO_REPO=\"$REPO\"
  [[ -f \"$REPO/scripts/victor/setup-estrato-schema.sh\" ]] && bash \"$REPO/scripts/victor/setup-estrato-schema.sh\"
"

# ─── Sprint 3: E-E-A-T ────────────────────────────────────────────
run_step 3 "E-E-A-T & Institucional" bash -c "
  export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
  export ESTRATO_REPO=\"$REPO\"
  export ESTRATO_PORTAL=\"$PORTAL_ID\"
  portal_sync_plugins
  if [[ -f \"$REPO/scripts/victor/setup-institutional-portal.php\" ]]; then
    portal_wp eval-file \"$REPO/scripts/victor/setup-institutional-portal.php\"
  elif [[ -f \"$REPO/scripts/victor/setup-institutional.php\" ]]; then
    portal_wp eval-file \"$REPO/scripts/victor/setup-institutional.php\"
  fi
  # Publicar privacidade com slug canônico
  pid=\$(portal_wp post list --post_type=page --name=politica-de-privacidade --field=ID 2>/dev/null || true)
  if [[ -n \"\$pid\" ]]; then
    portal_wp post update \"\$pid\" --post_status=publish --post_name=privacidade 2>/dev/null || true
  fi
  if [[ \"$PORTAL_ID\" == \"estrato-finance\" && -f \"$REPO/scripts/victor/setup-estrato-authors-wave0.sh\" ]]; then
    bash \"$REPO/scripts/victor/setup-estrato-authors-wave0.sh\"
  fi
"

# ─── Sprint 4: Conteúdo ────────────────────────────────────────────
if [[ "$SKIP_CONTENT" -eq 0 ]]; then
  run_step 4 "Conteúdo (enrich, linker)" bash -c "
    export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
    export ESTRATO_REPO=\"$REPO\"
    [[ -f \"$REPO/scripts/victor/setup-estrato-content.sh\" ]] && bash \"$REPO/scripts/victor/setup-estrato-content.sh\" || true
    portal_wp eval-file \"$REPO/scripts/victor/fix-uncategorized.php\" 2>/dev/null || true
  "
else
  portal_log "SKIP Sprint 4 — --skip-content"
fi

# ─── Sprint 5: Nav & Visual ─────────────────────────────────────────
run_step 5 "Nav, hubs, visual" bash -c "
  export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
  export ESTRATO_REPO=\"$REPO\"
  export ESTRATO_PORTAL=\"$PORTAL_ID\"
  if [[ \"$PORTAL_ID\" == \"estrato-finance\" && -f \"$REPO/scripts/victor/setup-estrato-nav.sh\" ]]; then
    bash \"$REPO/scripts/victor/setup-estrato-nav.sh\"
  elif [[ -f \"$REPO/scripts/victor/setup-sprint5-portal-nav.php\" ]]; then
    portal_wp eval-file \"$REPO/scripts/victor/setup-sprint5-portal-nav.php\"
  fi
"

# ─── Sprint 6: AEO/GEO ────────────────────────────────────────────
run_step 6 "AEO/GEO (llms.txt, IndexNow)" bash -c "
  export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
  export ESTRATO_REPO=\"$REPO\"
  [[ -f \"$REPO/scripts/victor/setup-estrato-aeo.sh\" ]] && bash \"$REPO/scripts/victor/setup-estrato-aeo.sh\"
  portal_wp eval-file \"$REPO/scripts/victor/setup-sprint6-aeo.php\" 2>/dev/null || true
"

# ─── Sprint 7: Gate ───────────────────────────────────────────────
run_step 7 "Gate & polish" bash -c "
  export WEB_ROOT=\"$PORTAL_WEB_ROOT\"
  export ESTRATO_REPO=\"$REPO\"
  portal_wp eval-file \"$REPO/scripts/victor/setup-sprint7-gate.php\" 2>/dev/null || true
  portal_wp yoast index --reindex --skip-confirmation 2>/dev/null || true
  portal_wp cache flush 2>/dev/null || true
  if [[ $STRICT_GATE -eq 1 && -f \"$REPO/scripts/victor/check-portal-regression.sh\" ]]; then
    ESTRATO_PORTAL=\"$PORTAL_ID\" WEB_ROOT=\"$PORTAL_WEB_ROOT\" bash \"$REPO/scripts/victor/check-portal-regression.sh\" --strict
  fi
"

# ─── Sprint 8: Taxonomia + Layout ─────────────────────────────────
run_step 8 "Taxonomia v2 + layout home" bash -c "
  export ESTRATO_PORTAL=\"$PORTAL_ID\"
  export ESTRATO_WP=\"$PORTAL_WEB_ROOT\"
  portal_sync_plugins
  portal_wp eval-file \"$REPO/scripts/victor/apply-portal-config.php\"
  if [[ \"$PORTAL_ID\" == \"estrato-finance\" && -f \"$REPO/scripts/victor/setup-sprint8-taxonomy.php\" ]]; then
    portal_wp eval-file \"$REPO/scripts/victor/setup-sprint8-taxonomy.php\"
  else
    portal_wp eval-file \"$REPO/scripts/victor/setup-sprint8-portal-layout.php\"
  fi
  if [[ \"\${SKIP_LEGACY_MERGE:-1}\" != \"1\" && \"$PORTAL_ID\" == \"estrato-finance\" ]]; then
    portal_wp eval-file \"$REPO/scripts/victor/merge-legacy-categories.php\" 2>/dev/null || true
  fi
"

# ─── Sprint 9: Curadoria RSS ──────────────────────────────────────
run_step 9 "Curadoria RSS" bash -c "
  if [[ -f \"$REPO/scripts/victor/validate-rss-feeds.py\" ]]; then
    python3 \"$REPO/scripts/victor/validate-rss-feeds.py\" \"$REPO/portals/${PORTAL_ID}-taxonomy.php\" || true
  fi
  portal_wp eval-file \"$REPO/scripts/victor/setup-sprint9-rss-curation.php\" 2>/dev/null || true
"

# ─── Sprint 10: Ops ───────────────────────────────────────────────
run_step 10 "Operação contínua" bash -c "
  portal_wp eval-file \"$REPO/scripts/victor/setup-sprint10-ops.php\" 2>/dev/null || true
"

# ─── Sprint 11: Auditoria taxonomia ─────────────────────────────
run_step 11 "Auditoria taxonomia v2" bash -c "
  portal_wp eval-file \"$REPO/scripts/victor/setup-sprint11-taxonomy-v2.php\" 2>/dev/null || true
"

# ─── Validação final ──────────────────────────────────────────────
portal_log "═══ Validação final ═══"
if [[ "$DRY_RUN" -eq 0 ]]; then
  portal_curl_check "/" 200 || true
  portal_curl_check "/wp-json/estrato/v1/health" 200 || portal_curl_check "/wp-json/estrato/v1/health/" 200 || true
  portal_curl_check "/robots.txt" 200 || true
  portal_curl_check "/llms.txt" 200 || true
  portal_curl_check "/news-sitemap.xml" 200 || true
  portal_curl_check "/sitemap_index.xml" 200 || true
  portal_curl_check "/sobre/" 200 || true
  portal_curl_check "/privacidade/" 200 || true
fi

portal_log "Full stack concluído: $PORTAL_ID"
portal_log "Log: $LOG_FILE"
echo ""
echo "Próximo passo: revisar log e rodar gate strict:"
echo "  ESTRATO_PORTAL=$PORTAL_ID WEB_ROOT=$PORTAL_WEB_ROOT bash $REPO/scripts/victor/check-portal-regression.sh --strict"
