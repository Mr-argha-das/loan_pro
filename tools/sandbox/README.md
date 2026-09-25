# Sandbox tooling

This checkout normally runs on Laravel + PHP + Composer. The Arena sandbox has
**no native PHP binary, no Docker, no Packagist and no getcomposer.org**, and its
storage is wiped between sessions, so the tooling here rebuilds everything from
the sources that *are* reachable (npm, PyPI, codeload.github.com).

| Path | Purpose |
| --- | --- |
| `up.sh` | One-command recovery: PHP runtime → `vendor/` → `.env`/SQLite → assets → HTTP server on `:8000`. |
| `runtime/` | WebAssembly PHP 8.4 (`@php-wasm/node`) exposed as `php.mjs` (a `php` CLI: Artisan, Composer, `-r`, …) and `serve.mjs` (the HTTP server that runs Laravel with per-client cookies and static assets). |
| `fetch-deps.py`, `fetch-locked-deps.py` | Download every locked Composer package as a GitHub tag archive into `$DEPS_DIR` (default `/home/user/loanpro-deps/deps`). |
| `build-vendor.py` | Assemble `vendor/`: link the archives and generate Composer-compatible autoload metadata (`autoload_*.php`, `autoload_real.php`, `installed.json`, `installed.php`, `platform_check.php`, `vendor/bin`), reusing Composer's own `ClassLoader.php` / `InstalledVersions.php`. |
| `make-sandbox-composer.py` | Writes the `composer.sandbox.json` path-repository manifest (kept for reference; `build-vendor.py` needs no Composer). |

```bash
bash tools/sandbox/up.sh            # rebuild what is missing, then serve :8000
bash tools/sandbox/up.sh --fresh    # also re-run migrate --seed
```

Browser verification lives in `tools/qa/`:

```bash
bash tools/qa/setup-browser.sh      # headless Chromium for the harness
node tools/qa/verify.mjs            # signs in + walks every module, asserts styled output
node tools/qa/interactions.mjs      # uploads, PDF/XLSX exports, wizard, role restrictions
```
