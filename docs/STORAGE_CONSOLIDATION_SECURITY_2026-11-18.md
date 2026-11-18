# Vouch — Tetranyble storage consolidation and signed-download hardening

Date: 2026-11-18

## The actual problem

`use App\Facades\DocumentStorage as Storage` was a misleading alias. It did not invoke Laravel's raw `Storage` facade, but it concealed the real package-managed storage boundary and encouraged account-unscoped operations. `SubmitFormDownloadController` also accepted an unsigned submitter slug to fetch completed PDFs, buffered PDFs in memory, performed expensive PDF generation inside public GET requests, and could duplicate a pre-generated combined PDF inside a ZIP.

## Implemented

- Removed `as Storage` aliases and direct Laravel Storage facade usage from **application** code. Application storage is only accessed through `App\Services\DocumentStorage` (Tetranyble's `UploadMedia`, `FileSystemContract`, `MediaDeletionService`) and its explicitly named facade. Persisted document references are `media:<id>` and resolve to private, workspace-owned Tetranyble records; filesystem paths are rejected.
- All `exists`, `get`, `size`, `mimeType`, `url`, `download`, and `delete` adapter calls require an explicit `accountId`. A guessed media ID cannot be fetched, deleted, or turned into a link from a different account. This includes PDF generation, webhooks, signed media delivery, thumbnails, attachments, template previews, API responses, document-copy emails and public previews.
- Completion-mail links are now seven-day HMAC-signed URLs. `/s/{slug}/download` requires a valid signed URL or an authenticated user from the owning account; the route is rate limited. Plain pre-migration unsigned links will fail. Completed-document downloads are denied for archived accounts, account mismatches or incomplete signing.
- The GET download action no longer generates a PDF. Background jobs remain responsible for PDF generation. The download action returns the original PDF, a precomputed combined PDF, or a bounded ZIP of original PDFs; it excludes duplicate combined/audit documents, checks ZIP creation errors, sanitizes names, disables response caching, and cleans its transient ZIP on send.
- Added source-level storage boundary check (`php scripts/check-storage-boundary.php`), wired to CI, plus regression tests covering cross-account media access, signed URL expiration and tampering, an unrelated logged-in user, and combining multiple documents.

## Important engineering distinction

The `App\Facades\DocumentStorage` Laravel facade is only a typed proxy to our `App\Services\DocumentStorage` adapter. **It is not `Illuminate\Support\Facades\Storage`**. That adapter is a deliberately stable boundary around `tetranyble/storage` so controllers and jobs do not duplicate vendor internals. The integration-test fixture uses Laravel `Storage::fake('local')` only to emulate the package's underlying filesystem disk, not to perform business file I/O. PHP `tempnam` and `ZipArchive` are used only for ephemeral PDF/ZIP processing; persisted files remain in Tetranyble storage.

## Compatibility / review points

- Previously distributed plain `/s/{slug}/download` links now return 404 unless accessed by an authenticated same-account user. New completion emails include expiring signed links. A reissue workflow should be implemented for signers whose seven-day links expire, including signers without email addresses.
- PHP's PDF and ZIP paths still consume in-memory file bytes through `FileSystemContract::get`. The ZIP path is bounded to 64 MiB and 50 documents, but full streaming requires verification of the installed Tetranyble `FileSystemContract` stream methods before implementation. No streaming claim is made.
- Other public signer endpoints (`/s/{slug}` and attached-document viewing) intentionally use high-entropy signing-link capabilities. They require a separate full authorization and signing-session threat-model review; this change does **not** claim to harden every public workflow.
- Verify private media disk configuration, route signatures and all mail flows on the deployed host. Confirm that existing completion emails and admin links meet the intended retention and reissue policy.

## Validation

- PHP `php -l`: PASS for all application, route and test source files.
- `php scripts/check-storage-boundary.php`: PASS for 155 application PHP files.
- Source TypeScript transpilation/imports: PASS for 88 modules using TypeScript (not semantic type checking).
- Full `php artisan test`: **NOT EXECUTED** (this environment does not have Composer-installed `vendor/` and required PHP extensions).
- Full `npm run typecheck` and `npm run build`: **NOT EXECUTED** (project npm dependencies unavailable). Source transpilation does not imply build success.

Run with installed dependencies and PHP 8.3+ extensions: `php scripts/check-storage-boundary.php && php artisan test && npm run check`. Do not deploy until PHPUnit, migrations, signing/download browser flows, and production build have passed.
