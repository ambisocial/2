import axios from 'axios';
import fs from 'fs';
import path from 'path';
import * as tus from 'tus-js-client';

const TOKEN = process.env.HOSTINGER_API_TOKEN;
const BASE = 'https://developers.hostinger.com';
const USERNAME = 'u343929971';
const DOMAIN = 'estrato.cc';
const SOFTWARE_ID = '29398598';

const uploads = [
  { local: '/workspace/Jannah-wp-theme/jannah.zip', remote: 'wp-content/themes/jannah.zip' },
  { local: '/workspace/Jannah-wp-theme/jannah-child.zip', remote: 'wp-content/themes/jannah-child.zip' },
];

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  'Content-Type': 'application/json',
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function fetchCreds() {
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/files/upload-urls`,
    { username: USERNAME, domain: DOMAIN },
    { headers, timeout: 60000 }
  );
  return data;
}

function uploadOnce(local, remote, creds) {
  return new Promise((resolve, reject) => {
    const stats = fs.statSync(local);
    const stream = fs.createReadStream(local);
    const url = `${creds.url.replace(/\/$/, '')}/${remote}?override=true`;
    const h = {
      'X-Auth': creds.auth_key,
      'X-Auth-Rest': creds.rest_auth_key,
      'upload-length': String(stats.size),
      'upload-offset': '0',
    };
    axios
      .post(url, '', { headers: h, validateStatus: (s) => s === 201, timeout: 180000 })
      .then(() => {
        const up = new tus.Upload(stream, {
          uploadUrl: url,
          headers: h,
          uploadSize: stats.size,
          chunkSize: 1048576,
          retryDelays: [2000, 5000, 10000, 20000, 30000],
          onProgress: (sent, total) => {
            const pct = ((sent / total) * 100).toFixed(1);
            if (sent === total || Math.floor(pct) % 10 === 0) console.log(`${path.basename(local)}: ${pct}%`);
          },
          onSuccess: resolve,
          onError: reject,
        });
        up.start();
      })
      .catch(reject);
  });
}

async function uploadWithRetries(local, remote) {
  let last;
  for (let i = 1; i <= 8; i++) {
    try {
      console.log(`Upload ${path.basename(local)} attempt ${i}/8`);
      const creds = await fetchCreds();
      await uploadOnce(local, remote, creds);
      console.log(`OK ${remote}`);
      return;
    } catch (e) {
      last = e;
      console.error(`Attempt ${i} failed: ${e.message}`);
      await sleep(i * 4000);
    }
  }
  throw last;
}

function scan(dir, base = dir) {
  const out = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const abs = path.join(dir, e.name);
    if (e.isDirectory()) out.push(...scan(abs, base));
    else out.push({ abs, rel: path.relative(base, abs).split(path.sep).join('/') });
  }
  return out;
}

async function deployHelper() {
  const PLUGIN_DIR = '/workspace/unzip-jannah-helper';
  const SLUG = 'unzip-jannah-helper';
  const suffix = Math.random().toString(36).slice(2, 10);
  const uploadDir = `${SLUG}-${suffix}`;
  const files = scan(PLUGIN_DIR);
  let creds = await fetchCreds();
  for (const f of files) {
    const remote = `wp-content/plugins/${uploadDir}/${f.rel}`;
    for (let i = 0; i < 5; i++) {
      try {
        if (i) creds = await fetchCreds();
        await uploadOnce(f.abs, remote, creds);
        break;
      } catch (e) {
        if (i === 4) throw e;
        await sleep(3000);
      }
    }
  }
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/wordpress/plugins/deploy`,
    { slug: SLUG, plugin_path: uploadDir, is_activated: false },
    { headers, timeout: 120000 }
  );
  console.log('Helper deploy:', JSON.stringify(data));
  await sleep(8000);
  const { data: act } = await axios.post(
    `${BASE}/api/hosting/v1/accounts/${USERNAME}/wordpress/${SOFTWARE_ID}/plugins/activate`,
    { slug: SLUG },
    { headers, timeout: 300000 }
  );
  console.log('Helper activate:', JSON.stringify(act));
}

async function main() {
  for (const item of uploads) {
    await uploadWithRetries(item.local, item.remote);
  }
  await deployHelper();
}

main().catch((e) => {
  console.error('FAILED:', e.message);
  process.exit(1);
});
