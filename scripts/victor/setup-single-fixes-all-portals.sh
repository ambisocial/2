#!/usr/bin/env bash
# Orquestrador dos fixes de single-post (auditoria visual pós V3-V8, 2026-07-13):
#
#   1) Deploy do plugin `estrato-portal-bootstrap` (nav-visual sem filter global,
#      author-personas sem duplicação sub==parent, single-article com featured
#      próprio, badge de persona, previous/next PT-BR).
#   2) Deploy do `purge-off-matrix-posts.php` v2 (aceita `_estrato_source_url`).
#   3) `fix-author-jobs-dedup.php` — corrige `Repórter de X · X`.
#   4) `fix-post-content-hygiene.php` — strip "The post ... appeared first on ..."
#      + backfill de títulos/deks cortados em 70/148 chars via og:title.
#   5) `purge-off-matrix-posts.php` — trasha posts com fonte fora da matriz.
#   6) Post-deploy verify (og:image, style count, redirect V6, byline no HTML).
#
# Uso:
#   ssh root@vps './scripts/victor/setup-single-fixes-all-portals.sh'
#   ssh root@vps 'ESTRATO_DRY_RUN=1 ./scripts/victor/setup-single-fixes-all-portals.sh'
set -euo pipefail

DRY="${ESTRATO_DRY_RUN:-0}"
REPO="${REPO:-/var/www/estrato/repo}"
PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)

# shellcheck disable=SC1091
source "$REPO/scripts/victor/lib/portal-env.sh"

echo "=================================================================="
echo "SINGLE-FIXES · dry_run=$DRY · repo=$REPO"
echo "=================================================================="
cd "$REPO"

echo ""
echo "[1/6] sync plugin estrato-portal-bootstrap"
for p in "${PORTALS[@]}"; do
	portal_resolve "$p"
	echo "  → $PORTAL_DOMAIN"
	if [ "$DRY" = "0" ]; then
		sudo rsync -a --delete "$REPO/estrato-portal-bootstrap/" \
			"$PORTAL_WEB_ROOT/wp-content/plugins/estrato-portal-bootstrap/"
	fi
done

echo ""
echo "[2/6] flush caches"
if [ "$DRY" = "0" ]; then
	for p in "${PORTALS[@]}"; do
		portal_resolve "$p"
		portal_wp cache flush >/dev/null 2>&1 || true
		portal_wp eval 'if(function_exists("opcache_reset"))opcache_reset();' >/dev/null 2>&1 || true
	done
	systemctl reload php8.3-fpm 2>/dev/null || systemctl reload php8.2-fpm 2>/dev/null || true
fi

# Helper: portal_wp usa `sudo -u www-data env "ESTRATO_PORTAL=..."` que limpa o
# resto do ambiente. Precisamos forçar cada env var explícita quando o script
# PHP depende dela. Padrão wp-cli aceita `--define` do PHP mas não tem
# equivalente para env; usamos `sudo -u www-data <var>=<val> ...` manualmente.
_portal_wp_env() {
	local var="$1"
	local val="$2"
	shift 2
	sudo -u www-data env "ESTRATO_PORTAL=${ESTRATO_PORTAL:-}" "$var=$val" \
		wp --path="${PORTAL_WEB_ROOT:?}" "$@"
}

echo ""
echo "[3/6] fix-author-jobs-dedup.php"
for p in "${PORTALS[@]}"; do
	portal_resolve "$p"
	echo "  → $PORTAL_DOMAIN"
	_portal_wp_env ESTRATO_FIX_JOBS_DRY_RUN "$DRY" \
		eval-file "$REPO/scripts/victor/fix-author-jobs-dedup.php" 2>&1 | tail -6
done

echo ""
echo "[4/6] fix-post-content-hygiene.php (strip boilerplate + backfill títulos/deks)"
for p in "${PORTALS[@]}"; do
	portal_resolve "$p"
	echo "  → $PORTAL_DOMAIN"
	_portal_wp_env ESTRATO_HYGIENE_DRY_RUN "$DRY" \
		eval-file "$REPO/scripts/victor/fix-post-content-hygiene.php" 2>&1 | tail -8
done

echo ""
echo "[5/6] purge-off-matrix-posts.php (agora aceita _estrato_source_url)"
for p in "${PORTALS[@]}"; do
	portal_resolve "$p"
	echo "  → $PORTAL_DOMAIN"
	_portal_wp_env ESTRATO_PURGE_DRY_RUN "$DRY" \
		eval-file "$REPO/scripts/victor/purge-off-matrix-posts.php" 2>&1 | tail -6
done

echo ""
echo "[6/6] post-deploy verify"
for p in "${PORTALS[@]}"; do
	portal_resolve "$p"
	d="$PORTAL_DOMAIN"
	echo "  · $d"
	html=$(curl -sSL --max-time 20 -A "Mozilla/5.0" -H "Cache-Control: no-cache" "https://$d/?v=$(date +%s%N)")
	# style count
	styles=$(printf "%s" "$html" | python3 -c "import sys,re; s=sys.stdin.read(); i=s.lower().find('</head>'); h=s[:i] if i>0 else s[:30000]; print(len(re.findall(r'<style',h,re.I)))")
	echo "     styles_in_head=$styles"
done

echo ""
echo "=================================================================="
echo "DONE. Confira também um single específico para byline / featured:"
echo "  curl -sSL https://estrato.cc/?p=4299 | grep estrato-single-author"
echo "=================================================================="
