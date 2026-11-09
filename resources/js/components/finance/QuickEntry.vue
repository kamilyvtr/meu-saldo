<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    CalendarDays,
    Check,
    CreditCard,
    Loader2,
    Plus,
    Wallet,
} from '@lucide/vue';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import { useQuickEntry } from '@/composables/useQuickEntry';
import { accountIcon } from '@/lib/accountIcons';
import { categoryIcon, categoryTint } from '@/lib/categoryIcons';
import {
    digitsToCents,
    formatAmount,
    sanitizeAmountDigits,
    today,
} from '@/lib/money';
import { index as accountsIndex } from '@/routes/accounts';
import { suggest as suggestCategory } from '@/routes/classification';
import {
    store as storeTransaction,
    update as updateTransaction,
} from '@/routes/transactions';
import type {
    FinanceAccount,
    FinanceCategory,
    FinanceTransactionType,
} from '@/types';

const { isOpen, editing, prefill, close } = useQuickEntry();
const page = usePage();

const accounts = computed(
    () => (page.props.quickAccounts ?? []) as FinanceAccount[],
);
const categories = computed(
    () => (page.props.quickCategories ?? []) as FinanceCategory[],
);

const amountInput = useTemplateRef<HTMLInputElement>('amountInput');

const amountDigits = ref('');

const form = useForm({
    description: '',
    type: 'expense' as FinanceTransactionType,
    account_id: null as number | null,
    category_id: null as number | null,
    date: today(),
    notes: '',
});

const filteredCategories = computed(() =>
    categories.value.filter((category) => category.type === form.type),
);

const selectedCategory = computed(() =>
    filteredCategories.value.find(
        (category) => category.id === form.category_id,
    ),
);

const suggestedCategory = ref<FinanceCategory | null>(null);

const visibleSuggestion = computed(() =>
    suggestedCategory.value &&
    filteredCategories.value.some(
        (category) => category.id === suggestedCategory.value?.id,
    )
        ? suggestedCategory.value
        : null,
);

let suggestionTimer: ReturnType<typeof setTimeout> | null = null;

const fetchSuggestion = (description: string) => {
    if (suggestionTimer) {
        clearTimeout(suggestionTimer);
    }

    if (description.trim().length < 3) {
        suggestedCategory.value = null;
        return;
    }

    suggestionTimer = setTimeout(async () => {
        try {
            const response = await fetch(
                suggestCategory.url({
                    query: {
                        description: description.trim(),
                        type: form.type,
                    },
                }),
                {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                },
            );

            if (!response.ok) {
                return;
            }

            const data = (await response.json()) as {
                category: FinanceCategory | null;
            };

            suggestedCategory.value = data.category;
        } catch {
            suggestedCategory.value = null;
        }
    }, 300);
};

const applySuggestion = () => {
    if (visibleSuggestion.value) {
        form.category_id = visibleSuggestion.value.id;
        suggestedCategory.value = null;
    }
};

watch(
    () => form.description,
    (value) => {
        fetchSuggestion(value);
    },
);

watch(
    () => form.type,
    () => {
        fetchSuggestion(form.description);
    },
);

const amountValue = computed(() =>
    formatAmount(digitsToCents(amountDigits.value)),
);

const canSubmit = computed(
    () => digitsToCents(amountDigits.value) > 0 && form.account_id !== null,
);

const title = computed(() =>
    editing.value ? 'Editar transação' : 'Nova transação',
);

const subtitle = computed(() =>
    editing.value
        ? 'Atualize os detalhes do lançamento.'
        : 'Registre em poucos toques.',
);

const accountPlaceholder = computed(() =>
    accounts.value.length > 0
        ? 'Selecione a conta'
        : 'Cadastre uma conta primeiro',
);

const resetForm = () => {
    form.clearErrors();
    form.description = '';
    form.type = prefill.value.type ?? 'expense';
    form.account_id =
        prefill.value.accountId ??
        accounts.value.find((account) => account.is_active)?.id ??
        accounts.value[0]?.id ??
        null;
    form.category_id = null;
    form.date = today();
    form.notes = '';
    amountDigits.value = editing.value ? String(editing.value.amount) : '';
};

watch(isOpen, (open) => {
    if (!open) {
        return;
    }

    if (editing.value) {
        form.description = editing.value.description;
        form.type = editing.value.type;
        form.account_id = editing.value.account?.id ?? null;
        form.category_id = editing.value.category?.id ?? null;
        form.date = editing.value.date;
        form.notes = editing.value.notes ?? '';
        amountDigits.value = String(editing.value.amount);
        form.clearErrors();
    } else {
        resetForm();
    }

    nextTick(() => {
        amountInput.value?.focus();
        amountInput.value?.select();
    });
});

watch(
    () => form.type,
    () => {
        const stillValid = filteredCategories.value.some(
            (category) => category.id === form.category_id,
        );

        if (!stillValid) {
            form.category_id = null;
        }
    },
);

const handleAmountInput = (event: Event) => {
    const target = event.target as HTMLInputElement;

    amountDigits.value = sanitizeAmountDigits(target.value);
    target.value = formatAmount(digitsToCents(amountDigits.value));
};

const selectCategory = (category: FinanceCategory) => {
    form.category_id = form.category_id === category.id ? null : category.id;
};

const submit = () => {
    if (!canSubmit.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        description:
            data.description.trim() ||
            selectedCategory.value?.name ||
            (data.type === 'income' ? 'Receita' : 'Gasto'),
        amount_cents: digitsToCents(amountDigits.value),
        notes: data.notes.trim() || null,
        category_id: data.category_id ?? null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => close(),
    };

    if (editing.value) {
        form.put(updateTransaction.url(editing.value.id), options);
    } else {
        form.post(storeTransaction.url(), options);
    }
};

const goToAccounts = () => {
    close();
    window.location.href = accountsIndex.url();
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => (value ? null : close())">
        <DialogContent
            :show-close-button="false"
            class="top-auto bottom-0 left-0 max-h-[92dvh] w-full max-w-lg translate-x-0 translate-y-0 gap-0 overflow-y-auto rounded-t-[28px] border-border p-0 pb-[env(safe-area-inset-bottom)] sm:top-1/2 sm:bottom-auto sm:left-1/2 sm:max-w-lg sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-3xl sm:pb-0 dark:border-white/10"
        >
            <DialogHeader
                class="sticky top-0 z-10 flex flex-row items-start justify-between gap-3 border-b border-border bg-background/95 px-5 py-4 backdrop-blur-xl dark:border-white/10"
            >
                <div class="min-w-0 text-left">
                    <DialogTitle class="text-base font-semibold">
                        {{ title }}
                    </DialogTitle>
                    <DialogDescription class="mt-0.5 text-xs">
                        {{ subtitle }}
                    </DialogDescription>
                </div>

                <div
                    class="inline-flex shrink-0 rounded-xl bg-muted p-1 text-xs font-semibold"
                >
                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 transition-colors"
                        :class="
                            form.type === 'expense'
                                ? 'bg-expense text-white shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="form.type = 'expense'"
                    >
                        <ArrowDownLeft class="size-3.5" />
                        Gasto
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 transition-colors"
                        :class="
                            form.type === 'income'
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="form.type = 'income'"
                    >
                        <ArrowUpRight class="size-3.5" />
                        Receita
                    </button>
                </div>
            </DialogHeader>

            <div class="space-y-5 px-5 py-5">
                <div>
                    <label
                        for="quick-amount"
                        class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                    >
                        Valor
                    </label>
                    <div
                        class="mt-1.5 flex items-baseline justify-center gap-2 rounded-2xl bg-surface-muted px-4 py-3 dark:bg-white/5"
                    >
                        <span class="text-lg font-medium text-muted-foreground"
                            >R$</span
                        >
                        <input
                            id="quick-amount"
                            ref="amountInput"
                            :value="amountValue"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            aria-label="Valor da transação"
                            class="w-full min-w-0 bg-transparent text-center text-4xl font-semibold tracking-tight tabular-nums outline-none placeholder:text-muted-foreground/50"
                            placeholder="0,00"
                            @input="handleAmountInput"
                        />
                    </div>
                    <InputError
                        class="mt-1.5 text-center"
                        :message="(form.errors as any).amount_cents"
                    />
                </div>

                <div class="grid gap-2">
                    <label for="quick-description" class="text-sm font-medium">
                        Descrição
                    </label>
                    <input
                        id="quick-description"
                        v-model="form.description"
                        type="text"
                        maxlength="255"
                        autocomplete="off"
                        placeholder="Ex.: Supermercado"
                        class="h-11 w-full rounded-xl border border-border bg-background px-3.5 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                        @keydown.enter.prevent="submit"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div
                    v-if="visibleSuggestion"
                    class="flex items-center justify-between gap-3 rounded-xl border border-primary/20 bg-primary/5 p-3"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-primary">
                            Sugestão de categoria
                        </p>
                        <p class="truncate text-sm font-semibold">
                            {{ visibleSuggestion.name }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        @click="applySuggestion"
                    >
                        Usar
                    </Button>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <label for="quick-account" class="text-sm font-medium">
                            Conta de origem
                        </label>
                        <div class="relative">
                            <Wallet
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <select
                                id="quick-account"
                                v-model="form.account_id"
                                class="h-11 w-full appearance-none rounded-xl border border-border bg-background pr-3 pl-9 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                            >
                                <option :value="null" disabled>
                                    {{ accountPlaceholder }}
                                </option>
                                <option
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name
                                    }}{{
                                        account.is_active ? '' : ' (inativa)'
                                    }}
                                </option>
                            </select>
                        </div>
                        <InputError :message="form.errors.account_id" />
                    </div>

                    <div class="grid gap-2">
                        <label for="quick-date" class="text-sm font-medium">
                            Data
                        </label>
                        <div class="relative">
                            <CalendarDays
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <input
                                id="quick-date"
                                v-model="form.date"
                                type="date"
                                class="h-11 w-full rounded-xl border border-border bg-background pr-3 pl-9 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                            />
                        </div>
                        <InputError :message="form.errors.date" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Categoria</span>
                        <span class="text-[11px] text-muted-foreground">
                            Opcional
                        </span>
                    </div>

                    <div
                        v-if="filteredCategories.length > 0"
                        class="grid max-h-52 grid-cols-3 gap-2 overflow-y-auto pr-0.5"
                    >
                        <button
                            v-for="category in filteredCategories"
                            :key="category.id"
                            type="button"
                            class="relative flex flex-col items-center gap-1.5 rounded-xl border px-2 py-2.5 text-[11px] font-medium transition-colors"
                            :class="
                                form.category_id === category.id
                                    ? 'border-primary bg-primary/5 text-foreground'
                                    : 'border-border text-muted-foreground hover:bg-muted dark:border-white/10'
                            "
                            @click="selectCategory(category)"
                        >
                            <span
                                class="flex size-8 items-center justify-center rounded-full"
                                :style="{
                                    color: categoryTint(category.color),
                                    backgroundColor: `${categoryTint(category.color)}1f`,
                                }"
                            >
                                <component
                                    :is="categoryIcon(category.icon)"
                                    class="size-4"
                                />
                            </span>
                            <span class="w-full truncate text-center">
                                {{ category.name }}
                            </span>
                            <span
                                v-if="form.category_id === category.id"
                                class="absolute top-1.5 right-1.5 flex size-4 items-center justify-center rounded-full bg-primary text-primary-foreground"
                            >
                                <Check class="size-2.5" stroke-width="3" />
                            </span>
                        </button>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-border p-4 text-center text-xs text-muted-foreground dark:border-white/10"
                    >
                        Nenhuma categoria disponível para este tipo.
                    </div>
                    <InputError :message="form.errors.category_id" />
                </div>

                <div class="grid gap-2">
                    <label for="quick-notes" class="text-sm font-medium">
                        Observação
                        <span class="text-[11px] text-muted-foreground">
                            (opcional)
                        </span>
                    </label>
                    <input
                        id="quick-notes"
                        v-model="form.notes"
                        type="text"
                        maxlength="1000"
                        autocomplete="off"
                        placeholder="Algum detalhe que ajude a lembrar"
                        class="h-10 w-full rounded-xl border border-border bg-background px-3.5 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div
                    v-if="accounts.length === 0"
                    class="flex items-start gap-2 rounded-xl bg-primary/10 p-3 text-xs leading-relaxed text-primary"
                >
                    <CreditCard class="mt-0.5 size-4 shrink-0" />
                    <div>
                        Você precisa de pelo menos uma conta para registrar
                        transações.
                        <button
                            type="button"
                            class="font-semibold underline underline-offset-2"
                            @click="goToAccounts"
                        >
                            Criar conta agora
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="sticky bottom-0 border-t border-border bg-background/95 px-5 py-4 backdrop-blur-xl dark:border-white/10"
            >
                <Button
                    type="button"
                    size="lg"
                    class="h-12 w-full text-sm font-semibold"
                    :disabled="!canSubmit || form.processing"
                    @click="submit"
                >
                    <Loader2
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    <Plus v-else-if="!editing" class="size-4" />
                    <Check v-else class="size-4" />
                    {{ editing ? 'Salvar alterações' : 'Salvar transação' }}
                </Button>
                <p
                    v-if="canSubmit"
                    class="mt-2 text-center text-[11px] text-muted-foreground"
                >
                    A conta será atualizada automaticamente.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
