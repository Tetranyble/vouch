# Vouch regression test matrix

All tests must pass before deployment. PHPUnit uses an in-memory SQLite database configured in `phpunit.xml`.

| Area | Checks |
| --- | --- |
| Schema feature/integration | `FreshInstallSchemaTest`: native Laravel columns and tables are present; obsolete Rails infrastructure absent; model hashes and hides passwords |
| Browser authentication | Registration, throttled login, logout, password confirmation/update, password-reset links, signed email verification, unverified-user handling |
| Google SSO | Mocked provider login: unverified emails rejected, verified emails safely linked to an existing local account |
| API authentication | Sanctum bearer success, invalid/revoked token rejection, legacy Token and X-Auth-Token rejection, query-token rejection |
| API tenancy | Account-scoped templates/submissions/submitters, completed documents; reject cross-account access |
| Storage integration | Private Tetranyble upload/read, signed links, expiry/tamper rejection, no legacy path access, per-account ownership |
| Signing journey | Template upload, signer attachment, PDF generation, audit trail, webhook (full manual end-to-end acceptance required) |

**Run:**

```bash
composer install
npm install
php artisan migrate:fresh --seed   # DESTROYS development DB data
vendor/bin/pint --test
php artisan test
npm run typecheck
npm run build
```

CI runs tests against an in-memory database. No green CI result has been observed in this work session; full runtime validation remains required.
