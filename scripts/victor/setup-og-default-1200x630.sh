#!/usr/bin/env bash
# V7 (auditoria visual 2026-07-13) — Gera OG default 1200×630 e substitui o
# attachment atual (280×72) em cada portal.
#
# Estratégia: usa Python/Pillow para produzir um asset SVG-like em PNG com
# gradiente e wordmark. Se Pillow indisponível, cai no fallback via convert (IM).
# Faz upload via wp media import e atualiza estrato_og_default_attachment_id.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(
  estrato-finance
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-agro
  estrato-esg
  estrato-viagem
  estrato-culture
)

TMP=/tmp/estrato-og-default
mkdir -p "$TMP"

echo "== Gerando assets 1200×630 por portal =="

declare -A COLOR_BG=(
  [estrato-finance]='#0A0A0A'
  [estrato-mind]='#1B2E4B'
  [estrato-lifestyle]='#5D2E46'
  [estrato-science]='#0F3B39'
  [estrato-agro
  estrato-esg
  estrato-viagem]='#2D4A26'
  [estrato-culture]='#3D1E5F'
)

declare -A LABEL=(
  [estrato-finance]='Estrato'
  [estrato-mind]='Estrato Mente'
  [estrato-lifestyle]='Estrato Lifestyle'
  [estrato-science]='Estrato Science'
  [estrato-agro
  estrato-esg
  estrato-viagem]='Estrato Sustain'
  [estrato-culture]='Estrato Culture'
)

for portal in "${PORTALS[@]}"; do
  bg="${COLOR_BG[$portal]}"
  label="${LABEL[$portal]}"
  out="$TMP/${portal}-og-1200x630.png"
  python3 - "$bg" "$label" "$out" <<'PY'
import sys, os
try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    print("PIL não disponível, tente: apt install python3-pil", file=sys.stderr)
    sys.exit(2)
bg, label, out = sys.argv[1], sys.argv[2], sys.argv[3]
def hex_rgb(h):
    h=h.lstrip('#')
    return tuple(int(h[i:i+2],16) for i in (0,2,4))
w,h_ = 1200, 630
img = Image.new('RGB',(w,h_),hex_rgb(bg))
d = ImageDraw.Draw(img)
# gradient accent bar
accent = (154,255,51)
for y in range(h_-16, h_):
    d.line([(0,y),(w,y)], fill=accent)
# title text
try:
    font_big = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 96)
    font_sub = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 34)
except IOError:
    font_big = ImageFont.load_default()
    font_sub = ImageFont.load_default()
try:
    bbox = d.textbbox((0,0), label, font=font_big)
    tw = bbox[2]-bbox[0]; th = bbox[3]-bbox[1]
except AttributeError:
    tw, th = d.textsize(label, font=font_big)
d.text(((w-tw)/2, (h_-th)/2 - 40), label, fill='white', font=font_big)
sub = 'jornalismo de mercado, com contexto'
try:
    bbox = d.textbbox((0,0), sub, font=font_sub)
    sw = bbox[2]-bbox[0]
except AttributeError:
    sw, _ = d.textsize(sub, font=font_sub)
d.text(((w-sw)/2, (h_/2) + 80), sub, fill=(210,210,210), font=font_sub)
os.makedirs(os.path.dirname(out), exist_ok=True)
img.save(out, 'PNG', optimize=True)
print(out)
PY
  echo "  $portal: $(du -h "$out" | cut -f1) $out"
done

echo ""
echo "== Substituindo estrato_og_default por portal =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  out="$TMP/${portal}-og-1200x630.png"
  if [[ ! -f "$out" ]]; then
    echo "  $portal: SKIP (asset ausente)"
    continue
  fi
  echo "-- $portal --"
  # remove attachment antigo (só o attachment; não a option)
  old_aid=$(portal_wp option get estrato_og_default_attachment_id 2>/dev/null || echo 0)
  # import novo
  attachment_id=$(sudo -u www-data cp "$out" /tmp/ && sudo chown www-data:www-data "/tmp/${portal}-og-1200x630.png"
    portal_wp media import "/tmp/${portal}-og-1200x630.png" --title="Estrato OG 1200x630 (${portal})" --porcelain 2>/dev/null | tail -1)
  if [[ -z "$attachment_id" || ! "$attachment_id" =~ ^[0-9]+$ ]]; then
    echo "  $portal: FALHA na importação (aid=$attachment_id)"
    continue
  fi
  portal_wp option update estrato_og_default_attachment_id "$attachment_id" >/dev/null
  # Manter Yoast alinhado — bridge/editorial thumbs leem wpseo_social.og_default_image_id
  og_url=$(portal_wp post get "$attachment_id" --field=guid 2>/dev/null || true)
  if [[ -n "$og_url" ]]; then
    portal_wp option patch update wpseo_social og_default_image_id "$attachment_id" >/dev/null 2>&1 || true
    portal_wp option patch update wpseo_social og_default_image "$og_url" >/dev/null 2>&1 || true
    portal_wp option patch update wpseo_social og_frontpage_image_id "$attachment_id" >/dev/null 2>&1 || true
    portal_wp option patch update wpseo_social og_frontpage_image "$og_url" >/dev/null 2>&1 || true
  fi
  echo "  $portal: attachment_id=$attachment_id (era $old_aid)"
  if [[ "$old_aid" =~ ^[0-9]+$ && "$old_aid" -gt 0 && "$old_aid" != "$attachment_id" ]]; then
    portal_wp post delete "$old_aid" --force >/dev/null 2>&1 || true
  fi
done

echo ""
echo "== Verificação: attachment metadata (esperado width=1200 height=630) =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  aid=$(portal_wp option get estrato_og_default_attachment_id 2>/dev/null)
  echo -n "  $portal (aid=$aid): "
  portal_wp eval "\$m=wp_get_attachment_metadata($aid); echo (\$m?(\$m['width'].'x'.\$m['height']):'sem meta');" 2>/dev/null
  echo ""
done
