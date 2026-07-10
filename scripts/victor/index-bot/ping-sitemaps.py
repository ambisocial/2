#!/usr/bin/env python3
"""Ping sitemaps Google/Bing — Sprint 6 index-bot."""
import os
import urllib.parse
import urllib.request

DOMAIN = os.getenv('ESTRATO_DOMAIN', 'https://estrato.cc')
SITEMAPS = [
    f'{DOMAIN}/sitemap_index.xml',
    f'{DOMAIN}/news-sitemap.xml',
]

def ping(endpoint: str, sitemap: str) -> tuple[int, str]:
    url = f'{endpoint}{urllib.parse.quote(sitemap, safe="")}'
    try:
        with urllib.request.urlopen(url, timeout=15) as resp:
            return resp.status, resp.read(200).decode('utf-8', errors='replace')
    except Exception as e:
        return 0, str(e)

def main():
    for sm in SITEMAPS:
        print(f'--- {sm} ---')
        code, body = ping('https://www.google.com/ping?sitemap=', sm)
        print(f'Google: HTTP {code} {body[:80]}')
        code, body = ping('https://www.bing.com/ping?sitemap=', sm)
        print(f'Bing: HTTP {code} {body[:80]}')

if __name__ == '__main__':
    main()
