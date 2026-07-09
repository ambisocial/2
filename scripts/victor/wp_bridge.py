#!/usr/bin/env python3
"""Publica artigos no WordPress via estrato-publisher-bridge."""
import json
import os
import urllib.error
import urllib.request

# portal slug -> (publish_url, secret env var)
WP_PORTALS = {
    'estrato': {
        'url': os.getenv('WP_ESTRATO_URL', 'https://estrato.cc/wp-json/estrato/v1/publish'),
        'secret_env': 'WP_ESTRATO_SECRET',
    },
}


def publish_to_wordpress(portal_slug, article):
    """Envia artigo publicado no Supabase para o WordPress do portal."""
    cfg = WP_PORTALS.get(portal_slug)
    if not cfg:
        return True, 'skip'

    secret = os.getenv(cfg['secret_env'], '')
    if not secret:
        return False, f'missing {cfg["secret_env"]}'

    payload = {
        'title': article.get('titulo') or '',
        'content': article.get('conteudo') or '',
        'excerpt': article.get('resumo') or '',
        'category': article.get('categoria') or 'negocios',
        'source_url': article.get('fonte_url') or '',
        'external_id': str(article.get('id') or article.get('slug') or ''),
        'status': 'publish',
    }

    data = json.dumps(payload).encode()
    req = urllib.request.Request(
        cfg['url'],
        data=data,
        headers={
            'Content-Type': 'application/json',
            'X-Estrato-Secret': secret,
            'User-Agent': 'EstratoPipeline/1.0',
        },
        method='POST',
    )

    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            body = json.loads(resp.read().decode())
            return True, body.get('url') or 'ok'
    except urllib.error.HTTPError as e:
        err = e.read().decode()[:300]
        return False, f'HTTP {e.code}: {err}'
    except Exception as e:
        return False, str(e)
