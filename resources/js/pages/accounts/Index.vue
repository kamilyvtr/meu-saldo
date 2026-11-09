<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CreditCard,
    Loader2,
    Pencil,
    Plus,
    Search,
    Trash2,
    Wallet,
    X,
} from '@lucide/vue';
import { reactive, ref } from 'vue';
import AccountFormDialog from '@/components/finance/AccountFormDialog.vue';
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
import { accountIcon } from '@/lib/accountIcons';
import { formatCurrency } from '@/lib/money';
import { dashboard } from '@/routes';
import { destroy, index as accountsIndex } from '@/routes/accounts';
import type {
    AccountFilters,
    AccountTypeOption,
    FinanceAccount,
} from '@/types';

const props = defineProps<{
    accounts: FinanceAccount[];
    filters: AccountFilters;
    types: AccountTypeOption[];
    summary: {
        available: number;
        card_debt: number;
        active_count: number;
        total_count: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Início',
                href: dashboard(),
            },
            {
                title: 'Contas',
                href: accountsIndex(),
            },
        ],
    },
});

const filters = reactive<AccountFilters>({
    search: props.filters.search,
    type: props.filters.type,
    status: props.filters.status,
});

const formOpen = ref(false);
const editTarget = ref<FinanceAccount | null>(null);
const deleteTarget = ref<FinanceAccount | null>(null);
const isDeleting = ref(false);

const applyFilters = () => {
    router.get(
        accountsIndex.url(),
        {
            search: filters.search,
            type: filters.type,
            status: filters.status,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filters.search = '';
    filters.type = '';
    filters.status = 'all';
    applyFilters();
};

const openCreate = () => {
    editTarget.value = null;
    formOpen.value = true;
};

const openEdit = (account: FinanceAccount) => {
    editTarget.value = account;
    formOpen.value = true;
};

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

const balanceLabel = (account: FinanceAccount) =>
    account.type === 'credit_card' ? 'Fatura atual' : 'Saldo';

const balanceValue = (account: FinanceAccount) =>
    account.type === 'credit_card'
        ? Math.abs(account.balance)
        : account.balance;
</script>

<template>
    <Head title="Contas" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Contas
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Tudo o que entra e sai passa por uma destas contas.
                </p>
            </div>

            <Button class="self-start sm:self-auto" @click="openCreate">
                <Plus class="size-4" />
                Nova conta
            </Button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <AppCard padding="lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        Disponível hoje
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Wallet class="size-5" />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight">
                    {{ formatCurrency(summary.available) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Contas ativas, sem cartão de crédito
                </p>
            </AppCard>

            <AppCard padding="lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        Devo no cartão
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-expense/10 text-expense"
                    >
                        <CreditCard class="size-5" />
                    </span>
                </div>
                <p
                    class="mt-3 text-2xl font-semibold tracking-tight"
                    :class="summary.card_debt > 0 ? 'text-expense' : ''"
                >
                    {{ formatCurrency(summary.card_debt) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Faturas em aberto
                </p>
            </AppCard>

            <AppCard padding="lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        Contas ativas
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-brand-accent/10 text-brand-accent"
                    >
                        <Wallet class="size-5" />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight">
                    {{ summary.active_count }}
                    <span class="text-base font-medium text-muted-foreground">
                        de {{ summary.total_count }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Somente ativas entram no saldo
                </p>
            </AppCard>
        </section>

        <AppCard padding="md">
            <form
                class="flex flex-col gap-3 lg:flex-row lg:items-center"
                @submit.prevent="applyFilters"
            >
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Buscar por nome ou instituição"
                        aria-label="Buscar contas"
                        class="h-10 w-full rounded-xl border border-border bg-background pr-3 pl-9 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                    />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <select
                        v-model="filters.type"
                        aria-label="Filtrar por tipo"
                        class="h-10 rounded-xl border border-border bg-background px-3 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                        @change="applyFilters"
                    >
                        <option value="">Todos os tipos</option>
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>

                    <div
                        class="inline-flex rounded-xl bg-muted p-1 text-xs font-semibold"
                    >
                        <button
                            v-for="option in [
                                { value: 'all', label: 'Todas' },
                                { value: 'active', label: 'Ativas' },
                                { value: 'inactive', label: 'Inativas' },
                            ] as const"
                            :key="option.value"
                            type="button"
                            class="rounded-lg px-3 py-1.5 transition-colors"
                            :class="
                                filters.status === option.value
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="
                                filters.status = option.value;
                                applyFilters();
                            "
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <Button type="submit" variant="secondary"> Filtrar </Button>
                    <Button type="button" variant="ghost" @click="clearFilters">
                        <X class="size-4" />
                        Limpar
                    </Button>
                </div>
            </form>
        </AppCard>

        <section
            v-if="accounts.length > 0"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <AppCard
                v-for="account in accounts"
                :key="account.id"
                class="flex flex-col"
                padding="lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-2xl"
                            :style="{
                                color: account.color ?? '#10b981',
                                backgroundColor: `${account.color ?? '#10b981'}1f`,
                            }"
                        >
                            <component
                                :is="accountIcon(account.type)"
                                class="size-5"
                            />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                {{ account.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{
                                    account.institution ||
                                    account.type_label ||
                                    account.type
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Editar ${account.name}`"
                            @click="openEdit(account)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            :aria-label="`Excluir ${account.name}`"
                            @click="deleteTarget = account"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-[11px] font-medium text-muted-foreground">
                        {{ balanceLabel(account) }}
                    </p>
                    <p
                        class="mt-1 text-2xl font-semibold tracking-tight tabular-nums"
                        :class="balanceValue(account) < 0 ? 'text-expense' : ''"
                    >
                        {{ formatCurrency(balanceValue(account)) }}
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground"
                    >
                        {{ account.type_label }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-medium"
                        :class="
                            account.is_active
                                ? 'bg-primary/10 text-primary'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="
                                account.is_active
                                    ? 'bg-primary'
                                    : 'bg-muted-foreground'
                            "
                        />
                        {{ account.is_active ? 'Ativa' : 'Inativa' }}
                    </span>
                </div>
            </AppCard>
        </section>

        <AppCard
            v-else
            class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center"
            padding="lg"
        >
            <span
                class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
            >
                <Wallet class="size-7" />
            </span>
            <div>
                <p class="text-base font-semibold">Nenhuma conta encontrada</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        summary.total_count === 0
                            ? 'Cadastre sua primeira conta para começar.'
                            : 'Tente ajustar os filtros para ver outras contas.'
                    }}
                </p>
            </div>
            <Button v-if="summary.total_count === 0" @click="openCreate">
                <Plus class="size-4" />
                Criar primeira conta
            </Button>
            <Button v-else variant="outline" @click="clearFilters">
                Limpar filtros
            </Button>
        </AppCard>

        <AccountFormDialog
            v-model:open="formOpen"
            :account="editTarget"
            :types="types"
        />

        <Dialog
            :open="deleteTarget !== null"
            @update:open="(value) => (value ? null : (deleteTarget = null))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Excluir conta</DialogTitle>
                    <DialogDescription>
                        Tem certeza de que deseja excluir
                        <span class="font-semibold text-foreground">
                            {{ deleteTarget?.name }}
                        </span>
                        ? As transações vinculadas também serão removidas. Esta
                        ação não pode ser desfeita.
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
                        Excluir conta
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
