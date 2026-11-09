import { ref } from 'vue';
import type { FinanceTransaction, FinanceTransactionType } from '@/types';

type QuickEntryPrefill = {
    type: FinanceTransactionType | null;
    accountId: number | null;
};

type QuickEntryOptions = {
    transaction?: FinanceTransaction;
    type?: FinanceTransactionType;
    accountId?: number;
};

const isOpen = ref(false);
const editing = ref<FinanceTransaction | null>(null);
const prefill = ref<QuickEntryPrefill>({
    type: null,
    accountId: null,
});

/**
 * Shared quick entry state so any screen can open the same modal.
 */
export function useQuickEntry() {
    const open = (options: QuickEntryOptions = {}): void => {
        editing.value = options.transaction ?? null;
        prefill.value = {
            type: options.type ?? null,
            accountId: options.accountId ?? null,
        };
        isOpen.value = true;
    };

    const close = (): void => {
        isOpen.value = false;
    };

    return {
        isOpen,
        editing,
        prefill,
        open,
        close,
    };
}
