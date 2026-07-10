# Estrato.cc — Melhores Práticas Consolidadas e Regras Anti-Regressão

**Versão:** 1.0.0 · 2026-07-09  
**Portais auditados:** Metrópoles, Times Brasil, O Bastidor*, InfoMoney, Folha, BPMoney, O Globo, Money Times, Finance News, ND Mais, Brasil 247, Poder360, Jota, Valor, Exame, G1  
**Artefatos executáveis:**
- `portals/estrato-anti-regression.yaml` — regras machine-readable
- `scripts/victor/check-portal-regression.sh` — validador (rode após cada deploy)
- `docs/ESTRATO-BENCHMARK-TECNICO.md` — matriz robots/sitemap/schema

\* O Bastidor bloqueou auditoria via Cloudflare challenge — **anti-pattern documentado**.

---

## 1. Confirmação: schema, robots.txt e sitemap no mapeamento

| Artefato | Roadmap anterior | Auditoria nova (17 portais) | Regras anti-regressão |
|----------|------------------|----------------------------|------------------------|
| `robots.txt` | Mencionado | ✅ Matriz completa §2 técnico | AR-ROBOTS-001…006 |
| `sitemap.xml` | Mencionado | ✅ Matriz + gaps (news, legado 2018) | AR-SITEMAP-001…005 |
| Schema JSON-LD | Parcial | ✅ Home + post por portal | AR-SCHEMA-001…010 |
| `news-sitemap.xml` | Citado como meta | ✅ Auditado (Metrópoles, ND, Money Times) | AR-SITEMAP-003, AR-ROBOTS-005 |
| `llms.txt` / GEO | Citado | ✅ Confirmado ausente no Estrato | AR-AEO-001 |
| Meta OG/Twitter | Não detalhado | ✅ Matriz §5 técnico | AR-SCHEMA-007…009 |
| Regras bots IA | Não | ✅ BPMoney, Folha, ND Mais | best_practices.robots |

---

## 2. Catálogo de melhores práticas — por fonte

Cada linha é uma regra que o Estrato **deve** manter após implementada.

### 2.1 BPMoney (referência #1 técnica)

| ID | Prática | Regra anti-regressão |
|----|---------|---------------------|
| BP-01 | 9 sitemaps segmentados (news, posts, pages, categories, tags, columns, author, video, web stories) | AR-SITEMAP-003 + expandir Yoast |
| BP-02 | `news-sitemap.xml` dedicado | AR-ROBOTS-005, AR-SITEMAP-003 |
| BP-03 | robots bloqueia scrapers (Ahrefs, Semrush, Bytespider…) | Adicionar em robots (warning até implementar) |
| BP-04 | robots bloqueia LLM scrapers (CCBot, PerplexityBot, ClaudeBot…) | Política GEO explícita |
| BP-05 | Disallow `/?s=`, `/search`, `/xmlrpc.php` | AR-ROBOTS-003, AR-ROBOTS-004 |

### 2.2 Money Times

| ID | Prática | Regra |
|----|---------|-------|
| MT-01 | `news-sitemap.xml` separado do index | AR-SITEMAP-003 |
| MT-02 | NewsMediaOrganization no schema | AR-SCHEMA-003 |
| MT-03 | Ticker + página `/cotacoes/` | AR-VISUAL-004 + hub mercados |
| MT-04 | Plantão B3 / tempo real Ibovespa | Pipeline cron mercados |
| MT-05 | Colunistas com página `/colunistas/` | AR-EEAT-005 |
| MT-06 | Newsletter com opt-in explícito | AR-NAV-004 + CF7 |

### 2.3 InfoMoney

| ID | Prática | Regra |
|----|---------|-------|
| IM-01 | Hubs `/tudo-sobre/{tema}/` | AR-NAV-003 |
| IM-02 | NewsArticle + Person + Breadcrumb em posts | AR-SCHEMA-004, 005 |
| IM-03 | Guias e cursos (evergreen AEO) | AR-AEO-002 |
| IM-04 | Blocos "Em alta" por tema | Layout Builder |
| IM-05 | Sitemap separado para vertical (simulador) | Info para futuro `/ferramentas/` |

### 2.4 Metrópoles

| ID | Prática | Regra |
|----|---------|-------|
| ME-01 | `google-news.xml` no robots | AR-ROBOTS-005 |
| ME-02 | Sitemap news **rotacionado por dia** | Indexação horária |
| ME-03 | NewsMediaOrganization + ContactPoint | AR-SCHEMA-003 |
| ME-04 | Disallow busca interna e parâmetros | AR-ROBOTS-004 |
| ME-05 | Colunas com página de autor | AR-EEAT-005 |

### 2.5 ND Mais

| ID | Prática | Regra |
|----|---------|-------|
| ND-01 | news-sitemap com `news:publication_date` -03:00 | Timezone AR em news-sitemap |
| ND-02 | NewsMediaOrganization + GeoCoordinates | AR-SCHEMA-003 |
| ND-03 | `article:published_time` + `article:modified_time` | AR-SCHEMA-009 |
| ND-04 | Regras AI no robots | best_practices.aeo_geo |

### 2.6 Brasil 247

| ID | Prática | Regra |
|----|---------|-------|
| B247-01 | 5 sitemaps (news, category, tag, author, page) | Segmentação silo |
| B247-02 | NewsArticle + NewsMediaOrganization em posts | AR-SCHEMA-004 |
| B247-03 | OG tags abundantes (21+ em artigo) | AR-SCHEMA-008 |

### 2.7 Jota

| ID | Prática | Regra |
|----|---------|-------|
| JO-01 | Sitemaps em subdomínio `sitemap.jota.info` | CDN dedicada para crawlers |
| JO-02 | Coberturas especiais `/coberturas/` | Hubs temáticos profundos |
| JO-03 | Posicionamento "fonte de confiança" | AR-EEAT-001, 002 |

### 2.8 Poder360

| ID | Prática | Regra |
|----|---------|-------|
| P360-01 | NewsMediaOrganization + WebAPI schema | Dados estruturados avançados |
| P360-02 | Política editorial explícita | AR-EEAT-002 |
| P360-03 | Regras robots para bots IA | best_practices |

### 2.9 Folha

| ID | Prática | Regra |
|----|---------|-------|
| FO-01 | Sitemap por editoria (`/mercado/sitemap.xml`) | Silo por categoria |
| FO-02 | User-agent rules para OAI-AdsBot, AI2Bot… | Política IA granular |
| FO-03 | NewsMediaOrganization + Person | AR-SCHEMA-003 |

### 2.10 Valor

| ID | Prática | Regra |
|----|---------|-------|
| VA-01 | `news.xml` por vertical | AR-SITEMAP-003 |
| VA-02 | Colunistas com página própria | AR-EEAT-005 |
| VA-03 | Valor Data / tickers embutidos | Ticker mercados |
| VA-04 | Newsletters segmentadas | CF7 + Brevo |

### 2.11 Exame

| ID | Prática | Regra |
|----|---------|-------|
| EX-01 | Hubs editoriais profundos | AR-NAV-003 |
| EX-02 | "Mais lidas" numeradas | Layout trending |
| EX-03 | Revista + longform | AR-CONTENT-002 |
| EX-04 | Branded content separado | Label `parceiro` nos posts |

### 2.12 Finance News

| ID | Prática | Regra |
|----|---------|-------|
| FN-01 | Foco B3 / empresas listadas | Categoria negócios |
| FN-02 | Article + Person schema | AR-SCHEMA-004, 005 |

### 2.13 G1 Economia

| ID | Prática | Regra |
|----|---------|-------|
| G1-01 | Ticker nativo dólar/Ibovespa/euro | PressGrid forex |
| G1-02 | Educação financeira no positioning | Tagline + hubs |

### 2.14 Times Brasil

| ID | Prática | Regra |
|----|---------|-------|
| TB-01 | NewsMediaOrganization + licenciamento CNBC | Sobre + editorial |
| TB-02 | Endereço postal no schema | Organization address |

### 2.15 O Bastidor (anti-padrão)

| ID | Prática | Regra |
|----|---------|-------|
| OB-ANTI-01 | **Nunca** Cloudflare challenge em robots/sitemap | AR-ROBOTS-006 |

---

## 3. Regras anti-regressão — resumo por severidade

### 🛑 BLOCKERS (deploy falha)

| ID | Regra |
|----|-------|
| AR-ROBOTS-001 | robots.txt HTTP 200 |
| AR-ROBOTS-002 | Sitemap declarado no robots |
| AR-ROBOTS-006 | Sem Cloudflare challenge em robots |
| AR-SITEMAP-001/002 | sitemap_index + post-sitemap OK |
| AR-SCHEMA-001/002/004/007/008 | Schema e meta mínimos |
| AR-EEAT-001/002/003/004 | Páginas institucionais |
| AR-CONTENT-001/003/004 | Sem thin, sem-categoria, sem thumbnail |
| AR-NAV-001/002 | Menu primário configurado |
| AR-VISUAL-001/002 | Logo + accent #9AFF33 |
| AR-PERF-001 | HTTPS 200 |

### ⚠️ WARNINGS (corrigir em 48h)

| ID | Regra |
|----|-------|
| AR-ROBOTS-003/004/005 | wp-admin, ?s=, news-sitemap no robots |
| AR-SITEMAP-003/004/005 | news-sitemap, sem URLs 2018, categorias limpas |
| AR-SCHEMA-003/006/009/010 | NewsMediaOrganization, sem dup schema, article:time, titles pt |
| AR-EEAT-005/006 | 7 autores, bios |
| AR-CONTENT-002/005 | 80% posts 300+ palavras |
| AR-NAV-003/004 | Hubs /tudo-sobre/, RSS |
| AR-VISUAL-003/004 | Sem búlgaro, ticker mercados |
| AR-AEO-001 | llms.txt |
| AR-PERF-002/003 | Security headers, LCP |

---

## 4. Como executar as regras

### Após cada deploy no Victor

```bash
bash /var/www/estrato/repo/scripts/victor/check-portal-regression.sh
```

### Modo estrito (falha em qualquer ❌ ou ⚠️)

```bash
bash scripts/victor/check-portal-regression.sh --strict
```

### Integrar no CI (GitHub Actions — sugerido)

```yaml
- name: Anti-regression check
  run: bash scripts/victor/check-portal-regression.sh --strict
```

### Ordem de implementação (sai do estado atual)

1. **Fase 0 técnica** — robots completo + news-sitemap + Yoast titles → passa AR-ROBOTS-003/005, AR-SCHEMA-010
2. **Fase 0 E-E-A-T** — páginas institucionais + 7 autores → passa AR-EEAT-*
3. **Fase 1 estrutura** — hubs /tudo-sobre/ → passa AR-NAV-003
4. **Fase 2 conteúdo** — 300 palavras → passa AR-CONTENT-*
5. **Fase 3 schema** — NewsMediaOrganization, remover dup → passa AR-SCHEMA-003/006
6. **Fase 4 AEO** — llms.txt → passa AR-AEO-001
7. **Limpeza sitemap** — remover posts 2018 → passa AR-SITEMAP-004

---

## 5. Estado atual vs. regras (snapshot 2026-07-09)

| Grupo | Pass estimado | Fail/Warn |
|-------|---------------|-----------|
| robots básico | 3/6 | wp-admin, ?s=, news-sitemap |
| sitemap | 2/5 | news-sitemap, URLs 2018 |
| schema home | 4/5 | NewsMediaOrganization |
| schema post | 5/7 | dup schema, article:time |
| E-E-A-T | 0/4 | todas páginas 404 |
| conteúdo | ? | thin posts, sem-categoria |
| branding | 2/2 | ✅ logo + accent |
| AEO | 0/1 | llms.txt |

**Estimativa:** ~15 PASS, ~20 WARN, ~8 BLOCKER no estado atual.

---

## 6. Lista única — TODAS as melhores práticas (checklist mestre)

Use como Definition of Done do portal. Marque ✅ quando implementado + regra anti-regressão ativa.

### Técnico SEO
- [ ] robots.txt com sitemap_index + news-sitemap
- [ ] Disallow wp-admin, ?s=, xmlrpc, wp-login
- [ ] Política user-agent para scrapers e bots IA
- [ ] sitemap_index + post + category + author + news
- [ ] news-sitemap com timezone America/Sao_Paulo
- [ ] Sem URLs pré-2024 no sitemap
- [ ] RSS feed funcional
- [ ] canonical único por URL
- [ ] og:image 1200×630 em posts
- [ ] article:published_time + modified_time
- [ ] Titles Yoast 100% pt_BR (sem "Archives")

### Schema
- [ ] WebSite + SearchAction na home
- [ ] NewsMediaOrganization na home
- [ ] NewsArticle + Person + BreadcrumbList em posts
- [ ] Sem duplicação Article+NewsArticle
- [ ] FAQPage em hubs evergreen
- [ ] Organization/Person com sameAs

### E-E-A-T
- [ ] /sobre/ /politica-editorial/ /contato/ /politica-de-privacidade/
- [ ] 7 autores com bio, foto, jobTitle
- [ ] Bylines visíveis no single
- [ ] Rodapé com CNPJ/razão social

### Conteúdo
- [ ] Mínimo 300 palavras (80% dos posts)
- [ ] Resumo AEO nos primeiros 100 palavras
- [ ] 3 links internos por post
- [ ] Conteúdo reescrito (não cópia da fonte)
- [ ] 100% posts com featured image
- [ ] Zero "sem-categoria"

### Estrutura
- [ ] Menu 7 categorias financeiras
- [ ] Hubs /tudo-sobre/ (7+)
- [ ] Ticker Ibovespa/USD/Selic
- [ ] Bloco Mais Lidas
- [ ] Newsletter funcional
- [ ] Layout Builder por editoria

### AEO / GEO
- [ ] llms.txt na raiz
- [ ] FAQs com schema
- [ ] Dados citáveis com fonte+data
- [ ] Entidades nomeadas (PETR4, Selic…)

### Indexação
- [ ] IndexNow após publish
- [ ] Bot GSC por categoria
- [ ] news-sitemap <48h

### Branding
- [ ] Logo estrato. no header
- [ ] Accent #9AFF33
- [ ] Zero strings búlgaras (i18n)
- [ ] Sem Cloudflare challenge em bots

---

*Documento vivo — atualizar quando novas regras forem implementadas. Rodar `check-portal-regression.sh` para validar.*

**Plano de execução por sprint:** `ESTRATO-SPRINT-PLAN.md` (Fase 1: Sprints 1–7 → gate 100% → Fase 2: categorias/RSS)*
