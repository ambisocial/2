# Auditoria Visual & de Conteúdo — 2026-07-13

> Feedback do editor:
> "Fiz uma auditoria visual no portal e está tudo em disfunção. Conteúdos que não têm nada a ver com nada, categorias erradas com conteúdos errados, estrutura visual com overflow e outros fatores. Cache importante — pesquisar a fundo cada página e criar um plano para correção total."

Este documento é o **plano de correção total** derivado de uma varredura em cada portal
(estrato.cc, mente.estrato.cc, lifestyle.estrato.cc, science.estrato.cc, sustain.estrato.cc,
culture.estrato.cc) cobrindo home, categorias, singles, hubs e páginas institucionais.

Todos os achados foram reproduzidos em produção via HTTP direto e confirmados
no banco WordPress por WP-CLI.

---

## Sumário executivo

| # | Bug | Severidade | Escopo | Correção |
|---|---|---|---|---|
| B1 | `og:image` corrompido injetando HTML dentro da URL | **P0** | Todos os portais, todas as páginas | Remover/refatorar `estrato_yoast_og_dimensions` |
| B2 | 37 posts órfãos por satélite (~80% do conteúdo) — feeds financeiros contaminando editorias incompatíveis | **P0** | 5 satélites (Mente, Lifestyle, Science, Sustain, Culture) | Purga + gate de coerência source×editoria |
| B3 | Kickers com `&amp;AMP;` (double-encoding + strtoupper não UTF-8) | **P0** visual | 5 satélites (13–15 ocorrências por home) | `html_entity_decode` + `mb_strtoupper` |
| B4 | Kicker vazio em single (CSS oculta header do tema sem re-renderizar contexto) | **P1** | Todos os portais em `single` | Ajustar template `single-article.php` |
| B5 | Categorias root não têm URL sem `/category/` — links diretos dão 404 | **P1** | Todos os portais | Rewrite ou remover `category_base` |
| B6 | Subcategorias com slug redundante (`jogos-imaginacao/jogos-imaginacao-rpg-mesa-osr`) | **P2** | Culture, Mente, Lifestyle, Sustain | Renomear slugs de filhos |
| B7 | `aria-label` com `&amp;` cru | **P2** | 5 satélites | `html_entity_decode` no atributo |
| B8 | 14 blocos `<style>` inline no `<head>` (CSS crítico fragmentado) | **P2** | Todos os portais | Consolidar em 1–2 blocos |
| B9 | OG default de 280×72 em vez de 1200×630 | **P2** | Todos os portais | Substituir imagem `estrato-og-default.png` |
| B10 | `estrato-finance` com 1792 rascunhos acumulados | **P3** | Finance | Rotina de higiene mensal |
| B11 | Ausência de gate anti-cross-editoria automatizado | **P1** | Anti-regressão | Nova regra AR-CONTENT-005 |

---

## B1 — `og:image` corrompido (P0)

### Evidência

Em qualquer página em produção (`estrato.cc`, `mente.estrato.cc`, etc.):

```html
<meta property="og:image" content="https://culture.estrato.cc/wp-content/uploads/2026/07/estrato-og-default.pngmeta%20property=og:image:width%20content=280%20/meta%20property=og:image:height%20content=72%20/" />
```

Yoast renderiza o valor bruto do filtro `wpseo_opengraph_image` diretamente dentro do
atributo `content="…"`. Nosso código está retornando `URL + HTML`, deixando o `<meta>`
inválido, o que **impede** compartilhamento correto no Facebook/X/WhatsApp/LinkedIn e
derruba pontos em Rich Results e Google Discover.

### Causa raiz

`estrato-portal-bootstrap/seo-yoast-defaults.php:49-72`:

```php
add_filter( 'wpseo_opengraph_image', 'estrato_yoast_og_dimensions', 99 );

function estrato_yoast_og_dimensions( $tag ) {
    // ...
    return $tag . '<meta property="og:image:width" content="1200" />' . "\n"
                . '<meta property="og:image:height" content="630" />' . "\n";
}
```

O filtro `wpseo_opengraph_image` espera **apenas a URL** da imagem. Yoast já
adiciona `og:image:width` e `og:image:height` automaticamente via
`WPSEO_OpenGraph_Image::add_image_by_url()` quando o attachment tem
`wp_get_attachment_metadata`. A tentativa manual de acrescentar dimensões
corrompeu o `<meta>` inteiro.

### Correção

1. **Remover** `add_filter( 'wpseo_opengraph_image', 'estrato_yoast_og_dimensions', 99 );`
2. **Manter** apenas `estrato_yoast_fallback_og_image` (que devolve a URL de fallback).
3. Alternativa opcional: se ainda quisermos garantir width/height, hookar em `wpseo_add_opengraph_images` para adicionar via API oficial (`WPSEO_OpenGraph_Image::add_image( array( 'url'=>…, 'width'=>1200, 'height'=>630 ) )`), nunca por string concatenation.
4. Trocar `estrato-og-default.png` (280×72) por um asset 1200×630 dedicado.

---

## B2 — Contaminação cruzada de conteúdo (P0)

### Evidência

Contagem de posts publicados cuja fonte RSS (`_estrato_rss_source_url`)
está **fora** da matriz `estrato_rss_import_matrix` do próprio portal:

| Portal | Off-matriz / Total | Exemplos |
|---|---|---|
| Mente | **37 / 56** (66%) | "Mercado do boi gordo" cat=`financas-comportamentais-financas-autonomos` |
| Lifestyle | **37 / 51** (73%) | "Mercado do boi gordo" cat=`hobbies-colecao` |
| Science | **37 / 53** (70%) | "Mercado do boi gordo" cat=`ia-seguranca` |
| Sustain | **37 / 49** (76%) | "Inmetro blockchain carros" cat=`economia-alternativa` |
| Culture | **37 / 45** (82%) | "Mercado do boi gordo" cat=`narrativas-som` |

Os 37 posts são **os mesmos** em todos os satélites — mesmas hosts (Canal Rural,
InfoMoney, Livecoins, PoX Globo, Portal do Bitcoin). O RSS pipeline em algum
snapshot anterior importou o preset `brasil-financeiro` em cada satélite,
categorizando forçadamente na editoria root default de cada portal, produzindo
combinações grotescas ("boi gordo" em "Narrativas & Som", "blockchain carros"
em "IA & Segurança").

### Causa raiz

Duas causas somadas:

1. Um snapshot antigo do plugin RSS não filtrava por matriz do satélite antes
   de publicar; ele lia o preset global e publicava tudo.
2. Não existe hoje um **gate de coerência source × editoria** que impeça
   publicação (o gate AR-CONTENT-003 checa que a categoria não é
   `sem-categoria`, mas aceita qualquer combinação).

### Correção

**Fase 1 — Purga (imediata):**

1. Script `scripts/victor/purge-off-matrix-posts.php` para cada satélite:
   - Iterar `post_status=publish` com `_estrato_rss_source_url`;
   - Se o host da URL não estiver nos feeds da matriz atual → mover para
     `trash` (não deletar; permitir undo em 30 dias).
2. Rodar via `setup-visual-audit-fix-all-portals.sh`.

**Fase 2 — Gate anti-regressão (AR-CONTENT-005):**

1. Adicionar em `estrato-portal-bootstrap/gate-safeguards.php`:
   ```php
   function estrato_gate_source_matches_matrix( $post_id )
   ```
   Bloqueia `publish → trash/draft` quando `parse_url($_estrato_rss_source_url)['host']`
   não está em `estrato_rss_import_matrix[*].feeds[*].url` do portal.
2. Adicionar regra em `portals/estrato-anti-regression.yaml`:
   `AR-CONTENT-005 zero_posts_off_matrix`.
3. Cobrir com `scripts/victor/check-portal-regression-all.sh` (verificar via SQL/WP-CLI).

**Fase 3 — RSS pipeline hardening:**

1. Em `estrato-rss-import`, antes de `wp_insert_post`, comparar o host do feed
   com a `estrato_rss_import_matrix` do site atual; abortar se não bater
   (com log em `estrato_rss_health_last_report`).

---

## B3 — Kickers com `&amp;AMP;` (P0 visual)

### Evidência

Home do Culture (idem 4 outros satélites):

```
JOGOS &amp;AMP; IMAGINAçãO · RPG DE MESA &amp;AMP; OSR
```

Aparece **13–15× por home** em Mente/Lifestyle/Science/Sustain/Culture.

### Causa raiz

`estrato-portal-bootstrap/home-layout.php:58`:

```php
return $sub ? strtoupper( $area . ' · ' . $sub ) : strtoupper( $area );
```

- `$area = $root->name` pode vir com entities já aplicadas (`"Jogos &amp; Imaginação"`);
- `strtoupper()` não é UTF-8 safe → transforma `amp;` em `AMP;` e destrói acentos (`ç`→`ç`, `ã`→`ã`);
- Depois `esc_html( $kicker )` na linha 178 re-encoda `&` em `&amp;`, produzindo `&amp;AMP;`.

### Correção

```php
// home-layout.php
function estrato_home_kicker_text( $post_id ) {
    // ...
    $area = html_entity_decode( $root->name, ENT_QUOTES, 'UTF-8' );
    $sub  = $sub ? html_entity_decode( $deepest->name, ENT_QUOTES, 'UTF-8' ) : '';
    $text = $sub ? $area . ' · ' . $sub : $area;
    return mb_strtoupper( $text, 'UTF-8' );
}
```

E na renderização (linha 178) continua usando `esc_html( $kicker )` — assim
`Jogos & Imaginação` → `JOGOS & IMAGINAÇÃO` → `JOGOS &amp; IMAGINAÇÃO`
(certo, `&amp;` é renderizado como `&` no navegador).

---

## B4 — Kicker/breadcrumb vazio em singles (P1)

### Evidência

`https://culture.estrato.cc/mercado-do-boi-gordo-.../`:

- CSS crítico injeta `.pg-single-header,.pg-single-meta{display:none!important}`;
- Nosso template `single-article.php` renderiza `<p class="estrato-kicker">…</p>`, mas o valor calculado sai vazio para posts órfãos (categoria root sem sub).
- Ausência total de breadcrumb no single.

### Correção

1. Em `single-article.php`, se `estrato_ds_post_kicker_text()` estiver vazia:
   fallback para nome do site (`Estrato Culture`) ou "Editorial".
2. Injetar `<nav aria-label="Breadcrumb">` com Home → Editoria → Post no
   `estrato-portal-bootstrap/single-breadcrumb.php` (novo módulo);
3. Já existe schema BreadcrumbList (Yoast) — só refletir visualmente.

---

## B5 — Categorias com prefixo `/category/` obrigatório (P1)

### Evidência

- `https://culture.estrato.cc/narrativas-som/` → **404**
- `https://culture.estrato.cc/category/narrativas-som/` → 200

Menu principal aponta corretamente para `/category/…`, mas humanos digitando ou
IA linkando naturalmente vão pro slug curto.

### Correção

Duas opções:

1. **A (preferida):** set `category_base = ''` via `wp option update` e regenerar rewrite rules em todos os portais.
2. **B:** Manter `/category/` e adicionar redirect 301 de `/{slug}/` para `/category/{slug}/` para os slugs raiz da matriz do portal (mais defensivo, evita colisão com posts que usarem o slug).

---

## B6 — Subcategoria com slug pai duplicado (P2)

### Evidência

Sub-categorias criadas pelo bootstrap têm slug `jogos-imaginacao-rpg-mesa-osr`
sob pai `jogos-imaginacao` → URL `/.../jogos-imaginacao/jogos-imaginacao-rpg-mesa-osr/`.

### Correção

Rodar migração de slugs no plugin bootstrap: se `child->slug` começar com
`parent->slug . '-'`, cortar prefixo.

```sql
UPDATE wp_terms t
JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
JOIN wp_term_taxonomy p ON p.term_id = tt.parent
JOIN wp_terms pt ON pt.term_id = p.term_id
SET t.slug = SUBSTRING(t.slug, LENGTH(pt.slug) + 2)
WHERE t.slug LIKE CONCAT(pt.slug, '-%') AND tt.taxonomy = 'category';
```

Rodar redirect 301 dos slugs antigos para os novos.

---

## B7 — `aria-label` com `&amp;` cru (P2)

Home HTML mostra `aria-label="Jogos &amp; Imaginação"`. Ferramentas de
acessibilidade e leitores de tela leem "Jogos amp Imaginação". Corrigir em
`home-layout.php` usando `esc_attr( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ) )`.

---

## B8 — 14 blocos `<style>` inline (P2)

Cada home devolve 14 `<style>` diferentes no `<head>`, causando parsing
extra e dificultando manutenção. Consolidar em 1 bloco via
`estrato_ds_critical_css()` + `perf_critical_home_css()`.

Meta: ≤ 3 blocos de CSS crítico total.

---

## B9 — OG default de tamanho errado (P2)

`estrato-og-default.png` é **280×72** — muito abaixo do mínimo Facebook
(600×315) e do recomendado (1200×630). Precisa asset novo 1200×630.

---

## B10 — 1792 rascunhos acumulados em finance (P3)

Higienização mensal via `scripts/victor/setup-drafts-hygiene.sh`:

- Rascunhos > 30 dias → `trash`;
- Trash > 60 dias → deletar.

---

## B11 — Gate AR-CONTENT-005 (source×editoria) (P1)

Ver §B2 Fase 2.

---

## Roteiro de execução

**Ordem sugerida** (autonoma, sem intervenção manual):

1. **Sprint V1 (bugs P0 código)**: B1 + B3 (correções PHP em `seo-yoast-defaults.php` e `home-layout.php`).
2. **Sprint V2 (purga)**: B2 fase 1 — `purge-off-matrix-posts.php` em cada satélite.
3. **Sprint V3 (gate)**: B2 fase 2 + B11 — AR-CONTENT-005 no `gate-safeguards.php` + regra no `estrato-anti-regression.yaml`.
4. **Sprint V4 (RSS hardening)**: B2 fase 3 — filtro na pipeline RSS antes do insert.
5. **Sprint V5 (single UX)**: B4 — breadcrumb + kicker fallback.
6. **Sprint V6 (URLs)**: B5 (rewrite) + B6 (slugs).
7. **Sprint V7 (assets/CSS)**: B7 + B8 + B9.
8. **Sprint V8 (hygiene)**: B10.

Cada sprint entrega commits atômicos + PRs curtos, sincroniza em Victor
via `setup-visual-audit-fix-all-portals.sh` (novo), roda
`check-portal-regression-all.sh` e `audit-e2e-production.sh` como gate.

## Métricas de sucesso

- 0 kickers `&AMP;` em qualquer home;
- 0 posts com `og:image` contendo `meta%20property=`;
- 0 posts publicados fora da matriz de RSS (por portal);
- `check-portal-regression-all.sh` passa AR-CONTENT-005 nos 6 portais;
- Rich Results (Google) valida OG image em amostra de 3 URLs por portal.
