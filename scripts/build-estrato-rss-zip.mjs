import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

const root = '/workspace';
const pluginDir = path.join(root, 'estrato-rss-bootstrap');
const zipPath = path.join(root, 'estrato-rss-bootstrap.zip');

if (!fs.existsSync(pluginDir)) {
  console.error('Missing plugin directory:', pluginDir);
  process.exit(1);
}

if (fs.existsSync(zipPath)) {
  fs.unlinkSync(zipPath);
}

execSync(`zip -r "${zipPath}" estrato-rss-bootstrap`, { cwd: root, stdio: 'inherit' });
const size = fs.statSync(zipPath).size;
console.log(`Built ${zipPath} (${size} bytes)`);
