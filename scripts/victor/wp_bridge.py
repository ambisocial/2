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


STOCK_IMAGE_HOSTS = (
    'images.unsplash.com',
    'plus.unsplash.com',
    'images.pexels.com',
    'image.pollinations.ai',
    'pollinations.ai',
)

# Mapeamento categoria pipeline → slug de autor WP (Sprint 3).
CATEGORY_AUTHOR_SLUGS = {
    'economia': 'ana-economia',
    'mercados': 'marcos-mercados',
    'negocios': 'lucia-negocios',
    'financas-pessoais': 'pedro-financas',
    'criptomoedas': 'rafa-cripto',
    'agronegocio': 'julia-agro',
    'mundo': 'henrique-mundo',
}


def _author_slug_for_category(category: str) -> str:
    slug = (category or 'negocios').strip().lower()
    return CATEGORY_AUTHOR_SLUGS.get(slug, 'lucia-negocios')


def _is_stock_image(url: str) -> bool:
    if not url:
        return True
    u = url.lower()
    return any(h in u for h in STOCK_IMAGE_HOSTS)


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
        'author_slug': _author_slug_for_category(article.get('categoria') or 'negocios'),
        'source_url': article.get('fonte_url') or '',
        'external_id': str(article.get('id') or article.get('slug') or ''),
        'status': 'publish',
    }
    if article.get('imagem_url') and not _is_stock_image(article['imagem_url']):
        payload['image_url'] = article['imagem_url']

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
