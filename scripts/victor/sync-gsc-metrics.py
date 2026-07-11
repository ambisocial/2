#!/usr/bin/env python3
"""Sincroniza métricas GSC por editoria → option WP estrato_gsc_manual_metrics."""
import json
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT / 'index-bot'))

from gsc_api import category_metrics, list_sites  # noqa: E402

WP = '/var/www/estrato.cc'


def main() -> int:
    sites = list_sites()
    if not sites:
        print('GSC: nenhuma propriedade — adicione estrato-gsc-bot no Search Console → Usuários')
        return 1

    print('GSC sites:')
    for s in sites:
        print(f"  {s.get('siteUrl')} ({s.get('permissionLevel')})")

    metrics = category_metrics(7)
    payload = json.dumps(metrics, ensure_ascii=False)
    subprocess.run(
        [
            'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
            'option', 'update', 'estrato_gsc_manual_metrics', payload,
            '--format=json',
        ],
        check=True,
    )
    synced_at = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
    subprocess.run(
        [
            'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
            'option', 'update', 'estrato_gsc_last_sync', synced_at,
        ],
        check=True,
    )
    print('GSC metrics synced:', payload)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
