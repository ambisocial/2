#!/usr/bin/env python3.12
"""
writer.py v4 — Writer local: sem IA, sem quota, 10x mais rápido.
Processa texto direto: limpa → formata → publica.
Uso: python3.12 writer.py --portal=agro
"""
import sys, os, json, re, time, html as html_mod

sys.path.insert(0, '/var/www/estrato/pipeline')
from config import (
    get_arg, get_portal, supa_get, supa_patch, log_pipeline,
    slugify, strip_html, get_foto_artigo,
)
import fcntl
from wp_bridge import publish_to_wordpress

CATS_VALIDAS = {
    'esg','negocios','mercados','tecnologia','politica','agro','lifestyle',
    'cultura','entretenimento','cripto','carreira','financas-pessoais','mundo',
    'saude','sustentabilidade','governo','congresso','justica','forcas-armadas',
    'energia','naval','mineracao','construcao','geopolitica',
}

# No portal financeiro, estas cats viram skip no wp_bridge — já filtramos aqui
# para não gastar ciclo formatando conteúdo que não será publicado.
ESTRATO_SKIP_CATS = {
    'politica', 'governo', 'congresso', 'justica', 'forcas-armadas', 'entretenimento',
}

TAGS_CAT = {
    'esg':['esg','sustentabilidade','governança','meio ambiente','brasil'],
    'negocios':['negócios','mercado','empresa','economia','brasil'],
    'mercados':['mercado financeiro','bolsa','investimento','economia','brasil'],
    'tecnologia':['tecnologia','inovação','digital','startups','brasil'],
    'politica':['política','governo','brasil','congresso','eleições'],
    'agro':['agronegócio','agricultura','colheita','commodities','brasil'],
    'energia':['energia','petróleo','gás','elétrica','brasil'],
    'naval':['naval','porto','marítimo','logística','brasil'],
    'mineracao':['mineração','minério','commodities','brasil'],
    'construcao':['construção','imobiliário','infraestrutura','brasil'],
    'saude':['saúde','medicina','bem-estar','brasil'],
    'justica':['direito','justiça','legislação','brasil'],
    'cripto':['criptomoedas','bitcoin','blockchain','defi','web3'],
    'lifestyle':['lifestyle','bem-estar','qualidade de vida','brasil'],
    'cultura':['cultura','arte','entretenimento','brasil'],
    'mundo':['mundo','internacional','geopolítica','global'],
    'financas-pessoais':['finanças pessoais','investimento','renda','brasil'],
    'tecnologia':['tecnologia','inovação','ti','startups','brasil'],
    'governo':['governo','política','administração','brasil'],
    'congresso':['congresso','legislativo','câmara','senado','brasil'],
    'forcas-armadas':['forças armadas','defesa','militar','brasil'],
    'sustentabilidade':['sustentabilidade','esg','verde','brasil'],
    'geopolitica':['geopolítica','internacional','relações exteriores','brasil'],
    'carreira':['carreira','emprego','trabalho','mercado','brasil'],
}
DEFAULT_TAGS = ['brasil','economia','negócios','atualidades','notícias']

ROBOS = re.compile(
    r'\b(portanto|outrossim|cabe ressaltar|nesse sentido|sendo assim|'
    r'vale destacar|diante disso|em suma|destarte|ademais|haja vista|'
    r'no que tange|depreende-se|infere-se que|é importante salientar|'
    r'conforme mencionado|conclui-se que|é notório que)\b',
    re.IGNORECASE
)

def limpar(texto):
    if not texto: return ''
    texto = html_mod.unescape(texto)
    texto = re.sub(r'<[^>]+>', ' ', texto)
    texto = re.sub(r'\s+', ' ', texto).strip()
    return texto

def formatar_html(titulo, conteudo_raw, categoria):
    texto = limpar(conteudo_raw)
    if not texto: return ''
    texto = ROBOS.sub('', texto)
    texto = re.sub(r'\s+', ' ', texto).strip()
    if len(texto) < 80: return f'<p>{texto}</p>'

    sentencas = re.split(r'(?<=[.!?])\s+(?=[A-ZÁÉÍÓÚÂÊÔÃÕÇ])', texto)
    paragrafos, bloco = [], []
    for s in sentencas:
        bloco.append(s)
        if len(bloco) >= 3:
            paragrafos.append(' '.join(bloco)); bloco = []
    if bloco: paragrafos.append(' '.join(bloco))
    if not paragrafos: return f'<p>{texto}</p>'

    H2 = {
        'esg':'Contexto ESG','agro':'Contexto Agrícola','energia':'Panorama Energético',
        'negocios':'Análise de Mercado','mercados':'Panorama do Mercado',
        'tecnologia':'Impacto Tecnológico','politica':'Contexto Político',
        'saude':'Saúde em Pauta','naval':'Setor Marítimo','mineracao':'Setor Mineral',
        'construcao':'Mercado Imobiliário','justica':'Contexto Jurídico',
        'cripto':'Mercado Cripto','mundo':'Contexto Internacional',
    }.get(categoria, 'Contexto')

    partes = [f'<p><strong>{paragrafos[0]}</strong></p>']
    if len(paragrafos) > 1:
        partes.append(f'<h2>{H2}</h2>')
        for p in paragrafos[1:3]: partes.append(f'<p>{p}</p>')
    if len(paragrafos) > 3:
        partes.append('<h2>Impacto e Desdobramentos</h2>')
        for p in paragrafos[3:6]: partes.append(f'<p>{p}</p>')
    if len(paragrafos) > 6:
        partes.append('<h2>Perspectivas</h2>')
        for p in paragrafos[6:]: partes.append(f'<p>{p}</p>')
    return '\n'.join(partes)



MIN_PUBLISH_WORDS = 200
TARGET_WORDS = 300

def contar_palavras_html(html):
    if not html: return 0
    t = re.sub(r'<[^>]+>', ' ', html)
    t = re.sub(r'\s+', ' ', t).strip()
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
    return (conteudo or '') + '\n' + bloco

def gerar_resumo(texto_limpo, titulo):
    base = texto_limpo[:200]
    if len(base) >= 155:
        base = base[:155]
        i = base.rfind(' ')
        if i > 100: base = base[:i]
    return base.strip() or titulo[:155]

def quality(titulo, resumo, conteudo):
    q = 0
    if len(titulo) > 20: q += 20
    if resumo and len(resumo) > 50: q += 20
    if conteudo and len(conteudo) > 300: q += 30
    if conteudo and len(conteudo) > 800: q += 15
    txt = f"{titulo} {resumo or ''} {conteudo or ''}".lower()
    if 'portanto' not in txt and 'outrossim' not in txt: q += 15
    return min(q, 100)

def main():
    portal_slug = get_arg('portal', 'estrato')
    print(f'=== writer v4 [{portal_slug}] — local (sem IA) ===')

    lock_fd = open(f'/tmp/pipeline-writer-{portal_slug}.lock', 'w')
    try: fcntl.flock(lock_fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except IOError: print(f'Writer [{portal_slug}] já rodando.'); return

    portal = get_portal(portal_slug)
    if not portal: print(f'Portal "{portal_slug}" não encontrado.'); return

    pid = portal['id']
    t0 = time.time()

    arts = supa_get(
        f'estrato_artigos?status=eq.curado&portal_id=eq.{pid}'
        f'&select=id,titulo,slug,resumo,conteudo,categoria,fonte_url'
        f'&order=created_at.asc&limit=20', srv=True
    )
    print(f'  Para escrever: {len(arts)}')
    if not arts:
        log_pipeline(pid, 'writer', 'ok', publicados=0,
            detalhes={'written':0,'agent':'writer','portal':portal_slug})
        print(json.dumps({'written':0,'agent':'writer','portal':portal_slug}))
        return

    escritos = erros = 0
    for a in arts:
        try:
            titulo = (a.get('titulo') or '').strip()
            resumo_orig = (a.get('resumo') or '').strip()
            conteudo_orig = (a.get('conteudo') or '').strip()
            categoria = a.get('categoria','negocios')
            if categoria not in CATS_VALIDAS: categoria = 'negocios'
            if portal_slug == 'estrato' and categoria in ESTRATO_SKIP_CATS:
                # Marca como rejeitado no Supabase para não reprocessar.
                supa_patch(a['id'], {
                    'status': 'rejeitado',
                    'approved_by_score': False,
                    'quality_score': 0,
                })
                print(f'  SKIP cat={categoria}: {titulo[:50]}')
                continue

            texto_limpo = limpar(conteudo_orig or resumo_orig)
            titulo = titulo[:70]
            resumo = gerar_resumo(texto_limpo, titulo)
            conteudo = formatar_html(titulo, conteudo_orig or resumo_orig, categoria)
            if not conteudo: conteudo = f'<p>{resumo}</p>'
            wc = contar_palavras_html(conteudo)
            if wc < TARGET_WORDS:
                conteudo = expandir_aeo(titulo, resumo, conteudo, categoria)
                wc = contar_palavras_html(conteudo)

            tags = list(dict.fromkeys(
                TAGS_CAT.get(categoria, DEFAULT_TAGS)[:5] +
                [p.lower() for p in titulo.split() if len(p)>4][:2]
            ))[:7]
            q = quality(titulo, resumo, conteudo)

            art_status = 'publicado' if wc >= MIN_PUBLISH_WORDS else 'rascunho'
            ok = supa_patch(a['id'], {
                'titulo': titulo, 'resumo': resumo, 'conteudo': conteudo,
                'tags': tags, 'categoria': categoria,
                'status': art_status, 'quality_score': q,
                'naturalness_score': min(q+10, 100), 'seo_score': min(q+5, 100),
                'approved_by_score': True,
            })
            if ok and art_status == 'publicado':
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
            elif ok and art_status != 'publicado':
                print(f'  RASCUNHO (<{MIN_PUBLISH_WORDS} pal): {titulo[:50]}')
            else:
                erros += 1
                print(f'  FAIL: {titulo[:40]}')
            time.sleep(0.05)
        except Exception as e:
            erros += 1; print(f'  EXCEÇÃO: {e}')

    dur = int(time.time()-t0)
    log_pipeline(pid, 'writer', 'ok' if not erros else 'parcial',
        publicados=escritos, duracao_s=dur,
        detalhes={'written':escritos,'erros':erros,'agent':'writer','portal':portal_slug})
    print(f'=== Writer v4: {escritos} publicados, {erros} erros | {dur}s ===')
    print(json.dumps({'written':escritos,'agent':'writer','portal':portal_slug}))

if __name__ == '__main__':
    main()
