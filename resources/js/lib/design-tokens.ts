/**
 * Semantic non-Tailwind tokens for document canvas and PDF overlays.
 * Palette definitions live exclusively in resources/css/app.css.
 * These values are CSS color expressions, not pre-resolved RGB strings.
 */
export const SIGNER_COLOR_COUNT = 10;

export function signerColor(index: number): string {
    const safeIndex = Number.isFinite(index) ? Math.trunc(index) : 0;
    return `hsl(var(--signer-${((safeIndex % SIGNER_COLOR_COUNT) + SIGNER_COLOR_COUNT) % SIGNER_COLOR_COUNT + 1}))`;
}

export const documentColors = {
    paper: 'hsl(var(--document))',
    ink: 'hsl(var(--document-foreground))',
    muted: 'hsl(var(--document-muted))',
    fieldStroke: 'hsl(var(--document-field-stroke))',
    fieldBlank: 'hsl(var(--document-field-blank) / 0.7)',
    fieldFilled: 'hsl(var(--document-field-filled) / 0.85)',
    signatureInk: 'hsl(var(--signature-ink))',
} as const;

/** The Canvas 2D API does not resolve CSS var() expressions in strokeStyle. */
export function resolvedCanvasColor(token: 'signature-ink' | 'document-foreground'): string {
    const channels = getComputedStyle(document.documentElement).getPropertyValue(`--${token}`).trim();
    if (!channels) throw new Error(`Missing required design token: --${token}`);
    return `hsl(${channels})`;
}
