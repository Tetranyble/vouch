# Vouch — shadcn design system consolidation (18 November 2026)

## Architecture decision

**Keep `@inertiajs/react` and Laravel's Inertia server integration.** Inertia supplies page resolution, navigation, form state, history and backend page props; it is not the UI component library. Replacing Inertia would require a separate SPA/API rewrite and alter routes and authentication. **Remove Laravel Breeze's default React presentation components**, whose functionality is now supplied by shadcn-styled local primitives at `@/Components/ui`.

## Changes

- Deleted 12 unused Breeze-era React components (`ApplicationLogo`, `Checkbox`, `DangerButton`, `Dropdown`, `InputError`, `InputLabel`, `Modal`, `NavLink`, `PrimaryButton`, `ResponsiveNavLink`, `SecondaryButton`, `TextInput`). All were checked for frontend imports first. The actual brand component `VouchLogo` is preserved.
- Routed remaining native UI controls through shared `Button`, `Input`, `Label`, `Checkbox`, `Select`, `Textarea` components. Native `<form>`, links, file and canvas interaction, graphic/media elements and native semantics stay intact. Existing shadcn wrappers remain the source of truth for their controls.
- Added a reusable typed `Textarea` component, constrained `Button` action semantics to `type=button` by default, and added compact/icon/text size variants for dense tools.
- Converted public signing upload/signature/decline overlays to Radix-backed shadcn `Dialog`; signature method selection now uses the controlled shadcn `Tabs`. Dialogs have accessible titles and descriptions.
- Set `components.json` `tsx: true`, removed unused `@headlessui/react`, added `@radix-ui/react-checkbox`, and added `npm run check:ui` to CI and `npm run check`.
- Converted both remaining native `<select>` controls to Radix shadcn Select with placeholder and valid nonempty SelectItem values.

## Why these boundaries remain

- The document canvas, drag/drop zones, PDF rendering, native `<form>` elements and hidden file inputs are functional platform primitives; they are not a competing visual component library.
- The locally maintained checkbox has been replaced by an accessible Radix-backed shadcn Checkbox. Its wrapper normalizes Radix indeterminate states to the application's boolean contract. This introduces `@radix-ui/react-checkbox`, which must be installed and reflected in a regenerated lockfile.
- Domain-specific templates, PDF field overlays and editor interactions are custom application components composed with the shared UI kit.

## Validation status

- TypeScript syntax parsing, static local imports, design-system policy and formula tests can run after the existing `typescript` dev dependency has been installed.
- **Strict TypeScript type-check and Vite build are not certified** in the build environment. The inherited `package-lock.json` is stale relative to four already-declared dependencies (`typescript`, `@types/react`, `@types/react-dom`, `@types/node`) and the npm registry could not be reached. The lockfile's root declaration was updated for Headless UI removal, but cannot be made fully deterministic offline.
- In an environment with npm network access, run `npm install --no-audit`, commit the refreshed `package-lock.json`, run `npm run check`, then use `npm ci` in CI. Preserve strict mode; resolve all compiler diagnostics instead of weakening `tsconfig`.
- Manually exercise dialogs, focus traps, Escape/overlay close, signing canvas with touch, signature upload, select fields, checkbox activation, file picking, save/submit and mobile editor navigation.

## Compatibility

The Laravel Inertia routes/controllers, backend storage security fixes, document workflow and API contracts are unchanged. This pass changes frontend presentation and does not claim to fix the separate source-level TypeScript errors reported previously.
