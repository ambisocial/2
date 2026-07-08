import axios from 'axios';
import fs from 'fs';
import path from 'path';
import * as tus from 'tus-js-client';

const TOKEN = process.env.HOSTINGER_API_TOKEN;
const BASE = 'https://developers.hostinger.com';
const USERNAME = 'u343929971';
const DOMAIN = 'estrato.cc';
const SLUG = 'jannah';
const THEME_PATH = '/workspace/Jannah-wp-theme/jannah/jannah';
const ACTIVATE = true;
const FILE_RETRIES = 5;
const SKIP = new Set(['.DS_Store']);

if (!TOKEN) process.exit(1);

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function scanDirectory(dirPath, basePath = dirPath) {
  const files = [];
  for (const entry of fs.readdirSync(dirPath, { withFileTypes: true })) {
    if (SKIP.has(entry.name)) continue;
    const absolutePath = path.join(dirPath, entry.name);
    if (entry.isDirectory()) files.push(...scanDirectory(absolutePath, basePath));
    else if (entry.isFile()) {
      files.push({
        absolutePath,
        relativePath: path.relative(basePath, absolutePath).split(path.sep).join('/'),
      });
    }
  }
  return files;
}

async function fetchUploadCredentials() {
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/files/upload-urls`,
    { username: USERNAME, domain: DOMAIN },
    { headers, timeout: 60000 }
  );
  return data;
}

function uploadFileOnce(filePath, relativePath, uploadUrl, authToken, authRestToken) {
  return new Promise((resolve, reject) => {
    const stats = fs.statSync(filePath);
    const fileStream = fs.createReadStream(filePath);
    const cleanUploadUrl = uploadUrl.replace(/\/$/, '');
    const uploadUrlWithFile = `${cleanUploadUrl}/${relativePath}?override=true`;
    const requestHeaders = {
      'X-Auth': authToken,
      'X-Auth-Rest': authRestToken,
      'upload-length': stats.size.toString(),
      'upload-offset': '0',
    };

    axios
      .post(uploadUrlWithFile, '', {
        headers: requestHeaders,
        timeout: 120000,
        validateStatus: (status) => status === 201,
      })
      .then(() => {
        const upload = new tus.Upload(fileStream, {
          uploadUrl: uploadUrlWithFile,
          retryDelays: [1000, 2000, 5000, 10000, 20000],
          uploadDataDuringCreation: false,
          parallelUploads: 1,
          chunkSize: Math.min(1048576, stats.size || 1),
          headers: requestHeaders,
          removeFingerprintOnSuccess: true,
          uploadSize: stats.size,
          metadata: { filename: path.basename(relativePath) },
          onError: reject,
          onSuccess: () => resolve(uploadUrlWithFile),
        });
        upload.start();
      })
      .catch(reject);
  });
}

async function uploadWithRetries(file, creds, remotePath) {
  let lastError;
  for (let attempt = 1; attempt <= FILE_RETRIES; attempt++) {
    try {
      if (attempt > 1) {
        creds = await fetchUploadCredentials();
        await sleep(attempt * 1000);
      }
      return await uploadFileOnce(
        file.absolutePath,
        remotePath,
        creds.url,
        creds.auth_key,
        creds.rest_auth_key
      );
    } catch (error) {
      lastError = error;
    }
  }
  throw lastError;
}

async function deployTheme(uploadDirName) {
  const { data } = await axios.post(
    `${BASE}/api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/wordpress/themes/deploy`,
    { slug: SLUG, theme_path: uploadDirName, is_activated: ACTIVATE },
    { headers, timeout: 120000 }
  );
  return data;
}

async function main() {
  const randomSuffix = Math.random().toString(36).slice(2, 10);
  const uploadDirName = `${SLUG}-${randomSuffix}`;
  const files = scanDirectory(THEME_PATH);
  let creds = await fetchUploadCredentials();
  let ok = 0;
  let fail = 0;
  const failed = [];

  console.log(`Uploading ${files.length} files -> wp-content/themes/${uploadDirName}/`);

  for (const file of files) {
    const remotePath = `wp-content/themes/${uploadDirName}/${file.relativePath}`;
    try {
      await uploadWithRetries(file, creds, remotePath);
      ok += 1;
      if (ok % 20 === 0) console.log(`Progress: ${ok}/${files.length}`);
    } catch (error) {
      fail += 1;
      failed.push({ path: remotePath, error: error.message });
      console.error(`FAILED ${file.relativePath}: ${error.message}`);
    }
  }

  console.log(`Upload done success=${ok} failed=${fail}`);
  if (fail > 0) {
    fs.writeFileSync('/workspace/jannah-failed.json', JSON.stringify(failed, null, 2));
  }

  if (fail === 0) {
    const result = await deployTheme(uploadDirName);
    console.log('Deploy:', JSON.stringify(result));
  } else if (ok > files.length * 0.95) {
    console.log('Trying deploy despite minor failures...');
    const result = await deployTheme(uploadDirName);
    console.log('Deploy:', JSON.stringify(result));
  } else {
    process.exit(1);
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
