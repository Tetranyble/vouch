# Refactor audit — updated for pre-production schema reset

The earlier compatibility-focused audit is superseded. Vouch has not been deployed, so all Rails authentication and unused infrastructure schemas have been removed directly from the migration history. There is **no data conversion or filesystem compatibility path**.

See [AUTH_SCHEMA_CLEANUP.md](AUTH_SCHEMA_CLEANUP.md) for table decisions, Laravel authentication model, `tetranyble/storage` integration and destructive fresh-migrate instructions; see [TEST_MATRIX.md](TEST_MATRIX.md) for regression coverage and release gates.

**Verification:** PHP syntax checks passed; full Laravel/PHPUnit execution and frontend TypeScript build are not verified in this container. The repository must pass CI and end-to-end signing validation before production exposure.
