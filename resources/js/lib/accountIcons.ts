import {
    Banknote,
    CreditCard,
    Landmark,
    PiggyBank,
    TrendingUp,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import type { FinanceAccountType } from '@/types';

const accountIcons: Record<FinanceAccountType, LucideIcon> = {
    checking: Landmark,
    savings: PiggyBank,
    cash: Banknote,
    credit_card: CreditCard,
    investment: TrendingUp,
};

/**
 * Resolve an account type into a Lucide component.
 */
export function accountIcon(type: FinanceAccountType): LucideIcon {
    return accountIcons[type] ?? Landmark;
}
