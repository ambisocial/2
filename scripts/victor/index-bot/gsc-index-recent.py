#!/usr/bin/env python3
"""Inspeciona posts recentes no GSC — todos os portais da rede."""
from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT))

from gsc_api import list_sites, request_indexing, resolve_site_url  # noqa: E402

REPO = Path(os.getenv('ESTRATO_REPO', '/var/www/estrato/repo'))
LIMIT_PER = int(os.getenv('GSC_INDEX_LIMIT_PER_PORTAL', '5'))
MAX_TOTAL = int(os.getenv('GSC_INDEX_LIMIT', '30'))

PORTAL_PATHS = [
    ('/var/www/estrato.cc', 'estrato-finance'),
    ('/var/www/mente.estrato.cc', 'estrato-mind'),
    ('/var/www/lifestyle.estrato.cc', 'estrato-lifestyle'),
    ('/var/www/science.estrato.cc', 'estrato-science'),
    ('/var/www/sustain.estrato.cc', 'estrato-sustain'),
    ('/var/www/culture.estrato.cc', 'estrato-culture'),
]


def wp_recent_urls(wp_path: str, limit: int) -> list[str]:
    if not Path(wp_path).joinpath('wp-config.php').is_file():
        return []
    proc = subprocess.run(
        [
            'sudo', '-u', 'www-data', 'wp', f'--path={wp_path}', 'post', 'list',
            '--post_status=publish', '--post_type=post',
            f'--posts_per_page={limit}', '--orderby=date', '--order=desc',
            '--field=url',
        ],
        capture_output=True,
        text=True,
        check=False,
    )
    if proc.returncode != 0:
        return []
    return [u.strip() for u in proc.stdout.splitlines() if u.strip()]


def url_matches_property(url: str, site: str) -> bool:
    host = (urlparse(url).hostname or '').lower()
    if site.startswith('sc-domain:'):
        domain = site.replace('sc-domain:', '').lower()
        return host == domain or host.endswith('.' + domain)
    base = site.rstrip('/')
    return url.startswith(base + '/') or url.rstrip('/') == base


def main() -> int:
    if not list_sites():
        print('GSC: sem acesso — confira Usuários no Search Console')
        return 1

    site = resolve_site_url()
    collected: list[str] = []
    seen: set[str] = set()
    for wp_path, portal_id in PORTAL_PATHS:
        for url in wp_recent_urls(wp_path, LIMIT_PER):
            if url in seen:
                continue
            if not url_matches_property(url, site):
                print(f'# skip fora da propriedade GSC ({site}): {url}', file=sys.stderr)
                continue
            seen.add(url)
            collected.append(url)
            print(f'# {portal_id}: {url}', file=sys.stderr)

    if not collected:
        print('[]')
        return 0

    results = []
    for url in collected[:MAX_TOTAL]:
        try:
            results.append(request_indexing(url))
        except Exception as exc:  # noqa: BLE001
            results.append({'url': url, 'error': str(exc)})

    print(json.dumps(results, indent=2, ensure_ascii=False))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
