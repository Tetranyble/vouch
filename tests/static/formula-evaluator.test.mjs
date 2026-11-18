import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const source = readFileSync(new URL('../../resources/js/lib/formula.ts', import.meta.url), 'utf8');
const moduleCode = ts.transpileModule(source, {
    fileName: 'formula.ts',
    compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022, strict: true },
}).outputText;
const { evaluateFormula } = await import(`data:text/javascript;charset=utf-8,${encodeURIComponent(moduleCode)}`);
const fields = [{ uuid: 'subtotal', name: 'Subtotal' }, { uuid: 'tax', name: 'Tax' }];
const values = { subtotal: '120.00', tax: 12 };
assert.equal(evaluateFormula('{{Subtotal}} + {{Tax}} * 2', fields, values), '144');
assert.equal(evaluateFormula('({Subtotal} - {Tax}) / 2', fields, values), '54');
assert.equal(evaluateFormula('-(5 + 2) * 3', fields, values), '-21');
assert.equal(evaluateFormula('2 / 0', fields, values), '');
assert.equal(evaluateFormula('Math.constructor.constructor("alert(1)")()', fields, values), '');
assert.equal(evaluateFormula('1;globalThis.evil=true', fields, values), '');
assert.equal(evaluateFormula('5 ** 2', fields, values), '');
assert.equal(evaluateFormula('', fields, values), '');
console.log('PASS formula calculator (builder placeholders, precedence, invalid input, code injection)');
