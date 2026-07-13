#!/usr/bin/env python3
"""Cliente Google Search Console — inspeção, indexação e métricas."""
from __future__ import annotations

import json
import os
import sys
from datetime import date, timedelta
from pathlib import Path

SCOPES = ['https://www.googleapis.com/auth/webmasters']
DEFAULT_KEY = Path('/root/estrato-gsc-service-account.json')
FALLBACK_KEY = Path(__file__).resolve().parent / 'estrato-gsc-service-account.json'


def key_path() -> Path:
    env = os.getenv('GSC_SERVICE_ACCOUNT_FILE', '').strip()
    if env:
        return Path(env)
    if DEFAULT_KEY.is_file():
        return DEFAULT_KEY
    return FALLBACK_KEY


def site_url() -> str:
    return os.getenv('GSC_SITE_URL', 'https://estrato.cc/').strip()


def sa_email() -> str:
    try:
        data = json.loads(key_path().read_text(encoding='utf-8'))
        return str(data.get('client_email', 'service-account'))
    except (OSError, json.JSONDecodeError, TypeError):
        return 'service-account'


def build_service():
    try:
        from google.oauth2 import service_account
        from googleapiclient.discovery import build
    except ImportError as exc:
        raise SystemExit(
            'Instale: pip3 install google-api-python-client google-auth'
        ) from exc

    path = key_path()
    if not path.is_file():
        raise SystemExit(f'Chave SA ausente: {path}')

    creds = service_account.Credentials.from_service_account_file(
        str(path), scopes=SCOPES
    )
    return build('searchconsole', 'v1', credentials=creds, cache_discovery=False)


def list_sites() -> list[dict]:
    svc = build_service()
    data = svc.sites().list().execute()
    return data.get('siteEntry', [])


def resolve_site_url() -> str:
    configured = site_url()
    sites = list_sites()
    urls = {s.get('siteUrl', '') for s in sites}
    if configured in urls:
        return configured
    for candidate in ('https://estrato.cc/', 'sc-domain:estrato.cc', 'https://www.estrato.cc/'):
        if candidate in urls:
            return candidate
    if urls:
        return sorted(urls)[0]
    raise SystemExit(
        f'Nenhuma propriedade GSC acessível. Adicione {sa_email()} '
        'em Search Console → Usuários (acesso total). sites=' + ', '.join(sorted(urls))
    )


def inspect_url(url: str) -> dict:
    svc = build_service()
    site = resolve_site_url()
    body = {'inspectionUrl': url, 'siteUrl': site}
    return svc.urlInspection().index().inspect(body=body).execute()


def request_indexing(url: str) -> dict:
    """Solicita indexação via URL Inspection (equivalente ao botão no GSC)."""
    result = inspect_url(url)
    status = result.get('inspectionResult', {}).get('indexStatusResult', {})
    return {
        'url': url,
        'verdict': status.get('verdict'),
        'coverageState': status.get('coverageState'),
        'indexingState': status.get('indexingState'),
        'lastCrawlTime': status.get('lastCrawlTime'),
    }


def category_metrics(days: int = 7) -> dict[str, dict[str, int]]:
    """Impressões e cliques por prefixo /category/{slug}/ nos últimos N dias."""
    svc = build_service()
    site = resolve_site_url()
    end = date.today()
    start = end - timedelta(days=max(1, days))
    slugs = os.getenv(
        'GSC_CATEGORY_SLUGS',
        'economia,mercados,negocios,financas-pessoais,criptomoedas,agronegocio,mundo',
    ).split(',')
    out: dict[str, dict[str, int]] = {}
    for slug in slugs:
        slug = slug.strip()
        if not slug:
            continue
        body = {
            'startDate': start.isoformat(),
            'endDate': end.isoformat(),
            'dimensions': ['page'],
            'dimensionFilterGroups': [{
                'filters': [{
                    'dimension': 'page',
                    'operator': 'contains',
                    'expression': f'/category/{slug}/',
                }],
            }],
            'rowLimit': 25000,
        }
        resp = svc.searchanalytics().query(siteUrl=site, body=body).execute()
        clicks = impressions = 0
        for row in resp.get('rows', []):
            clicks += int(row.get('clicks', 0))
            impressions += int(row.get('impressions', 0))
        out[slug] = {'clicks': clicks, 'impressions': impressions}
    return out


def main() -> None:
    if len(sys.argv) < 2:
        print('Uso: gsc_api.py sites|inspect <url>|index <url>|metrics [days]')
        raise SystemExit(1)

    cmd = sys.argv[1]
    if cmd == 'sites':
        sites = list_sites()
        if not sites:
            print(
                'Nenhuma propriedade GSC. Adicione em Search Console → Usuários:\n'
                f'  {sa_email()}\n'
                'Permissão: Usuário com acesso total (propriedade estrato.cc)'
            )
            raise SystemExit(1)
        for s in sites:
            print(f"{s.get('siteUrl')} → {s.get('permissionLevel')}")
        return

    if cmd == 'inspect' and len(sys.argv) >= 3:
        print(json.dumps(inspect_url(sys.argv[2]), indent=2, ensure_ascii=False))
        return

    if cmd == 'index' and len(sys.argv) >= 3:
        print(json.dumps(request_indexing(sys.argv[2]), indent=2, ensure_ascii=False))
        return

    if cmd == 'metrics':
        days = int(sys.argv[2]) if len(sys.argv) >= 3 else 7
        print(json.dumps(category_metrics(days), indent=2, ensure_ascii=False))
        return

    raise SystemExit(f'Comando desconhecido: {cmd}')


if __name__ == '__main__':
    main()
