# Clone karke run karna — exact commands

Repo: **https://github.com/Mr-argha-das/loan_pro.git** (public)

> **Zaroori baat:** poora application code branch **`arena/01a0d844-loan-pro`** par hai.
> `main` branch par sirf skeleton hai, isliye clone karte waqt **`-b` flag se branch batana zaroori hai**
> (ya PR [#1](https://github.com/Mr-argha-das/loan_pro/pull/1) merge hone ke baad `main` se bhi mil jaayega).

---

## 1. Clone

```bash
# recommended: branch ke saath
git clone -b arena/01a0d844-loan-pro https://github.com/Mr-argha-das/loan_pro.git loan_pro
cd loan_pro
```

SSH wale users ke liye:

```bash
git clone -b arena/01a0d844-loan-pro git@github.com:Mr-argha-das/loan_pro.git loan_pro
```

Bas latest snapshot chahiye (history chhoti, fast):

```bash
git clone --depth 1 -b arena/01a0d844-loan-pro https://github.com/Mr-argha-das/loan_pro.git loan_pro
```

Baad me `main` par switch karna ho (PR merge hone ke baad):

```bash
git fetch origin
git checkout main && git pull
```

---

## 2. Requirements install karein (Ubuntu / Debian)

```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-mbstring php8.3-xml php8.3-sqlite3 \
                    php8.3-mysql php8.3-curl php8.3-zip unzip git curl

# Node 20 (Vite 7 ke liye)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

php -v && composer -V && node -v && npm -v
```

Fedora: `sudo dnf install -y php-cli php-mbstring php-xml php-pdo php-sqlite3 php-mysqlnd nodejs npm`
Arch: `sudo pacman -S php php-sqlite composer nodejs npm`

---

## 3. Setup (SQLite — sabse aasan)

```bash
cd loan_pro

cp .env.example .env
composer install                 # vendor/ (Laravel + dompdf)
php artisan key:generate

# .env me: APP_NAME="LoanPro", APP_URL=http://127.0.0.1:8000, DB_CONNECTION=sqlite
touch database/database.sqlite

php artisan migrate --seed       # tables + roles + products + lenders + demo data
php artisan storage:link         # logo/avatar images ke liye
npm install
npm run build                    # public/build/ (CSS + JS)
```

### MySQL use karna ho (production jaisa)

```sql
CREATE DATABASE loanpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'loanpro'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON loanpro.* TO 'loanpro'@'localhost';
FLUSH PRIVILEGES;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loanpro
DB_USERNAME=loanpro
DB_PASSWORD=secret
```

```bash
php artisan migrate --seed
```

---

## 4. Run

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Browser: **http://127.0.0.1:8000**

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@loanpro.in` | `password` |
| Employee | `neha.singh@loanpro.in` | `password` |

Lead wizard ka dev OTP: **`123456`**

Development me CSS/JS live reload chahiye:

```bash
npm run dev          # alag terminal me (php artisan serve ke saath)
```

---

## 5. Ek command me (copy-paste)

```bash
git clone -b arena/01a0d844-loan-pro https://github.com/Mr-argha-das/loan_pro.git loan_pro && cd loan_pro
cp .env.example .env && composer install && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
npm install && npm run build && php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 6. Agar PHP/Composer install nahi kar sakte

Repo me ek containerless runtime bhi hai (WebAssembly PHP) — kisi bhi Linux par
bina PHP install kiye chalta hai:

```bash
bash tools/sandbox/up.sh          # PHP runtime → vendor/ → .env + SQLite → assets → http://localhost:8000
```

Ye script khud: PHP 8.4 (WASM) laata hai, `vendor/` banata hai (Composer ki zaroorat nahi),
database seed karta hai, assets build karta hai aur port 8000 par serve karta hai.
Details: `tools/sandbox/README.md`.

---

## 7. Troubleshooting (clone ke baad)

| Problem | Fix |
| --- | --- |
| `composer: command not found` | Composer install karein (upar step 2) |
| `could not find driver` | `sudo apt install php8.3-sqlite3` (ya `php8.3-mysql`) |
| Blank / **bina CSS** page | `npm install && npm run build` — `public/build/manifest.json` zaroori hai |
| `Vite manifest not found` | Same as above |
| `419 Page Expired` login par | `php artisan key:generate`, browser cookies on karein |
| Login ke baad wapas login page | `php artisan migrate` (sessions table) |
| Logo/avatar nahi dikh raha | `php artisan storage:link` |
| `storage/framework` write error | `mkdir -p storage/framework/{cache,sessions,views} storage/logs && chmod -R 775 storage bootstrap/cache` |
| Kuch change ho raha hi nahi | `php artisan optimize:clear` |
| `main` branch par code nahi mila | Branch use karein: `-b arena/01a0d844-loan-pro` (ya PR #1 merge karein) |
