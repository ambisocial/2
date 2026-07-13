#!/usr/bin/env bash
# Runbook produção Estrato (S7.6) — comandos canônicos no Victor.
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"

cat <<EOF
=== ESTRATO RUNBOOK PRODUÇÃO (Victor) ===
Repo: ${REPO}
Data: $(date -Iseconds)

## Deploy / sync
  cd ${REPO} && git pull origin Preditivit
  bash scripts/victor/setup-selfhosted-fonts.sh
  bash scripts/victor/setup-s7-perf-all-portals.sh
  bash scripts/victor/setup-s7-autonomous.sh

## Validação
  bash scripts/victor/check-portal-regression-all.sh --strict
  bash scripts/victor/check-schema-sample.sh
  bash scripts/victor/check-rich-results-sample.sh
  bash scripts/victor/audit-e2e-production.sh --strict
  ESTRATO_LIGHTHOUSE_MODE=local bash scripts/victor/check-portal-lighthouse-all.sh

## Indexação (self-hosted GSC bot)
  bash scripts/victor/setup-estrato-gsc.sh
  python3 scripts/victor/check-gsc-coverage.py

## Conteúdo / gate
  bash scripts/victor/setup-sprint-e3-all-portals.sh
  bash scripts/victor/backfill-editorial-thumbnails.php  # via portal_wp eval-file

## Secrets (ação humana quando disponível)
  /root/.secrets/newsletter.env  → bash scripts/victor/setup-newsletter-from-secrets.sh
  /root/estrato-gsc-service-account.json
  Google News Publisher Center (manual)

## Logs
  /var/www/estrato/repo/logs/
  /var/log/estrato/gsc-index.log
EOF
