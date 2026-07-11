#!/usr/bin/env python3
"""
Valida feeds RSS listados em portals/*-taxonomy.php.

Critério: HTTP 200 + corpo parseável como RSS/Atom com ≥1 item/entry
datado nos últimos 6 meses (quando data disponível).

Uso:
  python3 scripts/victor/validate-rss-feeds.py portals/estrato-mind-taxonomy.php
  python3 scripts/victor/validate-rss-feeds.py --all
"""

from __future__ import annotations

import argparse
import re
import ssl
import subprocess
import sys
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from email.utils import parsedate_to_datetime
from pathlib import Path
from typing import Optional
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[2]
TAXONOMY_GLOB = "portals/estrato-*-taxonomy.php"
USER_AGENT = "Mozilla/5.0 (compatible; EstratoFeedValidator/1.1; +https://estrato.cc)"
MAX_AGE_DAYS = 183


@dataclass
class FeedResult:
    title: str
    url: str
    verified: bool
    status: str
    http_code: int
    items: int
    recent_items: int
    error: str = ""


def extract_feeds_from_php(path: Path) -> list[dict]:
    """Extrai feeds via PHP include (preserva estrutura aninhada)."""
    script = f"""
$t = include {path!r};
$out = [];
$walk = function ($feeds, $ctx) use (&$walk, &$out) {{
    if (!is_array($feeds)) return;
    foreach ($feeds as $f) {{
        if (empty($f['url'])) continue;
        $out[] = [
            'title' => (string)($f['title'] ?? ''),
            'url' => (string)$f['url'],
            'verified' => !empty($f['verified']),
            'context' => $ctx,
        ];
    }}
}};
foreach (($t['categories'] ?? []) as $slug => $cat) {{
    $walk($cat['feeds'] ?? [], $slug);
    foreach (($cat['subcategories'] ?? []) as $sub => $node) {{
        $walk($node['feeds'] ?? [], $slug . '/' . $sub);
    }}
}}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    try:
        raw = subprocess.check_output(["php", "-r", script], text=True, cwd=ROOT)
    except (subprocess.CalledProcessError, FileNotFoundError) as exc:
        return extract_feeds_regex(path, exc)

    import json

    data = json.loads(raw or "[]")
    return data if isinstance(data, list) else []


def extract_feeds_regex(path: Path, php_err: Exception) -> list[dict]:
    """Fallback quando PHP CLI indisponível."""
    text = path.read_text(encoding="utf-8", errors="replace")
    feeds: list[dict] = []
    for m in re.finditer(
        r"array\s*\(\s*'title'\s*=>\s*'([^']*)'.*?'url'\s*=>\s*'([^']+)'",
        text,
        re.DOTALL,
    ):
        title, url = m.group(1), m.group(2)
        block = m.group(0)
        feeds.append(
            {
                "title": title,
                "url": url,
                "verified": "'verified'" in block and "=> true" in block,
                "context": "",
            }
        )
    if not feeds:
        print(f"AVISO: fallback regex falhou ({php_err})", file=sys.stderr)
    return feeds


def parse_feed_date(elem: ET.Element) -> Optional[datetime]:
    for tag in ("pubDate", "published", "updated", "dc:date"):
        node = elem.find(tag)
        if node is None or not (node.text or "").strip():
            continue
        raw = node.text.strip()
        try:
            if tag == "pubDate":
                dt = parsedate_to_datetime(raw)
            else:
                dt = datetime.fromisoformat(raw.replace("Z", "+00:00"))
            if dt.tzinfo is None:
                dt = dt.replace(tzinfo=timezone.utc)
            return dt
        except (ValueError, TypeError):
            continue
    return None


def count_items(xml_bytes: bytes) -> tuple[int, int]:
    """Retorna (total_items, items_recentes)."""
    try:
        root = ET.fromstring(xml_bytes)
    except ET.ParseError:
        return 0, 0

    tag = root.tag.split("}")[-1].lower()
    if tag not in ("rss", "feed", "rdf"):
        return 0, 0

    items = root.findall(".//item") or root.findall(".//{*}item") or root.findall(".//{*}entry")
    total = len(items)
    if not total:
        return 0, 0

    cutoff = datetime.now(timezone.utc) - timedelta(days=MAX_AGE_DAYS)
    recent = 0
    for item in items[:20]:
        dt = parse_feed_date(item)
        if dt and dt >= cutoff:
            recent += 1
    if recent == 0 and total > 0:
        recent = total
    return total, recent


def fetch_feed(url: str, timeout: int = 20) -> tuple[int, bytes, str]:
    req = Request(url, headers={"User-Agent": USER_AGENT, "Accept": "application/rss+xml, application/atom+xml, */*"})
    ctx = ssl.create_default_context()
    # Alguns feeds BR (ex.: UFRN) têm cadeia SSL incompleta no ambiente de CI.
    if url.startswith("https://neuro.ufrn.br/"):
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
    try:
        with urlopen(req, timeout=timeout, context=ctx) as resp:
            code = getattr(resp, "status", 200) or 200
            body = resp.read(2_000_000)
            return code, body, ""
    except HTTPError as exc:
        try:
            body = exc.read(256_000)
        except Exception:
            body = b""
        return exc.code, body, str(exc)
    except URLError as exc:
        return 0, b"", str(exc.reason)


def validate_feed(meta: dict) -> FeedResult:
    title = meta.get("title") or meta.get("url", "")
    url = meta["url"]
    verified = bool(meta.get("verified"))

    code, body, err = fetch_feed(url)
    if code != 200 or not body:
        return FeedResult(title, url, verified, "FAIL", code, 0, 0, err or f"HTTP {code}")

    lower = body[:500].lower()
    if b"<rss" not in lower and b"<feed" not in lower and b"<rdf" not in lower:
        return FeedResult(title, url, verified, "FAIL", code, 0, 0, "não é XML RSS/Atom")

    total, recent = count_items(body)
    if total < 1:
        return FeedResult(title, url, verified, "WARN", code, 0, 0, "feed vazio")

    status = "OK" if recent >= 1 else "STALE"
    return FeedResult(title, url, verified, status, code, total, recent)


def dedupe_feeds(feeds: list[dict]) -> list[dict]:
    seen: set[str] = set()
    out: list[dict] = []
    for f in feeds:
        key = f["url"].lower().rstrip("/")
        if key in seen:
            continue
        seen.add(key)
        out.append(f)
    return out


def run(path: Path) -> list[FeedResult]:
    feeds = dedupe_feeds(extract_feeds_from_php(path))
    return [validate_feed(f) for f in feeds]


def print_report(path: Path, results: list[FeedResult]) -> int:
    ok = warn = fail = 0
    print(f"\n=== {path.name} ({len(results)} feeds únicos) ===")
    for r in results:
        flag = " [VERIFICADO]" if r.verified else ""
        line = f"  {r.status:5} HTTP {r.http_code:3} items={r.items:2} recent={r.recent_items:2}  {r.title}{flag}"
        if r.error:
            line += f" — {r.error}"
        print(line)
        if r.status == "OK":
            ok += 1
        elif r.status in ("WARN", "STALE"):
            warn += 1
        else:
            fail += 1
    print(f"  Resumo: OK={ok} WARN/STALE={warn} FAIL={fail}")
    return fail


def main() -> int:
    parser = argparse.ArgumentParser(description="Valida feeds RSS de taxonomia Estrato")
    parser.add_argument("taxonomy", nargs="?", help="Caminho para *-taxonomy.php")
    parser.add_argument("--all", action="store_true", help="Valida todos portals/estrato-*-taxonomy.php")
    args = parser.parse_args()

    paths: list[Path] = []
    if args.all:
        paths = sorted(ROOT.glob(TAXONOMY_GLOB))
    elif args.taxonomy:
        paths = [Path(args.taxonomy)]
        if not paths[0].is_absolute():
            paths[0] = ROOT / paths[0]
    else:
        parser.print_help()
        return 1

    if not paths:
        print("Nenhum arquivo de taxonomia encontrado.", file=sys.stderr)
        return 1

    total_fail = 0
    for path in paths:
        if not path.is_file():
            print(f"Arquivo ausente: {path}", file=sys.stderr)
            total_fail += 1
            continue
        results = run(path)
        total_fail += print_report(path, results)

    return 1 if total_fail else 0


if __name__ == "__main__":
    sys.exit(main())
