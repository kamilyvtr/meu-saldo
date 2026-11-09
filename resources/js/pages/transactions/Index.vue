<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Loader2,
    Pencil,
    Plus,
    Search,
    Trash2,
    TrendingDown,
    TrendingUp,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import AppCard from '@/components/AppCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useQuickEntry } from '@/composables/useQuickEntry';
import { categoryIcon, categoryTint } from '@/lib/categoryIcons';
import { formatCurrency, formatSignedCurrency } from '@/lib/money';
import { dashboard } from '@/routes';
import { destroy, index as transactionsIndex } from '@/routes/transactions';
import type {
    FinanceAccountType,
    FinanceCategory,
    FinanceTransaction,
    FinanceTransactionType,
    MonthOption,
    Paginated,
    TransactionFilters,
} from '@/types';

type AccountOption = {
    id: number;
    name: string;
    type: FinanceAccountType;
    is_active: boolean;
};

const props = defineProps<{
    transactions: Paginated<FinanceTransaction>;
    filters: TransactionFilters;
    summary: {
        income: number;
        expense: number;
        balance: number;
    };
    accounts: AccountOption[];
    categories: FinanceCategory[];
    months: MonthOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Início',
                href: dashboard(),
            },
            {
                title: 'Transações',
                href: transactionsIndex(),
            },
        ],
    },
});

const { open: openQuickEntry } = useQuickEntry();

const filters = reactive<TransactionFilters>({
    search: props.filters.search,
    type: props.filters.type,
    account_id: props.filters.account_id,
    category_id: props.filters.category_id,
    month: props.filters.month,
});

const deleteTarget = ref<FinanceTransaction | null>(null);
const isDeleting = ref(false);

const typeOptions: Array<{
    value: '' | FinanceTransactionType;
    label: string;
}> = [
    { value: '', label: 'Todas' },
    { value: 'income', label: 'Receitas' },
    { value: 'expense', label: 'Despesas' },
];

const applyFilters = () => {
    router.get(
        transactionsIndex.url(),
        {
            search: filters.search,
            type: filters.type,
            account_id: filters.account_id,
            category_id: filters.category_id,
            month: filters.month,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const setType = (type: '' | FinanceTransactionType) => {
    filters.type = type;
    applyFilters();
};

const clearFilters = () => {
    filters.search = '';
    filters.type = '';
    filters.account_id = '';
    filters.category_id = '';
    filters.month = '';
    applyFilters();
};

const hasFilters = computed(
    () =>
        filters.search !== '' ||
        filters.type !== '' ||
        filters.account_id !== '' ||
        filters.category_id !== '' ||
        filters.month !== '',
);

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    isDeleting.value = true;

    router.delete(destroy.url(deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Transações" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Transações
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Cada entrada e saída, organizada do seu jeito.
                </p>
            </div>

            <Button class="self-start sm:self-auto" @click="openQuickEntry()">
                <Plus class="size-4" />
                Nova transação
            </Button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <AppCard padding="lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        Receitas
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <TrendingUp class="size-5" />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight">
                    {{ formatCurrency(summary.income) }}
                </p>
            </AppCard>

            <AppCard padding="lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        Despesas
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-expense/10 text-expense"
                    >
                        <TrendingDown class="size-5" />
                    </span>
                </div>
                <p
                    class="mt-3 text-2xl font-semibold tracking-tight text-expense"
                >
                    {{ formatCurrency(summary.expense) }}
                </p>
            </AppCard>

            <AppCard padding="lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        Saldo do período
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl"
                        :class="
                            summary.balance < 0
                                ? 'bg-expense/10 text-expense'
                                : 'bg-brand-accent/10 text-brand-accent'
                        "
                    >
                        <Wallet class="size-5" />
                    </span>
                </div>
                <p
                    class="mt-3 text-2xl font-semibold tracking-tight"
                    :class="summary.balance < 0 ? 'text-expense' : ''"
                >
                    {{ formatCurrency(summary.balance) }}
                </p>
            </AppCard>
        </section>

        <AppCard padding="md">
            <form class="space-y-3" @submit.prevent="applyFilters">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            v-model="filters.search"
                            type="search"
                            placeholder="Buscar por descrição"
                            aria-label="Buscar transações"
                            class="h-10 w-full rounded-xl border border-border bg-background pr-3 pl-9 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                        />
                    </div>

                    <div
                        class="inline-flex w-fit rounded-xl bg-muted p-1 text-xs font-semibold"
                    >
                        <button
                            v-for="option in typeOptions"
                            :key="option.value"
                            type="button"
                            class="rounded-lg px-3 py-1.5 transition-colors"
                            :class="
                                filters.type === option.value
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="setType(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <Button type="submit" variant="secondary"> Filtrar </Button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <select
                        v-model="filters.account_id"
                        aria-label="Filtrar por conta"
                        class="h-10 rounded-xl border border-border bg-background px-3 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                        @change="applyFilters"
                    >
                        <option value="">Todas as contas</option>
                        <option
                            v-for="account in accounts"
                            :key="account.id"
                            :value="String(account.id)"
                        >
                            {{ account.name
                            }}{{ account.is_active ? '' : ' (inativa)' }}
                        </option>
                    </select>

                    <select
                        v-model="filters.category_id"
                        aria-label="Filtrar por categoria"
                        class="h-10 rounded-xl border border-border bg-background px-3 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                        @change="applyFilters"
                    >
                        <option value="">Todas as categorias</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <select
                        v-model="filters.month"
                        aria-label="Filtrar por mês"
                        class="h-10 rounded-xl border border-border bg-background px-3 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                        @change="applyFilters"
                    >
                        <option value="">Todos os meses</option>
                        <option
                            v-for="monthOption in months"
                            :key="monthOption.value"
                            :value="monthOption.value"
                        >
                            {{ monthOption.label }}
                        </option>
                    </select>

                    <Button
                        type="button"
                        variant="ghost"
                        class="justify-start"
                        :disabled="!hasFilters"
                        @click="clearFilters"
                    >
                        <X class="size-4" />
                        Limpar filtros
                    </Button>
                </div>
            </form>
        </AppCard>

        <AppCard padding="none">
            <div
                v-if="transactions.data.length > 0"
                class="divide-y divide-border dark:divide-white/10"
            >
                <div
                    v-for="transaction in transactions.data"
                    :key="transaction.id"
                    class="flex items-center gap-3 px-4 py-3.5 sm:px-5"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                        :style="{
                            color: categoryTint(transaction.category?.color),
                            backgroundColor: `${categoryTint(transaction.category?.color)}1f`,
                        }"
                    >
                        <component
                            :is="categoryIcon(transaction.category?.icon)"
                            class="size-5"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ transaction.description }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{
                                transaction.category?.name ??
                                (transaction.type === 'income'
                                    ? 'Receita'
                                    : 'Gasto')
                            }}
                            <template v-if="transaction.account">
                                · {{ transaction.account.name }}
                            </template>
                            · {{ transaction.date_label }}
                        </p>
                    </div>

                    <p
                        class="shrink-0 text-sm font-semibold tabular-nums"
                        :class="
                            transaction.type === 'income'
                                ? 'text-primary'
                                : 'text-foreground'
                        "
                    >
                        {{
                            formatSignedCurrency(
                                transaction.amount,
                                transaction.type,
                            )
                        }}
                    </p>

                    <div class="flex shrink-0 items-center gap-0.5">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Editar ${transaction.description}`"
                            @click="openQuickEntry({ transaction })"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            :aria-label="`Excluir ${transaction.description}`"
                            @click="deleteTarget = transaction"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center"
            >
                <span
                    class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <ArrowLeftRight class="size-7" />
                </span>
                <div>
                    <p class="text-base font-semibold">
                        Nenhuma transação encontrada
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            hasFilters
                                ? 'Tente ajustar os filtros para ver outros lançamentos.'
                                : 'Registre sua primeira entrada ou saída.'
                        }}
                    </p>
                </div>
                <Button
                    v-if="hasFilters"
                    variant="outline"
                    @click="clearFilters"
                >
                    Limpar filtros
                </Button>
                <Button v-else @click="openQuickEntry()">
                    <Plus class="size-4" />
                    Nova transação
                </Button>
            </div>

            <div
                v-if="transactions.total > 0"
                class="flex flex-col gap-3 border-t border-border px-4 py-3 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-white/10"
            >
                <div class="flex items-center gap-1.5">
                    <CalendarDays class="size-3.5" />
                    Mostrando
                    <span class="font-semibold text-foreground">
                        {{ transactions.from ?? 0 }}–{{ transactions.to ?? 0 }}
                    </span>
                    de
                    <span class="font-semibold text-foreground">
                        {{ transactions.total }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="transactions.prev_page_url"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <Link
                            :href="transactions.prev_page_url"
                            preserve-scroll
                            preserve-state
                        >
                            <ChevronLeft class="size-4" />
                            Anterior
                        </Link>
                    </Button>
                    <Button v-else variant="outline" size="sm" disabled>
                        <ChevronLeft class="size-4" />
                        Anterior
                    </Button>

                    <span class="px-1 font-medium text-foreground">
                        {{ transactions.current_page }} /
                        {{ transactions.last_page }}
                    </span>

                    <Button
                        v-if="transactions.next_page_url"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <Link
                            :href="transactions.next_page_url"
                            preserve-scroll
                            preserve-state
                        >
                            Próxima
                            <ChevronRight class="size-4" />
                        </Link>
                    </Button>
                    <Button v-else variant="outline" size="sm" disabled>
                        Próxima
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </AppCard>

        <Dialog
            :open="deleteTarget !== null"
            @update:open="(value) => (value ? null : (deleteTarget = null))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Excluir transação</DialogTitle>
                    <DialogDescription>
                        Tem certeza de que deseja excluir
                        <span class="font-semibold text-foreground">
                            {{ deleteTarget?.description }}
                        </span>
                        ? O saldo da conta será ajustado novamente. Esta ação
                        não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="size-4 animate-spin"
                        />
                        <Trash2 v-else class="size-4" />
                        Excluir transação
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
