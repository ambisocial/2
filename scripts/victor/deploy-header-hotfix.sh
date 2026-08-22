#!/usr/bin/env bash
# Hotfix header 1.36.9 — rodar NO Victor (como root ou com sudo).
# Corrige: área branca clicável = links do trilho com color:#fff em coluna.
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
BRANCH="${ESTRATO_REPO_BRANCH:-cursor/seo-geo-eeat-f149}"
PLUGIN_SRC="${REPO}/estrato-portal-bootstrap"

echo "== 1) Sync repo ($BRANCH) =="
cd "$REPO"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "== 2) Rsync plugin para docroots =="
mapfile -t ROOTS < <(find /var/www -maxdepth 2 -type d -path '*/wp-content/plugins' 2>/dev/null | sed 's#/wp-content/plugins##')
if [[ ${#ROOTS[@]} -eq 0 ]]; then
  ROOTS=(/var/www/estrato.cc)
fi
for web in "${ROOTS[@]}"; do
  dest="${web}/wp-content/plugins/estrato-portal-bootstrap"
  if [[ -d "$dest" || -d "${web}/wp-content" ]]; then
    echo "  -> $dest"
    mkdir -p "$dest"
    rsync -a --delete "${PLUGIN_SRC}/" "${dest}/"
  fi
done

echo "== 3) OPcache (se WP-CLI disponível) =="
if command -v wp >/dev/null 2>&1; then
  for web in /var/www/estrato.cc /var/www/*.estrato.cc; do
    [[ -d "$web" ]] || continue
    sudo -u www-data wp --path="$web" eval 'if(function_exists("opcache_reset")){opcache_reset();echo "opcache_ok\n";}' 2>/dev/null || true
  done
fi

echo "== 4) Gate =="
if [[ -x "$REPO/scripts/victor/check-header-home-gate.sh" ]]; then
  bash "$REPO/scripts/victor/check-header-home-gate.sh" "https://187.127.12.186/" || true
fi

echo "== DONE =="
echo "Hard refresh mobile: https://estrato.cc/"
echo "Esperado: Início Economia Mercados na MESMA linha; área branca sem links fantasmas."
