# LoanPro — Linux par local setup (step by step)

Ye guide **Linux** (Ubuntu/Debian/Fedora/Arch) ke liye hai. Do raste hain:

| | Option A — SQLite (sabse fast, 5 min) | Option B — MySQL (production jaisa) |
| --- | --- | --- |
| Database | ek file (`database/database.sqlite`) | MySQL 8 server |
| Install chahiye | `pdo_sqlite` extension | MySQL server + `pdo_mysql` |
| Kab use karein | Demo, development, testing | Client/staging/production |

Baaki saare steps dono me same hain.

---

## 1. Kya kya download karna hai

| Software | Version | Kyun chahiye |
| --- | --- | --- |
| **PHP** | **8.2 ya usse upar** (8.3/8.4 best) | Laravel 12 chalane ke liye |
| **PHP extensions** | `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `session`, `tokenizer` + `pdo_sqlite` (Option A) **ya** `pdo_mysql` (Option B) | Laravel ki core dependencies inhi ki maang karti hain |
| **Composer** | 2.x | PHP packages (`vendor/`) install karne ke liye |
| **Node.js + npm** | Node **20.19+** ya **22.12+** (Vite 7 ki requirement) | CSS/JS assets build karne ke liye (`public/build/`) |
| **MySQL** (optional) | 8.0+ | Option B ke liye |
| **Git** | koi bhi recent | Repo clone karne ke liye |

> Note: `bcmath`, `gd`, `zip`, `intl`, `sodium` ki **zaroorat nahi** hai — project inke bina chalta hai
> (XLSX export hand-written OOXML se hota hai, koi `ext-zip` dependency nahi).

### Ubuntu / Debian / Mint / Kali

```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-sqlite3 php8.3-mysql php8.3-zip unzip git curl

# Node 20 LTS (NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

php -v && composer -V && node -v && npm -v      # verify
```

Agar aapke distro me PHP 8.2 hi hai to wahi theek hai — `php8.2-cli` likh dein.

### Fedora / RHEL / CentOS Stream

```bash
sudo dnf install -y php-cli php-mbstring php-xml php-pdo php-sqlite3 php-mysqlnd \
    php-opcache php-curl unzip git
sudo dnf install -y nodejs npm
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

### Arch / Manjaro

```bash
sudo pacman -S php php-sqlite php-gd composer nodejs npm git unzip
```

### MySQL (sirf Option B)

```bash
sudo apt install -y mysql-server          # Ubuntu/Debian
sudo systemctl enable --now mysql
sudo mysql                                # MySQL shell khulega
```

```sql
CREATE DATABASE loanpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'loanpro'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON loanpro.* TO 'loanpro'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 2. Project download + install

```bash
# 1) code le aaiye
git clone <repo-url> loan_pro
cd loan_pro

# 2) environment file (pehle ye, taki artisan ko .env mile)
cp .env.example .env

# 3) PHP packages (vendor/)
composer install

# 4) application key
php artisan key:generate
```

### 2a. Database configure kijiye

**Option A — SQLite (recommended for first run):** `.env` me sirf ye rakhein

```dotenv
DB_CONNECTION=sqlite
```

aur file bana lein:

```bash
touch database/database.sqlite
```

Laravel default `database/database.sqlite` hi uthata hai — aur kuch nahi likhna padta.

**Option B — MySQL:** `.env` me ye badlein

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loanpro
DB_USERNAME=loanpro
DB_PASSWORD=secret
```

### 2b. App ke basics

```dotenv
APP_NAME="LoanPro"
APP_URL=http://127.0.0.1:8000
```

### 2c. Tables + demo data

```bash
php artisan migrate --seed
```

Ye 14 migrations chalata hai (roles/permissions, users, cache, jobs, organisation,
product hierarchy, masters, customers, lenders, leads, applications, documents,
finance, notifications) aur 6 seeders:

* `RolePermissionSeeder` — ADMIN/EMPLOYEE roles + permissions
* `OrganisationSeeder` — departments, designations, 8 demo employees (**admin@loanpro.in / password**)
* `ProductCatalogSeeder` — 4 products, 6 loan categories, 3 insurance categories + sub-categories
* `MasterDataSeeder` — 15 masters (statuses, sources, document types, cities…)
* `LenderSeeder` — 10 lenders + 70 lender products (ROI/EMI data)
* `DemoDataSeeder` — 42 leads, 20 customers, 25 applications, invoices, disbursements, payments, attendance

Production me demo data nahi chahiye to:

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=OrganisationSeeder
# + jo masters chahiye: ProductCatalogSeeder, MasterDataSeeder, LenderSeeder
```

### 2d. Front-end assets (CSS/JS)

```bash
npm install
npm run build          # ek baar — public/build/ bana dega
```

Development me live reload chahiye to `npm run dev` (alag terminal me) chalayein.

---

## 3. Server start kijiye

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Browser me kholiye: **http://127.0.0.1:8000**

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@loanpro.in` | `password` |
| Employee | `neha.singh@loanpro.in` | `password` |

Lead wizard ka **dev OTP `123456`** hai (`APP_ENV=local` me OTPService fixed code deta hai;
production me random 6-digit OTP banta hai).

---

## 4. Optional (par production me zaroori)

```bash
php artisan storage:link        # zaroori — avatars, employee photos, lender logos, company logo
php artisan queue:work          # QUEUE_CONNECTION=database — notifications/mails ke liye
php artisan schedule:work       # scheduled jobs (agar add karein)
```

Do tarah ke uploads hain, dhyan rakhein:

* **KYC / customer / invoice documents** → `storage/app/private/documents/...` me jaate hain aur
  sirf authorised controller route (`documents.preview` / `documents.download`) se serve hote hain.
  Ye **kabhi public nahi** hote — `storage:link` in par koi asar nahi karta.
* **Branding / profile images** (company logo, employee photo, lender logo, user avatar) →
  `public` disk par jaate hain, isliye `php artisan storage:link` chalana zaroori hai warna
  ye images toot kar dikhengi.

---

## 5. Sab kuch ek saath (copy-paste)

```bash
git clone <repo-url> loan_pro && cd loan_pro
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite          # SQLite option
php artisan migrate --seed
npm install && npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 6. Common problems

| Problem | Wajah / Fix |
| --- | --- |
| `could not find driver` | `pdo_sqlite` (ya `pdo_mysql`) extension missing — `sudo apt install php8.3-sqlite3 php8.3-mysql` |
| UI **bina CSS** dikh raha hai / button bina style | `public/build/manifest.json` nahi hai → `npm install && npm run build`. `php artisan serve` ko `public/` docroot chahiye |
| `419 Page Expired` login par | `APP_KEY` khaali hai (`php artisan key:generate`) ya cookies block hain |
| `Vite manifest not found` | Assets build nahi hue — `npm run build` |
| `Please provide a valid cache path` / blank page | `mkdir -p storage/framework/{cache,sessions,views} storage/logs && chmod -R 775 storage bootstrap/cache` |
| `SQLSTATE[HY000] [2002]` (MySQL) | MySQL service band hai — `sudo systemctl start mysql`; `.env` me host/port check karein |
| `npm run build` par Node version error | Vite 7 ke liye Node 20.19+/22.12+ chahiye — `node -v` check karein |
| Login ke baad wapas login page | `SESSION_DRIVER=database` hai aur `sessions` table miss hai — `php artisan migrate` |
| Logo / avatar / employee photo dikh nahi raha | `php artisan storage:link` chalayein (public disk) |
| Upload ki hui file 404 de rahi hai | KYC documents private disk par hain; direct URL se nahi, app ke document route se khulte hain |
| Charts/empty states khaali lag rahe | Dashboard DB se aata hai; `php artisan migrate --seed` se demo data aa jaayega |
| `Class "..." not found` / purane views | `php artisan optimize:clear` (config + view + route cache clear) |
| Sab theek lag raha hai par kuch bhi change nahi ho raha | `php artisan optimize:clear && npm run build` |

---

## 7. MySQL ↔ SQLite switch karna

* Code driver-aware hai (dashboard ke month-wise queries `SQLITE`/`MYSQL` dono handle karte hain),
  isliye `.env` me sirf `DB_CONNECTION` badalna kaafi hai.
* Switch karne ke baad naye DB par `php artisan migrate --seed` chala dein.
* `config/database.php` me `mysql` block pehle se maujood hai — extra config ki zaroorat nahi.

---

## 8. Sandbox / no-PHP environment

Agar aapke paas PHP install karne ka access nahi hai (jaise Arena sandbox), to repo me
`tools/sandbox/up.sh` maujood hai jo WebAssembly PHP se poora stack khud bana deta hai:

```bash
bash tools/sandbox/up.sh          # PHP runtime → vendor/ → .env + SQLite → assets → :8000
```

Details: `tools/sandbox/README.md`.
