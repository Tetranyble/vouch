# Vouch

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/Tetranyble/vouch/tree/main.svg?style=svg)](https://dl.circleci.com/status-badge/redirect/gh/Tetranyble/vouch/tree/main)
[![Quality and regression checks](https://github.com/Tetranyble/vouch/actions/workflows/quality.yml/badge.svg)](https://github.com/Tetranyble/vouch/actions/workflows/quality.yml)
[![Latest release](https://img.shields.io/github/v/release/Tetranyble/vouch?sort=semver)](https://github.com/Tetranyble/vouch/releases/latest)

<p align="center">
  <img src="docs/assets/vouch_logo_correct_1024.svg" alt="Vouch logo" width="160">
</p>

Vouch is an electronic document-signing project from the Pensuh Innovation / Tetranyble ecosystem. Pensuh is the education technology product at [pensuh.com](https://pensuh.com), and Tetranyble's broader software portfolio is at [tetranyble.com](https://tetranyble.com).

Vouch is a multi-tenant document-signing application built with Laravel 12, Inertia, React, and TypeScript. Teams can create document templates, assign signing fields, invite recipients, collect signatures, and securely deliver completed documents.

## Features

- Template builder with PDF and image uploads, signing fields, conditional fields, and multiple signers.
- Sequential or parallel signing flows, with email and SMS invitations.
- Drawn, typed, and uploaded signatures; optional email OTP, SMS OTP, KBA, and payment fields.
- Completed-document delivery, audit trails, expiring download links, and workspace-scoped private storage.
- Team, account, SMTP, SSO, MFA, notification, storage, API-token, and webhook settings.
- Sanctum bearer-token API and signed webhook payloads.

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel, GD, ZIP, and your database driver.
- Composer 2.
- Node.js 20 or newer and npm.
- SQLite, MySQL, or PostgreSQL.
- A queue worker in any environment that sends invitations, reminders, webhooks, or completed documents.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
php artisan migrate --seed
```

Set your database connection in `.env`, then start the development processes:

```bash
composer dev
```

This starts Laravel, Vite, the queue listener, and the log viewer. Alternatively, run `php artisan serve`, `npm run dev`, and `php artisan queue:work` in separate terminals.

The development seeder creates `admin@vouch.test` with password `password`. It is strictly for local use; never deploy seeded development credentials.

### Resetting local data

The following command deletes all local data before recreating the schema and running seeders:

```bash
php artisan migrate:fresh --seed
```

Do not run it against a shared, staging, or production database.

## Configuration

Use `.env.example` as the complete configuration reference. At minimum, production needs:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.example.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=notifications@example.com
MAIL_FROM_NAME="Vouch"

QUEUE_CONNECTION=database
STORAGE_DISK=local
```

Configure `STRIPE_*`, `TWILIO_*`, and `GOOGLE_*` only when those integrations are enabled. Set `TRUSTED_PROXIES` to known reverse-proxy addresses in production; do not trust arbitrary forwarded headers.

Vouch stores document references as private, account-scoped media records. Always use the `DocumentStorage` service for document I/O rather than filesystem paths or public URLs.

## Background processing and scheduler

Queued work delivers invitations, reminders, completion emails, and webhooks. Keep a worker running in production:

```bash
php artisan queue:work --tries=3
```

The scheduler sends signing reminders daily at 09:00 and expires overdue submissions hourly. Run it continuously with:

```bash
php artisan schedule:work
```

Or invoke `php artisan schedule:run` once per minute from your platform scheduler.

## Email and webhooks

Transactional email is sent through the configured Laravel mailer. The default local mailer is `log`, so it records messages rather than delivering them.

Webhook deliveries include:

- `X-Vouch-Event`
- `X-Vouch-Signature` — an HMAC-SHA256 digest of the raw JSON payload using the webhook secret.
- `User-Agent: Vouch-Webhook/1.0`

Verify webhook requests using a timing-safe comparison:

```php
$signature = hash_hmac('sha256', $payload, $secret);
hash_equals($signature, $request->header('X-Vouch-Signature'));
```

## API

The API is available below `/api` and uses Laravel Sanctum bearer tokens:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

API tokens are shown only when issued. Store them securely and rotate or revoke them when they are no longer needed.

## Testing and quality checks

```bash
# Laravel tests
php artisan test

# Frontend checks: source policy, UI policy, design tokens, formulas, types, build
npm run check

# PHP style check
vendor/bin/pint --test
```

Useful individual checks:

```bash
npm run typecheck
npm run build
node scripts/check-typescript-source.mjs
php scripts/check-storage-boundary.php
node scripts/check-shadcn-ui.mjs
node scripts/check-design-tokens.mjs
```

## Deployment checklist

1. Provision the database, private document storage, HTTPS, and a worker process.
2. Set production environment variables, especially `APP_URL`, mail credentials, queue connection, storage, and trusted proxies.
3. Install immutable dependencies with `composer install --no-dev --optimize-autoloader` and `npm ci`.
4. Build frontend assets with `npm run build`.
5. Run migrations with `php artisan migrate --force`.
6. Cache framework configuration and views with `php artisan optimize`.
7. Start or restart queue workers and the scheduler.
8. Run the full test and frontend-check suite in CI before release.

## Releases

Download the deployable package and its SHA-256 checksum from the [latest GitHub Release](https://github.com/Tetranyble/vouch/releases/latest).

## Company and ecosystem

- **[Tetranyble](https://tetranyble.com)** — software studio and portfolio home.
- **[Pensuh](https://pensuh.com)** — school and institutional management software associated with Pensuh Innovation.
- **[Vouch](https://github.com/Tetranyble/vouch)** — open-source electronic document signing software (this project).

These products have distinct purposes and are not presented as interchangeable services. Commercial support, hosted deployments, and any paid offering should have their own explicitly published terms.

For copyright, attribution, and license details, see [LICENSE](LICENSE). Third-party dependencies remain subject to their respective licenses.

## License

Vouch is released under the [MIT License](LICENSE). Copyright © 2026 Pensuh.
