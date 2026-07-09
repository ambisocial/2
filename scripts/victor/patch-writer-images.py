#!/usr/bin/env python3
"""Remove imagem_url stock (Unsplash) do writer.py — WP busca og:image da fonte."""
from pathlib import Path

p = Path('/var/www/estrato/pipeline/writer.py')
text = p.read_text()

old = """            img = get_foto_artigo(titulo, categoria, a['slug'])
            q = quality(titulo, resumo, conteudo)

            ok = supa_patch(a['id'], {
                'titulo': titulo, 'resumo': resumo, 'conteudo': conteudo,
                'tags': tags, 'categoria': categoria, 'imagem_url': img,"""

new = """            q = quality(titulo, resumo, conteudo)

            ok = supa_patch(a['id'], {
                'titulo': titulo, 'resumo': resumo, 'conteudo': conteudo,
                'tags': tags, 'categoria': categoria,"""

if old not in text:
    raise SystemExit('writer.py block not found')
p.write_text(text.replace(old, new, 1))
print('writer.py: removed stock imagem_url assignment')
