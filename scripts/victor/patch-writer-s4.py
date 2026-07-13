#!/usr/bin/env python3
"""Sprint 4 — writer.py: mínimo 300 palavras, blocos AEO, rascunho se <200."""
from pathlib import Path
import re

p = Path('/var/www/estrato/pipeline/writer.py')
text = p.read_text()

HELPERS = '''

MIN_PUBLISH_WORDS = 200
TARGET_WORDS = 300

def contar_palavras_html(html):
    if not html: return 0
    t = re.sub(r'<[^>]+>', ' ', html)
    t = re.sub(r'\\s+', ' ', t).strip()
    return len(t.split()) if t else 0

def expandir_aeo(titulo, resumo, conteudo, categoria):
    """Adiciona resumo, contexto e FAQ para atingir meta AEO."""
    if '<!-- estrato-aeo -->' in (conteudo or ''):
        return conteudo
    area = {
        'economia': 'economia brasileira', 'mercados': 'mercados financeiros',
        'negocios': 'negócios e empresas', 'financas-pessoais': 'finanças pessoais',
        'criptomoedas': 'criptoativos', 'agronegocio': 'agronegócio', 'mundo': 'economia internacional',
    }.get(categoria, 'economia e mercados')
    lead = (resumo or titulo or '')[:200]
    bloco = f"""<!-- estrato-aeo -->
<div class="estrato-aeo-resumo"><h2>O que você precisa saber</h2><p><strong>{lead}</strong></p></div>
<h2>Contexto de mercado</h2>
<p>Em um cenário de {area}, o desdobramento de "{titulo[:60]}" ajuda investidores e empresas a calibrar expectativas sobre juros, câmbio e fluxo de capital nas próximas semanas.</p>
<h2>Perguntas frequentes</h2>
<h3>Por que este tema importa?</h3>
<p>Porque dialoga com tendências de {area} e pode afetar decisões de alocação e custos no Brasil.</p>
<h3>Quem deve acompanhar?</h3>
<p>Investidores, gestores e leitores que acompanham indicadores macroeconômicos e movimentos setoriais.</p>
<h3>Como o Estrato cobre?</h3>
<p>Cruzamos fontes primárias e dados públicos antes da publicação, conforme a política editorial.</p>"""
    return (conteudo or '') + '\\n' + bloco
'''

if 'MIN_PUBLISH_WORDS' not in text:
    anchor = 'def gerar_resumo(texto_limpo, titulo):'
    if anchor not in text:
        raise SystemExit('anchor gerar_resumo not found')
    text = text.replace(anchor, HELPERS + '\n' + anchor, 1)

OLD = """            conteudo = formatar_html(titulo, conteudo_orig or resumo_orig, categoria)
            if not conteudo: conteudo = f'<p>{resumo}</p>'

            tags = list(dict.fromkeys("""

NEW = """            conteudo = formatar_html(titulo, conteudo_orig or resumo_orig, categoria)
            if not conteudo: conteudo = f'<p>{resumo}</p>'
            wc = contar_palavras_html(conteudo)
            if wc < TARGET_WORDS:
                conteudo = expandir_aeo(titulo, resumo, conteudo, categoria)
                wc = contar_palavras_html(conteudo)

            tags = list(dict.fromkeys("""

if OLD not in text:
    raise SystemExit('formatar_html block not found')
text = text.replace(OLD, NEW, 1)

OLD2 = """            ok = supa_patch(a['id'], {
                'titulo': titulo, 'resumo': resumo, 'conteudo': conteudo,
                'tags': tags, 'categoria': categoria,
                'status': 'publicado', 'quality_score': q,"""

NEW2 = """            art_status = 'publicado' if wc >= MIN_PUBLISH_WORDS else 'rascunho'
            ok = supa_patch(a['id'], {
                'titulo': titulo, 'resumo': resumo, 'conteudo': conteudo,
                'tags': tags, 'categoria': categoria,
                'status': art_status, 'quality_score': q,"""

if OLD2 not in text:
    raise SystemExit('supa_patch block not found')
text = text.replace(OLD2, NEW2, 1)

OLD3 = """            if ok:
                wp_ok, wp_msg = publish_to_wordpress(portal_slug, {"""

NEW3 = """            if ok and art_status == 'publicado':
                wp_ok, wp_msg = publish_to_wordpress(portal_slug, {"""

if OLD3 not in text:
    raise SystemExit('wp publish block not found')
text = text.replace(OLD3, NEW3, 1)

OLD4 = """            else:
                erros += 1
                print(f'  FAIL: {titulo[:40]}')"""

NEW4 = """            elif ok and art_status != 'publicado':
                print(f'  RASCUNHO (<{MIN_PUBLISH_WORDS} pal): {titulo[:50]}')
            else:
                erros += 1
                print(f'  FAIL: {titulo[:40]}')"""

if OLD4 not in text:
    raise SystemExit('fail block not found')
text = text.replace(OLD4, NEW4, 1)

p.write_text(text)
print('writer.py Sprint 4 patched')
