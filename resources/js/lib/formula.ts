/**
 * Numeric formula evaluation for signing forms. Never evaluate template-defined
 * expressions as JavaScript: a template is not trusted executable code.
 */
export interface FormulaField {
    uuid: string;
    name?: string | null;
}

export function evaluateFormula(
    expression: string | null | undefined,
    fields: readonly FormulaField[],
    values: Record<string, unknown>,
): string {
    if (typeof expression !== 'string' || expression.trim() === '' || expression.length > 1024) {
        return '';
    }

    // Builder inserts {{Field Name}}. Support the earlier {Field Name} syntax
    // for existing templates without allowing replacement text to become code.
    const expanded = expression.replace(/\{\{([^{}]+)\}\}|\{([^{}]+)\}/g, (_match, doubleName: string | undefined, singleName: string | undefined) => {
        const name = (doubleName ?? singleName ?? '').trim();
        const field = fields.find((f) => (f.name || f.uuid) === name || f.uuid === name);
        const raw = field ? values[field.uuid] : 0;
        const number = Number.parseFloat(String(raw ?? 0));
        return Number.isFinite(number) ? String(number) : '0';
    });
    const compact = expanded.replace(/\s+/g, '');
    if (compact.length === 0 || compact.length > 2048) return '';

    const tokens = compact.match(/(?:\d+(?:\.\d*)?|\.\d+)|[()+*/%-]/g);
    if (!tokens || tokens.length > 256 || tokens.join('') !== compact) return '';

    let cursor = 0;
    const next = (): string | undefined => tokens[cursor];
    const consume = (): string | undefined => tokens[cursor++];

    const primary = (): number => {
        if (next() === '+' || next() === '-') {
            return (consume() === '-' ? -1 : 1) * primary();
        }
        if (next() === '(') {
            consume();
            const result = sum();
            if (consume() !== ')') throw new Error('Missing closing parenthesis');
            return result;
        }
        const token = consume();
        if (!token || !/^(?:\d+(?:\.\d*)?|\.\d+)$/.test(token)) {
            throw new Error('Expected a number');
        }
        return Number(token);
    };
    const product = (): number => {
        let result = primary();
        while (next() === '*' || next() === '/' || next() === '%') {
            const operator = consume();
            const rhs = primary();
            result = operator === '*' ? result * rhs : operator === '/' ? result / rhs : result % rhs;
        }
        return result;
    };
    const sum = (): number => {
        let result = product();
        while (next() === '+' || next() === '-') {
            const operator = consume();
            const rhs = product();
            result = operator === '+' ? result + rhs : result - rhs;
        }
        return result;
    };

    try {
        const result = sum();
        if (cursor !== tokens.length || !Number.isFinite(result)) return '';
        return String(Math.round(result * 100) / 100);
    } catch {
        return '';
    }
}
