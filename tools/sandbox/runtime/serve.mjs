#!/usr/bin/env node
/**
 * LoanPro development server.
 *
 * Forwards every HTTP request to Laravel's `public/index.php` running inside
 * the WebAssembly PHP runtime (see runtime.mjs).  Static files (CSS, JS,
 * images) inside `public/` are served straight from disk by the PHP request
 * handler, so the browser sees exactly what `php -S ... public/router.php`
 * would serve.
 *
 *   node serve.mjs [--port 8000] [--root /home/user/loan_pro]
 */
import http from 'node:http';
import { readFileSync, existsSync } from 'node:fs';
import { extname, join, normalize } from 'node:path';
import { PHPRequestHandler } from '@php-wasm/universal';

import { bootPhp, REPO_ROOT } from './runtime.mjs';

const args = process.argv.slice(2);
const portArg = args.indexOf('--port');
const PORT = Number(
    portArg !== -1 ? args[portArg + 1] : process.env.LOANPRO_PORT || 8000
);
const HOST = '0.0.0.0';
const DOCUMENT_ROOT = join(REPO_ROOT, 'public');

const MIME = {
    '.css': 'text/css; charset=utf-8',
    '.js': 'text/javascript; charset=utf-8',
    '.mjs': 'text/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.map': 'application/json; charset=utf-8',
    '.svg': 'image/svg+xml',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.gif': 'image/gif',
    '.webp': 'image/webp',
    '.ico': 'image/x-icon',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
    '.ttf': 'font/ttf',
    '.eot': 'application/vnd.ms-fontobject',
    '.txt': 'text/plain; charset=utf-8',
    '.pdf': 'application/pdf',
};

console.log('[loanpro] booting WebAssembly PHP runtime ...');
const t0 = Date.now();
const php = await bootPhp();
console.log(`[loanpro] PHP ${php.runtimeId ? '' : ''}runtime ready in ${Date.now() - t0} ms`);

const handler = new PHPRequestHandler({
    php,
    documentRoot: DOCUMENT_ROOT,
    absoluteUrl: `http://localhost:${PORT}`,
    // Laravel is a front-controller app: unknown paths are routed through
    // index.php exactly like the framework's own `router.php` does.
    getFileNotFoundAction: () => ({ type: 'internal-redirect', uri: '/index.php' }),
    // Honour the session cookie the *browser* sends instead of keeping one
    // shared cookie jar for every visitor (which would log everyone in as the
    // first user who signed in).
    cookieStore: false,
});

/** Serve a static file without waking PHP (fast path for assets/fonts). */
function tryStatic(pathname) {
    const relative = normalize(decodeURIComponent(pathname)).replace(/^(\.\.[/\\])+/, '');
    const file = join(DOCUMENT_ROOT, relative);
    if (!file.startsWith(DOCUMENT_ROOT) || !existsSync(file)) return null;
    try {
        const data = readFileSync(file);
        return data;
    } catch {
        return null;
    }
}

const server = http.createServer(async (request, response) => {
    const started = Date.now();
    try {
        const host = request.headers.host || `localhost:${PORT}`;
        const forwarded = String(request.headers['x-forwarded-proto'] || '').split(',')[0];
        const scheme = forwarded === 'https' ? 'https' : 'http';
        const url = `${scheme}://${host}${request.url}`;

        if ((request.method === 'GET' || request.method === 'HEAD') && !request.url.includes('?')) {
            const pathname = request.url.split('#')[0];
            if (!/\.php$/.test(pathname)) {
                const file = tryStatic(pathname === '/' ? '/index.html' : pathname);
                if (file) {
                    response.writeHead(200, {
                        'Content-Type': MIME[extname(pathname).toLowerCase()] || 'application/octet-stream',
                        'Content-Length': file.length,
                        'Cache-Control': 'public, max-age=60',
                    });
                    response.end(request.method === 'HEAD' ? undefined : file);
                    return;
                }
            }
        }

        const chunks = [];
        for await (const chunk of request) chunks.push(chunk);
        const body = Buffer.concat(chunks);

        const phpResponse = await handler.request({
            method: request.method,
            url,
            headers: Object.fromEntries(
                Object.entries(request.headers).map(([key, value]) => [
                    key,
                    Array.isArray(value) ? value : String(value ?? ''),
                ])
            ),
            body: body.length ? new Uint8Array(body) : undefined,
        });

        const headers = {};
        const cookies = [];
        for (const [key, values] of Object.entries(phpResponse.headers || {})) {
            const lower = key.toLowerCase();
            if (['transfer-encoding', 'connection', 'content-length'].includes(lower)) continue;
            // Never join multiple Set-Cookie headers: each one must stay on its
            // own line or browsers drop the session cookie.
            if (lower === 'set-cookie') {
                cookies.push(...(Array.isArray(values) ? values : [String(values)]));
                continue;
            }
            headers[key] = Array.isArray(values) ? values.join(', ') : String(values);
        }
        if (cookies.length) headers['Set-Cookie'] = cookies;
        const bytes = phpResponse.bytes instanceof Uint8Array ? phpResponse.bytes : new Uint8Array(phpResponse.bytes || []);
        response.writeHead(phpResponse.httpStatusCode || 200, headers);
        response.end(bytes);

        if (process.env.LOANPRO_LOG !== '0') {
            console.log(
                `${request.method} ${request.url} -> ${phpResponse.httpStatusCode} ${bytes.length}B ${Date.now() - started}ms`
            );
        }
        const errors = phpResponse.errors;
        if (errors) process.stderr.write(String(errors).slice(0, 4000) + '\n');
    } catch (error) {
        console.error('[loanpro] request failed:', error);
        if (!response.headersSent) response.writeHead(500, { 'Content-Type': 'text/plain' });
        response.end('LoanPro runtime error:\n' + (error?.stack || String(error)));
    }
});

server.keepAliveTimeout = 0;
server.headersTimeout = 0;
server.listen(PORT, HOST, () => {
    console.log(`[loanpro] serving ${DOCUMENT_ROOT} on http://${HOST}:${PORT}`);
});
