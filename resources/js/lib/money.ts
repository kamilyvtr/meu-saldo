const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const amountFormatter = new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * Format a value stored in cents as Brazilian currency.
 */
export function formatCurrency(cents: number): string {
    return currencyFormatter.format((cents ?? 0) / 100);
}

/**
 * Format a value stored in cents without the currency symbol.
 */
export function formatAmount(cents: number): string {
    return amountFormatter.format((cents ?? 0) / 100);
}

/**
 * Format a transaction amount with its sign.
 */
export function formatSignedCurrency(
    cents: number,
    type: 'income' | 'expense',
): string {
    return `${type === 'income' ? '+' : '-'}${formatCurrency(Math.abs(cents))}`;
}

/**
 * Turn user typed digits into integer cents.
 */
export function digitsToCents(digits: string): number {
    const normalized = digits.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

    if (normalized === '') {
        return 0;
    }

    return Number(normalized);
}

/**
 * Keep only digits and cap the amount at a safe integer range.
 */
export function sanitizeAmountDigits(digits: string): string {
    return digits
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/^0+(?=\d)/, '');
}

/**
 * Format a percentage with a leading sign.
 */
export function formatChange(value: number | null): string {
    if (value === null) {
        return 'Sem base no mês anterior';
    }

    const formatted = Math.abs(value).toFixed(1).replace('.', ',');

    return `${value >= 0 ? '+' : '-'}${formatted}% vs. mês anterior`;
}

/**
 * Return today's date in YYYY-MM-DD format using the local timezone.
 */
export function today(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}
