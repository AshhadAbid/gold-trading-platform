# Asasa gold trading demo

A deployable, single-user gold trading experience built with Laravel, React, and PostgreSQL. It shows a trusted live 24K price in PKR per gram, lets a customer enter PKR or grams, locks a quote for 75 seconds, settles a buy or sale atomically, updates all balances, and issues a receipt.

## Deploy to Render

The repository includes `render.yaml`, which creates the application and a private managed PostgreSQL database. Connect this GitHub repository as a Render Blueprint and approve the two resources:

[Deploy on Render](https://render.com/deploy?repo=https://github.com/AshhadAbid/gold-trading-platform)

Render generates the Laravel encryption secret, injects the internal PostgreSQL connection string, runs migrations and the idempotent demo seed, and deploys every new commit from `main`.

## Run locally

Requirements: PHP 8.3+, Composer, Node 20+, npm, and PostgreSQL 15+. Docker Compose is optional for PostgreSQL.

For the complete containerized application, create a real Laravel key and start all services:

```bash
cp backend/.env.example backend/.env
cd backend && composer install && php artisan key:generate --show
# Put the printed value in APP_KEY in your shell or a root .env file.
cd ..
docker compose up --build
```

Then open `http://localhost:5173`.

For a native development setup:

```bash
docker compose up -d
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

In another terminal:

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

Open `http://localhost:5173`. The API is served at `http://localhost:8000/api/v1`. For a deployment without outbound market access, set `TRADING_PRICE_MODE=demo`; the UI labels that source honestly. Use `primary_down` to demonstrate provider fallback and `all_down` to demonstrate paused trading. Clear the application cache after changing modes: `php artisan cache:clear`.

## Pricing and trade rules

- Primary source: PakGold. Fallback: GoldPrice.org.
- The server fetches at most once every five minutes; source and observation time are shown.
- Customer buy price: `max(market × 1.10, PKR 45,000 guardrail)`.
- Customer sell price: `market × 0.90`.
- Quotes expire after 75 seconds. An expired quote is never repriced silently.
- PostgreSQL transactions and row locks settle cash, customer gold, platform inventory, and receipt together.
- A unique quote-to-trade constraint and idempotent confirmation prevent double trades.

## Verification

```bash
cd backend && php artisan test
cd frontend && npm run build
```

The feature tests cover buy settlement, the guardrail, repeated confirmation, expiry, insufficient gold, and rollback behavior.
