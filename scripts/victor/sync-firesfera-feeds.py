#!/usr/bin/env python3
"""
Extrai o diretório FIRESFERA (Google Sheets do AA40), descobre feeds RSS,
valida HTTP/XML e gera manifesto + bloco PHP para estrato-mind-taxonomy.

Fonte: https://aposenteaos40.org/diretorio-de-blogs

Uso:
  python3 scripts/victor/sync-firesfera-feeds.py
  python3 scripts/victor/sync-firesfera-feeds.py --apply portals/estrato-mind-taxonomy.php
  python3 scripts/victor/sync-firesfera-feeds.py --json portals/firesfera-feeds.json
"""

from __future__ import annotations

import argparse
import csv
import io
import json
import re
import sys
import xml.etree.ElementTree as ET
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import asdict, dataclass
from datetime import datetime, timedelta, timezone
from email.utils import parsedate_to_datetime
from pathlib import Path
from typing import Optional
from urllib.error import HTTPError, URLError
from urllib.parse import urljoin, urlparse
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[2]
SHEET_CSV = (
    "https://docs.google.com/spreadsheets/d/e/"
    "2PACX-1vSYwcITnLhpr22RCl6eIIVzi6p0QLpT1kAnNppD86BnffSWrujp66ZzOw7_YwR4hGhZ9tDwJv4dEyLZ"
    "/pub?gid=1946668026&single=true&output=csv"
)
USER_AGENT = "EstratoFIRESFERASync/1.0 (+https://estrato.cc)"
MAX_AGE_DAYS = 183
WORKERS = 8

# Já presentes na taxonomia mind (normalizado sem trailing slash).
EXISTING_FEEDS = {
    "https://aposenteaos40.org/feed",
    "https://bilionariodozero.blogspot.com/feeds/posts/default?alt=rss",
    "https://querovirarvagabundo.blogspot.com/feeds/posts/default?alt=rss",
    "https://firearrozcomfeijao.blogspot.com/feeds/posts/default?alt=rss",
    "https://professorafire.blogspot.com/feeds/posts/default?alt=rss",
    "https://viagemlenta.com/feed",
}

FOREIGN_LOCATION = re.compile(
    r"exterior|su[ií]ça|nova zel[aâ]ndia|estados unidos|canad[aá]|europa|inglaterra|portugal",
    re.I,
)
FOREIGN_HOST_FRAGMENTS = (
    "millennialrevolution",
    "choosefi.com",
    "madfientist",
    "jlcollinsnh",
    "mrmoneymustache",
    "caniretireyet",
    "rootofgood",
    "gocurrycracker",
    "esimoney.com",
    "earlyretirementnow",
    "trendsetconsulting.com",
    "ifologiapop.com",
)


@dataclass
class BlogEntry:
    blog_id: str
    name: str
    blog_url: str
    location: str
    feed_url: str = ""
    status: str = "pending"
    http_code: int = 0
    items: int = 0
    recent_items: int = 0
    tier: int = 3
    error: str = ""


def norm_url(url: str) -> str:
    u = (url or "").strip()
    if not u:
        return ""
    if not u.startswith(("http://", "https://")):
        u = "https://" + u
    parsed = urlparse(u)
    path = parsed.path.rstrip("/") or ""
    return f"{parsed.scheme}://{parsed.netloc.lower()}{path}"


def norm_feed(url: str) -> str:
    return norm_url(url).lower()


def fetch_bytes(url: str, timeout: int = 18, retries: int = 2) -> tuple[int, bytes, str]:
    last_err = ""
    for attempt in range(retries + 1):
        req = Request(
            url,
            headers={
                "User-Agent": USER_AGENT,
                "Accept": "application/rss+xml, application/atom+xml, text/html, */*",
            },
        )
        try:
            with urlopen(req, timeout=timeout) as resp:
                return getattr(resp, "status", 200) or 200, resp.read(400_000), ""
        except HTTPError as exc:
            try:
                body = exc.read(200_000)
            except Exception:
                body = b""
            last_err = str(exc)
            if exc.code in (429, 503) and attempt < retries:
                continue
            return exc.code, body, last_err
        except URLError as exc:
            last_err = str(exc.reason)
            if attempt < retries:
                continue
            return 0, b"", last_err
        except Exception as exc:
            last_err = str(exc)
            if attempt < retries:
                continue
            return 0, b"", last_err
    return 0, b"", last_err


def is_feed_xml(body: bytes) -> bool:
    if not body:
        return False
    head = body[:800].lower()
    return b"<rss" in head or b"<feed" in head or b"<rdf:rdf" in head


def parse_item_dates(body: bytes) -> tuple[int, int]:
    try:
        root = ET.fromstring(body)
    except ET.ParseError:
        return 0, 0
    items = root.findall(".//item") or root.findall(".//{*}entry")
    total = len(items)
    if not total:
        return 0, 0
    cutoff = datetime.now(timezone.utc) - timedelta(days=MAX_AGE_DAYS)
    recent = 0
    for item in items[:25]:
        for tag in ("pubDate", "published", "updated", "dc:date"):
            node = item.find(tag)
            if node is None or not (node.text or "").strip():
                continue
            raw = node.text.strip()
            try:
                dt = (
                    parsedate_to_datetime(raw)
                    if tag == "pubDate"
                    else datetime.fromisoformat(raw.replace("Z", "+00:00"))
                )
                if dt.tzinfo is None:
                    dt = dt.replace(tzinfo=timezone.utc)
                if dt >= cutoff:
                    recent += 1
                break
            except (ValueError, TypeError):
                continue
    if recent == 0 and total > 0:
        recent = min(total, 1)
    return total, recent


def discover_feed_urls(blog_url: str) -> list[str]:
    base = norm_url(blog_url)
    if not base:
        return []
    parsed = urlparse(base)
    host = parsed.netloc.lower()
    candidates: list[str] = []

    if "blogspot.com" in host or "blogger.com" in host:
        root = f"{parsed.scheme}://{host}"
        candidates.append(f"{root}/feeds/posts/default?alt=rss")
    if "wordpress.com" in host or "wordpress" in host:
        candidates.append(urljoin(base + "/", "feed/"))
    if "substack.com" in host:
        candidates.append(urljoin(base + "/", "feed"))

    candidates.extend(
        [
            urljoin(base + "/", "feed/"),
            urljoin(base + "/", "feed"),
            urljoin(base + "/", "rss.xml"),
            urljoin(base + "/", "rss"),
            urljoin(base + "/", "index.xml"),
        ]
    )

    seen: set[str] = set()
    out: list[str] = []
    for c in candidates:
        key = c.lower()
        if key not in seen:
            seen.add(key)
            out.append(c)
    return out


def validate_feed_url(url: str) -> tuple[str, int, int, int, str]:
    code, body, err = fetch_bytes(url)
    if code != 200 or not body:
        return "fail", code, 0, 0, err or f"HTTP {code}"
    if not is_feed_xml(body):
        return "fail", code, 0, 0, "não é RSS/Atom"
    total, recent = parse_item_dates(body)
    if total < 1:
        return "warn", code, 0, 0, "feed vazio"
    if recent < 1:
        return "stale", code, total, 0, "sem posts recentes"
    return "ok", code, total, recent, ""


def choose_tier(status: str, recent: int, has_rss_flag: bool) -> int:
    if status == "ok" and recent >= 3:
        return 2 if not has_rss_flag else 1
    if status == "ok":
        return 2
    if status == "stale":
        return 3
    return 0


def is_brazilian_row(row: dict) -> bool:
    url = (row.get("URL do Blog") or "").strip()
    if not url:
        return False
    host = urlparse(norm_url(url)).netloc.lower()
    if any(f in host for f in FOREIGN_HOST_FRAGMENTS):
        return False
    loc = (row.get("Cidade/Estado") or "").strip()
    if loc and FOREIGN_LOCATION.search(loc):
        return False
    return True


def download_directory() -> list[dict]:
    code, body, err = fetch_bytes(SHEET_CSV, timeout=30)
    if code != 200 or not body:
        raise RuntimeError(f"Falha ao baixar FIRESFERA CSV: {err or code}")
    text = body.decode("utf-8", errors="replace")
    return list(csv.DictReader(io.StringIO(text)))


def process_blog(row: dict) -> Optional[BlogEntry]:
    if not is_brazilian_row(row):
        return None
    name = (row.get("Nome do Blog") or row.get("Blogueiro") or "").strip()
    blog_url = norm_url(row.get("URL do Blog") or "")
    if not name or not blog_url:
        return None

    entry = BlogEntry(
        blog_id=str(row.get("ID") or "").strip(),
        name=name[:80],
        blog_url=blog_url,
        location=(row.get("Cidade/Estado") or "").strip(),
    )
    has_rss_flag = (row.get("Link RSS") or "").strip().lower() == "rss"

    for feed_url in discover_feed_urls(blog_url):
        status, code, total, recent, error = validate_feed_url(feed_url)
        if status in ("ok", "stale"):
            entry.feed_url = feed_url
            entry.status = status
            entry.http_code = code
            entry.items = total
            entry.recent_items = recent
            entry.tier = choose_tier(status, recent, has_rss_flag)
            entry.error = error
            break

    if not entry.feed_url:
        entry.status = "fail"
        entry.error = "nenhum feed RSS encontrado"
        entry.tier = 0
    return entry


def load_existing_from_taxonomy(path: Path) -> set[str]:
    if not path.is_file():
        return set(EXISTING_FEEDS)
    text = path.read_text(encoding="utf-8", errors="replace")
    urls = re.findall(r"'url'\s*=>\s*'([^']+)'", text)
    return {norm_feed(u) for u in urls} | {norm_feed(u) for u in EXISTING_FEEDS}


def php_array_entry(entry: BlogEntry, indent: str = "\t\t\t\t\t\t") -> str:
    title = entry.name.replace("'", "\\'")
    url = entry.feed_url.replace("'", "\\'")
    lines = [
        f"{indent}array(",
        f"{indent}\t'title' => '{title}',",
        f"{indent}\t'url'   => '{url}',",
        f"{indent}\t'tier'  => {entry.tier},",
    ]
    if entry.status == "ok" and entry.recent_items >= 1:
        lines.append(f"{indent}\t'firesfera' => true,")
    lines.append(f"{indent}),")
    return "\n".join(lines)


def apply_to_taxonomy(taxonomy_path: Path, entries: list[BlogEntry]) -> int:
    text = taxonomy_path.read_text(encoding="utf-8")
    marker = "'fire-consumo'"
    pos = text.find(marker)
    if pos < 0:
        raise RuntimeError("Subcategoria fire-consumo não encontrada")
    feeds_key = "'feeds'       => array("
    feeds_pos = text.find(feeds_key, pos)
    if feeds_pos < 0:
        feeds_key = "'feeds' => array("
        feeds_pos = text.find(feeds_key, pos)
    if feeds_pos < 0:
        raise RuntimeError("feeds de fire-consumo não encontrados")
    array_start = text.find("array(", feeds_pos)
    depth = 0
    array_end = -1
    for i in range(array_start, len(text)):
        ch = text[i : i + 1]
        if ch == "(":
            depth += 1
        elif ch == ")":
            depth -= 1
            if depth == 0:
                array_end = i
                break
    if array_start < 0 or array_end < 0:
        raise RuntimeError("Bloco feeds fire-consumo malformado")

    existing = load_existing_from_taxonomy(taxonomy_path)
    new_entries = [
        e
        for e in entries
        if e.feed_url and e.status in ("ok", "stale") and norm_feed(e.feed_url) not in existing
    ]
    new_entries.sort(key=lambda e: (e.tier, -e.recent_items, e.name.lower()))

    if not new_entries:
        return 0

    block = "\n".join(php_array_entry(e) for e in new_entries)
    # Inserir antes do parêntese de fechamento do array de feeds.
    updated = text[:array_end] + "\n" + block + text[array_end:]
    taxonomy_path.write_text(updated, encoding="utf-8")
    return len(new_entries)


def main() -> int:
    parser = argparse.ArgumentParser(description="Sync FIRESFERA → feeds RSS")
    parser.add_argument("--json", default=str(ROOT / "portals/firesfera-feeds.json"))
    parser.add_argument("--apply", help="Atualiza estrato-mind-taxonomy.php")
    parser.add_argument("--workers", type=int, default=WORKERS)
    args = parser.parse_args()

    print("Baixando diretório FIRESFERA (AA40)...")
    rows = download_directory()
    br_rows = [r for r in rows if is_brazilian_row(r) and (r.get("Nome do Blog") or "").strip()]
    print(f"  {len(rows)} linhas CSV, {len(br_rows)} blogs BR candidatos")

    results: list[BlogEntry] = []
    with ThreadPoolExecutor(max_workers=max(1, args.workers)) as pool:
        futures = {pool.submit(process_blog, r): r for r in br_rows}
        done = 0
        for fut in as_completed(futures):
            done += 1
            entry = fut.result()
            if entry:
                results.append(entry)
            if done % 20 == 0:
                print(f"  validados {done}/{len(br_rows)}...")

    ok = [e for e in results if e.status == "ok"]
    stale = [e for e in results if e.status == "stale"]
    fail = [e for e in results if e.status == "fail"]
    print(f"Resultado: OK={len(ok)} STALE={len(stale)} FAIL={len(fail)}")

    manifest = {
        "source": SHEET_CSV,
        "synced_at": datetime.now(timezone.utc).isoformat(),
        "total_candidates": len(br_rows),
        "validated_ok": len(ok),
        "validated_stale": len(stale),
        "failed": len(fail),
        "feeds": [asdict(e) for e in sorted(results, key=lambda x: x.name.lower())],
    }
    json_path = Path(args.json)
    json_path.parent.mkdir(parents=True, exist_ok=True)
    json_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Manifesto: {json_path}")

    if args.apply:
        tax_path = Path(args.apply)
        if not tax_path.is_absolute():
            tax_path = ROOT / tax_path
        added = apply_to_taxonomy(tax_path, ok + stale)
        print(f"Taxonomia atualizada: +{added} feeds em fire-consumo ({tax_path})")

    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
