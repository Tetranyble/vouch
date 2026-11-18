# TypeScript source policy

All first-party frontend application source lives in `resources/js` and uses `.ts` or `.tsx`.

- `.tsx` **is TypeScript**. Use it for React components with JSX markup. Files without JSX should use `.ts`.
- `.jsx` and `.js` are not permitted for first-party frontend application modules.
- Tailwind configuration is `tailwind.config.ts`; PostCSS is configured programmatically in `vite.config.ts`.
- `public/pdf.worker.js` is a **third-party, generated distribution artifact** from Mozilla PDF.js, not application source. Its original JavaScript format is required for execution in browsers and is copied by the Vite plugin. Do not rename it to `.ts` or `.tsx`.
- Vite-generated production bundles are JavaScript, even when authored entirely in TypeScript.

## Validation

Run `npm install`, `npm run typecheck`, `npm run build`, and `php artisan test` in a fully configured development environment. The previous archive has unrelated strict type-check and runtime validation blockers; this configuration-only update does not certify them as fixed.
