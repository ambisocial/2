#!/usr/bin/env python3.12
"""
publisher.py — Adiciona imagens Unsplash a artigos publicados de um portal.
Uso: python3.12 publisher.py --portal=agro
"""
import sys, os, json, time

sys.path.insert(0, '/var/www/estrato/pipeline')
from config import (
    get_arg, get_portal, supa_get, supa_patch, log_pipeline,
    get_foto_artigo,
)

import fcntl

def main():
    portal_slug = get_arg('portal', 'estrato')
    print(f'=== publisher [{portal_slug}] iniciando ===')

    lock_fd = open(f'/tmp/pipeline-publisher-{portal_slug}.lock', 'w')
    try:
        fcntl.flock(lock_fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except IOError:
        print(f'Publisher [{portal_slug}] já está rodando. Saindo.')
        return

    portal = get_portal(portal_slug)
    if not portal:
        print(f'Portal "{portal_slug}" não encontrado.')
        return

    portal_id = portal['id']
    t0 = time.time()

    # Buscar artigos publicados sem imagem deste portal
    arts = supa_get(
        f'estrato_artigos?status=eq.publicado&portal_id=eq.{portal_id}'
        f'&imagem_url=is.null'
        f'&select=id,titulo,categoria,slug'
        f'&order=created_at.desc&limit=50',
        srv=True
    )

    print(f'  Sem imagem: {len(arts)}')

    if not arts:
        log_pipeline(portal_id, 'publisher', 'ok', publicados=0,
                     detalhes={'aviso': 'nenhum artigo sem imagem'})
        print(f'=== publisher [{portal_slug}] — nada a fazer ===')
        return

    processados = 0
    erros = 0

    for a in arts:
        art_id   = a['id']
        titulo   = a.get('titulo') or ''
        categoria = a.get('categoria') or 'negocios'
        slug_str = a.get('slug') or titulo

        img_url = get_foto_artigo(titulo, categoria, slug_str)

        # No Estrato finance o bridge rejeita stock (Unsplash/Pexels). Não
        # poluir imagem_url — senão publish volta 422 / draft sem thumb.
        if portal_slug == 'estrato' and img_url and any(
            h in img_url.lower()
            for h in ('unsplash.com', 'pexels.com', 'pollinations.ai')
        ):
            print(f'  SKIP stock image: {titulo[:50]}')
            continue

        if supa_patch(art_id, {'imagem_url': img_url}):
            processados += 1
        else:
            erros += 1

    duracao = int(time.time() - t0)
    log_pipeline(portal_id, 'publisher', 'ok',
                 publicados=processados, duracao_s=duracao,
                 detalhes={'erros': erros})

    print(f'=== publisher [{portal_slug}] — {processados} imagens adicionadas, {erros} erros ({duracao}s) ===')
    print(json.dumps({'published': processados, 'agent': 'publisher', 'portal': portal_slug}))

if __name__ == '__main__':
    main()
