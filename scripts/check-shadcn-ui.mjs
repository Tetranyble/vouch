import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { resolve, join, relative } from 'node:path';
import ts from 'typescript';

const root = resolve(import.meta.dirname, '..');
const frontend = join(root, 'resources/js');
const legacy = [
    'ApplicationLogo', 'Checkbox', 'DangerButton', 'Dropdown', 'InputError',
    'InputLabel', 'Modal', 'NavLink', 'PrimaryButton', 'ResponsiveNavLink',
    'SecondaryButton', 'TextInput',
];
const forbiddenTags = new Set(['button', 'input', 'textarea', 'select', 'label']);
const failures = [];
let screens = 0;
let filesChecked = 0;

for (const name of legacy) {
    if (existsSync(join(frontend, 'Components', `${name}.tsx`))) {
        failures.push(`Obsolete Breeze component still present: ${name}.tsx`);
    }
}
const configuration = JSON.parse(readFileSync(join(root, 'components.json'), 'utf8'));
if (!configuration.tsx || configuration.aliases?.ui !== '@/Components/ui') {
    failures.push('components.json must generate TypeScript shadcn components at @/Components/ui');
}
function walk(folder) {
    for (const entry of readdirSync(folder, { withFileTypes: true })) {
        const file = join(folder, entry.name);
        if (entry.isDirectory()) { walk(file); continue; }
        if (!entry.name.endsWith('.tsx') && !entry.name.endsWith('.ts')) continue;
        const source = readFileSync(file, 'utf8');
        const rel = relative(root, file);
        const shadcnPrimitive = rel.startsWith('resources/js/Components/ui/');
        const ast = ts.createSourceFile(file, source, ts.ScriptTarget.Latest, true,
            file.endsWith('.tsx') ? ts.ScriptKind.TSX : ts.ScriptKind.TS);
        filesChecked++;
        if (rel.startsWith('resources/js/Pages/') || rel.startsWith('resources/js/Layouts/')) screens++;
        for (const diag of ast.parseDiagnostics) {
            failures.push(`${rel}: ${ts.flattenDiagnosticMessageText(diag.messageText, ' ')}`);
        }
        function visit(node) {
            if (ts.isImportDeclaration(node) && ts.isStringLiteral(node.moduleSpecifier)) {
                const module = node.moduleSpecifier.text;
                if (module === '@headlessui/react') failures.push(`${rel}: Headless UI / Breeze import is forbidden`);
                if (legacy.some((name) => module.endsWith(`/Components/${name}`))) {
                    failures.push(`${rel}: deprecated Breeze component import ${module}`);
                }
            }
            if (!shadcnPrimitive && (ts.isJsxOpeningElement(node) || ts.isJsxSelfClosingElement(node))) {
                const name = node.tagName.getText(ast);
                if (forbiddenTags.has(name)) {
                    const pos = ast.getLineAndCharacterOfPosition(node.getStart(ast));
                    failures.push(`${rel}:${pos.line+1}: raw <${name}>; use @/Components/ui instead`);
                }
            }
            ts.forEachChild(node, visit);
        }
        visit(ast);
    }
}
walk(frontend);
if (failures.length) {
    console.error(failures.join('\n'));
    process.exit(1);
}
console.log(`PASS: ${filesChecked} frontend TypeScript files (${screens} page/layout files) comply with shadcn source policy.`);
console.log('Inertia.js remains an intentional navigation/rendering dependency, not a UI component library.');
