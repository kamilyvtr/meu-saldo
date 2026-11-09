<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Loader2,
    Pause,
    Pencil,
    Play,
    Plus,
    Repeat,
    Trash2,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
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

const storeRecurrence = () => '/recurrences';
const updateRecurrence = (id: number) => `/recurrences/${id}`;
const deleteRecurrence = (id: number) => `/recurrences/${id}`;
const toggleRecurrence = (id: number) => `/recurrences/${id}/toggle`;

const props = defineProps<{
    recurrences: any[];
    accounts: any[];
    categories: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Recorrências', href: '/recurrences' },
        ],
    },
});

const formOpen = ref(false);
const editTarget = ref<any>(null);
const deleteTarget = ref<any>(null);
const isDeleting = ref(false);

const form = useForm({
    description: '',
    amount_cents: 0 as number,
    type: 'expense',
    category_id: '' as any,
    account_id: '' as any,
    frequency: 'monthly',
    next_run_date: '' as string,
    end_date: '' as string,
    is_active: true,
});

watch(
    () => formOpen.value,
    (open) => {
        if (!open) return;
        form.clearErrors();
        if (editTarget.value) {
            form.description = editTarget.value.description;
            form.amount_cents = editTarget.value.amount_cents;
            form.type = editTarget.value.type;
            form.category_id = editTarget.value.category_id ?? '';
            form.account_id = editTarget.value.account_id ?? '';
            form.frequency = editTarget.value.frequency;
            form.next_run_date = editTarget.value.next_run_date ?? '';
            form.end_date = editTarget.value.end_date ?? '';
            form.is_active = editTarget.value.is_active;
        } else {
            form.reset();
            form.type = 'expense';
            form.frequency = 'monthly';
            form.next_run_date = new Date().toISOString().slice(0, 10);
            form.is_active = true;
        }
    },
);

const openCreate = () => {
    editTarget.value = null;
    formOpen.value = true;
};

const openEdit = (recurrence: any) => {
    editTarget.value = recurrence;
    formOpen.value = true;
};

const submit = () => {
    form.transform((data: any) => ({
        description: data.description,
        amount_cents: Number(data.amount_cents),
        type: data.type,
        category_id: data.category_id === '' ? null : Number(data.category_id),
        account_id: Number(data.account_id),
        frequency: data.frequency,
        next_run_date: data.next_run_date || null,
        end_date: data.end_date || null,
        is_active: data.is_active,
    }));
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    };
    if (editTarget.value) {
        form.put(updateRecurrence(editTarget.value.id), options);
    } else {
        form.post(storeRecurrence(), options);
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(deleteRecurrence(deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteTarget.value = null),
        onFinish: () => (isDeleting.value = false),
    });
};

const handleToggle = (recurrence: any) => {
    router.patch(toggleRecurrence(recurrence.id), {}, { preserveScroll: true });
};

const frequencyLabel = (value: string) => {
    return value === 'weekly'
        ? 'Semanal'
        : value === 'yearly'
          ? 'Anual'
          : 'Mensal';
};

const typeLabel = (value: string) =>
    value === 'income' ? 'Receita' : 'Despesa';
</script>

<template>
    <Head title="Recorrências" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Recorrências
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Automatize receitas e despesas que se repetem.
                </p>
            </div>
            <Button class="self-start sm:self-auto" @click="openCreate">
                <Plus class="size-4" />
                Nova recorrência
            </Button>
        </header>

        <section
            v-if="recurrences.length > 0"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <AppCard
                v-for="recurrence in recurrences"
                :key="recurrence.id"
                class="flex flex-col"
                padding="lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">
                            {{ recurrence.description }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ typeLabel(recurrence.type) }} ·
                            {{ frequencyLabel(recurrence.frequency) }}
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="openEdit(recurrence)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10"
                            @click="deleteTarget = recurrence"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="mt-4 space-y-1 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Valor</span>
                        <span class="font-medium">{{
                            formatCurrency(recurrence.amount_cents)
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground"
                            >Próxima execução</span
                        >
                        <span class="font-medium">{{
                            recurrence.next_run_date
                        }}</span>
                    </div>
                    <div
                        v-if="recurrence.end_date"
                        class="flex items-center justify-between"
                    >
                        <span class="text-muted-foreground">Término</span>
                        <span class="font-medium">{{
                            recurrence.end_date
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Status</span>
                        <span class="font-medium">{{
                            recurrence.is_active ? 'Ativa' : 'Pausada'
                        }}</span>
                    </div>
                </div>

                <div class="mt-4">
                    <Button
                        size="sm"
                        variant="outline"
                        class="w-full"
                        @click="handleToggle(recurrence)"
                    >
                        <Pause v-if="recurrence.is_active" class="size-4" />
                        <Play v-else class="size-4" />
                        {{ recurrence.is_active ? 'Pausar' : 'Ativar' }}
                    </Button>
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
                <Repeat class="size-7" />
            </span>
            <p class="text-base font-semibold">
                Nenhuma recorrência encontrada
            </p>
            <p class="text-sm text-muted-foreground">
                Crie uma recorrência para automatizar lançamentos.
            </p>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Criar recorrência
            </Button>
        </AppCard>

        <Dialog :open="formOpen" @update:open="(v) => (formOpen = v)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        editTarget ? 'Editar recorrência' : 'Nova recorrência'
                    }}</DialogTitle>
                    <DialogDescription
                        >Configure o lançamento automático.</DialogDescription
                    >
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Descrição</label>
                        <input
                            v-model="form.description"
                            type="text"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Valor (centavos)</label
                        >
                        <input
                            v-model.number="form.amount_cents"
                            type="number"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                        <InputError
                            :message="(form.errors as any).amount_cents"
                        />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Tipo</label>
                        <select
                            v-model="form.type"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        >
                            <option value="expense">Despesa</option>
                            <option value="income">Receita</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Categoria (opcional)</label
                        >
                        <select
                            v-model="form.category_id"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        >
                            <option value="">Nenhuma</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Conta</label
                        >
                        <select
                            v-model="form.account_id"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        >
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                        <InputError :message="(form.errors as any).account_id" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Frequência</label>
                        <select
                            v-model="form.frequency"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        >
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensal</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Próxima execução</label
                        >
                        <input
                            v-model="form.next_run_date"
                            type="date"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                        <InputError
                            :message="(form.errors as any).next_run_date"
                        />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Término (opcional)</label
                        >
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                        />
                        <label for="is_active" class="text-sm font-medium"
                            >Ativa</label
                        >
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
                    <DialogTitle>Excluir recorrência</DialogTitle>
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
