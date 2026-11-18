# TypeScript validation status — 2026-11-18

The frontend uses `.ts` and `.tsx` source. `.tsx` is required for React files containing JSX.

## Quality gates

1. `npm run check:source`: parses and transpiles first-party `.ts`/`.tsx` source and checks local relative module imports. This is syntax/integrity validation, **not** TypeScript semantic checking.
2. `npm run typecheck`: uses strict TypeScript type checking including Vite and Tailwind configurations.
3. `npm run build`: must successfully compile production assets with Vite, including copying the PDF.js worker.
4. `npm run check`: runs all three checks, with CI gating on any failure.

## Outstanding blocker

The execution environment could not install npm dependencies from the registry. Therefore `tsc` could not load `vite/client` type definitions; a semantic type-check and production build **could not be confirmed**. The previous archive is also not verified to build. Once network access is available, run:

```sh
npm install --no-audit --no-fund
npm run check
```

Commit the refreshed lockfile after installation. Do not deploy until `npm run typecheck` and `npm run build` both pass. Never weaken `strict` or add catch-all module declarations merely to silence errors.
