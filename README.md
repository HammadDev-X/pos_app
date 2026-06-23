# Musa Jan Frozen Foods POS

Laravel POS for sales, invoices, inventory, purchases, expenses, customer ledger, recovery payments, returns, and profit reports.

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL or MariaDB
- PHP extensions: `bcmath`, `ctype`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip`

## First-Time Setup

```bash
git clone <repo-url>
cd laravel-pos

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create a database, then update `.env`:

```env
APP_NAME="Musa Jan Frozen Foods"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_pos
DB_USERNAME=root
DB_PASSWORD=
```

Finish setup:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

Default seeded login:

```text
Email: admin@gmail.com
Password: admin123
```

## Daily Development

Run these in two terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

## After Pulling New Code

```bash
composer install
npm install
php artisan migrate
npm run build
php artisan optimize:clear
```

## Useful Checks

```bash
php artisan test
npm run build
php artisan route:list
```

## Notes

- Do not commit `.env`, `vendor`, `node_modules`, `public/build`, or generated IDE helper files.
- This project uses Vite. Legacy Laravel Mix files and built `public/css` / `public/js` artifacts are intentionally not tracked.
- Uploaded product/customer/supplier images are stored through Laravel storage. Run `php artisan storage:link` after first setup.
