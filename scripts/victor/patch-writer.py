#!/usr/bin/env python3
"""Patch writer.py to publish estrato portal articles to WordPress."""
from pathlib import Path

p = Path('/var/www/estrato/pipeline/writer.py')
text = p.read_text()

if 'from wp_bridge import publish_to_wordpress' not in text:
    text = text.replace(
        'import fcntl\n',
        'import fcntl\nfrom wp_bridge import publish_to_wordpress\n',
        1,
    )

replacement = """            if ok:
                wp_ok, wp_msg = publish_to_wordpress(portal_slug, {
                    'id': a['id'], 'titulo': titulo, 'resumo': resumo,
                    'conteudo': conteudo, 'categoria': categoria,
                    'fonte_url': a.get('fonte_url'), 'slug': a.get('slug'),
                })
                if wp_ok:
                    escritos += 1
                    suffix = ' -> WP' if wp_msg != 'skip' else ''
                    print(f'  OK {titulo[:60]}{suffix}')
                else:
                    erros += 1
                    print(f'  WP ERR: {wp_msg}')
            else:
                erros += 1
                print(f'  FAIL: {titulo[:40]}')"""

lines = text.splitlines()
out = []
patched = False
i = 0
while i < len(lines):
    line = lines[i]
    if (
        not patched
        and line.strip().startswith('if ok: escritos += 1')
        and 'titulo[:60]' in line
    ):
        out.extend(replacement.splitlines())
        patched = True
        i += 1
        if i < len(lines) and lines[i].strip().startswith('else: erros'):
            i += 1
        continue
    out.append(line)
    i += 1

if not patched:
    raise SystemExit('writer.py publish block not found')

p.write_text('\n'.join(out) + '\n')
print('writer.py patched successfully')
