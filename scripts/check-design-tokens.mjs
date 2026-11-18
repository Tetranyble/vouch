import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { join, resolve, relative } from 'node:path';

/* Dependency-free design system contract guard; run in CI and locally. */
const root = resolve(import.meta.dirname, '..');
const cssPath = join(root, 'resources/css/app.css');
const tailwindPath = join(root, 'tailwind.config.ts');
const css = readFileSync(cssPath, 'utf8');
const tailwind = readFileSync(tailwindPath, 'utf8');
const failures = [];

function blockAfter(source, needle) {
    const start = source.indexOf(needle);
    if (start < 0) return '';
    const open = source.indexOf('{', start);
    let depth = 0;
    for (let i = open; i < source.length; i++) {
        if (source[i] === '{') depth++;
        else if (source[i] === '}') {
            depth--;
            if (depth === 0) return source.slice(open + 1, i);
        }
    }
    return '';
}
function readVars(source) {
    return Object.fromEntries([...source.matchAll(/--([\w-]+):\s*([^;]+);/g)].map(m => [m[1],m[2].trim()]));
}
const rootValues = readVars(blockAfter(css, ':root {'));
const darkValues = readVars(blockAfter(css, '.dark {'));
const rootNames = new Set(Object.keys(rootValues));
for (const name of ['background','foreground','card','card-foreground','primary','primary-foreground','secondary','muted','border','input','ring','success','success-soft','warning','warning-soft','destructive','destructive-soft','info','info-soft','radius','radius-card','radius-control','font-ui','shadow-soft','shadow-focus','motion-base','document','document-foreground','signature-ink','signer-1','signer-10']) {
    if (!rootNames.has(name)) failures.push(`Missing foundation token --${name}`);
}
for (const name of Object.keys(darkValues)) {
    if (!rootNames.has(name)) failures.push(`Dark theme token --${name} lacks a light default`);
}
for (const name of Object.keys(rootValues)) {
    if (/^(?:signer-\d+|document(?:-.*)?|signature-ink)$/.test(name) && name in darkValues) {
        failures.push(`Physical document token --${name} must not change under .dark`);
    }
}
for (const match of tailwind.matchAll(/var\(--([\w-]+)\)/g)) {
    if (!rootNames.has(match[1]) && !match[1].startsWith('radix-')) failures.push(`Tailwind references missing token --${match[1]}`);
}
for (const token of ['primary','background','foreground','border','success','destructive','info','warning']) {
    if (!tailwind.includes(`'hsl(var(--${token}) / <alpha-value>)'`)) {
        failures.push(`Tailwind opacity-aware token ${token} is missing`);
    }
}
function channelsToRgb(value) {
    const parsed = value.match(/^(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)%\s+(\d+(?:\.\d+)?)%$/);
    if (!parsed) throw new Error(`Not HSL channels: ${value}`);
    const [,h,s,l]=parsed.map(Number);
    const H=h/360, S=s/100, L=l/100;
    const hue2rgb=(p,q,t)=>{ if(t<0)t+=1;if(t>1)t-=1;if(t<1/6)return p+(q-p)*6*t;if(t<1/2)return q;if(t<2/3)return p+(q-p)*(2/3-t)*6;return p; };
    if (S===0) return [L,L,L];
    const q=L<.5?L*(1+S):L+S-L*S, p=2*L-q;
    return [hue2rgb(p,q,H+1/3),hue2rgb(p,q,H),hue2rgb(p,q,H-1/3)];
}
function luminance(value) {
    const rgb=channelsToRgb(value).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4);
    return rgb[0]*.2126+rgb[1]*.7152+rgb[2]*.0722;
}
function ratio(a,b) { const A=luminance(a), B=luminance(b);return (Math.max(A,B)+.05)/(Math.min(A,B)+.05); }
const contrastPairs = [
    ['foreground','background'],['card-foreground','card'],['muted-foreground','background'],
    ['primary-foreground','primary'],['secondary-foreground','secondary'],['accent-foreground','accent'],
    ...['success','warning','destructive','info'].map(role=>[`${role}-foreground`,role]),
    ...['primary','accent','success','warning','destructive','info'].map(role=>[`${role}-soft-foreground`,`${role}-soft`]),
];
for (const [theme,palette] of [['light',rootValues],['dark',{...rootValues,...darkValues}]]) {
    for (const [foreground,background] of contrastPairs) {
        if (!(foreground in palette) || !(background in palette)) {
            failures.push(`Missing contrast pair ${foreground}/${background} in ${theme}`);continue;
        }
        try {
            const actual=ratio(palette[foreground],palette[background]);
            if (actual<4.5) failures.push(`${theme}: ${foreground} on ${background} has ${actual.toFixed(2)}:1 (min 4.5:1)`);
        } catch(e) { failures.push(`${theme} invalid HSL pair ${foreground}/${background}: ${e.message}`); }
    }
}
for (let i=1;i<=10;i++) {
    const actual=ratio(rootValues['signer-foreground'],rootValues[`signer-${i}`]);
    if (actual<4.5) failures.push(`Signer ${i} label contrast ${actual.toFixed(2)}:1; requires 4.5:1`);
}
const forbidden = /(?:^|[\s'"`])(?:dark:)?(?:hover:)?(?:bg|text|border|ring|fill|stroke)-(?:red|green|blue|yellow|orange|amber|gray|slate|zinc|neutral|stone|emerald|indigo|sky|purple|pink|violet|teal|lime|cyan|rose|fuchsia|black|white)(?:-\d{2,3})?(?:\/\d+)?/g;
let filesChecked=0;
function walk(folder) {
    for (const entry of readdirSync(folder,{withFileTypes:true})) {
        const filename=join(folder,entry.name);
        if(entry.isDirectory()){walk(filename);continue;}
        if (!/\.(ts|tsx)$/.test(filename)) continue;
        const source=readFileSync(filename,'utf8');
        const rel=relative(root,filename);
        filesChecked++;
        const paletteClasses=[...source.matchAll(forbidden)].map(m=>m[0].trim());
        if (paletteClasses.length) failures.push(`${rel}: raw color utilities ${[...new Set(paletteClasses)].join(', ')}`);
        // External Google SVG fills and the customer-configurable signer-brand field
        // are intentional exceptions. No other UI hex literal is permitted.
        const exceptional=rel==='resources/js/Pages/Auth/Login.tsx' || rel==='resources/js/Pages/Auth/Register.tsx' || rel==='resources/js/Pages/Admin/Settings/Personalization.tsx';
        if (!exceptional && /#[0-9A-Fa-f]{6}\b/.test(source)) failures.push(`${rel}: raw hex literal; use a semantic color token`);
    }
}
walk(join(root,'resources/js'));
if (!existsSync(join(root,'resources/js/lib/design-tokens.ts'))) failures.push('Missing non-DOM document token helper');
if(failures.length){console.error(failures.join('\n'));process.exit(1);}
console.log(`PASS: ${filesChecked} TS/TSX files use semantic color tokens; Tailwind variables resolve.`);
console.log('PASS: light + dark primary, body, feedback pairs meet 4.5:1 contrast; signer identities meet 4.5:1.');
console.log('PASS: PDF paper, signature ink and signer annotations are theme-independent.');
