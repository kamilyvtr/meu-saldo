export type FinanceAccountType =
    | 'checking'
    | 'savings'
    | 'cash'
    | 'credit_card'
    | 'investment';

export type FinanceTransactionType = 'income' | 'expense';

export type FinanceAccount = {
    id: number;
    name: string;
    type: FinanceAccountType;
    type_label?: string;
    balance: number;
    color: string | null;
    institution?: string | null;
    is_active: boolean;
};

export type FinanceCategory = {
    id: number;
    name: string;
    type: FinanceTransactionType;
    color: string | null;
    icon: string | null;
};

export type FinanceTransaction = {
    id: number;
    description: string;
    type: FinanceTransactionType;
    amount: number;
    date: string;
    date_label: string;
    notes: string | null;
    account: {
        id: number;
        name: string;
        type: FinanceAccountType;
    } | null;
    category: {
        id: number;
        name: string;
        color: string | null;
        icon: string | null;
    } | null;
};

export type AccountTypeOption = {
    value: FinanceAccountType;
    label: string;
};

export type MonthOption = {
    value: string;
    label: string;
};

export type AccountFilters = {
    search: string;
    type: string;
    status: 'all' | 'active' | 'inactive';
};

export type TransactionFilters = {
    search: string;
    type: string;
    account_id: string;
    category_id: string;
    month: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type DashboardSummary = {
    available: number;
    card_debt: number;
    income: number;
    expense: number;
    savings: number;
    savings_rate: number;
    income_change: number | null;
    expense_change: number | null;
    accounts_count: number;
    active_accounts_count: number;
    has_accounts: boolean;
};

export type DashboardChartPoint = {
    date: string;
    label: string;
    income: number;
    expense: number;
    balance: number;
};
