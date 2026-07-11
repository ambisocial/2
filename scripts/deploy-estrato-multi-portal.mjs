/**
 * Deploy multi-portal Estrato via Hostinger API (estrato.cc + assets).
 *
 * Boas práticas portais multi-categoria:
 *  1. Plugins canônicos (portal + rss + bridge)
 *  2. Taxonomia PHP em wp-content/estrato-portals/
 *  3. Purge cache + ping para estrato_rss_maybe_upgrade
 *
 * Uso:
 *   PORTAL=estrato-finance node scripts/deploy-estrato-multi-portal.mjs
 *   PORTAL=estrato-mind   DOMAIN=mente.estrato.cc node scripts/deploy-estrato-multi-portal.mjs
 *   PORTAL=estrato-science DOMAIN=science.estrato.cc node scripts/deploy-estrato-multi-portal.mjs
 *   PORTAL=estrato-sustain DOMAIN=sustain.estrato.cc node scripts/deploy-estrato-multi-portal.mjs
 */
import axios from 'axios';
import fs from 'fs';
import path from 'path';
import * as tus from 'tus-js-client';

const TOKEN = process.env.HOSTINGER_API_TOKEN;
const BASE = 'https://developers.hostinger.com';
const USERNAME = 'u343929971';
const DOMAIN = process.env.DOMAIN || 'estrato.cc';
const PORTAL = process.env.PORTAL || 'estrato-finance';
const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');

const PLUGINS = [
  'estrato-rss-bootstrap',
  'estrato-portal-bootstrap',
  'estrato-publisher-bridge',
];

async function api(method, urlPath, data) {
  const { data: res } = await axios({
    method,
    url: `${BASE}${urlPath}`,
    headers: { Authorization: `Bearer ${TOKEN}`, 'Content-Type': 'application/json' },
    data,
    timeout: 120000,
    validateStatus: () => true,
  });
  return res;
}

async function fetchCreds() {
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/files/upload-urls`,
    { username: USERNAME, domain: DOMAIN },
    { headers: { Authorization: `Bearer ${TOKEN}`, 'Content-Type': 'application/json' } }
  );
  return data;
}

function scan(dir, base = dir) {
  const out = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    if (e.name === 'node_modules' || e.name === '.git') continue;
    const abs = path.join(dir, e.name);
    if (e.isDirectory()) out.push(...scan(abs, base));
    else out.push({ abs, rel: path.relative(base, abs).split(path.sep).join('/') });
  }
  return out;
}

function uploadOnce(file, remote, creds) {
  return new Promise((resolve, reject) => {
    const stats = fs.statSync(file);
    const stream = fs.createReadStream(file);
    const url = `${creds.url.replace(/\/$/, '')}/${remote}?override=true`;
    const h = {
      'X-Auth': creds.auth_key,
      'X-Auth-Rest': creds.rest_auth_key,
      'upload-length': String(stats.size),
      'upload-offset': '0',
    };
    axios
      .post(url, '', { headers: h, validateStatus: (s) => s === 201, timeout: 120000 })
      .then(() => {
        const up = new tus.Upload(stream, {
          uploadUrl: url,
          headers: h,
          uploadSize: stats.size,
          chunkSize: Math.max(512, stats.size),
          retryDelays: [1000, 3000, 8000, 15000],
          onSuccess: resolve,
          onError: reject,
        });
        up.start();
      })
      .catch(reject);
  });
}

async function uploadFiles(files, remotePrefix) {
  let creds = await fetchCreds();
  let ok = 0;
  for (const f of files) {
    const remote = `${remotePrefix}/${f.rel}`;
    for (let i = 0; i < 6; i++) {
      try {
        if (i) creds = await fetchCreds();
        await uploadOnce(f.abs, remote, creds);
        ok++;
        console.log('OK', remote);
        break;
      } catch (e) {
        if (i === 5) throw new Error(`${f.rel}: ${e.message}`);
        await new Promise((r) => setTimeout(r, 2000 * (i + 1)));
      }
    }
  }
  return ok;
}

async function deployPlugin(slug, uploadDir) {
  const res = await api('POST', `/api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/wordpress/plugins/deploy`, {
    slug,
    plugin_path: uploadDir,
    is_activated: true,
  });
  console.log(`Deploy ${slug}:`, JSON.stringify(res));
  return res;
}

async function uploadPluginInPlace(slug) {
  const src = path.join(ROOT, slug);
  const files = scan(src);
  console.log(`In-place upload ${slug} (${files.length} files)...`);
  await uploadFiles(files, `wp-content/plugins/${slug}`);
}

async function clearCache() {
  const res = await api('DELETE', `/api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/cache/clear`);
  console.log('Cache clear:', JSON.stringify(res));
}

async function pingUpgrade() {
  const urls = [`https://${DOMAIN}/`, `https://${DOMAIN}/wp-json/estrato/v1/health`];
  for (const u of urls) {
    try {
      const { status, data } = await axios.get(u, { timeout: 30000, validateStatus: () => true });
      console.log('Ping', u, status, typeof data === 'object' ? JSON.stringify(data) : String(data).slice(0, 80));
    } catch (e) {
      console.log('Ping fail', u, e.message);
    }
  }
}

async function main() {
  if (!TOKEN) throw new Error('HOSTINGER_API_TOKEN ausente');

  console.log(`=== Deploy ${PORTAL} → ${DOMAIN} ===`);

  for (const slug of PLUGINS) {
    const src = path.join(ROOT, slug);
    if (!fs.existsSync(src)) throw new Error(`Plugin ausente: ${src}`);
    await uploadPluginInPlace(slug);
    const suffix = Math.random().toString(36).slice(2, 10);
    const uploadDir = `${slug}-${suffix}`;
    try {
      await deployPlugin(slug, uploadDir);
    } catch (e) {
      console.log(`Deploy API ${slug} skipped:`, e.message);
    }
  }

  const portalFiles = [];
  const portalsDir = path.join(ROOT, 'portals');
  for (const name of fs.readdirSync(portalsDir)) {
    if (name.endsWith('.php') || name.endsWith('.yaml') || name.endsWith('.json')) {
      portalFiles.push({
        abs: path.join(portalsDir, name),
        rel: name,
      });
    }
  }
  console.log(`Uploading taxonomia (${portalFiles.length} arquivos)...`);
  await uploadFiles(portalFiles, 'wp-content/estrato-portals');

  await clearCache();
  await new Promise((r) => setTimeout(r, 5000));
  await pingUpgrade();

  console.log('=== Deploy concluído ===');
  console.log(`Portal config alvo: ${PORTAL}`);
  console.log('Próximo: aplicar preset via WP-CLI ou endpoint /estrato/v1/taxonomy-sync');
}

main().catch((e) => {
  console.error('FAILED:', e.message);
  process.exit(1);
});
