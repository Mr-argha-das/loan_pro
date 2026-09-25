/**
 * LoanPro sandbox runtime.
 *
 * The sandbox has no native PHP binary, no Docker and no access to php.net /
 * Packagist / getcomposer.org.  PHP itself is available as WebAssembly through
 * the `@php-wasm/node` package (PHP 8.4 with sqlite3/pdo_sqlite, openssl, zip,
 * gd, mbstring, ctype, session, ...), so this module boots that runtime and
 * exposes it to the rest of the sandbox:
 *
 *   - `php.mjs`   -> a drop-in `php` CLI (artisan, composer.phar, -r, ...)
 *   - `serve.mjs` -> an HTTP server on 0.0.0.0:8000 that forwards every request
 *                    to Laravel's `public/index.php`
 *
 * The host filesystem is mounted into the runtime 1:1, so PHP sees the same
 * absolute paths as the sandbox shell does.
 */
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

export const RUNTIME_DIR = dirname(fileURLToPath(import.meta.url));
export const REPO_ROOT = process.env.LOANPRO_ROOT || '/home/user/loan_pro';
export const PHP_VERSION = process.env.LOANPRO_PHP_VERSION || '8.4';

let processIdCounter = 100 + Math.floor(Math.random() * 400);

/**
 * Boot a fresh PHP instance with the host filesystem mounted.
 */
export async function bootPhp({ extensions = [], ini = {} } = {}) {
    const runtime = await loadNodeRuntime(PHP_VERSION, {
        emscriptenOptions: { processId: processIdCounter++ },
        extensions,
    });

    const php = new PHP(runtime);

    // Reflects the sandbox filesystem into the wasm filesystem (read + write).
    useHostFilesystem(php);

    // Sensible defaults for a Laravel dev server.
    const defaults = {
        'memory_limit': '512M',
        'error_reporting': 'E_ALL',
        'display_errors': '1',
        'display_startup_errors': '1',
        'log_errors': '1',
        'date.timezone': 'Asia/Kolkata',
        'session.save_path': '/tmp',
        'session.cookie_httponly': '1',
        'upload_max_filesize': '16M',
        'post_max_size': '20M',
        'opcache.enable': '1',
        'opcache.enable_cli': '1',
        'opcache.validate_timestamps': '1',
        'opcache.revalidate_freq': '0',
        'opcache.file_update_protection': '0',
        'opcache.jit': 'disable',
        ...ini,
    };
    for (const [key, value] of Object.entries(defaults)) {
        try {
            php.setPhpIniEntry(key, String(value));
        } catch (error) {
            // Non-fatal: the runtime ignores unknown ini keys.
        }
    }

    return php;
}

export function repoPath(...parts) {
    return join(REPO_ROOT, ...parts);
}
