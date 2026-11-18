import { readdirSync, readFileSync, existsSync } from 'node:fs';
import { join, resolve, dirname, extname } from 'node:path';
import ts from 'typescript';

const root = resolve(import.meta.dirname, '..');
const frontend = join(root, 'resources/js');
const targetFiles = [join(root, 'vite.config.ts'), join(root, 'tailwind.config.ts')];
function walk(folder) {
    for (const entry of readdirSync(folder, { withFileTypes: true })) {
        const file = join(folder, entry.name);
        if (entry.isDirectory()) walk(file);
        else if (/\.(ts|tsx|js|jsx)$/.test(entry.name) && !entry.name.endsWith('.d.ts')) targetFiles.push(file);
    }
}
walk(frontend);
const errors = [];
let transpiled = 0;
for (const file of targetFiles) {
    if (/\.(js|jsx)$/.test(file)) {
        errors.push(`${file}: first-party frontend source must use TypeScript`);
        continue;
    }
    const content = readFileSync(file, 'utf8');
    const result = ts.transpileModule(content, {
        fileName: file,
        reportDiagnostics: true,
        compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022, jsx: ts.JsxEmit.ReactJSX },
    });
    for (const error of result.diagnostics ?? []) {
        errors.push(`${file}: ${ts.flattenDiagnosticMessageText(error.messageText, '\n')}`);
    }
    transpiled++;
    const sourceFile = ts.createSourceFile(file, content, ts.ScriptTarget.Latest, true, file.endsWith('.tsx') ? ts.ScriptKind.TSX : ts.ScriptKind.TS);
    function visit(node) {
        if ((ts.isImportDeclaration(node) || ts.isExportDeclaration(node)) && node.moduleSpecifier && ts.isStringLiteral(node.moduleSpecifier)) {
            checkSpecifier(node.moduleSpecifier.text);
        }
        if (ts.isCallExpression(node) && node.arguments.length === 1 && ts.isStringLiteral(node.arguments[0]) && node.expression.kind === ts.SyntaxKind.ImportKeyword) {
            checkSpecifier(node.arguments[0].text);
        }
        ts.forEachChild(node, visit);
    }
    function checkSpecifier(specifier) {
        if (!specifier.startsWith('.')) return;
        const base = resolve(dirname(file), specifier);
        if (![base, `${base}.ts`, `${base}.tsx`, `${base}.json`, join(base, 'index.ts'), join(base, 'index.tsx')].some(existsSync)) {
            errors.push(`${file}: unresolved relative import ${specifier}`);
        }
    }
    visit(sourceFile);
}
if (errors.length) {
    for (const error of errors) console.error(error);
    process.exit(1);
}
console.log(`PASS: ${transpiled} first-party TypeScript modules transpile without syntax errors and relative imports resolve.`);
console.log('Note: this is NOT a substitute for npm run typecheck or npm run build.');
