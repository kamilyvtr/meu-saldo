<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Loader2, Pencil, Plus, Target, Trash2, X } from '@lucide/vue';
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
import { destroy, index as goalsIndex, store, update } from '@/routes/goals';
import { deposit, withdraw } from '@/routes/goals';

const props = defineProps<{
    goals: any[];
    accounts: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Metas', href: goalsIndex() },
        ],
    },
});

const formOpen = ref(false);
const editTarget = ref<any>(null);
const deleteTarget = ref<any>(null);
const isDeleting = ref(false);
const depositTarget = ref<any>(null);
const withdrawTarget = ref<any>(null);
const amountDigits = ref('');

const form = useForm({
    name: '',
    target_cents: 0 as number,
    current_cents: 0 as number,
    deadline: '' as string,
    account_id: '' as any,
});

watch(
    () => formOpen.value,
    (open) => {
        if (!open) return;
        form.clearErrors();
        if (editTarget.value) {
            form.name = editTarget.value.name;
            form.target_cents = editTarget.value.target_cents;
            form.current_cents = editTarget.value.current_cents;
            form.deadline = editTarget.value.deadline ?? '';
            form.account_id = editTarget.value.account_id ?? '';
        } else {
            form.reset();
            form.deadline = '';
        }
    },
);

const openCreate = () => {
    editTarget.value = null;
    formOpen.value = true;
};

const openEdit = (goal: any) => {
    editTarget.value = goal;
    formOpen.value = true;
};

const submit = () => {
    form.transform((data: any) => ({
        name: data.name,
        target_cents: Number(data.target_cents),
        current_cents: Number(data.current_cents),
        deadline: data.deadline || null,
        account_id: data.account_id === '' ? null : Number(data.account_id),
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

const openDeposit = (goal: any) => {
    depositTarget.value = goal;
    amountDigits.value = '';
};

const openWithdraw = (goal: any) => {
    withdrawTarget.value = goal;
    amountDigits.value = '';
};

const submitAmount = (mode: 'deposit' | 'withdraw') => {
    const goal =
        mode === 'deposit' ? depositTarget.value : withdrawTarget.value;
    if (!goal) return;
    const amount = Number(amountDigits.value || 0);
    const routeFn = mode === 'deposit' ? deposit : withdraw;
    router.post(
        routeFn.url(goal.id),
        { amount_cents: amount },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (mode === 'deposit') depositTarget.value = null;
                else withdrawTarget.value = null;
            },
        },
    );
};

const handleAmountInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    amountDigits.value = target.value.replace(/\D/g, '');
    target.value = amountDigits.value;
};

const purple = '#8B5CF6';
</script>

<template>
    <Head title="Metas" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Metas
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Guarde dinheiro para seus objetivos.
                </p>
            </div>
            <Button class="self-start sm:self-auto" @click="openCreate">
                <Plus class="size-4" />
                Nova meta
            </Button>
        </header>

        <section
            v-if="goals.length > 0"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <AppCard
                v-for="goal in goals"
                :key="goal.id"
                class="flex flex-col"
                padding="lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">{{ goal.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                goal.deadline
                                    ? 'Prazo: ' + goal.deadline
                                    : 'Sem prazo'
                            }}
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="openEdit(goal)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10"
                            @click="deleteTarget = goal"
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
                            >Guardado:
                            {{ formatCurrency(goal.current_cents) }}</span
                        >
                        <span
                            >Meta: {{ formatCurrency(goal.target_cents) }}</span
                        >
                    </div>
                    <div
                        class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :style="{
                                width: Math.min(100, goal.percentage) + '%',
                                backgroundColor: purple,
                            }"
                        />
                    </div>
                    <p class="mt-2 text-xs font-medium text-muted-foreground">
                        {{ goal.percentage }}% concluído
                    </p>
                </div>

                <div class="mt-4 flex gap-2">
                    <Button
                        size="sm"
                        class="flex-1"
                        :style="{ backgroundColor: purple, color: 'white' }"
                        @click="openDeposit(goal)"
                    >
                        Guardar dinheiro
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        class="flex-1"
                        @click="openWithdraw(goal)"
                    >
                        Resgatar
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
                class="flex size-14 items-center justify-center rounded-2xl"
                :style="{ backgroundColor: purple + '1f', color: purple }"
            >
                <Target class="size-7" />
            </span>
            <p class="text-base font-semibold">Nenhuma meta encontrada</p>
            <p class="text-sm text-muted-foreground">
                Crie uma meta para começar a guardar.
            </p>
            <Button
                @click="openCreate"
                :style="{ backgroundColor: purple, color: 'white' }"
            >
                <Plus class="size-4" />
                Criar meta
            </Button>
        </AppCard>

        <Dialog :open="formOpen" @update:open="(v) => (formOpen = v)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        editTarget ? 'Editar meta' : 'Nova meta'
                    }}</DialogTitle>
                    <DialogDescription
                        >Defina sua meta de economia.</DialogDescription
                    >
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Nome</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Meta (centavos)</label
                        >
                        <input
                            v-model.number="form.target_cents"
                            type="number"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                        <InputError
                            :message="(form.errors as any).target_cents"
                        />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Guardado (centavos)</label
                        >
                        <input
                            v-model.number="form.current_cents"
                            type="number"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Prazo</label>
                        <input
                            v-model="form.deadline"
                            type="date"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium"
                            >Conta (opcional)</label
                        >
                        <select
                            v-model="form.account_id"
                            class="h-10 rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        >
                            <option value="">Nenhuma</option>
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="outline" type="button"
                                >Cancelar</Button
                            >
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            :style="{ backgroundColor: purple, color: 'white' }"
                        >
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
            :open="depositTarget !== null"
            @update:open="(v) => (v ? null : (depositTarget = null))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Guardar dinheiro</DialogTitle>
                    <DialogDescription
                        >Informe o valor em centavos.</DialogDescription
                    >
                </DialogHeader>
                <form
                    class="space-y-4"
                    @submit.prevent="submitAmount('deposit')"
                >
                    <input
                        :value="amountDigits"
                        type="text"
                        inputmode="numeric"
                        class="h-10 w-full rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        @input="handleAmountInput"
                    />
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="outline" type="button"
                                >Cancelar</Button
                            >
                        </DialogClose>
                        <Button
                            type="submit"
                            :style="{ backgroundColor: purple, color: 'white' }"
                            >Guardar</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="withdrawTarget !== null"
            @update:open="(v) => (v ? null : (withdrawTarget = null))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Resgatar</DialogTitle>
                    <DialogDescription
                        >Informe o valor em centavos.</DialogDescription
                    >
                </DialogHeader>
                <form
                    class="space-y-4"
                    @submit.prevent="submitAmount('withdraw')"
                >
                    <input
                        :value="amountDigits"
                        type="text"
                        inputmode="numeric"
                        class="h-10 w-full rounded-xl border border-border bg-background px-3 text-sm dark:border-white/10"
                        @input="handleAmountInput"
                    />
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="outline" type="button"
                                >Cancelar</Button
                            >
                        </DialogClose>
                        <Button type="submit" variant="outline"
                            >Resgatar</Button
                        >
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
                    <DialogTitle>Excluir meta</DialogTitle>
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
