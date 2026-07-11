#!/usr/bin/env bash
# Deploy multi-portal Estrato no Victor (SSH): DNS Cloudflare + WP + nginx + taxonomia.
set -euo pipefail

REPO_URL="${ESTRATO_REPO_URL:-https://github.com/ambisocial/2.git}"
REPO_BRANCH="${ESTRATO_REPO_BRANCH:-cursor/taxonomy-rss-keywords-2c04}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
ZONE="${ESTRATO_ZONE:-estrato.cc}"
CF_SECRETS="${CLOUDFLARE_CREDENTIALS:-/root/.secrets/cloudflare.ini}"
CERT_EMAIL="${ESTRATO_CERT_EMAIL:-tpb@ambi.social}"
TEMPLATE_WP="${ESTRATO_TEMPLATE_WP:-/var/www/estrato.cc}"

# domain|portal_id|rss_preset|title|tagline
PORTALS=(
  "estrato.cc|estrato-finance|brasil-financeiro|Estrato|Economia, mercados e finanças"
  "mente.estrato.cc|estrato-mind|brasil-mind|Estrato Mente|Conhecimento, mente e desenvolvimento pessoal"
  "lifestyle.estrato.cc|estrato-lifestyle|brasil-lifestyle|Estrato Lifestyle|Estilos de vida, hobbies e consumo apaixonado"
  "science.estrato.cc|estrato-science|brasil-science|Estrato Science|Ciência, tecnologia e futuro"
  "sustain.estrato.cc|estrato-sustain|brasil-sustain|Estrato Sustain|Sustentabilidade e economia alternativa"
  "culture.estrato.cc|estrato-culture|brasil-culture|Estrato Culture|Entretenimento, cultura pop e narrativas de nicho"
)

log() { echo "[$(date -Iseconds)] $*"; }

sync_repo() {
  log "=== Sync repositório ==="
  if [[ -d "$REPO/.git" ]]; then
    cd "$REPO"
    git fetch origin "$REPO_BRANCH" --depth=1 2>/dev/null || git fetch origin
    git checkout "$REPO_BRANCH" 2>/dev/null || git checkout -B "$REPO_BRANCH" "origin/$REPO_BRANCH"
    git pull --ff-only origin "$REPO_BRANCH" 2>/dev/null || true
  else
    rm -rf "$REPO"
    mkdir -p "$(dirname "$REPO")"
    git clone --depth 1 -b "$REPO_BRANCH" "$REPO_URL" "$REPO"
  fi
  log "Repo em $(cd "$REPO" && git log -1 --oneline)"
}

ensure_dns() {
  local name="$1"
  local host="$2"
  if [[ ! -f "$CF_SECRETS" ]]; then
    log "AVISO: sem $CF_SECRETS — configure DNS manual: ${host} → ${VPS_IP}"
    return 0
  fi
  local CF_EMAIL CF_KEY ZONE_ID EXISTING
  CF_EMAIL=$(grep -E '^dns_cloudflare_email' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  CF_KEY=$(grep -E '^dns_cloudflare_api_key' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  ZONE_ID=$(curl -sS -X GET "https://api.cloudflare.com/client/v4/zones?name=${ZONE}" \
    -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
    | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
  [[ -n "$ZONE_ID" ]] || { log "AVISO: zone ${ZONE} não encontrada"; return 0; }
  EXISTING=$(curl -sS -X GET \
    "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?name=${host}" \
    -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
    | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
  if [[ -z "$EXISTING" ]]; then
    curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records" \
      -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
      -d "{\"type\":\"A\",\"name\":\"${name}\",\"content\":\"${VPS_IP}\",\"proxied\":true,\"ttl\":1}" \
      | python3 -c "import sys,json; d=json.load(sys.stdin); print('DNS OK' if d.get('success') else d)"
  else
    log "DNS ${host} já existe"
  fi
}

ensure_ssl() {
  local host="$1"
  if [[ -f "/etc/letsencrypt/live/${host}/fullchain.pem" ]]; then
    return 0
  fi
  if [[ -f "$CF_SECRETS" ]] && command -v certbot >/dev/null 2>&1; then
    log "Certbot DNS para ${host}..."
    certbot certonly --dns-cloudflare \
      --dns-cloudflare-credentials "$CF_SECRETS" \
      --dns-cloudflare-propagation-seconds 90 \
      -d "$host" \
      --non-interactive --agree-tos -m "$CERT_EMAIL" || log "AVISO: certbot falhou ${host}"
  fi
}

ensure_php_pool() {
  local domain="$1"
  local pool="/etc/php/8.3/fpm/pool.d/${domain}.conf"
  [[ -f "$pool" ]] && return 0
  sed "s/estrato.cc/${domain}/g" /etc/php/8.3/fpm/pool.d/estrato.cc.conf > "$pool"
  systemctl reload php8.3-fpm
  log "PHP-FPM pool ${domain}"
}

ensure_nginx() {
  local domain="$1"
  local conf="/etc/nginx/conf.d/domains/${domain}.conf"
  [[ -f "$conf" ]] && return 0
  if [[ ! -f "/etc/letsencrypt/live/${domain}/fullchain.pem" ]]; then
    log "AVISO: sem cert para ${domain}, nginx HTTP only"
    cat > "$conf" <<NGX
server {
    listen ${VPS_IP}:80;
    server_name ${domain};
    root /var/www/${domain};
    index index.php;
    location / { try_files \$uri \$uri/ /index.php?\$args; }
    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm-${domain}.sock;
    }
}
NGX
  else
    sed -e "s/estrato\.cc/${domain}/g" \
        -e "s/www\.estrato\.cc/${domain}/g" \
        /etc/nginx/conf.d/domains/estrato.cc.conf > "$conf"
  fi
  nginx -t && systemctl reload nginx
  log "nginx ${domain}"
}

deploy_plugins() {
  local web="$1"
  for slug in estrato-portal-bootstrap estrato-rss-bootstrap estrato-publisher-bridge; do
    rsync -a --delete "${REPO}/${slug}/" "${web}/wp-content/plugins/${slug}/"
  done
  mkdir -p "${web}/wp-content/estrato-portals"
  rsync -a "${REPO}/portals/" "${web}/wp-content/estrato-portals/"
}

provision_wordpress() {
  local domain="$1"
  local title="$2"
  local web="/var/www/${domain}"
  local db_name="wp_${domain//./_}"

  if [[ -f "${web}/wp-config.php" ]]; then
    return 0
  fi

  log "Provisionando WordPress: ${domain}"
  mkdir -p "$web"
  chown www-data:www-data "$web"
  sudo -u www-data wp core download --path="$web" --locale=pt_BR --quiet

  # Credenciais do template (mesmo usuário MySQL do estrato.cc)
  local db_user db_pass
  db_user=$(grep "DB_USER" "$TEMPLATE_WP/wp-config.php" | head -1 | sed "s/.*'\([^']*\)'.*/\1/")
  db_pass=$(grep "DB_PASSWORD" "$TEMPLATE_WP/wp-config.php" | head -1 | sed "s/.*'\([^']*\)'.*/\1/")

  mysql -e "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${db_user}'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null || true

  sudo -u www-data wp config create --path="$web" \
    --dbname="$db_name" --dbuser="$db_user" --dbpass="$db_pass" --skip-check

  local admin_user admin_email admin_pass
  admin_user=$(sudo -u www-data wp --path="$TEMPLATE_WP" user list --field=user_login --role=administrator 2>/dev/null | head -1)
  admin_email=$(sudo -u www-data wp --path="$TEMPLATE_WP" option get admin_email 2>/dev/null || echo "tpb@tbj.com.br")
  admin_pass=$(openssl rand -base64 18)

  sudo -u www-data wp core install --path="$web" \
    --url="https://${domain}" \
    --title="$title" \
    --admin_user="${admin_user:-estrato}" \
    --admin_password="$admin_pass" \
    --admin_email="$admin_email" \
    --skip-email

  rsync -a "${TEMPLATE_WP}/wp-content/themes/pressgrid/" "${web}/wp-content/themes/pressgrid/"
  rsync -a "${TEMPLATE_WP}/wp-content/plugins/wordpress-seo/" "${web}/wp-content/plugins/wordpress-seo/" 2>/dev/null || true
  rsync -a "${TEMPLATE_WP}/wp-content/plugins/contact-form-7/" "${web}/wp-content/plugins/contact-form-7/" 2>/dev/null || true

  sudo -u www-data wp --path="$web" theme activate pressgrid 2>/dev/null || true
  chown -R www-data:www-data "$web"
  log "WP instalado ${domain} (admin: ${admin_user:-estrato})"
}

apply_portal() {
  local domain="$1"
  local portal_id="$2"
  local preset="$3"
  local title="$4"
  local tagline="$5"
  local web="/var/www/${domain}"

  deploy_plugins "$web"

  sudo -u www-data wp --path="$web" plugin activate estrato-portal-bootstrap estrato-rss-bootstrap estrato-publisher-bridge 2>/dev/null || true

  export ESTRATO_PORTAL="$portal_id"
  if [[ -f "${REPO}/scripts/victor/apply-portal-config.php" ]]; then
    sudo -u www-data env ESTRATO_PORTAL="$portal_id" wp --path="$web" eval-file "${REPO}/scripts/victor/apply-portal-config.php" 2>&1 || true
  else
    sudo -u www-data wp --path="$web" eval "
      if ( function_exists( 'estrato_rss_apply_preset' ) ) {
        estrato_rss_apply_preset( '${preset}' );
      }
    " 2>/dev/null || true
  fi

  sudo -u www-data wp --path="$web" option update blogname "$title" 2>/dev/null || true
  sudo -u www-data wp --path="$web" option update blogdescription "$tagline" 2>/dev/null || true
  # REST API exige permalink_structure; --hard falha em pools www-data (proc_open).
  sudo -u www-data wp --path="$web" option update permalink_structure '/%postname%/' 2>/dev/null || true
  sudo -u www-data wp --path="$web" rewrite flush 2>/dev/null || true

  local health
  health=$(curl -sk -H "Host: ${domain}" "https://${VPS_IP}/wp-json/estrato/v1/health" 2>/dev/null || echo "fail")
  log "${domain} health: ${health}"
}

main() {
  sync_repo

  for entry in "${PORTALS[@]}"; do
    IFS='|' read -r domain portal_id preset title tagline <<< "$entry"
    if [[ "$domain" == "estrato.cc" ]]; then
      continue
    fi
    short="${domain%%.${ZONE}}"
    ensure_dns "$short" "$domain"
  done

  sleep 10

  for entry in "${PORTALS[@]}"; do
    IFS='|' read -r domain portal_id preset title tagline <<< "$entry"
    if [[ "$domain" != "estrato.cc" ]]; then
      ensure_ssl "$domain"
      ensure_php_pool "$domain"
      ensure_nginx "$domain"
      provision_wordpress "$domain" "$title"
    fi
    apply_portal "$domain" "$portal_id" "$preset" "$title" "$tagline"
  done

  log "=== Deploy Victor concluído ==="
}

main "$@"
