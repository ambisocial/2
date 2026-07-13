import axios from 'axios';
import fs from 'fs';
import path from 'path';
import * as tus from 'tus-js-client';

const TOKEN = process.env.HOSTINGER_API_TOKEN;
const BASE = 'https://developers.hostinger.com';
const USERNAME = 'u343929971';
const DOMAIN = 'estrato.cc';
const SLUG = 'jannah-child';
const THEME_PATH = '/workspace/Jannah-wp-theme/jannah-child/jannah-child';
const SOFTWARE_ID = '29398598';

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

function scan(dir, base = dir) {
  const out = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const abs = path.join(dir, e.name);
    if (e.isDirectory()) out.push(...scan(abs, base));
    else out.push({ abs, rel: path.relative(base, abs).split(path.sep).join('/') });
  }
  return out;
}

async function fetchCreds() {
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/files/upload-urls`,
    { username: USERNAME, domain: DOMAIN },
    { headers, timeout: 60000 }
  );
  return data;
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
          retryDelays: [1000, 3000, 8000],
          onSuccess: resolve,
          onError: reject,
        });
        up.start();
      })
      .catch(reject);
  });
}

async function main() {
  const suffix = Math.random().toString(36).slice(2, 10);
  const uploadDir = `${SLUG}-${suffix}`;
  const files = scan(THEME_PATH);
  let creds = await fetchCreds();
  for (const f of files) {
    const remote = `wp-content/themes/${uploadDir}/${f.rel}`;
    for (let i = 0; i < 5; i++) {
      try {
        if (i) creds = await fetchCreds();
        await uploadOnce(f.abs, remote, creds);
        console.log('OK', f.rel);
        break;
      } catch (e) {
        if (i === 4) throw e;
      }
    }
  }
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/wordpress/themes/deploy`,
    { slug: SLUG, theme_path: uploadDir, is_activated: false },
    { headers, timeout: 120000 }
  );
  console.log('Deploy child:', JSON.stringify(data));
  const { data: act } = await axios.post(
    `${BASE}/api/hosting/v1/accounts/${USERNAME}/wordpress/${SOFTWARE_ID}/themes/activate`,
    { slug: SLUG },
    { headers, timeout: 120000 }
  );
  console.log('Activate child:', JSON.stringify(act));
}

main().catch((e) => {
  console.error(e.message);
  process.exit(1);
});
