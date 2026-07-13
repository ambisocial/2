import axios from 'axios';
import fs from 'fs';
import path from 'path';
import * as tus from 'tus-js-client';

const TOKEN = process.env.HOSTINGER_API_TOKEN;
const BASE = 'https://developers.hostinger.com';
const USERNAME = 'u343929971';
const DOMAIN = 'estrato.cc';
const LOCAL_ZIP = '/workspace/Jannah-wp-theme/jannah.zip';
const REMOTE_ZIP = 'wp-content/themes/jannah.zip';
const MAX_ATTEMPTS = 8;

if (!TOKEN) process.exit(1);

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

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
          retryDelays: [2000, 5000, 10000, 20000, 30000, 45000],
          uploadDataDuringCreation: false,
          parallelUploads: 1,
          chunkSize: 1048576,
          headers: requestHeaders,
          removeFingerprintOnSuccess: true,
          uploadSize: stats.size,
          metadata: { filename: path.basename(relativePath) },
          onError: reject,
          onSuccess: () => resolve(uploadUrlWithFile),
          onProgress: (sent, total) => {
            if (sent === total || sent % (2 * 1048576) < 1048576) {
              console.log(`Progress: ${((sent / total) * 100).toFixed(1)}%`);
            }
          },
        });
        upload.start();
      })
      .catch(reject);
  });
}

async function uploadWithRetries() {
  let lastError;
  for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
    try {
      console.log(`Attempt ${attempt}/${MAX_ATTEMPTS}`);
      const creds = await fetchUploadCredentials();
      const result = await uploadFileOnce(
        LOCAL_ZIP,
        REMOTE_ZIP,
        creds.url,
        creds.auth_key,
        creds.rest_auth_key
      );
      return result;
    } catch (error) {
      lastError = error;
      console.error(`Attempt ${attempt} failed: ${error.message}`);
      await sleep(attempt * 3000);
    }
  }
  throw lastError;
}

uploadWithRetries()
  .then((result) => {
    console.log('Upload OK:', result);
  })
  .catch((error) => {
    console.error('All attempts failed:', error.message);
    process.exit(1);
  });
