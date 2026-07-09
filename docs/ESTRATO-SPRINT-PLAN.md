# Estrato.cc — Plano de Sprints (Correção → 100% → Categorias/RSS)

**Versão:** 1.0.0 · 2026-07-09  
**Premissa:** só iniciamos **Fase 2 (categorias + feeds RSS)** quando o validador anti-regressão passar em modo `--strict` com **0 blockers e 0 warnings**.

**Artefatos de controle:**
- Validador: `scripts/victor/check-portal-regression.sh --strict`
- Regras: `portals/estrato-anti-regression.yaml`
- Benchmark: `docs/ESTRATO-BENCHMARK-TECNICO.md`, `docs/ESTRATO-ANTI-REGRESSAO.md`

---

## Visão geral

```
┌─────────────────────────────────────────────────────────────────────────┐
│  FASE 1 — Portal 100% (Sprints 1–7)                                   │
│  Sprint 1 → 2 → 3 → 4 → 5 → 6 → 7 (gate)                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼ Gate: check-portal-regression --strict
┌─────────────────────────────────────────────────────────────────────────┐
│  FASE 2 — Categorias & RSS (Sprint 8+)                                │
│  Preset brasil-financeiro · feeds por categoria · menu · home sections  │
└─────────────────────────────────────────────────────────────────────────┘
```

| Sprint | Foco | Referência principal | Regras liberadas |
|--------|------|----------------------|------------------|
| **S1** | Robots + sitemap + Yoast titles | BPMoney, Money Times | AR-ROBOTS, AR-SITEMAP, AR-SCHEMA-010 |
| **S2** | Schema + meta OG | ND Mais, InfoMoney, Metrópoles | AR-SCHEMA-001…009 |
| **S3** | E-E-A-T + autores | Jota, Valor, Poder360 | AR-EEAT-* |
| **S4** | Conteúdo + pipeline | Valor, InfoMoney, Exame | AR-CONTENT-* |
| **S5** | Estrutura + visual | G1, Money Times, InfoMoney | AR-NAV-*, AR-VISUAL-* |
| **S6** | AEO/GEO + indexação bot | Folha, BPMoney, Metrópoles | AR-AEO-*, AR-INDEX-* |
| **S7** | Gate 100% + polish | Todos | Checklist mestre completo |
| **S8+** | Categorias + RSS | Preset financeiro | *(após gate)* |

---

## Estado inicial (baseline 2026-07-09)

| Métrica | Valor |
|---------|-------|
| Validador | 13 pass · 15 warn · 4 blockers |
| Posts | 732 |
| URLs sitemap pré-2024 | 68 |
| Páginas E-E-A-T | 0/4 publicadas |
| news-sitemap | ausente |
| Hubs /tudo-sobre/ | 0/7 |
| llms.txt | ausente |

---

## Sprint 1 — Fundação técnica SEO

**Objetivo:** robots.txt e sitemaps no padrão BPMoney/Money Times; titles Yoast em pt_BR; limpar lixo de migração no sitemap.

**Referências por área:**

| Área | Copiar de | O que implementar |
|------|-----------|-------------------|
| robots.txt | **BPMoney** | Disallow wp-admin, ?s=, xmlrpc, wp-login |
| robots.txt | **Metrópoles** | Disallow parâmetros de busca |
| sitemap | **Money Times** | Declarar news-sitemap no robots |
| sitemap | **BPMoney** | Segmentação (posts, categories, authors) via Yoast |
| titles | **InfoMoney** | Templates pt_BR sem "Archives" |
| limpeza | — | Remover/arquivar posts 2018–2023 do sitemap |

### Tarefas

| # | Tarefa | Artefato | Responsável |
|---|--------|----------|-------------|
| S1.1 | Estender `robots.txt` (filtro Yoast ou virtual robots) | `estrato-portal-bootstrap/seo-robots.php` | Bot |
| S1.2 | Ativar news-sitemap Yoast + declarar no robots | Victor WP-CLI | Bot |
| S1.3 | Configurar Yoast title templates pt_BR (home, category, post) | `scripts/victor/setup-estrato-seo.sh` | Bot |
| S1.4 | Auditar posts pré-2024: arquivar ou noindex em lote | `scripts/victor/archive-legacy-posts.php` | Bot |
| S1.5 | Recategorizar 58 posts `sem-categoria` via regras pipeline | `scripts/victor/fix-uncategorized.php` | Bot |
| S1.6 | Ocultar categorias legado (política, tecnologia, brasil) do sitemap | RSS plugin / Yoast | Bot |
| S1.7 | Registrar Google Search Console + Bing Webmaster | Manual / doc | Humano |
| S1.8 | Deploy Victor + purge Cloudflare | SSH | Bot |

### Critérios de aceite (DoD Sprint 1)

```bash
bash scripts/victor/check-portal-regression.sh
# Esperado:
# ✅ AR-ROBOTS-003, 004, 005
# ✅ AR-SITEMAP-003, 004
# ✅ AR-SCHEMA-010
# ⚠️ 0 novos blockers
```

| Regra | Meta |
|-------|------|
| AR-ROBOTS-003/004/005 | PASS |
| AR-SITEMAP-003/004 | PASS |
| AR-SCHEMA-010 | PASS |
| AR-CONTENT-003 | PASS (sem-categoria = 0) |

---

## Sprint 2 — Schema & Meta (rich results)

**Objetivo:** schema no padrão Metrópoles/ND Mais/InfoMoney; eliminar duplicação; OG completo.

**Referências:**

| Área | Copiar de | Implementar |
|------|-----------|-------------|
| Publisher | **Metrópoles**, **ND Mais** | NewsMediaOrganization na home |
| Post | **InfoMoney**, **Money Times** | NewsArticle + Person + BreadcrumbList |
| Meta | **ND Mais** | article:published_time + modified_time |
| OG | **Brasil 247** | og:image 1200×630 por post (não logo) |
| Anti-dup | — | Remover NewsArticle do theme; manter Yoast |

### Tarefas

| # | Tarefa | Artefato |
|---|--------|----------|
| S2.1 | `estrato-portal-bootstrap/seo-schema.php` — NewsMediaOrganization | Plugin |
| S2.2 | Desativar JSON-LD duplicado do PressGrid no single | Filter no bootstrap |
| S2.3 | Yoast: Organization com sameAs (LinkedIn, Instagram, X) | WP-CLI / customizer |
| S2.4 | OG image default 1200×630 + por post automático | Yoast social |
| S2.5 | Garantir Person schema com jobTitle por autor | Yoast author |
| S2.6 | Breadcrumb visível no single (além do schema) | PressGrid child/filter |

### DoD Sprint 2

| Regra | Meta |
|-------|------|
| AR-SCHEMA-001/002/003 | PASS |
| AR-SCHEMA-004/005/007/008 | PASS |
| AR-SCHEMA-006 | PASS (sem dup) |
| AR-SCHEMA-009 | PASS |

---

## Sprint 3 — E-E-A-T & Institucional

**Objetivo:** confiança YMYL (finanças) no padrão Jota/Valor/Poder360.

**Referências:**

| Área | Copiar de | Implementar |
|------|-----------|-------------|
| Transparência | **Jota** | "Fonte de confiança" — política editorial |
| Institucional | **Valor**, **Poder360** | Sobre, Contato, Privacidade |
| Autores | **Money Times**, **Valor** | 7 perfis com bio, foto, especialidade |
| Footer | **Folha** | 4 colunas + CNPJ/razão social |
| Byline | **InfoMoney** | "Por [Nome], [Especialidade]" no single |

### Tarefas

| # | Tarefa | Conteúdo |
|---|--------|----------|
| S3.1 | Publicar `/sobre/` | Missão, equipe, metodologia |
| S3.2 | Publicar `/politica-editorial/` | Fontes, correções, conflitos |
| S3.3 | Publicar `/contato/` | Formulário CF7 + email |
| S3.4 | Publicar `/politica-de-privacidade/` | Draft ID 3 → publish |
| S3.5 | Criar 7 autores WP (1 por categoria financeira) | `scripts/victor/create-authors.sh` |
| S3.6 | Bios + avatars + meta `job_title` | User meta |
| S3.7 | Mapear pipeline → author_id por categoria | `wp_bridge.py` |
| S3.8 | Footer menu institucional | WP menus |
| S3.9 | Remover "Página de exemplo" | WP-CLI delete |

### Autores planejados

| Slug | Nome | Categoria | Especialidade |
|------|------|-----------|---------------|
| `ana-economia` | Ana Ribeiro | economia | Macroeconomia |
| `marcos-mercados` | Marcos Vieira | mercados | Mercado financeiro |
| `lucia-negocios` | Lúcia Mendes | negocios | Empresas e M&A |
| `pedro-financas` | Pedro Alves | financas-pessoais | Finanças pessoais |
| `rafa-cripto` | Rafa Costa | criptomoedas | Criptoativos |
| `julia-agro` | Júlia Santos | agronegocio | Agronegócio |
| `henrique-mundo` | Henrique Lima | mundo | Economia internacional |

### DoD Sprint 3

| Regra | Meta |
|-------|------|
| AR-EEAT-001/002/003/004 | PASS (HTTP 200) |
| AR-EEAT-005 | PASS (≥7 autores) |
| AR-EEAT-006 | PASS |

---

## Sprint 4 — Conteúdo & Pipeline

**Objetivo:** sair do modo agregador thin-content; padrão Valor/InfoMoney/Exame.

**Referências:**

| Área | Copiar de | Implementar |
|------|-----------|-------------|
| Profundidade | **Valor** | Mínimo 300 palavras |
| Formato AEO | **InfoMoney** | Resumo + FAQ + entidades |
| Análise | **Exame** | 1 longform/semana/categoria |
| Links | **Todos** | 3 internal links/post (linker) |
| Imagens | — | 100% featured image |

### Tarefas

| # | Tarefa | Artefato |
|---|--------|----------|
| S4.1 | `writer.py`: rejeitar/rascunho se <300 palavras | Victor patch |
| S4.2 | Template AEO no prompt (resumo, FAQ, entidades) | `content/templates/post-aeo.md` |
| S4.3 | `linker.py`: 3 links internos (categoria + hub) | Victor cron |
| S4.4 | Backfill: reescrever top 50 thin posts | Script batch |
| S4.5 | `noindex` em posts <200 palavras até rewrite | Bridge meta |
| S4.6 | 1 análise longform (1500+ pal) por categoria | Pipeline mode `analysis` |
| S4.7 | Validar 100% posts com thumbnail | Bridge refresh cron |

### DoD Sprint 4

| Regra | Meta |
|-------|------|
| AR-CONTENT-001 | PASS (0 posts <200 palavras) |
| AR-CONTENT-002 | PASS (≥80% com 300+ palavras) |
| AR-CONTENT-004 | PASS |
| AR-CONTENT-005 | PASS |

---

## Sprint 5 — Estrutura, navegação & visual

**Objetivo:** home e silos no padrão G1/InfoMoney/Money Times/Exame.

**Referências:**

| Área | Copiar de | Implementar |
|------|-----------|-------------|
| Ticker | **G1**, **Money Times** | Ibovespa, USD, EUR, Selic na topbar |
| Hubs | **InfoMoney**, **Exame** | 7 páginas `/tudo-sobre/` |
| Home layout | **Money Times** | Hero economia, grid mercados, sidebar finanças |
| Mais lidas | **Exame**, **Money Times** | Query 7 dias real |
| Newsletter | **ND Mais**, **Valor** | CF7 → Brevo/Mailchimp |
| Cotações | **Money Times** | Página `/cotacoes/` |
| Categoria | **Folha** | Intro SEO 150 palavras por silo |

### Tarefas

| # | Tarefa | Detalhe |
|---|--------|---------|
| S5.1 | PressGrid Layout Builder por editoria | 7 seções amarradas às categorias |
| S5.2 | Ativar forex ticker PressGrid | Customizer |
| S5.3 | Criar 7 hubs `/tudo-sobre/{tema}/` | selic, ibovespa, dolar, cripto, inflacao, tributacao, agronegocio |
| S5.4 | Intro SEO única em `category_description` | 150 palavras × 7 |
| S5.5 | Página `/cotacoes/` | Frankfurter + delay B3 |
| S5.6 | Bloco "Mais lidas" (7 dias) | PressGrid section |
| S5.7 | Newsletter CF7 integrada | Brevo API |
| S5.8 | Bylines visíveis no single | Template filter |
| S5.9 | Tempo de leitura + "Atualizado em" | PHP snippet |
| S5.10 | Footer 4 colunas | Menus + institucional |

### DoD Sprint 5

| Regra | Meta |
|-------|------|
| AR-NAV-001/002/003/004 | PASS |
| AR-VISUAL-003/004 | PASS |
| AR-NAV-003 | PASS (3/3 hubs mínimo → meta 7/7) |

---

## Sprint 6 — AEO, GEO & Indexação

**Objetivo:** visibilidade em IA de resposta e Google News; bot de indexação.

**Referências:**

| Área | Copiar de | Implementar |
|------|-----------|-------------|
| robots IA | **BPMoney**, **Folha** | Regras CCBot, GPTBot, PerplexityBot |
| news-sitemap | **ND Mais** | Timezone America/Sao_Paulo |
| GEO | **InfoMoney** | llms.txt + dados citáveis |
| AEO | **InfoMoney** | FAQPage em hubs |
| Indexação | **Metrópoles** | News sitemap diário + ping |
| IndexNow | — | Ping após publish |

### Tarefas

| # | Tarefa | Artefato |
|---|--------|----------|
| S6.1 | `llms.txt` + `llms-full.txt` na raiz | `assets/llms.txt` |
| S6.2 | FAQ schema nos 7 hubs | `seo-schema.php` |
| S6.3 | Regras user-agent IA no robots | BPMoney-style |
| S6.4 | IndexNow no publisher-bridge | Hook on publish |
| S6.5 | `scripts/victor/index-bot/` — GSC + ping | Python |
| S6.6 | Cron news-sitemap refresh hourly | WP cron |
| S6.7 | Google News Publisher Center | Manual (após S3+S4) |
| S6.8 | `ads.txt` placeholder (futuro GAM) | Static file |

### DoD Sprint 6

| Regra | Meta |
|-------|------|
| AR-AEO-001 | PASS |
| AR-AEO-002 | PASS (≥3 FAQs) |
| AR-INDEX-001/002 | PASS |

---

## Sprint 7 — Gate 100% & Polish final

**Objetivo:** validador `--strict` verde; checklist mestre completo; preparar handoff para Fase 2.

### Tarefas

| # | Tarefa |
|---|--------|
| S7.1 | Rodar `check-portal-regression.sh --strict` até 0 blockers |
| S7.2 | Resolver todos os warnings restantes |
| S7.3 | Lighthouse: LCP < 2.5s, CLS < 0.1 (home + single) |
| S7.4 | Rich Results Test: 0 erros schema (amostra 10 URLs) |
| S7.5 | GSC: cobertura >90% URLs válidas |
| S7.6 | Documentar runbook de produção atualizado |
| S7.7 | Tag git `estrato-portal-v1.0` |

### Gate de saída Fase 1 (obrigatório)

```
✅ check-portal-regression.sh --strict → RESULT: PASS
✅ Checklist mestre (ESTRATO-ANTI-REGRESSAO.md §6) → 100% marcado
✅ 7 autores · 4 páginas institucionais · 7 hubs
✅ news-sitemap ativo · 0 URLs legadas no sitemap
✅ ≥80% posts com 300+ palavras
✅ Logo + accent + menu financeiro
```

**Somente após este gate → iniciar Sprint 8.**

---

## Fase 2 — Sprint 8+ (Categorias & RSS) — APÓS 100%

> **Escopo adiado deliberadamente.** Não alterar preset `brasil-financeiro` nem feeds até o gate Sprint 7.

### Sprint 8 — Taxonomia financeira

**Referências:** InfoMoney (editorias), Money Times (tags), Valor (verticais)

| # | Tarefa |
|---|--------|
| S8.1 | Validar 7 categorias menu vs. conteúdo real (volume, thin) |
| S8.2 | Fundir/arquivar política, tecnologia, brasil (legado) |
| S8.3 | Criar subcategorias se necessário (ex.: `mercados/ibovespa`) |
| S8.4 | Sincronizar YAML `estrato-finance.yaml` → PHP preset |
| S8.5 | `category_description` SEO revisada com dados de GSC |
| S8.6 | PressGrid sections 1:1 com categorias finais |

### Sprint 9 — Curadoria RSS por categoria

**Referências:** BPMoney (feeds segmentados), InfoMoney (fontes tier-1), G1 (economia)

| # | Tarefa |
|---|--------|
| S9.1 | Matriz fonte × categoria (sem duplicata Exame em 3 categorias) |
| S9.2 | Tier-1: Valor, InfoMoney, BC, B3, Reuters, Folha Mercado |
| S9.3 | Tier-2: Money Times, Finance News, Investing.com |
| S9.4 | Remover feeds quebrados ou duplicados |
| S9.5 | `pipeline_primary`: manter 2 items/feed, 15 max, hourly |
| S9.6 | Rebuild menu `estrato_rss_rebuild_menus()` |
| S9.7 | Indexação bot **categoria por categoria** (mercados → negócios → …) |

### Sprint 10 — Operação contínua

| # | Tarefa |
|---|--------|
| S10.1 | CI: `check-portal-regression.sh --strict` em cada deploy |
| S10.2 | Relatório semanal GSC por categoria |
| S10.3 | Rotina mensal: revisar feeds + thin posts |

---

## Ritual de cada sprint

```
1. Planning   → revisar tarefas + regras AR-* alvo
2. Build      → implementar no repo + deploy Victor
3. Validate   → check-portal-regression.sh
4. Commit     → push + update PR
5. Review     → comparar métricas vs baseline
6. Retro      → bloqueios para próximo sprint
```

### Comandos padrão

```bash
# Antes do sprint (baseline)
bash scripts/victor/check-portal-regression.sh | tee sprint-N-before.log

# Deploy
bash scripts/victor/setup-estrato-seo.sh          # S1
bash scripts/victor/setup-estrato-portal-branding.sh

# Após sprint (validação)
bash scripts/victor/check-portal-regression.sh --strict | tee sprint-N-after.log
```

---

## Mapa rápido: concorrente → sprint

| Portal | Melhor em | Sprint |
|--------|-----------|--------|
| BPMoney | robots + 9 sitemaps | S1, S6 |
| Money Times | news-sitemap + ticker + colunistas | S1, S5 |
| InfoMoney | hubs + guias AEO | S4, S5 |
| Metrópoles | news diário + NewsMediaOrganization | S1, S2, S6 |
| ND Mais | news timezone + article:time | S2, S6 |
| Brasil 247 | OG abundante + news sitemaps | S2 |
| Jota | editorial trust + coberturas | S3 |
| Poder360 | política editorial | S3 |
| Valor | colunistas + newsletters + dados | S3, S5 |
| Folha | silo por editoria + robots IA | S1, S5, S6 |
| Exame | hubs + mais lidas + longform | S4, S5 |
| G1 | ticker nativo | S5 |
| Finance News | B3/empresas | S8 (categoria negócios) |
| Times Brasil | NewsMediaOrganization | S2 |
| O Bastidor | anti-padrão CF | S1 (nunca bloquear bots) |

---

## Próximo passo imediato

**Iniciar Sprint 1** — fundação técnica SEO (robots, news-sitemap, Yoast titles, limpeza sitemap 2018).

Confirme para executar Sprint 1 no Victor.
