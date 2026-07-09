# Estrato Portal Factory

Fábrica de portais de notícias WordPress — 100% automatizável pelo Cloud Agent.

## Temas

| Portal | Tema | Repo |
|--------|------|------|
| **Finanças** (estrato.cc) | [PressGrid v2.5](https://github.com/stantchev/PressGrid-WordPress-Theme) | Layout builder, ads, forex ticker |
| **Generalista** | [Newspack Scott](https://github.com/Automattic/newspack-theme) + plugin | Automattic, redações reais |

## Config por portal

Edite `portals/*.yaml` e faça deploy. Exemplo:

- `portals/estrato-finance.yaml` — estrato.cc + PressGrid
- `portals/estrato-general.yaml` — template generalista + Newspack

## Plugins

| Plugin | Função |
|--------|--------|
| `estrato-portal-bootstrap` | Instala tema, ativa RSS, aplica config |
| `estrato-rss-bootstrap` | Feeds RSS reais (categorias BR) |
| `estrato-publisher-bridge` | REST API para pipeline Victor |

### Bridge API (pipeline Victor → WordPress)

```http
POST /wp-json/estrato/v1/publish
X-Estrato-Secret: <secret em WP Admin → Estrato Bridge>
Content-Type: application/json

{
  "title": "Título da notícia",
  "content": "<p>Corpo HTML</p>",
  "category": "economia",
  "source_url": "https://...",
  "source_name": "G1",
  "external_id": "unique-id-for-dedup"
}
```

## Pipeline Victor (o que descobrimos)

No VPS **Victor** (`187.127.12.186`) rodam crons Python (não estão no GitHub público):

| Cron | Intervalo | Função provável |
|------|-----------|-----------------|
| estrato-scout | 1h | Coleta fontes |
| estrato-curator | 5min | Curadoria |
| estrato-writer | 5min | Redação |
| estrato-publisher | 5min | Publicação |
| estrato-linker | 5min | Links internos |

**Próximo passo técnico:** auditar `/root` ou `/opt/estrato` no Victor via SSH e apontar `publisher` para o endpoint REST acima.

Projetos relacionados no GitHub `ambisocial`:

- `estrato-politica` — stack Supabase + TanStack (SSR, não WordPress)
- `estrato-news-hub` — React + Supabase
- Victor = pipeline Python separado

## Migração estrato.cc → Victor

**Status (2026-07-09): concluída**

| Item | Estado |
|------|--------|
| Zona Cloudflare `estrato.cc` | Ativa — NS `anahi.ns.cloudflare.com`, `ignat.ns.cloudflare.com` |
| DNS A → Victor | `187.127.12.186` (Cloudflare proxy + SSL Full) |
| SSL origem | Let's Encrypt via certbot DNS-Cloudflare (`/etc/letsencrypt/live/estrato.cc/`) |
| Imagens Home | PressGrid exige `post_thumbnail` — bridge/RSS fazem sideload; backfill em `/scripts/victor/backfill-featured-images.php` |
| WordPress | `/var/www/estrato.cc` — tema **PressGrid 2.5** |
| Plugins Estrato | portal-bootstrap, rss-bootstrap, publisher-bridge |
| Pipeline → WP | `writer.py` chama `wp_bridge.py` para portal `estrato` |

Credenciais WP no Victor: `/root/estrato-cc-wp.env`  
Bridge secret também em `/var/www/estrato/.env.local` (`WP_ESTRATO_SECRET`).

**Cloudflare:** o token `cfut_…` do doc mestre retorna inválido no cloud agent; use a **Global API Key** (`cfk_…`) com email `tpb@ambi.social` — mesma credencial em `cf_api.py` no Victor.

### Fase A — Infra (concluída)

1. Zona `estrato.cc` no Cloudflare
2. NS Hostinger → Cloudflare
3. WordPress + nginx + PHP 8.3-FPM em `/var/www/estrato.cc`
4. SSL via Cloudflare flexible (origin HTTP na porta 80)

### Fase B — Deploy (GitHub → Victor)

Opção 1: Webhook existente `http://72.61.129.243:3100/api/webhook/github`  
Opção 2: GitHub Actions + SSH para Victor (secrets no repo)

### Fase C — Bootstrap

1. Upload/ativar `estrato-portal-bootstrap.zip` + `estrato-rss-bootstrap.zip` + `estrato-publisher-bridge.zip`
2. `wp option update estrato_portal_config '<json do yaml>'`
3. Reativar plugin bootstrap
4. Conectar publisher Victor ao bridge

## Build local

```bash
bash scripts/build-portal-packages.sh
```

## Links dos temas (demos)

- PressGrid: https://stantchev.github.io/PressGrid-WordPress-Theme/
- Newspack: https://newspack.com
- Newspack child Scott: ver releases em https://github.com/Automattic/newspack-theme/releases
