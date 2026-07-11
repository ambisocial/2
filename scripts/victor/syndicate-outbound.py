#!/usr/bin/env python3
"""Dispara notícias Estrato para agregadores (IndexNow, PlatPhorm, GSC)."""
from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
import urllib.error
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT / 'index-bot'))

WP = Path('/var/www/estrato.cc')
LOG = Path('/var/log/estrato/syndicate.log')
INDEXNOW_KEY = WP / 'estrato-indexnow-key.txt'
PLATPHORM_URL = 'https://docs.platphormnews.com/api/v1/submissions'


def log_entry(entry: dict) -> None:
    LOG.parent.mkdir(parents=True, exist_ok=True)
    entry.setdefault('ts', datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'))
    with LOG.open('a', encoding='utf-8') as handle:
        handle.write(json.dumps(entry, ensure_ascii=False) + '\n')


def http_json(method: str, url: str, payload: dict | None = None, timeout: int = 25) -> dict:
    data = None
    headers = {'User-Agent': 'EstratoSyndicate/1.0'}
    if payload is not None:
        data = json.dumps(payload).encode('utf-8')
        headers['Content-Type'] = 'application/json'
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            body = resp.read().decode('utf-8', errors='replace')
            return {'ok': True, 'status': resp.status, 'body': json.loads(body) if body else {}}
    except urllib.error.HTTPError as exc:
        body = exc.read().decode('utf-8', errors='replace')
        try:
            parsed = json.loads(body) if body else {}
        except json.JSONDecodeError:
            parsed = {'raw': body[:500]}
        return {'ok': False, 'status': exc.code, 'body': parsed}
    except Exception as exc:  # noqa: BLE001
        return {'ok': False, 'status': 0, 'error': str(exc)}


def ping_indexnow(url: str) -> dict:
    if not INDEXNOW_KEY.is_file():
        return {'ok': False, 'error': 'indexnow_key_missing'}
    key = INDEXNOW_KEY.read_text(encoding='utf-8').strip()
    payload = {
        'host': 'estrato.cc',
        'key': key,
        'keyLocation': 'https://estrato.cc/estrato-indexnow-key.txt',
        'urlList': [url],
    }
    return http_json('POST', 'https://api.indexnow.org/indexnow', payload)


def ping_platphorm(url: str, title: str, content: str) -> dict:
    payload = {
        'title': title or 'Notícia Estrato',
        'content': content or f'Publicação editorial no Estrato.\n\n{url}',
        'source_url': url,
        'sourceidentifier': 'estrato.cc',
        'authorname': 'Estrato',
        'metadata': {'category': 'finance'},
    }
    result = http_json('POST', PLATPHORM_URL, payload)
    if result.get('ok') and isinstance(result.get('body'), dict):
        data = result['body'].get('data') or {}
        result['published_url'] = data.get('url')
        result['status'] = data.get('status')
    return result


def ping_gsc(url: str) -> dict:
    try:
        from gsc_api import request_indexing  # noqa: WPS433
    except ImportError as exc:
        return {'ok': False, 'error': f'gsc_import:{exc}'}
    try:
        data = request_indexing(url)
        return {'ok': True, 'body': data}
    except Exception as exc:  # noqa: BLE001
        return {'ok': False, 'error': str(exc)}


def wp_recent_urls(limit: int = 10) -> list[dict]:
    cmd = [
        'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
        'post', 'list', '--post_status=publish', '--post_type=post',
        f'--posts_per_page={limit}', '--orderby=date', '--order=desc',
        '--fields=url,post_title,post_excerpt', '--format=json',
    ]
    out = subprocess.check_output(cmd, text=True)
    rows = json.loads(out)
    return rows if isinstance(rows, list) else []


def syndicate(url: str, title: str = '', content: str = '') -> dict:
    results = {
        'url': url,
        'indexnow': ping_indexnow(url),
        'platphorm': ping_platphorm(url, title, content),
        'gsc': ping_gsc(url),
    }
    ok = any(
        r.get('ok') or r.get('status') in (200, 202)
        for r in results.values()
        if isinstance(r, dict)
    )
    log_entry({'url': url, 'title': title, 'ok': ok, 'results': results})
    print(json.dumps(results, indent=2, ensure_ascii=False))
    return results


def main() -> int:
    parser = argparse.ArgumentParser(description='Syndicate Estrato article URLs')
    parser.add_argument('--url', help='Single article URL')
    parser.add_argument('--title', default='', help='Article title')
    parser.add_argument('--content', default='', help='Short summary/body')
    parser.add_argument('--recent', type=int, default=0, help='Syndicate N recent posts')
    args = parser.parse_args()

    if args.url:
        syndicate(args.url.strip(), args.title, args.content)
        return 0

    if args.recent > 0:
        for row in wp_recent_urls(args.recent):
            syndicate(
                row.get('url', '').strip(),
                row.get('post_title', '') or '',
                row.get('post_excerpt', '') or '',
            )
        return 0

    parser.print_help()
    return 1


if __name__ == '__main__':
    raise SystemExit(main())
