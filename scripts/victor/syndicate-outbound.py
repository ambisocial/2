#!/usr/bin/env python3
"""Dispara notícias Estrato para agregadores externos (não self-hosted)."""
from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT / 'index-bot'))

WP = Path(os.getenv('ESTRATO_WP_PATH', '/var/www/estrato.cc')).resolve()
LOG = Path(os.getenv('ESTRATO_SYNDICATE_LOG', '/var/log/estrato/syndicate.log'))
SECRETS_DIR = Path(os.getenv('ESTRATO_SECRETS_DIR', '/root/.secrets'))
INDEXNOW_KEY = WP / 'estrato-indexnow-key.txt'
DOMAIN = os.getenv('ESTRATO_DOMAIN', 'https://estrato.cc').rstrip('/')
PLATPHORM_URL = 'https://docs.platphormnews.com/api/v1/submissions'
FREESPOKE_URL = os.getenv('FREESPOKE_PUBLISHER_URL', 'https://api.partners.freespoke.com/v1/content')
NEO_TIMES_URL = os.getenv('NEO_TIMES_API_URL', 'https://thetimes.neoworlder.com/api/v1/submissions')
MSN_FEED_PATH = WP / 'msn-feed.xml'
SITEMAPS = [
    f'{DOMAIN}/sitemap_index.xml',
    f'{DOMAIN}/news-sitemap.xml',
    f'{DOMAIN}/msn-feed.xml',
]


def load_secret(name: str) -> str:
    env_key = name.upper().replace('-', '_')
    if os.getenv(env_key):
        return os.getenv(env_key, '').strip()
    for path in (SECRETS_DIR / f'{name}.env', SECRETS_DIR / name):
        if not path.is_file():
            continue
        for line in path.read_text(encoding='utf-8').splitlines():
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, value = line.split('=', 1)
            if key.strip() == env_key or key.strip() == name:
                return value.strip().strip('"').strip("'")
    return ''


def log_entry(entry: dict) -> None:
    LOG.parent.mkdir(parents=True, exist_ok=True)
    entry.setdefault('ts', datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'))
    with LOG.open('a', encoding='utf-8') as handle:
        handle.write(json.dumps(entry, ensure_ascii=False) + '\n')


def http_json(
    method: str,
    url: str,
    payload: dict | None = None,
    headers: dict | None = None,
    timeout: int = 25,
) -> dict:
    data = None
    req_headers = {'User-Agent': 'EstratoSyndicate/1.1'}
    if headers:
        req_headers.update(headers)
    if payload is not None:
        data = json.dumps(payload).encode('utf-8')
        req_headers['Content-Type'] = 'application/json'
    req = urllib.request.Request(url, data=data, headers=req_headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            body = resp.read().decode('utf-8', errors='replace')
            parsed: dict | list | str = {}
            if body:
                try:
                    parsed = json.loads(body)
                except json.JSONDecodeError:
                    parsed = {'raw': body[:500]}
            return {'ok': True, 'status': resp.status, 'body': parsed}
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
        return {'ok': False, 'skipped': True, 'error': 'indexnow_key_missing'}
    key = INDEXNOW_KEY.read_text(encoding='utf-8').strip()
    payload = {
        'host': 'estrato.cc',
        'key': key,
        'keyLocation': f'{DOMAIN}/estrato-indexnow-key.txt',
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
        return {'ok': False, 'skipped': True, 'error': f'gsc_import:{exc}'}
    try:
        data = request_indexing(url)
        return {'ok': True, 'body': data}
    except Exception as exc:  # noqa: BLE001
        return {'ok': False, 'error': str(exc)}


def ping_sitemaps() -> dict:
    """Google/Bing descontinuaram sitemap ping (2023). Mantemos tentativa informativa."""
    results: dict[str, dict] = {}
    for sm in SITEMAPS:
        encoded = urllib.parse.quote(sm, safe='')
        for engine, endpoint in (
            ('google', 'https://www.google.com/ping?sitemap='),
            ('bing', 'https://www.bing.com/ping?sitemap='),
        ):
            key = f'{engine}:{sm}'
            try:
                with urllib.request.urlopen(f'{endpoint}{encoded}', timeout=15) as resp:
                    results[key] = {'ok': True, 'status': resp.status}
            except urllib.error.HTTPError as exc:
                results[key] = {
                    'ok': False,
                    'status': exc.code,
                    'deprecated': exc.code in (404, 410),
                    'error': str(exc),
                }
            except Exception as exc:  # noqa: BLE001
                results[key] = {'ok': False, 'error': str(exc)}
    # IndexNow + GSC substituem ping; não falhar por deprecação.
    ok = True
    return {'ok': ok, 'deprecated': True, 'note': 'use IndexNow + GSC', 'results': results}


def ping_freespoke(article: dict) -> dict:
    token = load_secret('FREESPOKE_PUBLISHER_API_KEY')
    if not token:
        return {'ok': False, 'skipped': True, 'error': 'freespoke_key_missing'}
    payload = {
        'url': article['url'],
        'title': article.get('title') or 'Notícia Estrato',
        'description': article.get('excerpt') or '',
        'content': article.get('content_html') or article.get('excerpt') or article['url'],
        'authors': [{'id': 'estrato', 'name': article.get('author') or 'Estrato'}],
        'keywords': article.get('keywords') or ['finance', 'economia', 'brasil'],
        'publish_time': article.get('published_rfc3339') or datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'),
        'image_url': article.get('image_url') or '',
        'content_medium': 'MEDIUM_WEBPAGE',
    }
    if os.getenv('FREESPOKE_TEST_MODE', '').lower() in ('1', 'true', 'yes'):
        payload['test_mode'] = True
    return http_json(
        'POST',
        FREESPOKE_URL,
        payload,
        headers={'Authorization': f'Bearer {token}'},
    )


def ping_neotimes(article: dict) -> dict:
    token = load_secret('NEO_TIMES_API_TOKEN')
    if not token:
        return {'ok': False, 'skipped': True, 'error': 'neotimes_key_missing'}
    body = article.get('excerpt') or article.get('content_text') or article['url']
    if article.get('content_text'):
        body = f"{article.get('excerpt', '')}\n\n{article['content_text'][:4000]}"
    payload = {
        'agent': {'name': 'estrato_syndication', 'version': '1.1'},
        'submission': {
            'type': 'article',
            'title': article.get('title') or 'Notícia Estrato',
            'body': body[:8000],
            'summary': (article.get('excerpt') or '')[:500],
            'tags': article.get('keywords') or ['finance', 'brazil'],
            'category': article.get('category') or 'economia',
        },
        'context': {'source_url': article['url']},
        'controls': {'priority': 'normal', 'draft': False},
    }
    return http_json(
        'POST',
        NEO_TIMES_URL,
        payload,
        headers={'Authorization': f'Bearer {token}'},
    )


def google_news_note() -> dict:
    """Google News é algorítmico (sem API de submissão desde 2024)."""
    return {
        'ok': True,
        'mode': 'algorithmic',
        'note': 'news-sitemap + GSC + IndexNow; sem Publisher Center RSS',
        'news_sitemap': f'{DOMAIN}/news-sitemap.xml',
    }


def wp_recent_articles(limit: int = 10) -> list[dict]:
    cmd = [
        'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
        'post', 'list', '--post_status=publish', '--post_type=post',
        f'--posts_per_page={limit}', '--orderby=date', '--order=desc',
        '--fields=ID,url,post_title,post_excerpt,post_date,post_content',
        '--format=json',
    ]
    out = subprocess.check_output(cmd, text=True)
    rows = json.loads(out)
    if not isinstance(rows, list):
        return []

    articles: list[dict] = []
    for row in rows:
        post_id = int(row.get('ID') or 0)
        url = (row.get('url') or '').strip()
        if not url:
            continue
        article = {
            'id': post_id,
            'url': url,
            'title': row.get('post_title') or '',
            'excerpt': row.get('post_excerpt') or '',
            'content_text': re.sub(r'\s+', ' ', re.sub('<[^>]+>', ' ', row.get('post_content') or '')).strip(),
            'content_html': (row.get('post_content') or '')[:12000],
            'author': 'Estrato',
            'category': 'economia',
            'keywords': ['finance', 'economia', 'brasil'],
            'image_url': '',
        }
        if row.get('post_date'):
            try:
                dt = datetime.strptime(row['post_date'], '%Y-%m-%d %H:%M:%S').replace(tzinfo=timezone.utc)
                article['published_rfc3339'] = dt.strftime('%Y-%m-%dT%H:%M:%SZ')
            except ValueError:
                pass
        if post_id:
            article.update(_wp_post_meta(post_id))
        articles.append(article)
    return articles


def _wp_post_meta(post_id: int) -> dict:
    meta: dict = {}
    try:
        author_cmd = [
            'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
            'post', 'meta', 'list', str(post_id), '--format=json',
        ]
        rows = json.loads(subprocess.check_output(author_cmd, text=True))
        thumb_id = None
        for row in rows if isinstance(rows, list) else []:
            key = row.get('meta_key')
            val = row.get('meta_value')
            if key == '_thumbnail_id' and val:
                thumb_id = val
        author_out = subprocess.check_output(
            [
                'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
                'post', 'get', str(post_id), '--field=post_author',
            ],
            text=True,
        ).strip()
        if author_out:
            name = subprocess.check_output(
                [
                    'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
                    'user', 'get', author_out, '--field=display_name',
                ],
                text=True,
            ).strip()
            if name:
                meta['author'] = name
        terms = json.loads(
            subprocess.check_output(
                [
                    'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
                    'post', 'term', 'list', str(post_id), 'category', '--format=json',
                ],
                text=True,
            )
        )
        if isinstance(terms, list) and terms:
            meta['category'] = terms[0].get('slug') or 'economia'
            meta['keywords'] = [terms[0].get('name', 'economia'), 'brasil', 'finance']
        if thumb_id:
            img = subprocess.check_output(
                [
                    'sudo', '-u', 'www-data', 'wp', f'--path={WP}',
                    'post', 'get', thumb_id, '--field=guid',
                ],
                text=True,
            ).strip()
            if img:
                meta['image_url'] = img
    except (subprocess.CalledProcessError, json.JSONDecodeError, ValueError):
        pass
    return meta


def generate_msn_feed(limit: int = 50) -> dict:
    articles = wp_recent_articles(limit)
    ET.register_namespace('media', 'http://search.yahoo.com/mrss/')
    ET.register_namespace('dc', 'http://purl.org/dc/elements/1.1/')
    ET.register_namespace('mi', 'http://schemas.ingestion.microsoft.com/common/')
    ET.register_namespace('content', 'http://purl.org/rss/1.0/modules/content/')
    rss = ET.Element('rss', {
        'version': '2.0',
        'xmlns:media': 'http://search.yahoo.com/mrss/',
        'xmlns:dc': 'http://purl.org/dc/elements/1.1/',
        'xmlns:mi': 'http://schemas.ingestion.microsoft.com/common/',
        'xmlns:content': 'http://purl.org/rss/1.0/modules/content/',
    })
    channel = ET.SubElement(rss, 'channel')
    ET.SubElement(channel, 'title').text = 'Estrato Finance'
    ET.SubElement(channel, 'link').text = DOMAIN
    ET.SubElement(channel, 'description').text = 'Notícias de economia, mercados e negócios no Brasil'
    ET.SubElement(channel, 'language').text = 'pt-BR'
    ET.SubElement(channel, 'lastBuildDate').text = datetime.now(timezone.utc).strftime('%a, %d %b %Y %H:%M:%S +0000')

    for article in articles:
        item = ET.SubElement(channel, 'item')
        ET.SubElement(item, 'title').text = article.get('title') or 'Estrato'
        ET.SubElement(item, 'link').text = article['url']
        guid = ET.SubElement(item, 'guid', {'isPermaLink': 'true'})
        guid.text = article['url']
        if article.get('published_rfc3339'):
            pub = datetime.strptime(article['published_rfc3339'], '%Y-%m-%dT%H:%M:%SZ')
            ET.SubElement(item, 'pubDate').text = pub.strftime('%a, %d %b %Y %H:%M:%S +0000')
        ET.SubElement(item, 'dc:creator').text = article.get('author') or 'Estrato'
        desc = article.get('excerpt') or article.get('content_text', '')[:300]
        ET.SubElement(item, 'description').text = desc
        if article.get('content_html'):
            content_el = ET.SubElement(
                item,
                '{http://purl.org/rss/1.0/modules/content/}encoded',
            )
            content_el.text = article['content_html'][:8000]
        if article.get('image_url'):
            media = ET.SubElement(item, 'media:content', {
                'url': article['image_url'],
                'medium': 'image',
            })
            media.set('type', 'image/jpeg')
            ET.SubElement(item, 'media:thumbnail', {'url': article['image_url']})
        ET.SubElement(item, 'mi:shortTitle').text = (article.get('title') or 'Estrato')[:40]
        ET.SubElement(item, 'mi:dateTimeWritten').text = article.get('published_rfc3339', '')
        rights = ET.SubElement(item, 'mi:hasSyndicationRights')
        rights.text = 'true'

    tree = ET.ElementTree(rss)
    ET.indent(tree, space='  ')
    MSN_FEED_PATH.parent.mkdir(parents=True, exist_ok=True)
    tree.write(MSN_FEED_PATH, encoding='utf-8', xml_declaration=True)
    return {'ok': True, 'path': str(MSN_FEED_PATH), 'items': len(articles), 'url': f'{DOMAIN}/msn-feed.xml'}


def syndicate(article: dict, *, ping_maps: bool = False) -> dict:
    url = article['url']
    title = article.get('title') or ''
    content = article.get('excerpt') or article.get('content_text') or ''
    results = {
        'url': url,
        'indexnow': ping_indexnow(url),
        'platphorm': ping_platphorm(url, title, content),
        'gsc': ping_gsc(url),
        'google_news': google_news_note(),
        'freespoke': ping_freespoke(article),
        'neotimes': ping_neotimes(article),
    }
    if ping_maps:
        results['sitemaps'] = ping_sitemaps()
    ok = any(
        r.get('ok') or r.get('status') in (200, 202)
        for r in results.values()
        if isinstance(r, dict) and not r.get('skipped')
    )
    log_entry({'url': url, 'title': title, 'ok': ok, 'results': results})
    print(json.dumps(results, indent=2, ensure_ascii=False))
    return results


def main() -> int:
    parser = argparse.ArgumentParser(description='Syndicate Estrato article URLs to external aggregators')
    parser.add_argument('--url', help='Single article URL')
    parser.add_argument('--title', default='', help='Article title')
    parser.add_argument('--content', default='', help='Short summary/body')
    parser.add_argument('--recent', type=int, default=0, help='Syndicate N recent posts')
    parser.add_argument('--ping-sitemaps', action='store_true', help='Ping Google/Bing sitemaps')
    parser.add_argument('--generate-msn-feed', action='store_true', help='Generate MSN-compatible RSS feed')
    parser.add_argument('--msn-limit', type=int, default=50, help='Items in MSN feed')
    parser.add_argument('--wp-path', default='', help='WordPress root path')
    parser.add_argument('--domain', default='', help='Portal base URL')
    args = parser.parse_args()

    global WP, DOMAIN, INDEXNOW_KEY, MSN_FEED_PATH, SITEMAPS
    if args.wp_path:
        WP = Path(args.wp_path).resolve()
        INDEXNOW_KEY = WP / 'estrato-indexnow-key.txt'
        MSN_FEED_PATH = WP / 'msn-feed.xml'
    if args.domain:
        DOMAIN = args.domain.rstrip('/')
    SITEMAPS = [
        f'{DOMAIN}/sitemap_index.xml',
        f'{DOMAIN}/news-sitemap.xml',
        f'{DOMAIN}/msn-feed.xml',
    ]

    if args.generate_msn_feed:
        print(json.dumps(generate_msn_feed(args.msn_limit), indent=2, ensure_ascii=False))
        return 0

    if args.ping_sitemaps:
        print(json.dumps(ping_sitemaps(), indent=2, ensure_ascii=False))
        return 0

    if args.url:
        article = {
            'url': args.url.strip(),
            'title': args.title,
            'excerpt': args.content,
            'content_text': args.content,
        }
        syndicate(article, ping_maps=True)
        return 0

    if args.recent > 0:
        articles = wp_recent_articles(args.recent)
        for idx, article in enumerate(articles):
            syndicate(article, ping_maps=(idx == 0))
        if args.ping_sitemaps or args.recent > 0:
            generate_msn_feed()
        return 0

    parser.print_help()
    return 1


if __name__ == '__main__':
    raise SystemExit(main())
