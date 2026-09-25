#!/usr/bin/env bash
#
# LoanPro sandbox bootstrap.
#
# This environment has no native PHP, no Docker, no Packagist and no
# getcomposer.org, and the sandbox storage is wiped between sessions.  This
# script rebuilds the whole runtime from scratch and starts the app:
#
#   bash tools/sandbox/up.sh              # repair anything missing + serve
#   bash tools/sandbox/up.sh --fresh      # also re-seed the database
#   PORT=8000 bash tools/sandbox/up.sh
#
# Steps
#   1. WASM PHP runtime   (npm @php-wasm/node: PHP 8.4 + pdo_sqlite/openssl/zip/gd)
#   2. vendor/            (composer.sandbox.lock -> GitHub archives -> autoloader)
#   3. .env + APP_KEY + SQLite database (migrate --seed)
#   4. front-end assets   (npm ci && npm run build)
#   5. HTTP server on 0.0.0.0:$PORT  (PHP request handler -> Laravel public/index.php)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
RUNTIME="$ROOT/tools/sandbox/runtime"
DEPS_DIR="${DEPS_DIR:-/home/user/loanpro-deps/deps}"
COMPOSER_SRC="${COMPOSER_SRC:-/home/user/loanpro-deps/composer-src}"
PORT="${PORT:-8000}"
FRESH=0
[[ "${1:-}" == "--fresh" ]] && FRESH=1

PHP() { node "$RUNTIME/php.mjs" "$@"; }

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

step "1/5 PHP runtime"
if [[ ! -d "$RUNTIME/node_modules/@php-wasm" ]]; then
    (cd "$RUNTIME" && npm install --no-audit --no-fund >/dev/null)
fi
PHP -r 'echo "    php ", PHP_VERSION, " (", implode(",", array_intersect(["ctype","openssl","session","pdo_sqlite","mbstring","zip","gd"], get_loaded_extensions())), ")\n";'

step "2/5 vendor/"
if [[ ! -f "$ROOT/vendor/autoload.php" ]]; then
    if [[ ! -d "$DEPS_DIR/laravel/framework" ]]; then
        echo "    fetching locked packages from GitHub ..."
        DEPS_DIR="$DEPS_DIR" python3 "$ROOT/tools/sandbox/fetch-locked-deps.py"
    fi
    if [[ ! -f "$COMPOSER_SRC/src/Composer/Autoload/ClassLoader.php" ]]; then
        echo "    fetching Composer's ClassLoader/InstalledVersions sources ..."
        mkdir -p "$COMPOSER_SRC"
        curl -sSfL "https://codeload.github.com/composer/composer/tar.gz/refs/tags/2.8.4" |
            tar xz -C "$COMPOSER_SRC" --strip-components=1
    fi
    DEPS_DIR="$DEPS_DIR" COMPOSER_SRC="$COMPOSER_SRC" python3 "$ROOT/tools/sandbox/build-vendor.py"
fi

step "3/5 environment + database"
[[ -f "$ROOT/.env" ]] || cp "$ROOT/.env.example" "$ROOT/.env"
grep -q '^APP_NAME="LoanPro"' "$ROOT/.env" || sed -i 's/^APP_NAME=.*/APP_NAME="LoanPro"/' "$ROOT/.env"
grep -q '^APP_URL=' "$ROOT/.env" || echo "APP_URL=http://localhost:$PORT" >>"$ROOT/.env"
sed -i "s#^APP_URL=.*#APP_URL=http://localhost:$PORT#" "$ROOT/.env"
grep -q '^DB_DATABASE=' "$ROOT/.env" || echo "DB_DATABASE=$ROOT/database/database.sqlite" >>"$ROOT/.env"
touch "$ROOT/database/database.sqlite"
mkdir -p "$ROOT/storage/framework/"{cache,sessions,views} "$ROOT/storage/logs" "$ROOT/bootstrap/cache"
if ! grep -q '^APP_KEY=base64:' "$ROOT/.env"; then
    PHP "$ROOT/artisan" key:generate --force
fi
if [[ "$FRESH" == "1" || "$(PHP -r '$db=new PDO("sqlite:'"$ROOT"'/database/database.sqlite"); try { echo $db->query("select count(*) from users")->fetchColumn(); } catch (Throwable $e) { echo 0; }')" == "0" ]]; then
    PHP "$ROOT/artisan" migrate --force
    PHP "$ROOT/artisan" migrate --seed --force
fi
PHP "$ROOT/artisan" package:discover >/dev/null

step "4/5 assets"
if [[ ! -d "$ROOT/node_modules" ]]; then
    (cd "$ROOT" && npm ci --no-audit --no-fund >/dev/null)
fi
if [[ ! -f "$ROOT/public/build/manifest.json" ]]; then
    (cd "$ROOT" && npm run build)
fi

step "5/5 server"

# A previous run may still hold the port (a stale server pointed at an old
# document root is exactly what makes the app look "broken but running").
if command -v ss >/dev/null 2>&1 && ss -ltn 2>/dev/null | grep -q ":$PORT "; then
    STALE="$(pgrep -f "serve\.mjs --port $PORT" || true)"
    if [[ -n "$STALE" ]]; then
        echo "    stopping the previous LoanPro server (pid $STALE)"
        # shellcheck disable=SC2086
        kill $STALE 2>/dev/null || true
        for _ in $(seq 1 20); do
            ss -ltn 2>/dev/null | grep -q ":$PORT " || break
            sleep 0.25
        done
    fi
fi
if ss -ltn 2>/dev/null | grep -q ":$PORT "; then
    echo "    !! port $PORT is already in use by another process:" >&2
    ss -ltnp 2>/dev/null | grep ":$PORT " >&2 || true
    echo "    run with another port, e.g.  PORT=8001 bash tools/sandbox/up.sh" >&2
    exit 1
fi

echo "    http://0.0.0.0:$PORT  (docroot $ROOT/public)"
exec node "$RUNTIME/serve.mjs" --port "$PORT"
