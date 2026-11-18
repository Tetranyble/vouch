# Vouch — second Tetranyble/storage and security sweep

**Date:** 2026-11-18  
**Baseline:** `vouch_tetranyble_storage_security_2026-11-18.zip`  
**Deliverable:** `vouch_storage_deep_sweep_2026-11-18.zip`  
**Status:** source changes implemented; **NOT production-verified**.

## 1. Confirmed issues addressed

1. **Workspace lookup bypass:** `DocumentStorage::findMedia` previously accepted a nullable account scope; the private media controller called it without a workspace to infer ownership. It now requires an account ID. Signed-media URLs include the workspace (`account` query argument) within Laravel's URL signature; the controller verifies the signature *before* performing an account-scoped lookup. This is a deliberate URL format change; preexisting signed media URLs with no account argument will be rejected.
2. **Stale test contract:** existing storage integration tests called `exists`, `get`, `url`, and `findMedia` without the required account ID. These calls have been corrected, and link-tampering coverage added. `Storage::fake()` is retained **only in tests** to emulate the package's underlying filesystem; it is not an application storage access path.
3. **Completed preview capability:** `/e/{slug}` and `/e/{slug}/download/{doc_id}` previously allowed a public viewer based only on a submission slug and recent completion time. External viewers now require a valid signed URL *and* the prior completion-time window. Workspace-authenticated users retain access. `App\Support\SubmissionPreviewLink::for` creates a signed public preview link when the submission is fully completed and within the 40-minute window. Previously shared unsigned preview links will no longer work.
4. **Generation retries / orphan media:** PDF generation, audit trail generation, and combined-PDF generation now call `App\Services\CompletedDocumentWriter`. The writer locks the signer for concurrent DB writes, reuses unchanged artifacts, updates existing generated records on changed content, and attempts to clean up superseded package media only after a successful database update. Failed replacements attempt to remove the newly uploaded media; cleanup exceptions are logged. Prior duplicated records already present in a production database are **not** automatically reconciled.
5. **Temporary files:** added `App\Support\TemporaryDocumentFile`; PDF generation/merging, administrative previews, and PKCS#7 verification now use checked temporary file creation and deterministic `finally` cleanup. These files are transient input for third-party libraries, **not** persisted Vouch media.
6. **Upload database failures:** newly uploaded template documents, thumbnails, and signer attachments are deleted if their subsequent Eloquent update fails. API signer attachments persist the metadata and field value in a single Eloquent update.
7. **Preview and download handling:** user-controlled filenames are passed through Symfony's `HeaderUtils` for safe `Content-Disposition`; preview responses use `no-store` and `nosniff`. Base64 thumbnails are limited and MIME-verified as JPEG. One email attachment and preview path now avoid presenting combined PDFs alongside every source PDF.
8. **Shared template media retention:** `CloneTemplateAction` copies references in JSON, not independent physical media. Therefore, deleting an original template document or replacing an original thumbnail must **not** immediately destroy the shared package media. Removed immediate physical deletes in those flows pending reference-aware garbage collection. Newly uploaded media with a failed DB write remains safe to delete.
9. **Static boundary CI:** strengthened `scripts/check-storage-boundary.php` to scan both `app/` and `routes/`, detect storage facade aliases, and require explicit account parameters on app-facing storage operations, including `findMedia`.

## 2. New regression-test source

- `tests/Feature/Storage/DocumentStorageIntegrationTest.php`: corrected method signatures; workspace tampering and forged/expired signed media links.
- `tests/Feature/Storage/CompletedDocumentWriterTest.php`: repeated generation, changed content, and workspace mismatch.
- `tests/Feature/Storage/SharedTemplateMediaRetentionTest.php`: cloned document still resolves after removing its original template entry.
- `tests/Feature/Public/SubmissionPreviewSecurityTest.php`: unsigned preview/download denial; valid signed view; different-workspace rejection.
- `tests/Unit/Support/TemporaryDocumentFileTest.php`: complete content writing and filename extension validation.

These tests are authored but **not executed against Laravel** in this environment.

## 3. Validation actually performed

| Gate | Result |
| --- | --- |
| PHP `php -l` on application/config/routes/database/tests source | PASS (all checked files) |
| `php scripts/check-storage-boundary.php` | PASS (162 app and route PHP files) |
| TypeScript source transpilation and import resolution | PASS (88 `.ts` / `.tsx` modules, using available globally installed TypeScript compiler APIs) |
| Standalone temporary-file PHP smoke test | PASS |
| `php artisan test` | NOT RUN: Composer `vendor/autoload.php` unavailable; required PHP extensions missing |
| `npm run typecheck` | BLOCKED: frontend dependencies, including `vite/client` types, unavailable |
| `npm run build` | BLOCKED: Vite executable unavailable |
| PHP migrations/MySQL, package integration tests, full browser signing | NOT RUN |

**Do not mark this revision as compiling, passing PHPUnit, or ready for production.** It is a security and storage source revision requiring runtime validation.

## 4. Remaining design and release decisions

1. **Reference-aware garbage collection:** model every media reference retained by templates, template clones, submission snapshots, completed documents, uploads, and pending queues. Do not reclaim template-source media solely because one JSON entry was removed. Specify retention/hold periods, quota accounting, and restoration/disaster recovery.
2. **Streaming:** `DocumentStorage::get` and the inline response still materialize the whole file in PHP memory. Evaluate the package's supported streaming API and apply it to signed downloads, previews, ZIPs, and large mail attachments. No unverified `FileSystemContract` methods were invented here.
3. **Signer-link model:** `/s/{slug}` and attachment URLs intentionally behave as bearer capabilities; review token entropy, revocation, access expiration, replay/rate limiting, and signer identity requirements with product/legal owners.
4. **Public preview integration:** where the UI or emails intend to provide a completed-submission preview, generate an actual `SubmissionPreviewLink` instead of a plain `/e/{slug}` URL. Existing unsigned links are no longer valid for external viewers.
5. **Production data migration:** inspect existing direct filesystem paths, historical package references, already duplicated `completed_documents`, signed-link consumers, and queue ordering before deploying. This revision does **not** modify production rows.
6. **Enforce release gates:** install Composer and Node dependencies, run PHP migration/factory/integration tests on a disposable database, run strict TS type-check and frontend build, then test document signing, file retention, download expiry, signers, webhooks, and account isolation through HTTP.

## 5. Reproduction

```bash
composer install
npm install
php scripts/check-storage-boundary.php
php artisan test
npm run check:source
npm run typecheck
npm run build
```

Do **not** run `migrate:fresh` against a production database.
