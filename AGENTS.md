# AGENTS.md

## Cursor Cloud specific instructions

This repo is the **Estrato Portal Factory**: custom WordPress plugins (PHP), portal
config (`portals/*.yaml`, `*-taxonomy.php`), the Jannah theme, and ops/deploy scripts.
There is **no local web app**. The live portals run on a remote VPS ("Victor") + Hostinger
behind Cloudflare, so most of `scripts/victor/**` and `scripts/*.mjs` (SSH / WP-CLI /
Hostinger API / Cloudflare) are **remote-only and not runnable locally** — they need
secrets (`VICTOR_SSH_*`, `HOSTINGER_API_TOKEN`) and live infrastructure.

There is **no dependency manifest** (`package.json`, `composer.json`, `requirements.txt`,
`Gemfile`). The toolchain is system binaries: `node`, `python3`, `php`, `zip`, `bash`.
The `.gitignore` mentions Jekyll/GitHub Pages but there is no Jekyll site here.

What runs locally (no network/secrets needed):

- **Lint / test (same as CI):** `bash scripts/victor/check-portal-regression-ci.sh`.
  Degrades gracefully when `php` is absent (skips `php -l`); with `php-cli` installed it
  runs the full plugin lint. This is the check in `.github/workflows/estrato-anti-regression.yml`.
- **Build packages:** `bash scripts/build-portal-packages.sh` (needs `zip` + `node`).
  It **regenerates the committed root `*.zip` artifacts** (`estrato-portal-bootstrap.zip`,
  `estrato-publisher-bridge.zip`, `estrato-rss-bootstrap.zip`). If you only built to verify,
  restore them with `git checkout -- '*.zip'` so the tree stays clean.
- **Bash syntax:** `bash -n scripts/victor/check-portal-regression*.sh`.

Gotchas:

- `php -l` over the plugins requires `php-cli` (the startup update script installs it; CI
  installs it too). Prod runs PHP 8.3, so keep the local `php-cli` on 8.x.
- Running the CI check's `python3 -m py_compile` step leaves a `scripts/victor/__pycache__/`
  directory (untracked) — delete it before committing.
