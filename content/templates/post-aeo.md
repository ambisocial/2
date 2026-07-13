# Template AEO — Sprint 4 (writer / pipeline)

Use este esqueleto ao formatar matérias do portal Estrato. Meta: **mínimo 300 palavras** publicadas.

## Estrutura obrigatória

1. **Lead** — 1 parágrafo forte (40–60 palavras) com o fato principal.
2. **O que você precisa saber** — resumo em 2–3 frases (bloco `estrato-aeo-resumo`).
3. **Contexto de mercado** — 2 parágrafos ligando o fato à macro/categoria.
4. **Corpo** — detalhes, dados, citações de fonte.
5. **Perguntas frequentes** — 3 pares pergunta/resposta curtos.
6. **Leia também** — 3 links internos (mesma categoria + matérias relacionadas).

## Entidades a menciar quando relevante

- Instituições: Banco Central, B3, CVM, IBGE, Tesouro Nacional
- Indicadores: Selic, IPCA, Ibovespa, dólar, PIB
- Setores: agronegócio, bancos, varejo, energia, cripto

## Regras de qualidade

| Regra | Ação |
|-------|------|
| &lt; 200 palavras | **Não publicar** — manter rascunho |
| 200–299 palavras | Publicar com `noindex` até reescrita |
| ≥ 300 palavras | Publicar normalmente |
| Sem imagem | Bridge busca og:image da fonte; fallback OG default |

## Exemplo de FAQ

```html
<h2>Perguntas frequentes</h2>
<h3>Por que este tema importa agora?</h3>
<p>…</p>
<h3>Quem deve acompanhar?</h3>
<p>…</p>
<h3>Como o Estrato cobre?</h3>
<p>…</p>
```

## Modo análise (longform)

1x por semana/categoria: mínimo **1500 palavras**, seções H2 adicionais:

- Panorama
- Dados e evidências
- Riscos e oportunidades
- O que monitorar nos próximos dias
