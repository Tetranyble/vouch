# Fresh-install Laravel authentication and Rails infrastructure cleanup

## Scope

Vouch is still in development. There is no backward compatibility or data backfill. The original migrations were edited or removed instead of creating data-preserving conversion migrations. To apply the new schema to an existing development database, run `php artisan migrate:fresh --seed` **after deciding the development data can be destroyed**.

## Replaced with Laravel

| Area | Fresh-install implementation |
| --- | --- |
| Users | Laravel `users`: `password`, `email_verified_at`, `remember_token`, `deleted_at`, `google_id`, `account_id`, role/name/uuid |
| Browser authentication | Laravel session guard + CSRF + throttled login; verified email |
| Password reset | Laravel `password_reset_tokens`, `Password` broker |
| Sessions | Laravel `sessions` |
| API authentication | Sanctum `personal_access_tokens`, `auth:sanctum`, bearer headers only |
| Google SSO | Laravel Socialite, provider-verified email required for linking |
| Documents | `tetranyble/storage` private account media; no legacy disk fallback |

Removed outright: Devise columns, the custom `access_tokens` table, three Rails OAuth tables, three `active_storage_*` tables, four `console1984_*` tables, `lock_events`, unused `account_accesses` and `account_linked_accounts`; their models/factories and obsolete legacy routes/middleware were also removed.

Business tables such as `accounts`, `templates`, `submissions`, `submitters`, `completed_documents`, webhook/event tables and `template_accesses` remain to support Vouch workflows. `archived_at` on templates/submissions/accounts is domain-specific and remains; user soft deletion uses Laravel `deleted_at`.

## Security policy

- API requests use `Authorization: Bearer <personal-access-token>`; query secrets, `Authorization: Token`, and `X-Auth-Token` are not accepted.
- A Google identity must have a nonempty provider ID and provider-confirmed verified email before it may be created or linked; mismatched linked accounts are rejected.
- Local new registrations are unverified until they follow Laravel's signed verification email.
- Raw document disk paths are never resolved. Use registered Tetranyble media references only.
- Deleted/archived users are excluded by Laravel SoftDeletes' global scope.

## Minimum verification before production

1. Run `composer install`, `php artisan migrate:fresh --seed` on a disposable database, and `php artisan test`.
2. Confirm `FreshInstallSchemaTest`, all existing authentication tests, Google OAuth mock tests, Sanctum API tests, storage and signer workflow tests are green.
3. Run `vendor/bin/pint --test`, `npm run typecheck`, `npm run build`; fix any failing checks rather than bypassing them.
4. Manual full signing journey: create a template, upload document, add fields, invite signer, submit attachment, sign, generate audit-trail PDF, download, and verify the webhook.
5. Configure mail delivery and an explicit trusted proxy/storage provider before internet exposure. Never seed sample password accounts in production.

**Environment verification limit:** Source PHP lint passed, but PHP extensions required for SQLite/Laravel test execution are absent here, and a complete frontend typecheck/build has not passed. The tests have been added/updated but are not represented as executed/passing.
