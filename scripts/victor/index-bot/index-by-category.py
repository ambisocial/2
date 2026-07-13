#!/usr/bin/env python3
"""IndexNow por categoria — Sprint 9 (mercados → negócios → …)."""
import json
import os
import urllib.error
import urllib.request

DOMAIN = os.getenv('ESTRATO_DOMAIN', 'https://estrato.cc').rstrip('/')
KEY_FILE = os.getenv('ESTRATO_INDEXNOW_KEY_URL', f'{DOMAIN}/estrato-indexnow-key.txt')

INDEX_ORDER = [
    'mercados',
    'negocios',
    'economia',
    'financas-pessoais',
    'criptomoedas',
    'agronegocio',
    'mundo',
]


def fetch_key() -> str:
    env_key = os.getenv('ESTRATO_INDEXNOW_KEY', '').strip()
    if env_key:
        return env_key
    try:
        with urllib.request.urlopen(KEY_FILE, timeout=15) as resp:
            return resp.read().decode('utf-8').strip().splitlines()[0]
    except Exception as e:
        print(f'Aviso: não foi possível ler chave IndexNow ({e}); pulando ping externo.')
        return ''


def category_urls() -> list[str]:
    host = DOMAIN.replace('https://', '').replace('http://', '')
    return [f'{DOMAIN}/category/{slug}/' for slug in INDEX_ORDER]


def ping_indexnow(urls: list[str], key: str) -> tuple[int, str]:
    host = DOMAIN.replace('https://', '').replace('http://', '')
    payload = json.dumps({
        'host': host,
        'key': key,
        'keyLocation': KEY_FILE,
        'urlList': urls,
    }).encode('utf-8')
    req = urllib.request.Request(
        'https://api.indexnow.org/indexnow',
        data=payload,
        headers={'Content-Type': 'application/json; charset=utf-8'},
        method='POST',
    )
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            return resp.status, resp.read(200).decode('utf-8', errors='replace')
    except urllib.error.HTTPError as e:
        return e.code, e.read(200).decode('utf-8', errors='replace')
    except Exception as e:
        return 0, str(e)


def main():
    key = fetch_key()
    if not key:
        return
    urls = category_urls()
    print(f'IndexNow {len(urls)} categorias:')
    for u in urls:
        print(f'  - {u}')
    code, body = ping_indexnow(urls, key)
    print(f'Resultado: HTTP {code} {body[:120]}')


if __name__ == '__main__':
    main()
