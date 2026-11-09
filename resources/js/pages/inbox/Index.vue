<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Inbox, Loader2 } from '@lucide/vue';
import { ref } from 'vue';
import AppCard from '@/components/AppCard.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { dashboard } from '@/routes';
import { categorize } from '@/routes/transactions';
import { index as transactionsIndex } from '@/routes/transactions';
import type { FinanceCategory, FinanceTransaction } from '@/types';

const props = defineProps<{
    transactions: {
        data: Array<{
            id: number;
            description: string;
            type: 'income' | 'expense';
            amount: number;
            date: string;
            date_label: string;
            account: { id: number; name: string; type: string } | null;
        }>;
        total: number;
    };
    categories: FinanceCategory[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Caixa de entrada', href: transactionsIndex() },
        ],
    },
});

const assigning = ref<number | null>(null);

const assignCategory = (transactionId: number, categoryId: number | null) => {
    assigning.value = transactionId;
    router.patch(
        categorize.url(transactionId),
        { category_id: categoryId },
        {
            preserveScroll: true,
            onFinish: () => {
                assigning.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Caixa de entrada" />

    <div class="space-y-6">
        <header>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                Caixa de entrada
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Lançamentos salvos sem categoria. Atribua uma categoria
                rapidamente.
            </p>
        </header>

        <AppCard padding="none">
            <div
                v-if="props.transactions.data.length > 0"
                class="divide-y divide-border dark:divide-white/10"
            >
                <div
                    v-for="transaction in props.transactions.data"
                    :key="transaction.id"
                    class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:px-5"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ transaction.description }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ transaction.account?.name ?? 'Sem conta' }} ·
                            {{ transaction.date_label }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Select
                            :model-value="null"
                            @update:model-value="
                                (value) =>
                                    assignCategory(
                                        transaction.id,
                                        Number(value),
                                    )
                            "
                        >
                            <SelectTrigger class="w-48">
                                <SelectValue placeholder="Escolher categoria" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in categories.filter(
                                        (c) => c.type === transaction.type,
                                    )"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Loader2
                            v-if="assigning === transaction.id"
                            class="size-4 animate-spin"
                        />
                        <Check v-else class="size-4 text-muted-foreground" />
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
                    <Inbox class="size-7" />
                </span>
                <p class="text-base font-semibold">Caixa de entrada vazia</p>
                <p class="text-sm text-muted-foreground">
                    Todos os lançamentos já têm categoria.
                </p>
            </div>
        </AppCard>
    </div>
</template>
