# Estrato.cc — Matriz Técnica SEO (robots, sitemap, schema)

**Auditoria:** 2026-07-09 · 17 portais + estrato.cc  
**Complementa:** `ESTRATO-BENCHMARK-ROADMAP.md` e `ESTRATO-ANTI-REGRESSAO.md`

---

## 1. Resposta direta: o mapeamento anterior cobria schema/robots/sitemap?

**Parcialmente.** O roadmap citava Yoast e sitemap, mas **não havia matriz comparativa auditada** nem regras anti-regressão. Este documento fecha essa lacuna com dados coletados ao vivo.

---

## 2. Matriz técnica — robots.txt

| Portal | Status | Sitemaps declarados | News sitemap | Bloqueia `?s=` | Regras AI/bots | Melhor prática a copiar |
|--------|--------|---------------------|--------------|----------------|----------------|-------------------------|
| **estrato.cc** | ✅ 200 | 1 (`sitemap_index.xml`) | ❌ | ❌ | ❌ | Baseline Yoast — falta news + disallow admin |
| **Metrópoles** | ✅ | `google-news.xml` | ✅ | ✅ (`?s=`, busca) | ❌ | News sitemap dedicado + bloqueio de busca interna |
| **Times Brasil** | ✅ | Yoast index | ❌ | ❌ | ❌ | NewsMediaOrganization no schema |
| **O Bastidor** | ⚠️ CF 403 | — | — | — | — | **Não bloquear bots em robots/sitemap** (Cloudflare challenge) |
| **InfoMoney** | ✅ | 2 (site + simulador) | ❌ | ❌ | ❌ | Sitemap separado por produto vertical |
| **Folha** | ✅ | `sitemap.xml` (multi-área) | ❌ | parcial | ✅ (AI2Bot, OAI-AdsBot…) | Regras explícitas por user-agent de IA |
| **BPMoney** | ✅ | **9 sitemaps** | ✅ | ✅ | ✅ bloqueia scrapers/IA | **Referência #1 robots** — news + taxonomias + vídeo + web stories |
| **O Globo** | ✅ | (Globo CDN) | — | — | — | Schema Organization + WebPage |
| **Money Times** | ✅ | index + **news-sitemap.xml** | ✅ | ❌ | ❌ | News sitemap Yoast separado |
| **Finance News** | ✅ | Yoast index | ✅ (no robots) | ❌ | ❌ | Posts com Article + Person |
| **ND Mais** | ✅ | index + **news-sitemap.xml** | ✅ | ❌ | ✅ | News sitemap + NewsMediaOrganization + LocalBusiness |
| **Brasil 247** | ✅ | 5 (news, category, tag…) | ✅ | — | ✅ | Múltiplos sitemaps por tipo + news |
| **Poder360** | ✅ | (dinâmico) | — | — | ✅ | NewsMediaOrganization + WebAPI |
| **Jota** | ✅ | 5 no subdomínio `sitemap.jota.info` | ✅ | — | ❌ | Sitemap em CDN/subdomínio dedicado |
| **Valor** | ✅ | valor + **news.xml** | ✅ | — | ✅ | News sitemap por vertical Globo |
| **Exame** | ✅ | `sitemap.xml` | ❌ | — | ❌ | Hubs editoriais profundos |
| **G1** | ⚠️ | robots no domínio raiz | — | — | — | Ticker de mercado nativo |

### Gaps do Estrato vs. líderes (robots)

| Regra | Estrato | Alvo (BPMoney/Money Times/ND Mais) |
|-------|---------|-------------------------------------|
| `Disallow: /wp-admin/` | ❌ | ✅ |
| `Disallow: /?s=` e `/search` | ❌ | ✅ |
| `Disallow: /xmlrpc.php` | ❌ | ✅ |
| `Sitemap: news-sitemap.xml` | ❌ | ✅ |
| Sitemaps por taxonomia/autor | ❌ parcial | ✅ |
| Regras para scrapers (Ahrefs, Semrush…) | ❌ | ✅ opcional |
| Política explícita para bots de IA | ❌ | ✅ Folha/BPMoney/ND Mais |

---

## 3. Matriz técnica — sitemap

| Portal | Tipo | Segmentação | Imagem no sitemap | News extension | Observação |
|--------|------|-------------|-------------------|----------------|------------|
| **estrato.cc** | Yoast index | post, page, category, author | ✅ `image:image` | ❌ | ⚠️ Contém URLs **2018** (legado pré-migração) |
| **Metrópoles** | Custom | static + **news diário** (`news-2026-07-09.xml`) | ✅ | ✅ | Sitemap news rotacionado por dia |
| **BPMoney** | SEOX Publisher | posts, pages, categories, tags, **columns**, author, **video**, web stories | ✅ | ✅ news-sitemap | **Referência #1 sitemap** |
| **Money Times** | Yoast + news | post (paginado), news | ✅ | ✅ | `news-sitemap.xml` separado |
| **ND Mais** | Yoast + news | index + news | ✅ | ✅ | `news:publication_date` com timezone BR |
| **Brasil 247** | Custom | news, category, tag, author, page | — | ✅ | Granularidade máxima |
| **Jota** | Subdomínio | google-news, index, tags | — | ✅ | Infra separada para crawlers |
| **Valor** | Globo | sitemap + news por vertical | — | ✅ | |
| **InfoMoney** | Yoast | post (multi-arquivo) + simulador | ✅ | ❌ | |
| **Finance News** | Yoast | padrão | ✅ | ❌ | |
| **Folha** | Multi-área | por editoria (`/mercado/sitemap.xml`…) | — | ❌ | Silo por subdomínio/path |

### Gaps do Estrato (sitemap)

1. **Sem `news-sitemap.xml`** — obrigatório para Google News
2. **Sem sitemap por categoria** — dificulta indexação bot categoria a categoria
3. **URLs legadas 2018 no post-sitemap** — thin/stale content; risco de crawl budget waste
4. **Sem `video-sitemap`** — quando houver vídeo
5. **Sem `lastmod` consistente** em posts pipeline (usar data de publicação real)

---

## 4. Matriz técnica — schema.org (JSON-LD)

### Home

| Portal | Tipos detectados | Destaque |
|--------|------------------|----------|
| **estrato.cc** | WebSite, Organization, CollectionPage, BreadcrumbList, SearchAction | ✅ básico Yoast |
| **InfoMoney** | NewsArticle, Organization, Person, BreadcrumbList | Article até na home |
| **Money Times** | NewsMediaOrganization, Organization, BreadcrumbList | |
| **Metrópoles** | **NewsMediaOrganization**, WebSite, SearchAction | E-E-A-T publisher |
| **ND Mais** | **NewsMediaOrganization**, Place, GeoCoordinates, ContactPoint | Local + publisher |
| **Brasil 247** | **NewsMediaOrganization**, Person, ContactPoint | |
| **Jota** | **NewsMediaOrganization**, WebSite | |
| **Poder360** | **NewsMediaOrganization**, WebAPI | |
| **Folha** | **NewsMediaOrganization**, Person, ContactPoint | |
| **BPMoney** | Organization, ItemList, BreadcrumbList | |
| **Finance News** | Organization, CollectionPage | |
| **Valor** | Organization, WebPage, WebSite | Mínimo (JSON em outro layer) |
| **Times Brasil** | **NewsMediaOrganization**, Country, PostalAddress | |

### Single post (artigo real)

| Portal | NewsArticle | Person (autor) | BreadcrumbList | article:published | FAQPage |
|--------|-------------|----------------|----------------|-------------------|---------|
| **estrato.cc** | ✅ (+ Article duplicado ⚠️) | ✅ (`tpb`) | ✅ | ✅ 1 tag | ❌ |
| **InfoMoney** | ✅ | ✅ | ✅ | ✅ 3 tags | ❌ |
| **Money Times** | ✅ | ✅ | ✅ | ✅ 3 tags | ❌ |
| **Finance News** | ✅ (Article) | ✅ | ✅ | ✅ 2 tags | ❌ |
| **Brasil 247** | ✅ | ✅ | ✅ | ✅ 9 tags | ❌ |
| **Valor** | ❌ (JS/AMP) | ❌ | ❌ | ❌ | ❌ |

### Gaps do Estrato (schema)

| Item | Status | Ação |
|------|--------|------|
| **NewsMediaOrganization** na home | ❌ | Adicionar via Yoast ou custom |
| **Schema duplicado** Article + NewsArticle | ⚠️ | Desativar theme NewsArticle; manter Yoast |
| **Person com credenciais** | ⚠️ só "tpb" | 7 autores com `jobTitle`, `sameAs` |
| **FAQPage** em guias | ❌ | AEO — hubs e posts evergreen |
| **VideoObject** | ❌ | Quando houver vídeo |
| **Speakable** | ❌ | AEO opcional para assistentes |

---

## 5. Meta tags e arquivos auxiliares

| Arquivo / tag | Estrato | BPMoney | Money Times | ND Mais | Folha |
|---------------|---------|---------|-------------|---------|-------|
| `rel=canonical` (post) | ✅ (2x ⚠️ dup) | ✅ | ✅ | ✅ | ✅ |
| `og:*` | ✅ 12 | ✅ | ✅ 10 | ✅ 13 | ✅ |
| `twitter:*` | ✅ 7 | — | — | — | — |
| `article:published_time` | ✅ | — | ✅ | ✅ | — |
| RSS feed | ✅ | ✅ | ✅ | ✅ | ✅ |
| `llms.txt` | ❌ 404 | — | — | — | — |
| `ads.txt` | ❌ 404 | — | — | — | — |
| AMP | ❌ | — | — | — | parcial |

---

## 6. Melhor prática por portal — o que importar para o Estrato

| Portal | **Copiar isto** | Aplicar em |
|--------|-----------------|------------|
| **BPMoney** | robots completo (9 sitemaps, news, AI/scraper rules) | `robots.txt` + Yoast sitemaps |
| **Money Times** | news-sitemap + ticker + plantão B3 + colunistas | Mercados + cron B3 |
| **InfoMoney** | hubs `/tudo-sobre/`, guias, cursos, AEO | Finanças pessoais |
| **Metrópoles** | news sitemap diário + NewsMediaOrganization | Pipeline news |
| **ND Mais** | news-sitemap com timezone BR + geo schema | Local opcional |
| **Brasil 247** | sitemaps por tipo (category, tag, author) | Silos |
| **Jota** | sitemap em subdomínio + coberturas especiais | Tributos/regulação |
| **Poder360** | Poder Data / APIs + política editorial forte | Política econômica |
| **Valor** | colunistas + Valor Data + newsletters | Análise + dados |
| **Folha** | regras por user-agent IA + silo por editoria | robots IA + URLs |
| **Exame** | hubs temáticos + revista + branded content | Negócios |
| **Finance News** | foco B3/empresas listadas | Negócios |
| **Times Brasil** | licenciamento CNBC + NewsMediaOrganization | Mundo |
| **G1** | ticker nativo dólar/Ibovespa na home | Mercados |
| **O Globo** | rede Globo cross-link | Mundo (opcional) |
| **O Bastidor** | — | ⚠️ não bloquear crawlers |

---

## 7. Estado atual estrato.cc — checklist técnico

| Item | Resultado auditoria |
|------|---------------------|
| robots.txt acessível | ✅ HTTP 200 |
| Sitemap index | ✅ 4 sub-sitemaps |
| News sitemap | ❌ |
| Schema home | ✅ WebSite + Organization |
| Schema post | ✅ NewsArticle + Article (dup) |
| Canonical | ✅ (duplicado em alguns templates) |
| OG/Twitter | ✅ |
| RSS | ✅ |
| llms.txt | ❌ |
| ads.txt | ❌ |
| Posts legados no sitemap | ⚠️ URLs 2018 |
| Autor schema | ⚠️ genérico `tpb` |

---

*Próximo artefato: regras anti-regressão em `ESTRATO-ANTI-REGRESSAO.md` + `portals/estrato-anti-regression.yaml` + `scripts/victor/check-portal-regression.sh`*
