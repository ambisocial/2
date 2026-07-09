# Estrato.cc — Benchmark de Portais Financeiros e Roadmap Executável

**Data:** 2026-07-09  
**Escopo:** estrato.cc vs. G1 Economia, Valor, Exame, InfoMoney, Money Times  
**Objetivo:** fechar gaps de conteúdo, estrutura, visual, SEO, AEO, GEO e E-E-A-T — com plano executável fase a fase, incluindo indexação categoria a categoria via bot.

> **Nota sobre ISTOÉ:** a revista encerrou publicação impressa e digital em 2023. No benchmark, ISTOÉ foi substituída por **Veja Negócios / Época Negócios** como referência de revista generalista com vertente econômica.

---

## 1. Resumo executivo

### O que os grandes têm (padrão de mercado)

| Pilar | Padrão dos líderes |
|-------|-------------------|
| **Conteúdo** | Reportagem original, colunas assinadas, análise (não só repetição), hubs temáticos, dados de mercado ao vivo |
| **Estrutura** | Árvore editorial profunda (categoria → subcategoria → tag → hub), páginas institucionais, autores com perfil |
| **Visual** | Hero forte, ticker de mercados, blocos por editoria, “Mais lidas”, newsletters, vídeo/podcast |
| **SEO clássico** | Titles únicos, meta descriptions, schema NewsArticle completo, sitemaps segmentados, internal linking |
| **E-E-A-T** | Autores nomeados, política editorial, transparência de fontes, correções, sobre/quem somos |
| **AEO** | FAQs, respostas diretas no primeiro parágrafo, listas, tabelas, HowTo schema |
| **GEO** | Conteúdo citável, entidades nomeadas, dados estruturados, freshness, autoridade de domínio |

### Onde o Estrato está hoje (diagnóstico live)

| Métrica | Valor atual | Problema |
|---------|-------------|----------|
| Posts publicados | **732** | Volume alto, qualidade irregular |
| Autores WP | **1** (`tpb`) | E-E-A-T crítico |
| Páginas institucionais | **0 publicadas** (404 em /sobre, /contato, /politica-editorial) | Confiança baixa |
| Categorias ativas no menu | **7 financeiras** | OK no menu, mas WP tem **11 categorias** (legado) |
| Posts “Sem categoria” | **58** | Sitemap poluído, silos quebrados |
| Yoast SEO | **Ativo, não configurado** | Titles genéricos (“Economia Archives - Estrato”) |
| Schema | **Duplicado** (Yoast Article + theme NewsArticle) | Risco de rich result inválido |
| Conteúdo médio | **~72 palavras** (amostra pipeline) | Thin content — penalização |
| Origem do conteúdo | **Pipeline + RSS** com link “Fonte:” | Agregador, não jornalismo |
| Google News | **Não aplicado** | Tráfego de descoberta zero |
| Indexação bot | **Inexistente** | Sem GSC API, sem Indexing API |

### Gap principal (uma frase)

> O Estrato tem **infraestrutura de portal** (WordPress, PressGrid, pipeline, Yoast, sitemap), mas ainda opera como **agregador automatizado** sem camada editorial, confiança (E-E-A-T) e profundidade de silo que G1/Valor/Exame usam para ranquear.

---

## 2. Matriz comparativa detalhada

Legenda: ✅ forte · ⚠️ parcial · ❌ ausente

| Recurso | G1 Econ. | Valor | Exame | InfoMoney | Money Times | **Estrato** |
|---------|----------|-------|-------|-----------|-------------|-------------|
| **Ticker Ibovespa/Dólar** | ✅ | ✅ | ⚠️ | ✅ | ✅ | ⚠️ forex theme |
| **Breaking news bar** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Hero / destaque principal** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Blocos por editoria** | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ layout parcial |
| **Colunistas assinados** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Newsletter segmentada** | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ UI sem backend |
| **Podcast / vídeo** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **“Mais lidas”** | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ trending theme |
| **Hubs temáticos** (/tudo-sobre/) | ⚠️ | ⚠️ | ✅ | ✅ | ✅ tags | ❌ |
| **Cotações dedicadas** | ✅ | ✅ Valor Data | ⚠️ | ⚠️ | ✅ /cotacoes | ❌ |
| **Plantão B3 / fatos relevantes** | ⚠️ | ✅ | ⚠️ | ⚠️ | ✅ | ❌ |
| **Reportagem original longform** | ✅ | ✅ | ✅ | ✅ | ✅ entrevistas | ❌ |
| **Página Sobre / Editorial** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Perfis de autores** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Google News** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Schema NewsArticle completo** | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ incompleto |
| **URLs limpas por editoria** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Internal linking automático** | ✅ | ✅ | ✅ | ✅ | ✅ linker? | ❌ |
| **Conteúdo >300 palavras médio** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **E-E-A-T / autores reais** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **AEO (FAQ, respostas diretas)** | ⚠️ | ⚠️ | ✅ | ✅ guias | ⚠️ | ❌ |
| **GEO (dados citáveis, entidades)** | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ |

---

## 3. Análise por dimensão

### 3.1 Conteúdo editorial

**O que os líderes fazem**

1. **Pirâmide de valor**
   - **Breaking** (curto, <300 palavras) — velocidade
   - **News** (300–800 palavras) — contexto
   - **Análise** (800–2000 palavras) — interpretação com especialista
   - **Longform / Reportagem** (2000+) — autoridade e backlinks

2. **Assinaturas e vozes**
   - Valor: colunistas (Assis Moreira, Maria Cristina Fernandes…)
   - Exame: colunistas + “Insight” + revista
   - InfoMoney: colunistas + cursos + newsletters
   - Money Times: colunas (“Comprar ou vender?”, “Money Trader”)

3. **Diferenciação por categoria**
   - Mercados → tickers, reação do Ibovespa, juros, câmbio
   - Negócios → M&A, resultados trimestrais, CEOs
   - Finanças pessoais → guias, calculadoras, “como fazer”
   - Cripto → preço, regulação, tecnologia blockchain

**O que falta no Estrato**

| Gap | Impacto | Evidência |
|-----|---------|-----------|
| Conteúdo thin (~72 palavras) | Alto — Helpful Content System | `wordCount: 72` no schema Yoast |
| Sem camada de análise | Alto — não compete com Valor/InfoMoney | Posts são resumo + link fonte |
| 1 autor para 732 posts | Crítico — E-E-A-T | WP user `tpb` em todos |
| 58 posts sem categoria | Médio — silo quebrado | `sem-categoria` count=58 |
| Categorias legado (política, tecnologia) | Médio — foco financeiro diluído | 84 política, 108 tecnologia |
| Sem hubs `/tudo-sobre/criptomoedas/` | Alto — AEO/GEO | URLs só `/category/slug/` |

---

### 3.2 Estrutura de informação (IA / UX)

**Padrão dos líderes**

```
Home
├── Destaque (hero)
├── Ticker mercado
├── Editorias (silos)
│   ├── Economia
│   │   ├── Inflação / PIB / Emprego (sub-hubs)
│   │   └── Tags: Selic, IPCA, BC…
│   ├── Mercados
│   │   ├── Ibovespa, Dólar, Juros
│   │   └── Ao vivo / intraday
│   └── …
├── Colunistas
├── Newsletters
├── Institucional (Sobre, Editorial, Contato, Privacidade)
└── Autores (/autor/nome/)
```

**Estrato atual**

```
Home (PressGrid)
├── Topbar + logo ✅
├── Menu 7 categorias ✅
├── Breaking ✅
├── Hero + sections ⚠️ (não amarradas às categorias financeiras)
├── Posts em grid ✅
└── Institucional ❌ (404)
```

**Títulos de arquivo errados**

- Atual: `Economia Archives - Estrato` (Yoast default inglês)
- Alvo: `Economia: notícias, análises e indicadores | Estrato`

---

### 3.3 Visual e identidade

**Benchmark visual**

| Elemento | Referência | Estrato |
|----------|------------|---------|
| Logo forte no masthead | Todos | ✅ estrato. preto + verde |
| Paleta consistente | Valor (azul), Exame (vermelho), G1 (amarelo) | ✅ preto + #9AFF33 |
| Tipografia editorial | Serif no logo, sans no corpo | ⚠️ PressGrid default |
| Densidade de informação | Alta (Valor, Money Times) | ⚠️ média |
| Cards com imagem 16:9 | Todos | ✅ após backfill |
| Bylines visíveis | Todos | ❌ só “tpb” |
| Data + tempo de leitura | Exame, InfoMoney | ❌ |
| CTA newsletter | Todos | ⚠️ bloco existe, sem integração |

**Melhorias visuais prioritárias**

1. Bylines com foto + nome de especialista por categoria
2. Tempo de leitura + data atualizada (“Atualizado em…”)
3. Bloco “Mercados agora” fixo (Ibovespa, USD, Selic) — PressGrid forex ticker
4. Página de categoria com hero próprio (não só listagem)
5. Footer institucional completo (Sobre, Editorial, Privacidade, Anuncie)

---

### 3.4 SEO clássico (Google Search)

**Checklist técnico**

| Item | Status Estrato | Ação |
|------|----------------|------|
| HTTPS + canonical | ✅ | — |
| robots.txt + sitemap | ✅ Yoast | Segmentar sitemaps por categoria |
| Meta title/description home | ⚠️ genérico | Customizar no Yoast |
| Title templates por arquivo | ❌ “Archives” | Configurar `%%term_title%% %%page%% \| Estrato` |
| Open Graph image home | ⚠️ logo (não foto) | Imagem 1200×630 dedicada |
| NewsArticle schema | ⚠️ duplicado | Unificar via Yoast, desativar theme schema |
| Breadcrumb schema | ✅ | Manter |
| Paginação rel next/prev | ✅ | — |
| Core Web Vitals | ⚠️ não medido | Lighthouse + Cloudflare polish |
| Hreflang | N/A (só pt_BR) | — |
| 404 customizada | ⚠️ | Criar página 404 com busca |

**Problemas específicos detectados**

1. **Thin content** — posts com <100 palavras não ranqueiam para head terms
2. **Duplicate content** — mesmo feed em pipeline + RSS + fonte original
3. **Author “tpb”** — sem Person schema credível
4. **Sem `dateModified`** em posts atualizados
5. **Categorias órfãs** no sitemap (política, tecnologia, sem-categoria)

---

### 3.5 E-E-A-T (Experience, Expertise, Authoritativeness, Trust)

**O que Google avalia em YMYL (Your Money Your Life) — finanças é YMYL**

| Sinal | Grandes portais | Estrato |
|-------|-----------------|---------|
| **Experience** | Repórteres em campo, entrevistas | ❌ |
| **Expertise** | Economistas, analistas CNPI | ❌ |
| **Authoritativeness** | Marca 20+ anos, prêmios, citações | ❌ marca nova |
| **Trustworthiness** | Sobre, editorial, contato, HTTPS, privacidade | ❌ páginas 404 |

**Plano E-E-A-T mínimo viável**

1. Página **Quem somos** — missão, equipe, credenciais
2. Página **Política editorial** — fontes, correções, conflito de interesse
3. Página **Como citamos dados** — metodologia para mercados
4. **7 autores WP** (1 por categoria) com bio, foto, LinkedIn, `sameAs` schema
5. Bylines no template single — `Por [Nome], [Especialidade]`
6. Rodapé com CNPJ / razão social / endereço
7. Página **Política de privacidade** — publicar draft existente (ID 3)

---

### 3.6 AEO — Answer Engine Optimization (IA de resposta)

**Alvo:** aparecer em Google AI Overviews, Perplexity, ChatGPT search, Bing Copilot.

**Táticas dos líderes**

| Tática | Exemplo InfoMoney/Exame | Estrato |
|--------|-------------------------|---------|
| Resposta nos primeiros 100 palavras | “O Ibovespa fechou em X…” | ❌ |
| Perguntas no H2/H3 | “O que é Selic?” | ❌ |
| FAQ schema no final | Guias de investimento | ❌ |
| Tabelas comparativas | CDB vs LCI | ❌ |
| Listas numeradas | “5 ações para comprar” | ❌ |
| Dados com fonte e data | “Segundo o BC, em 09/07…” | ⚠️ |

**Formato de post AEO-ready (template)**

```markdown
## Resumo em 30 segundos
[Resposta direta em 2-3 frases com número e data]

## O que aconteceu
[Contexto 150-300 palavras]

## Por que importa para o investidor
[Análise 200-400 palavras]

## O que vem pela frente
[Projeção com ressalvas]

## Perguntas frequentes
### [Pergunta 1]?
[Resposta curta]
```

---

### 3.7 GEO — Generative Engine Optimization

**Alvo:** ser citado como fonte em respostas de LLMs.

**Fatores GEO (pesquisa Princeton/Georgia Tech, 2024)**

1. **Estatísticas e citações** — incluir números concretos com fonte
2. **Quotes de especialistas** — frases atribuídas a analistas reais
3. **Fluência + autoridade** — texto bem escrito, não robótico
4. **Freshness** — `dateModified` recente
5. **Entidades nomeadas** — empresas, pessoas, índices (B3, PETR4, Selic)
6. **Dados estruturados** — JSON-LD rico

**Ações para o pipeline `writer.py`**

- Injetar bloco “**Dado-chave:**” com número + fonte + data
- Injetar “**Entidades:** PETR4, Ibovespa, BC” como tags automáticas
- Gerar `FAQ` com 3 perguntas por post (schema FAQPage)
- Atualizar posts de breaking após 2h com “**Atualização:**” (freshness)

---

## 4. Roadmap executável — 48 tarefas em 6 fases

Cada tarefa tem: **ID**, **esforço** (S/M/L), **impacto** (1-5), **responsável** (bot/humano), **comando ou artefato**.

---

### FASE 0 — Fundação técnica (Semana 1)

| ID | Tarefa | Esforço | Impacto | Execução |
|----|--------|---------|---------|----------|
| 0.1 | Configurar Yoast titles/templates pt_BR | S | 5 | `wp yoast set-options` — ver script abaixo |
| 0.2 | Publicar páginas institucionais (Sobre, Editorial, Contato, Privacidade) | M | 5 | WP pages + menu footer |
| 0.3 | Criar 7 autores (1 por categoria) com bio + avatar | M | 5 | `wp user create` + meta |
| 0.4 | Migrar posts `sem-categoria` → categorias corretas | M | 4 | Script PHP batch |
| 0.5 | Ocultar/arquivar categorias legado (política, tecnologia, brasil) | S | 3 | `default_category` + noindex |
| 0.6 | Desativar schema duplicado do theme (manter Yoast) | S | 4 | Filter em portal-bootstrap |
| 0.7 | Imagem OG home 1200×630 (não logo) | S | 3 | Upload + Yoast social |
| 0.8 | Configurar PressGrid Layout Builder por categoria | M | 4 | WP admin ou export JSON |
| 0.9 | Ativar forex ticker na topbar (Selic, USD, EUR, IBOV) | S | 4 | PressGrid customizer |
| 0.10 | Registrar Google Search Console + Bing Webmaster | S | 5 | DNS TXT ou HTML |

**Script 0.1 — Yoast titles (Victor)**

```bash
sudo -u www-data wp --path=/var/www/estrato.cc option patch update wpseo_titles title-home-wpseo "Estrato | Economia, mercados e finanças"
sudo -u www-data wp --path=/var/www/estrato.cc option patch update wpseo_titles metadesc-home-wpseo "Notícias de economia, mercados financeiros, negócios e finanças pessoais. Acompanhe Ibovespa, dólar, Selic e mais."
sudo -u www-data wp --path=/var/www/estrato.cc option patch update wpseo_titles title-tax-category "%%term_title%%: notícias e análises %%page%% | Estrato"
sudo -u www-data wp --path=/var/www/estrato.cc option patch update wpseo_titles metadesc-tax-category "Acompanhe as últimas notícias de %%term_title%% no Estrato. Análises, dados e contexto para investidores."
sudo -u www-data wp --path=/var/www/estrato.cc option patch update wpseo_titles title-post "%%title%% | Estrato"
```

---

### FASE 1 — Estrutura editorial e visual (Semanas 2-3)

| ID | Tarefa | Esforço | Impacto | Execução |
|----|--------|---------|---------|----------|
| 1.1 | Homepage: seção hero → Economia, grid → Mercados, sidebar → Finanças pessoais | M | 5 | PressGrid Layout Builder |
| 1.2 | Template de categoria com intro SEO (150 palavras únicas por silo) | M | 5 | `category_description` + template |
| 1.3 | Criar hubs `/tudo-sobre/{tema}/` (7 hubs iniciais) | L | 5 | WP pages + links internos |
| 1.4 | Bloco “Mais lidas” com query real (7 dias) | S | 4 | PressGrid widget / custom |
| 1.5 | Bylines visíveis no single (foto, nome, especialidade, data) | M | 5 | Child theme ou filter |
| 1.6 | Tempo de leitura + “Atualizado em” no single | S | 3 | Plugin snippet ou PHP |
| 1.7 | Footer 4 colunas: editorias, institucional, newsletter, social | M | 4 | WP menus + widgets |
| 1.8 | Página `/cotacoes/` com iframe ou API (Frankfurter + B3 delay) | L | 4 | Nova page template |
| 1.9 | Newsletter: integrar CF7 → Mailchimp/Brevo | M | 4 | CF7 + API |
| 1.10 | Remover “Página de exemplo” e draft lixo | S | 2 | `wp post delete` |

**7 hubs iniciais**

1. `/tudo-sobre/selic/`
2. `/tudo-sobre/ibovespa/`
3. `/tudo-sobre/dolar/`
4. `/tudo-sobre/criptomoedas/`
5. `/tudo-sobre/inflacao/`
6. `/tudo-sobre/tributacao/`
7. `/tudo-sobre/agronegocio/`

---

### FASE 2 — Qualidade de conteúdo e pipeline (Semanas 3-5)

| ID | Tarefa | Esforço | Impacto | Execução |
|----|--------|---------|---------|----------|
| 2.1 | Writer: mínimo 300 palavras (rejeitar/publicar rascunho se <300) | M | 5 | `writer.py` patch |
| 2.2 | Writer: template AEO (resumo + FAQ + entidades) | M | 5 | Prompt engineering |
| 2.3 | Writer: atribuir autor por categoria automaticamente | S | 5 | `wp_bridge.py` author_id map |
| 2.4 | Curator: priorizar fontes BR tier-1 (Valor, InfoMoney, BC, B3) | M | 4 | `curator.py` rules |
| 2.5 | Linker: 3 links internos por post (mesma categoria + hub) | M | 5 | `linker.py` on Victor |
| 2.6 | Não republicar conteúdo idêntico à fonte (rewrite obrigatório) | M | 5 | Writer prompt |
| 2.7 | Marcar posts agregados como `noindex` até rewrite | S | 4 | Meta `_estrato_needs_rewrite` |
| 2.8 | Criar 1 “análise” longform por categoria/semana (1500+ palavras) | L | 5 | Pipeline mode `analysis` |
| 2.9 | Adicionar `dateModified` ao atualizar posts | S | 3 | Bridge PATCH endpoint |
| 2.10 | Backfill: recategorizar 84 política + 108 tecnologia → arquivo ou merge | M | 3 | Batch script |

---

### FASE 3 — SEO avançado + schema (Semana 5)

| ID | Tarefa | Esforço | Impacto | Execução |
|----|--------|---------|---------|----------|
| 3.1 | Sitemap segmentado por categoria (7 sitemaps) | M | 4 | Yoast filter ou custom |
| 3.2 | FAQ schema automático (Yoast ou custom JSON-LD) | M | 5 | Bridge meta `estrato_faq` |
| 3.3 | Person schema para cada autor | M | 4 | Yoast author schema |
| 3.4 | Organization schema com `sameAs` (LinkedIn, Instagram) | S | 4 | Yoast social |
| 3.5 | Breadcrumb visível no single (não só schema) | S | 3 | PressGrid template |
| 3.6 | Paginação de categorias: meta unique por página | S | 3 | Yoast |
| 3.7 | Auditoria Core Web Vitals (LCP, CLS, INP) | M | 4 | Lighthouse CI |
| 3.8 | Lazy load + WebP para thumbnails | S | 3 | WP + Cloudflare |
| 3.9 | `llms.txt` e `llms-full.txt` para GEO | S | 4 | Arquivo estático na raiz |
| 3.10 | Aplicar Google News Publisher Center | L | 5 | Requisitos abaixo |

**Requisitos Google News**

- Autores visíveis em cada artigo ✅ (após 1.5)
- URLs únicas e permanentes ✅
- Páginas Sobre + Contato ✅ (após 0.2)
- Sitemap de notícias (`news-sitemap.xml`) — Yoast Premium ou custom
- Conteúdo original (não só agregação) — após 2.6

---

### FASE 4 — Indexação bot categoria a categoria (Semanas 6-10)

**Estratégia:** indexar um silo por vez, esperar cobertura >80% no GSC antes do próximo.

#### Arquitetura do bot de indexação

```
index-bot/
├── gsc_api.py          # Google Search Console API
├── indexer.py          # Orquestrador por categoria
├── sitemap_ping.py     # Ping Google/Bing após publicação
└── config.yaml         # Ordem de categorias + URLs semente
```

#### Ordem de indexação (prioridade por volume + intenção)

| Ordem | Categoria | Posts | URL semente | Motivo |
|-------|-----------|-------|-------------|--------|
| 1 | `mercados` | 164 | `/category/mercados/` | Maior volume, alta intenção |
| 2 | `negocios` | 152 | `/category/negocios/` | Corporate, CPM alto |
| 3 | `economia` | 66 | `/category/economia/` | Head terms (Selic, IPCA) |
| 4 | `criptomoedas` | 38 | `/category/criptomoedas/` | Nicho com menos concorrência BR |
| 5 | `financas-pessoais` | 12 | `/category/financas-pessoais/` | AEO forte (perguntas) |
| 6 | `agronegocio` | 14 | `/category/agronegocio/` | Sazonal, safra |
| 7 | `mundo` | 10 | `/category/mundo/` | Internacional |

#### Protocolo por categoria (bot)

| Passo | Ação | Ferramenta |
|-------|------|------------|
| 1 | Auditar posts: thin, sem categoria, duplicados | `wp eval-file audit-category.php` |
| 2 | Reescrever/enriquecer posts <300 palavras da categoria | pipeline `writer.py --rewrite` |
| 3 | Adicionar 5 links internos por hub `/tudo-sobre/` | linker |
| 4 | Publicar 3 artigos originais longform na categoria | pipeline `analysis` |
| 5 | Atualizar `category_description` (150 palavras SEO) | WP CLI |
| 6 | Gerar sitemap categoria e pingar Google | `sitemap_ping.py` |
| 7 | Submeter URL semente + top 20 posts no GSC API | `gsc_api.py index()` |
| 8 | Solicitar indexação via Indexing API (batch 200/dia) | Google Indexing API |
| 9 | Monitorar cobertura GSC por 7 dias | Dashboard |
| 10 | Se cobertura ≥80%, avançar próxima categoria | `indexer.py --next` |

**Limites Google Indexing API**

- Máx. ~200 requisições/dia (quota padrão)
- Só para páginas com `JobPosting` ou `BroadcastEvent` — **não serve para news comum**
- Para news: usar **GSC URL Inspection API** (solicitar indexação) + sitemap ping
- Alternativa: **IndexNow** (Bing, Yandex) — implementar em `estrato-publisher-bridge`

#### Implementação IndexNow + GSC (tarefas)

| ID | Tarefa | Esforço | Impacto |
|----|--------|---------|---------|
| 4.1 | Criar `scripts/victor/index-bot/gsc_api.py` | M | 5 |
| 4.2 | Hook no bridge: ping sitemap após publish | S | 4 |
| 4.3 | IndexNow key file em `estrato.cc/{key}.txt` | S | 4 |
| 4.4 | Cron: indexar 50 URLs/dia da categoria ativa | M | 5 |
| 4.5 | Relatório diário: indexed vs submitted vs errors | S | 3 |

---

### FASE 5 — AEO + GEO + autoridade (Semanas 10-16)

| ID | Tarefa | Esforço | Impacto |
|----|--------|---------|---------|
| 5.1 | 7 guias evergreen (1 por categoria, 2000+ palavras) | L | 5 |
| 5.2 | FAQ global por categoria (10 perguntas cada) | M | 5 |
| 5.3 | Calculadoras: CDI, Selic, dólar turismo | L | 4 |
| 5.4 | Coluna semanal assinada (autor real ou persona editorial) | L | 5 |
| 5.5 | `llms.txt` com links dos guias + política editorial | S | 4 |
| 5.6 | Parcerias de citação (guest posts, dados públicos BC/B3) | L | 5 |
| 5.7 | Podcast “Estrato Mercados” (5 min diário) | L | 4 |
| 5.8 | Google Discover: imagens 1200px+, títulos emocionais | M | 4 |
| 5.9 | Link building: 10 backlinks/mês (guest posts, HARO) | L | 5 |
| 5.10 | Monitorar citações em Perplexity/ChatGPT (GEO tracking) | M | 3 |

---

### FASE 6 — Escala e monetização (Semanas 16+)

| ID | Tarefa | Esforço | Impacto |
|----|--------|---------|---------|
| 6.1 | Google Ad Manager / ads.txt | M | 4 |
| 6.2 | Programmatic via PressGrid ad zones | M | 3 |
| 6.3 | Paywall leve para análises (opcional) | L | 3 |
| 6.4 | API pública de cotações (widget embed) | L | 4 |
| 6.5 | Expansão para `estrato.com.br` ou subdomínios | L | 4 |

---

## 5. KPIs por fase

| Fase | KPI principal | Meta |
|------|---------------|------|
| 0 | Páginas institucionais live | 4/4 |
| 0 | Autores com bio | 7 |
| 1 | Categorias com intro SEO | 7/7 |
| 2 | Posts com ≥300 palavras | >80% |
| 3 | Rich results válidos (GSC) | 0 erros schema |
| 4 | Cobertura indexação por categoria | ≥80% |
| 5 | Impressões orgânicas (GSC) | +50% mês/mês |
| 5 | FAQs com rich result | 7 hubs |
| 6 | Page RPM | baseline + 20% |

---

## 6. Priorização rápida (se só der para 10 ações)

1. **0.2** — Páginas Sobre + Editorial + Contato (E-E-A-T)
2. **0.3** — 7 autores com bio (E-E-A-T)
3. **0.1** — Yoast titles pt_BR (SEO)
4. **2.1** — Mínimo 300 palavras no pipeline (conteúdo)
5. **2.5** — Linker interno automático (SEO)
6. **1.3** — Hubs `/tudo-sobre/` (AEO/GEO)
7. **4.1-4.4** — Bot de indexação por categoria (SEO)
8. **2.8** — 1 análise longform/semana (autoridade)
9. **3.2** — FAQ schema automático (AEO)
10. **0.10** — Google Search Console (medição)

---

## 7. Artefatos a criar no repositório (próximos PRs)

| Arquivo | Fase | Descrição |
|---------|------|-----------|
| `scripts/victor/setup-estrato-seo.sh` | 0 | Yoast + páginas institucionais |
| `scripts/victor/audit-category.php` | 4 | Auditoria por categoria |
| `scripts/victor/index-bot/indexer.py` | 4 | Orquestrador de indexação |
| `scripts/victor/index-bot/gsc_api.py` | 4 | GSC URL inspection |
| `estrato-portal-bootstrap/seo-schema.php` | 3 | FAQ + Person + desativa schema theme |
| `estrato-publisher-bridge/indexnow.php` | 4 | Ping IndexNow on publish |
| `content/templates/post-aeo.md` | 2 | Template AEO para writer |
| `content/hubs/*.md` | 1 | Textos dos 7 hubs |
| `portals/estrato-finance.yaml` → `seo:` section | 0 | Config SEO no YAML |

---

## 8. Referências de benchmark (URLs)

| Portal | URL | O que copiar |
|--------|-----|--------------|
| G1 Economia | https://g1.globo.com/economia/ | Ticker, simplicidade, volume |
| Valor | https://valor.globo.com/ | Profundidade, colunistas, Valor Data |
| Exame | https://exame.com/ | Hubs, revista, branded content |
| InfoMoney | https://www.infomoney.com.br/ | AEO, guias, cursos, colunistas |
| Money Times | https://www.moneytimes.com.br/ | Cotações, plantão B3, tempo real |
| Valor Investe | https://valorinveste.globo.com/ | Finanças pessoais, educação |

---

## 9. Conclusão

O Estrato **não precisa virar Valor** para ranquear — precisa:

1. **Parar de publicar thin content** e passar a ter voz editorial
2. **Construir confiança** (E-E-A-T) com páginas e autores reais
3. **Organizar silos** com hubs e linking interno
4. **Indexar com método** (categoria por categoria, não tudo de uma vez)
5. **Formatar para IA** (AEO/GEO) com respostas diretas, FAQs e dados citáveis

Com as 48 tarefas acima executadas em ordem, o portal sai de “agregador automatizado” para **portal financeiro indexável e competitivo** em 12-16 semanas de execução bot + supervisão editorial mínima.

---

*Documento gerado para execução pelo Cloud Agent. Próximo passo recomendado: FASE 0 (tarefas 0.1–0.10).*

**Atualização 2026-07-09:** matriz técnica (robots/sitemap/schema) em `ESTRATO-BENCHMARK-TECNICO.md` · regras anti-regressão em `ESTRATO-ANTI-REGRESSAO.md` · validador `scripts/victor/check-portal-regression.sh`
