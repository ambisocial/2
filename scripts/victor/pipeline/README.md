# Pipeline Estrato (Victor) — cópia versionada dos scripts em /var/www/estrato/pipeline

Arquivos sincronizados por `scripts/victor/setup-pending-all.sh`:

- `wp_bridge.py` — publish REST + skip de cats legado + exige `fonte_url`
- `writer.py` — filtra politica/etc no portal `estrato` antes de formatar
- `publisher.py` — backfill de imagens (Unsplash ainda existe no servidor; bridge rejeita stock)

Deploy: `bash scripts/victor/setup-pending-all.sh` no Victor.
