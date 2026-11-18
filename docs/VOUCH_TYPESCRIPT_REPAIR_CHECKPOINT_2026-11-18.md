# Vouch — strict TypeScript repair checkpoint (2026-11-18)

## Status: NOT COMPILATION-VERIFIED / NOT RELEASE-READY

User's genuine local `npm run typecheck` output from November 18 reported **1,117** diagnostics in **71** files. They are not caused by merely missing dependencies: many TS/TSX files have untyped signatures and invalid React refs. Prior reports that syntax transpilation implied TypeScript readiness were incorrect. No `tsconfig` strictness flags were weakened; no `@ts-nocheck` or blanket `any` suppression was added.

## Source fixes applied in this checkpoint

- Explicitly type first-party React components, input/button variants, dropdown context, modal props and native refs.
- Apply Radix `forwardRef<ElementRef<typeof Primitive>, ComponentPropsWithoutRef<typeof Primitive>>` signatures, including custom dropdown/checkbox properties.
- Replace invalid `useRef(null)` reference inference in all frontend pages with typed element/SignaturePad refs.
- Define explicit template builder domain payloads for template, field, geometry, options, submitters and documents; correct the `drawingField` state from `string | null` to an actual field object.
- Define explicit signing workflow domain payloads, field values, submitters, canvas events, upload handlers and image-modal state.
- Add nil guards around canvas rendering, file input access and `FileReader` results; avoid executing unguarded `.getContext()` methods.
- Type profile/account/team forms and user props; move team dialog state synchronization out of render.
- Correct a React scroll area primitive mapping and field-owner color calculation.

## Validation actually executed

- `node scripts/check-typescript-source.mjs`: PASS — 89 first-party TypeScript modules parsed/transpiled and relative source imports resolved.
- `node tests/static/formula-evaluator.test.mjs`: PASS.
- Strict TypeScript: BLOCKED at `vite/client` missing; this environment has no installed npm dependencies. `npm install` failed with DNS `EAI_AGAIN` to registry.npmjs.org.
- Vite production build: NOT EXECUTED. Full PHPUnit: NOT EXECUTED.

**This is a repair checkpoint, not a claim of zero TypeScript errors.** Source-level transpilation never establishes semantic TypeScript correctness. A clean `npm run typecheck` and `npm run build` on a machine with dependencies installed are required.

## Known remaining work

1. Continue typing other admin template/submission/settings pages that still have missing props or event types; the original log included errors in these modules. **The current checkpoint will likely still report TypeScript errors once dependencies are present.**
2. `package-lock.json` is stale: its root dependency graph does not contain the declared `typescript`, `@types/react`, `@types/react-dom` and `@types/node`. On a networked development machine, run `npm install` (not `npm ci`), commit the regenerated lockfile, then run `npm run typecheck` and `npm run build`.
3. Run the full test suite including signing, media, PDF generation and storage integration before deploying.

## Correct release-gate commands

```sh
npm install
npm run check:source
npm run check:formula
npm run typecheck
npm run build
php artisan test
```

Run `npm ci` only after the refreshed package-lock is committed. Keep `strict: true`, `allowJs: false`, and the existing React + TSX conventions. Never suppress compiler diagnostics in order to assert that a build passes.
