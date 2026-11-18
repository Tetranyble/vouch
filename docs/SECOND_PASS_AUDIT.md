# Vouch — second deep audit and remediation

**Date:** 5 November 2026  
**Input:** the latest Laravel-native cleanup archive.  
**Output:** `vouch_second_pass.zip`, with the source and regression tests.  
**Release verdict:** **NOT YET VERIFIED FOR PRODUCTION.** The audit found and repaired concrete defects, but the runtime and strict-type quality gates did not pass in this build environment.

## 1. Defects repaired in this pass

### Database, models, and factories

- Removed nonexistent `template_id` and `source` entries from the `Submitter` model's mass-assignable field list.
- Corrected factories for templates, folders, submissions, submitters, completed signers, submission events, template access, email messages, and webhook events to derive related account/user identifiers from the same parent rather than creating inconsistent tenant relationships.
- Standardized the submission factory's order mode to `preserved`, consistent with the modes handled by the application (`preserved` / `random`).
- Replaced the template-sharing policy's reference to a deleted `linked_account_id` column with explicit `template_sharings` and read/write permission checks.
- Added a schema/factory integrity test to catch any missing application-model factory or model `$fillable` field not represented in the database.

### Signing workflows, tenancy, and API authorization

- Public `StartFormController` now rejects disabled/archived sharing links, validates contact fields, and creates submissions plus their submitters atomically.
- `CreateSubmissionAction` and `CloneTemplateAction` reject cross-account records; submission and signer inserts are transactional.
- `CreateTemplateAction` rejects a `folder_id` belonging to another account.
- API attachment uploads reject a different account and completed/declined signers, and keep the canonical `media:<id>` reference instead of storing a transient signed URL as a field value.
- Completed/declined submitters cannot have their signed content changed through the API.
- Public signer signature/initials prefill is shown only when the **authenticated user's account and email both match** the targeted signer. Mere possession of a signing link no longer discloses another user's saved signature.
- Public signing payloads and API template update payloads require the expected array types.
- Admin template status filters use an allowlist instead of dispatching arbitrary query methods derived from request input.
- Added a request rate limit to public shared-form creation; capped API page sizes to 100.
- Prevented the development seeder (which creates `admin@vouch.test` with a sample password) from running in the production environment.

### React/TypeScript

- Corrected an Inertia `router.delete()` call that supplied the wrong number of parameters.
- Improved types for common React UI components, including button, badge, input, card, label, and Radix sheet primitives; fixed several unsafe DOM selectors, pagination/props assumptions, and type annotations.
- Typed the `cn` utility's rest arguments using `ClassValue`.
- Corrected the storage settings screen's obsolete claim that legacy filesystem paths remain readable.
- Source `.ts` / `.tsx` files remain TypeScript (not converted back to JS).

### Test regressions repaired and new coverage

- Fixed two clone-action unit tests that had constructed the user in the wrong account.
- Fixed a sequential-signing test that mistakenly exercised the default parallel mode.
- Added tests for cross-tenant template sharing and folder selection, transactional submission rollback, disabled/archived public template access, malformed signer inputs, valid/invalid signing payloads, completed signer immutability, account-scoped attachments, matching-user signature prefill privacy, and the fresh-install schema/factory structure.

## 2. Static validation results (measured)

| Check | Result | Meaning |
| --- | --- | --- |
| PHP syntax | **PASS** — 266 PHP files | All files in `app`, `database`, `routes`, `config`, `tests`, and `bootstrap` passed `php -l` |
| Laravel route registration | **PASS** — 172 routes | `php artisan route:list --json` executed successfully |
| Application model factories | **PASS (static)** — 22/22 | Every application model has a corresponding factory class. Factory execution remains untested. |
| Migrations | **29 files** | Fresh-install schema has been audited statically; actual SQLite/MySQL migrations must still be run. |
| PHPUnit test files | **39** | Added or extended tests; not a claim that these tests passed. |
| TypeScript transpilation | **PASS** — 86 TS/TSX modules | Syntax transpilation completed with zero diagnostics; this is **not** strict type checking. |
| Strict TypeScript (`npm run typecheck`) | **FAIL** — 4,330 diagnostics | Uploaded macOS `node_modules` is missing React/ReactDOM/Node declaration packages despite `package.json` declaring them. Many diagnostics cascade from missing JSX typings; there are also source-specific errors to correct after reinstall. |
| Frontend build (`npm run build`) | **BLOCKED** | Mac-origin `node_modules` lacks Linux Rollup native binary (`@rollup/rollup-linux-x64-gnu`). |
| PHPUnit (`php artisan test`) | **BLOCKED** | Local PHP 8.4 lacks `dom`, `mbstring`, `xml`, and `xmlwriter`; `pdo_sqlite` is also unavailable. |
| npm install | **BLOCKED** | Registry access failed from this environment (`EAI_AGAIN`). |

A passing PHP syntax/transpilation check does **not** establish that the application is working end-to-end. The current ZIP is a reviewed source revision, not a certified release.

## 3. Remaining release blockers

1. **Install frontend dependencies on the target OS and regenerate/commit `package-lock.json`.** The archive's lockfile predates TypeScript declaration additions. Do not use `npm ci` until the lockfile is refreshed. Missing React types mask genuine TS errors; the reported 4,330 diagnostic count is not a trustworthy count of distinct code defects.
2. **Resolve every remaining strict TypeScript diagnostic** after the declarations are installed. `npm run typecheck` currently fails; do not weaken `tsconfig.json` to make it pass.
3. **Run all PHP tests** using PHP 8.3+ with `mbstring`, `dom`, `xml`, `xmlwriter`, `pdo_sqlite`, and other Composer-required extensions. Inspect real migration/factory/relationship behavior and fix test failures.
4. **Run `migrate:fresh --seed` against a disposable development database only.** This destroys records. Verify package migrations and private document storage using the configured disk.
5. **Verify the complete product flow manually**: create account, login and verification, create template, upload document, add signers/fields, send invitations, complete signature/attachment, generate audit/combined PDFs, download signed media, and trigger webhooks/queues.
6. **Run `vendor/bin/pint --test`**, the full CI job, and the frontend production build; do not deploy while any gate fails. Ensure production uses real credentials, mail, private disk, and queue workers.

## 4. Reproduction commands

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install                         # refreshes frontend dependencies + lockfile
npm run typecheck                   # fix all errors; should exit 0
npm run build                       # should exit 0
php artisan migrate:fresh --seed    # disposable development database ONLY
php artisan test                    # should exit 0
vendor/bin/pint --test              # should exit 0
```

Once regenerated, **commit `package-lock.json`**, then change the CI frontend install step back to `npm ci` for reproducible installs. Avoid shipping an uploaded `vendor/` or `node_modules/` directory from another operating system.

## 5. Architectural scope

The work preserves the earlier Laravel-native direction: **no Rails/Devise auth schema, Rails OAuth tables, Active Storage tables, or backward-compatibility path**. API auth stays on Sanctum and document bytes stay under `tetranyble/storage`. Business entities (`accounts`, `templates`, `submissions`, `submitters`, events, etc.) remain. No production-data migration was attempted because the application is development-only.
