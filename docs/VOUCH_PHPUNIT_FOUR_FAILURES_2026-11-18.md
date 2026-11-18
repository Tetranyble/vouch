# Vouch PHPUnit regression fix — 18 November 2026

## Base

Built directly from `vouch_global_design_tokens_2026-11-18.zip` (SHA-256 `aeff999887f5650b41fc5f398becd3f0c4c33c393b9aa559dd63c8cecb8323a7`). All existing production source files and existing design tokens are unchanged.

## Reported local test run

- 280 passed, 4 failed (948 assertions, per user's output).
- Four failures were mismatches between expected test behavior and the existing application contract.

## Test corrections

1. `tests/Feature/Admin/Settings/AccountTest.php`
   - The user model uses `SoftDeletes`, and `AccountController::removeUser()` calls `$user->delete()`.
   - Verify the removed member is soft-deleted, absent from active queries, and retained for audit. Verify the acting user remains active.
   - Strengthen self-removal and cross-account denial tests to verify the targeted users are still active (not merely present as soft-deleted rows).

2. `tests/Feature/ExampleTest.php`
   - The anonymous homepage deliberately serves the Inertia `Welcome` component with HTTP 200; login is separate.
   - Assert the guest homepage and add an authenticated-user redirect assertion. Add `RefreshDatabase` for its new factory-based authenticated test.

3. `tests/Feature/ProfileTest.php`
   - `ProfileController::destroy()` calls `$user->delete()`, which soft-deletes `User` records.
   - Assert the user was logged out, soft-deleted, excluded from active model queries, and retained under `withTrashed()`.
   - Verify an incorrect password leaves the user active.

4. `tests/Feature/Public/SubmitFormTest.php`
   - Symfony normalizes Cache-Control directive order. `private, no-store` and `no-store, private` both prohibit shared caching and storage.
   - Assert each required directive is present, independently of ordering, while still asserting that the signed document response is successful and contains the expected PDF data.

## Validation performed

- PHP lint: **PASS**, 274 PHP files in app, tests, database, routes, config, and bootstrap.
- PHP static storage-boundary check: **PASS**, 162 application files checked.
- Design-token/contrast checks: **PASS**.
- PHPUnit: **NOT EXECUTABLE HERE**; no `vendor/autoload.php`, no Composer executable, and PHP environment lacks required extensions. Therefore the four tests are not claimed as runtime-passing.
- Frontend build/typecheck: **NOT RE-RUN** in this test-only patch; dependencies are unavailable in this environment.

## Local verification commands

```bash
composer install
php artisan test --filter='AccountTest|ExampleTest|ProfileTest|SubmitFormTest'
php artisan test
```

Keep `strict` TS compiler configuration and the existing storage/service architecture unchanged.
