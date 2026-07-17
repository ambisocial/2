# Plano executável — SEO / AEO / EEAT / GEO / YMYL → 9/10

Base pós-1.36.2 (~8.4–8.5). Cada item tem comando/verificação mensurável.

## Meta por pilar

| Pilar | Agora | Alvo | Bloqueio principal |
|-------|-------|------|--------------------|
| GEO | ~9.0 | 9.0 | Manter Allow em todos os satélites |
| AEO | ~8.5 | 9.0 | FAQ mais contextual + llms-full útil |
| SEO | ~8.5 | 9.0 | Titles/CTR, aliases, inventário fino |
| YMYL | ~8.0 | 9.0 | Páginas aviso + revisão real |
| EEAT | ~8.2 | 9.0 | Credenciais verificáveis + consistência |

---

## Fase A — Estabilizar (já feito / validar)

1. Plugin `1.36.2` em todos os docroots.
2. `ESTRATO_SKIP_PORTRAITS=1 ESTRATO_SKIP_NETWORK_BLOGS=1 bash scripts/victor/setup-seo-eeat-hardening-all.sh`
3. Gates: `scripts/victor/check-portal-regression-ci.sh` (AR-SEO-*).
4. Live checks:
   - `curl -s https://mente.estrato.cc/robots.txt | grep -A1 GPTBot` → `Allow: /`
   - `curl -sI https://estrato.cc/ciencia/` → `301` → `/science/`
   - `wp eval` linkedin metas = 0; top authors = staff.

## Fase B — EEAT → 9

1. **Retratos restantes** (satélites sem avatar):
   ```bash
   for root in /var/www/*.estrato.cc; do
     env -u ESTRATO_SKIP_PORTRAITS wp --allow-root --path="$root" \
       eval 'echo wp_json_encode(estrato_staff_backfill_portraits(false));'
   done
   ```
2. **Bios longas** (200–400 palavras) com escopo, critérios de fonte e link para metodologia — editar `estrato_staff_build_bio` ou páginas `/blog/{slug}/`.
3. **sameAs real** só com URLs verificáveis (perfil Mastodon/LinkedIn da pessoa, ORCID se houver). Nunca fabricar.
4. **byline UI**: foto + cargo + link blog em single (confirmar `single-article.php` / tema).
5. **Corrigir portal_id de sustain** (`estrato-agro` indevido) no catálogo/nav — evita roster errado.

## Fase C — YMYL → 9

1. Criar páginas `aviso-medico` / `aviso-financeiro` em `estrato_ymyl_ensure_institutional_pages()` e linkar no box.
2. `reviewedBy` só quando editor-chefe de fato revisou (meta `estrato_reviewed_by` + data), não em 100% automático cego.
3. Log de correções público em `/correcoes/` (lista das últimas N).
4. Distinguir disclaimers por vertical (saúde ≠ finanças ≠ geral) — já parcial.

## Fase D — AEO / GEO → 9+

1. Regenerar `llms-full.txt` com top 50 URLs + resumos (não só stub).
2. FAQ por post: 3–5 Qs geradas do H2 do artigo (não só templates).
3. Monitorar robots trimestralmente; alerta CI se `Disallow: /` voltar em bots de IA.
4. IndexNow + ping news-sitemap após publish (já há hooks — auditar taxa de sucesso).

## Fase E — SEO clássico → 9

1. Titles path hubs já enriquecidos; espelhar em OG/Twitter e nav.
2. Completar aliases (`/negocios` ok) + Search Console coverage.
3. Inventário fino (Esporte etc.): cota mínima de posts/semana + entity hubs com ≥3 FAQs reais.
4. Core Web Vitals: LCP hero, CLS fontes (já há self-host) — medir PSI mobile top 5 URLs.
5. Internal links: home → path hubs → entity → posts (3 cliques máx.).

## Fase F — Governança

1. Gate CI live (cron Victor) que falha se GEO/EEAT/YMYL regredir.
2. Não reexecutar `setup-sprint-e1` antigo sem o patch de sameAs interno.
3. Rotacionar chave SSH exposta e restringir deploy a script idempotente.

## Ordem sugerida de execução

1. B1 retratos satélites + B5 sustain portal_id  
2. C1 avisos YMYL  
3. D1–D2 llms-full + FAQ contextual  
4. E3 inventário fino + E4 CWV  
5. B2–B3 credenciais reais (humano)

**Definição de pronto 9/10:** checklist live 100% verde em GEO Allow, 0 LinkedIn fake, ≥90% posts com staff+retrato, path aliases 301, avisos YMYL 200, FAQPage em ≥95% posts, PSI mobile ≥80 nas 5 URLs piloto.
