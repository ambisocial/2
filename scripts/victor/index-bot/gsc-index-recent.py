#!/usr/bin/env python3
"""Inspeciona posts recentes no GSC (últimas 24h publicados)."""
import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT / 'index-bot'))

from gsc_api import list_sites, request_indexing  # noqa: E402

WP = '/var/www/estrato.cc'
LIMIT = int(__import__('os').getenv('GSC_INDEX_LIMIT', '10'))


def recent_urls() -> list[str]:
    out = subprocess.check_output(
        [
            'sudo', '-u', 'www-data', 'wp', f'--path={WP}', 'post', 'list',
            '--post_status=publish', '--post_type=post',
            f'--posts_per_page={LIMIT}', '--orderby=date', '--order=desc',
            '--field=url',
        ],
        text=True,
    )
    return [u.strip() for u in out.splitlines() if u.strip()]


def main() -> int:
    if not list_sites():
        print('GSC: sem acesso — confira Usuários no Search Console')
        return 1

    results = []
    for url in recent_urls():
        try:
            results.append(request_indexing(url))
        except Exception as exc:  # noqa: BLE001
            results.append({'url': url, 'error': str(exc)})

    print(json.dumps(results, indent=2, ensure_ascii=False))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
