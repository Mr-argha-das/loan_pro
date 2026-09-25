#!/usr/bin/env bash
# Installs the headless Chromium used by the QA harnesses (no apt, no
# storage.googleapis.com: the browser ships inside an npm package).
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"
[[ -d node_modules/puppeteer-core ]] || npm install --no-audit --no-fund puppeteer-core @sparticuz/chromium >/dev/null
node - <<'JS'
import { brotliDecompressSync } from 'node:zlib';
import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
import chromium from '@sparticuz/chromium';

// The Chromium binary ships inside the npm package; the shared libraries it
// links against (libnss3, libnspr4, ...) come as a separate brotli tarball.
const binary = await chromium.executablePath();
const libs = resolve('node_modules/@sparticuz/chromium/bin/al2023.tar.br');

if (existsSync(libs)) {
    mkdirSync('/tmp/al2023', { recursive: true });
    writeFileSync('/tmp/al2023.tar', brotliDecompressSync(readFileSync(libs)));
    execFileSync('tar', ['xf', '/tmp/al2023.tar', '-C', '/tmp/al2023']);
}
console.log(`chromium: ${binary}  (LD_LIBRARY_PATH=/tmp/al2023/lib)`);
JS
