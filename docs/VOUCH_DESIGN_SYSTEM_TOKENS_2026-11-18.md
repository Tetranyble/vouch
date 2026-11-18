# Vouch Design System — Semantic Tokens v1

**Date:** 18 November 2026  
**Source baseline:** `vouch_shadcn_ui_consolidated_2026-11-18.zip`  
**Status:** Token migration source complete; semantic TypeScript compilation and production build **not yet verified**.

## Architecture and non-goals

- The authoritative design tokens are the CSS variables in `resources/css/app.css`; Tailwind 3 maps their **semantic names** through `tailwind.config.ts`, with opacity placeholders to support e.g. `bg-primary/10`, `border-primary/50`, `bg-overlay/60`.
- Components live under `resources/js/Components/ui/`, using shadcn/Radix + Tailwind. This is the only visual component foundation. `@inertiajs/react` remains for routing/rendering and is not an alternate design system.
- Default visual identity is **forest-green action, warm-ivory canvas, white surfaces, gold accent**. It is deliberately not changed to a new palette.
- Root `.dark` switches application UI tokens. Existing routes remain unchanged; this pass does **not** add user theme preference storage or a theme toggle. The dark override can be applied by theme infrastructure later.
- Account-configurable `settings.primary_color` is *customer-facing signer branding*, **not** a global UI token. Its default and persistence contract are unchanged.
- PDF **paper and signature ink do not switch** with UI themes: signed artifacts must retain their visual/ink semantics. Document tokens, `--signer-1` … `--signer-10`, and `--signature-ink` have no dark override.

## Semantic color taxonomy

| Purpose | Tailwind classes or token | Usage |
| --- | --- | --- |
| App canvas, foreground | `bg-background text-foreground` | Page body and typography |
| Elevated / sunken surfaces | `bg-card`, `bg-surface-sunken`, `bg-surface-hover` | Cards, wells and hover |
| Brand action | `bg-primary text-primary-foreground`, `hover:bg-primary-hover` | Primary buttons |
| Supporting action | `bg-secondary text-secondary-foreground` | Secondary controls |
| Interactive emphasis | `bg-accent text-accent-foreground`, `bg-primary-soft text-primary-soft-foreground` | Current nav, emphasis |
| Secondary text | `text-muted-foreground` | Help labels / supporting copy |
| Form borders and keyboard focus | `border-input`, `border-border`, `ring-ring`, `shadow-focus` | Native and Radix control states |
| Success | `bg-success-soft text-success-soft-foreground` | Signed, active, confirmed |
| Warning | `bg-warning-soft text-warning-soft-foreground` | Pending, opened, delayed |
| Error | `bg-destructive-soft text-destructive-soft-foreground` | Declined, error, destructive |
| Information | `bg-info-soft text-info-soft-foreground` | Informational, invitation or integration |
| Dialog scrim | `bg-overlay/60` | Dialog, sheet, lightbox |
| Document paper | `ui-document-paper`, `bg-document text-document-foreground` | PDF, handwritten signature canvas |
| Signer annotations | `signerColor(index)` from `@/lib/design-tokens` | Stable color per signer |

Each state also has `-border`, `-soft`, `-soft-foreground`, `-foreground` counterparts. Strong background `*-foreground` tokens must not be substituted for soft-background text; they represent different contrast pairs.

## Sizing, typography, rhythm, elevation, motion

| Role | Token | Application |
| --- | --- | --- |
| Base font | `--font-ui`, `font-sans` | Inter with system fallback |
| Caption / body | `text-caption`, `text-body` | Notes, controls |
| Section / page titles | `text-section-title`, `text-page-title`, `ui-page-title` | Headings |
| Control heights | `h-control-sm`, `h-control-md`, `h-control-lg` | Buttons, input, select |
| Horizontal control padding | `px-control-x` | Form controls |
| Standard card padding | `p-card` | shadcn Card, common panel |
| Page / section spacing | `p-page`, `gap-section` | Containers |
| Component radii | `rounded-control`, `rounded-card`, `rounded-dialog` | Controls, cards, dialogs |
| Elevation | `shadow-soft`, `shadow-elegant`, `shadow-focus` | Surfaces, focus rings |
| Motion | `duration-fast`, `duration-ui`, `duration-slow`, `ease-ui` | Interactive transitions |

Composable `@layer components` classes: `.ui-page`, `.ui-page-header`, `.ui-page-title`, `.ui-page-description`, `.ui-section`, `.ui-subtle-panel`, `.ui-field-label`, `.ui-field-help`, `.ui-field-error`, `.ui-document-paper`.

## Examples

```tsx
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';

export function SubmissionSummary() {
    return (
        <section className="ui-page">
            <h1 className="ui-page-title">Submissions</h1>
            <Card className="mt-6">
                <CardContent>
                    <Badge variant="success">Completed</Badge>
                    <p className="mt-3 text-muted-foreground">All parties have signed.</p>
                    <Button className="mt-4">Download signed PDF</Button>
                </CardContent>
            </Card>
        </section>
    );
}
```

```ts
import { signerColor, resolvedCanvasColor } from '@/lib/design-tokens';

const signerLabelColor = signerColor(2); // CSS color expression, stable across themes
// CanvasRenderingContext2D does NOT resolve var() directly:
ctx.strokeStyle = resolvedCanvasColor('signature-ink');
```

**Exception rules:** Third-party Google SVG brand mark paths retain official colors. Customer-supplied `settings.primary_color` remains a dynamic explicit hex value. Arbitrary CSS/JS color constants or Tailwind palette utilities in app source are otherwise prohibited.

## Enforcement and checks

`npm run check:tokens` runs without npm dependencies and verifies:

1. All required semantic tokens exist, dark-mode overrides refer to real base variables, Tailwind var references are defined, opacity-aware color mappings are present.
2. Body/foreground, buttons, muted text, all status pairs and 10 signer labels have a calculated luminance contrast **at least 4.5:1** in the applicable theme.
3. Physical PDF/ink/signer tokens are invariant under `.dark`.
4. App TS/TSX files do not introduce hard-coded Tailwind palette names or new hex colors, except approved user-/provider-brand exceptions.

`npm run check:tokens` is wired into `npm run check` and GitHub Actions.  **Static contrast does not replace keyboard, screen-reader, responsive, screenshot, PDF, or browser E2E validation.**

## Known outstanding release blockers

- `npm install` is still needed on a registry-connected machine; the existing lockfile is out of sync with frontend dependency declarations from earlier passes. This change adds **no new npm dependencies** and does not repair that lockfile.
- `npm run typecheck` must pass with `strict` enabled. The previous JavaScript-to-TypeScript migration contained many untyped page props; this token pass is *not* a blanket TypeScript repair.
- `npm run build` must pass in CI and on the target machine. Full Laravel tests and screenshot regression tests are not executed as part of this token migration.
- Manual review should verify all active, focused, disabled, hover and dark states at mobile + desktop breakpoints and compare signer document annotations to their persisted PDF output.
