<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CreditCard as CreditCardIcon,
    Plus,
    Trash2,
    Pencil,
} from '@lucide/vue';
import { reactive, ref } from 'vue';
import AppCard from '@/components/AppCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatCurrency } from '@/lib/money';
import { dashboard } from '@/routes';
import {
    index as creditCardsIndex,
    store,
    update,
    destroy,
} from '@/routes/credit-cards';
import { store as purchaseStore } from '@/routes/card-purchases';

type Card = {
    id: number;
    name: string;
    brand: string | null;
    limit_cents: number;
    closing_day: number;
    due_day: number;
    color: string | null;
    institution: string | null;
    is_active: boolean;
    debt_cents: number;
    available_limit_cents: number;
    account_id: number | null;
};

const props = defineProps<{ cards: Card[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Cartões', href: creditCardsIndex() },
        ],
    },
});

const formOpen = ref(false);
const editTarget = ref<Card | null>(null);
const deleteTarget = ref<Card | null>(null);
const isDeleting = ref(false);

const form = reactive({
    name: '',
    brand: '',
    institution: '',
    color: '',
    limit_cents: 0,
    closing_day: 1,
    due_day: 10,
    is_active: true,
});

const resetForm = () => {
    form.name = '';
    form.brand = '';
    form.institution = '';
    form.color = '';
    form.limit_cents = 0;
    form.closing_day = 1;
    form.due_day = 10;
    form.is_active = true;
};

const openCreate = () => {
    editTarget.value = null;
    resetForm();
    formOpen.value = true;
};

const openEdit = (card: Card) => {
    editTarget.value = card;
    form.name = card.name;
    form.brand = card.brand ?? '';
    form.institution = card.institution ?? '';
    form.color = card.color ?? '';
    form.limit_cents = card.limit_cents;
    form.closing_day = card.closing_day;
    form.due_day = card.due_day;
    form.is_active = card.is_active;
    formOpen.value = true;
};

const submit = () => {
    const payload = {
        name: form.name,
        brand: form.brand || null,
        institution: form.institution || null,
        color: form.color || null,
        limit_cents: form.limit_cents,
        closing_day: form.closing_day,
        due_day: form.due_day,
        is_active: form.is_active,
    };

    if (editTarget.value) {
        router.patch(update.url(editTarget.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => (formOpen.value = false),
        });
    } else {
        router.post(store.url(), payload, {
            preserveScroll: true,
            onSuccess: () => (formOpen.value = false),
        });
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
</script>

<template>
    <Head title="Cartões" />
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Cartões de crédito</h1>
            <Button @click="openCreate">
                <Plus class="mr-2 size-4" />
                Novo cartão
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <AppCard v-for="card in cards" :key="card.id" class="space-y-3 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <CreditCardIcon class="size-5 text-primary" />
                        <span class="font-semibold">{{ card.name }}</span>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            @click="openEdit(card)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            @click="deleteTarget = card"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
                <div class="text-sm text-muted-foreground">
                    Limite: {{ formatCurrency(card.limit_cents) }}
                </div>
                <div class="text-sm">
                    Dívida: {{ formatCurrency(card.debt_cents) }}
                </div>
                <div class="text-sm text-muted-foreground">
                    Disponível: {{ formatCurrency(card.available_limit_cents) }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Fecha dia {{ card.closing_day }} • Vence dia
                    {{ card.due_day }}
                </div>
            </AppCard>
        </div>

        <Dialog v-model:open="formOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        editTarget ? 'Editar cartão' : 'Novo cartão'
                    }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-3">
                    <input
                        v-model="form.name"
                        placeholder="Nome"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model="form.brand"
                        placeholder="Bandeira"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model="form.institution"
                        placeholder="Instituição"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model.number="form.limit_cents"
                        type="number"
                        placeholder="Limite (centavos)"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model.number="form.closing_day"
                        type="number"
                        placeholder="Dia de fechamento"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model.number="form.due_day"
                        type="number"
                        placeholder="Dia de vencimento"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model="form.color"
                        placeholder="Cor (#000000)"
                        class="w-full rounded border px-3 py-2"
                    />
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button @click="submit">Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="!!deleteTarget"
            @update:open="(v) => !v && (deleteTarget = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Remover cartão?</DialogTitle>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteTarget = null"
                        >Cancelar</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                        >Remover</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
