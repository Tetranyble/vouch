import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

import type { Config } from 'tailwindcss';

const config: Config = {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{ts,tsx}',
    ],
    theme: {
        container: {
            center: true,
            padding: '2rem',
            screens: { '2xl': '1400px' },
        },
        extend: {
            fontFamily: {
                sans: ['var(--font-ui)', ...defaultTheme.fontFamily.sans],
            },
            // Semantic tokens are CSS variables in resources/css/app.css.
            // Keep the alpha placeholder: bg-primary/10, border-primary/50, etc.
            colors: {
                background: 'hsl(var(--background) / <alpha-value>)',
                foreground: 'hsl(var(--foreground) / <alpha-value>)',
                card: { DEFAULT: 'hsl(var(--card) / <alpha-value>)', foreground: 'hsl(var(--card-foreground) / <alpha-value>)' },
                popover: { DEFAULT: 'hsl(var(--popover) / <alpha-value>)', foreground: 'hsl(var(--popover-foreground) / <alpha-value>)' },
                surface: {
                    DEFAULT: 'hsl(var(--surface) / <alpha-value>)',
                    sunken: 'hsl(var(--surface-sunken) / <alpha-value>)',
                    hover: 'hsl(var(--surface-hover) / <alpha-value>)',
                    inverse: 'hsl(var(--surface-inverse) / <alpha-value>)',
                    'inverse-foreground': 'hsl(var(--surface-inverse-foreground) / <alpha-value>)',
                },
                primary: {
                    DEFAULT: 'hsl(var(--primary) / <alpha-value>)',
                    foreground: 'hsl(var(--primary-foreground) / <alpha-value>)',
                    hover: 'hsl(var(--primary-hover) / <alpha-value>)',
                    soft: 'hsl(var(--primary-soft) / <alpha-value>)',
                    'soft-foreground': 'hsl(var(--primary-soft-foreground) / <alpha-value>)',
                },
                secondary: { DEFAULT: 'hsl(var(--secondary) / <alpha-value>)', foreground: 'hsl(var(--secondary-foreground) / <alpha-value>)' },
                muted: { DEFAULT: 'hsl(var(--muted) / <alpha-value>)', foreground: 'hsl(var(--muted-foreground) / <alpha-value>)' },
                accent: {
                    DEFAULT: 'hsl(var(--accent) / <alpha-value>)',
                    foreground: 'hsl(var(--accent-foreground) / <alpha-value>)',
                    soft: 'hsl(var(--accent-soft) / <alpha-value>)',
                    'soft-foreground': 'hsl(var(--accent-soft-foreground) / <alpha-value>)',
                },
                success: { DEFAULT: 'hsl(var(--success) / <alpha-value>)', foreground: 'hsl(var(--success-foreground) / <alpha-value>)', soft: 'hsl(var(--success-soft) / <alpha-value>)', 'soft-foreground': 'hsl(var(--success-soft-foreground) / <alpha-value>)', border: 'hsl(var(--success-border) / <alpha-value>)' },
                warning: { DEFAULT: 'hsl(var(--warning) / <alpha-value>)', foreground: 'hsl(var(--warning-foreground) / <alpha-value>)', soft: 'hsl(var(--warning-soft) / <alpha-value>)', 'soft-foreground': 'hsl(var(--warning-soft-foreground) / <alpha-value>)', border: 'hsl(var(--warning-border) / <alpha-value>)' },
                destructive: { DEFAULT: 'hsl(var(--destructive) / <alpha-value>)', foreground: 'hsl(var(--destructive-foreground) / <alpha-value>)', soft: 'hsl(var(--destructive-soft) / <alpha-value>)', 'soft-foreground': 'hsl(var(--destructive-soft-foreground) / <alpha-value>)', border: 'hsl(var(--destructive-border) / <alpha-value>)' },
                info: { DEFAULT: 'hsl(var(--info) / <alpha-value>)', foreground: 'hsl(var(--info-foreground) / <alpha-value>)', soft: 'hsl(var(--info-soft) / <alpha-value>)', 'soft-foreground': 'hsl(var(--info-soft-foreground) / <alpha-value>)', border: 'hsl(var(--info-border) / <alpha-value>)' },
                border: 'hsl(var(--border) / <alpha-value>)',
                input: 'hsl(var(--input) / <alpha-value>)',
                ring: 'hsl(var(--ring) / <alpha-value>)',
                overlay: 'hsl(var(--overlay) / <alpha-value>)',
                document: {
                    DEFAULT: 'hsl(var(--document) / <alpha-value>)',
                    foreground: 'hsl(var(--document-foreground) / <alpha-value>)',
                    muted: 'hsl(var(--document-muted) / <alpha-value>)',
                    'field-stroke': 'hsl(var(--document-field-stroke) / <alpha-value>)',
                },
                signer: { foreground: 'hsl(var(--signer-foreground) / <alpha-value>)' },
            },
            borderRadius: {
                sm: 'calc(var(--radius-control) - 4px)',
                md: 'var(--radius-control)',
                lg: 'var(--radius-card)',
                xl: 'var(--radius-dialog)',
                '2xl': 'calc(var(--radius-dialog) + 4px)',
                control: 'var(--radius-control)',
                card: 'var(--radius-card)',
                dialog: 'var(--radius-dialog)',
            },
            spacing: {
                'control-x': 'var(--space-control-x)',
                'control-y': 'var(--space-control-y)',
                card: 'var(--space-card)',
                section: 'var(--space-section)',
                page: 'var(--space-page)',
                'control-sm': 'var(--control-sm)',
                'control-md': 'var(--control-md)',
                'control-lg': 'var(--control-lg)',
            },
            fontSize: {
                caption: ['var(--font-size-caption)', { lineHeight: '1.4' }],
                body: ['var(--font-size-body)', { lineHeight: 'var(--line-height-body)' }],
                'section-title': ['var(--font-size-section)', { lineHeight: '1.4' }],
                'page-title': ['var(--font-size-heading)', { lineHeight: '1.25' }],
            },
            boxShadow: {
                soft: 'var(--shadow-soft)',
                elegant: 'var(--shadow-elevated)',
                focus: 'var(--shadow-focus)',
            },
            transitionDuration: {
                fast: 'var(--motion-fast)',
                ui: 'var(--motion-base)',
                slow: 'var(--motion-slow)',
            },
            transitionTimingFunction: { ui: 'var(--ease-ui)' },
            keyframes: {
                'accordion-down': {
                    from: { height: '0' },
                    to: { height: 'var(--radix-accordion-content-height)' },
                },
                'accordion-up': {
                    from: { height: 'var(--radix-accordion-content-height)' },
                    to: { height: '0' },
                },
            },
            animation: {
                'accordion-down': 'accordion-down 0.2s ease-out',
                'accordion-up': 'accordion-up 0.2s ease-out',
            },
        },
    },
    plugins: [forms, animate],
};

export default config;
