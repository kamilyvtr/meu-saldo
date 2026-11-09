<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Loader2, Pencil, Plus, Search, Target, Trash2, X } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
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
import InputError from '@/components/InputError.vue';
import { formatCurrency } from '@/lib/money';
import { dashboard } from '@/routes';
import {
    destroy,
    index as budgetsIndex,
    store,
    update,
} from '@/routes/budgets';

const props = defineProps<{
    budgets: any[];
    categories: any[];
    month: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Orçamentos', href: budgetsIndex() },
        ],
    },
});

const filters = reactive({ month: props.month });
const formOpen = ref(false);
const editTarget = ref<any>(null);
const deleteTarget = ref<any>(null);
const isDeleting = ref(false);

const form = useForm({
    category_id: '' as any,
    month: props.month,
    limit_cents: 0 as number,
});

const limitDigits = ref('');

const applyFilters = () => {
    router.get(
        budgetsIndex.url(),
        { month: filters.month },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(
    () => props.month,
    (value) => {
        filters.month = value;
    },
);

watch(
    () => formOpen.value,
    (open) => {
        if (!open) return;
        form.clearErrors();
        if (editTarget.value) {
            form.category_id = editTarget.value.category_id;
            form.month = editTarget.value.month;
            form.limit_cents = editTarget.value.limit_cents;
            limitDigits.value = String(editTarget.value.limit_cents);
        } else {
            form.reset();
            form.month = filters.month || props.month;
            limitDigits.value = '';
        }
    },
);

const openCreate = () => {
    editTarget.value = null;
    formOpen.value = true;
};

const openEdit = (budget: any) => {
    editTarget.value = budget;
    formOpen.value = true;
};

const submit = () => {
    form.transform((data) => ({
        category_id: Number(data.category_id),
        month: data.month,
        limit_cents: Number(limitDigits.value || 0),
    }));
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    };
    if (editTarget.value) {
        form.put(update.url(editTarget.value.id), options);
    } else {
        form.post(store.url(), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(destroy.url(deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteTarget.value = null),
        onFinish: () => (isDeleting.value = false),
    });
};

const progressColor = (b: any) => {
    if (b.is_over) return 'bg-red-500';
    if (b.percentage >= 80) return 'bg-orange-500';
    return 'bg-green-500';
};

const handleLimitInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const digits = target.value.replace(/\D/g, '');
    limitDigits.value = digits;
    target.value = digits;
};
</script>

<template>
    <Head title="Orçamentos" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Orçamentos
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Defina um limite de gasto por categoria e acompanhe o mês.
                </p>
            </div>
            <Button class="self-start sm:self-auto" @click="openCreate">
                <Plus class="size-4" />
                Novo orçamento
            </Button>
        </header>

        <AppCard padding="md">
            <form
                class="flex flex-col gap-3 sm:flex-row sm:items-center"
                @submit.prevent="applyFilters"
            >
                <input
                    v-model="filters.month"
                    type="month"
                    class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                />
                <Button type="submit" variant="secondary">Filtrar</Button>
            </form>
        </AppCard>

        <section
            v-if="budgets.length > 0"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <AppCard
                v-for="budget in budgets"
                :key="budget.id"
                class="flex flex-col"
                padding="lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">
                            {{ budget.category?.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ budget.month }}
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="openEdit(budget)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10"
                            @click="deleteTarget = budget"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="mt-4">
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span
                            >Gasto:
                            {{ formatCurrency(budget.spent_cents) }}</span
                        >
                        <span
                            >Limite:
                            {{ formatCurrency(budget.limit_cents) }}</span
                        >
                    </div>
                    <div
                        class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :class="progressColor(budget)"
                            :style="{
                                width: Math.min(100, budget.percentage) + '%',
                            }"
                        />
                    </div>
                    <p
                        class="mt-2 text-xs font-medium"
                        :class="
                            budget.is_over
                                ? 'text-red-500'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ budget.percentage }}% utilizado
                        <span v-if="budget.is_over"> - Limite estourado</span>
                    </p>
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
                <Target class="size-7" />
            </span>
            <p class="text-base font-semibold">Nenhum orçamento encontrado</p>
            <p class="text-sm text-muted-foreground">
                Crie um orçamento para acompanhar seus gastos.
            </p>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Criar orçamento
            </Button>
        </AppCard>

        <Dialog :open="formOpen" @update:open="(v) => (formOpen = v)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        editTarget ? 'Editar orçamento' : 'Novo orçamento'
                    }}</DialogTitle>
                    <DialogDescription
                        >Defina categoria, mês e limite.</DialogDescription
                    >
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Categoria</label>
                        <select
                            v-model="form.category_id"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        >
                            <option value="">Selecione</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category_id" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Mês</label>
                        <input
                            v-model="form.month"
                            type="month"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                        <InputError :message="form.errors.month" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Limite (centavos)</label
                        >
                        <input
                            :value="limitDigits"
                            type="text"
                            inputmode="numeric"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                            @input="handleLimitInput"
                        />
                        <InputError
                            :message="(form.errors as any).limit_cents"
                        />
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="outline" type="button"
                                >Cancelar</Button
                            >
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2
                                v-if="form.processing"
                                class="size-4 animate-spin"
                            />
                            {{ editTarget ? 'Salvar' : 'Criar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="deleteTarget !== null"
            @update:open="(v) => (v ? null : (deleteTarget = null))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Excluir orçamento</DialogTitle>
                    <DialogDescription>Tem certeza?</DialogDescription>
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
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
