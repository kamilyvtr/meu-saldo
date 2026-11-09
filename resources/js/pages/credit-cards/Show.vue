<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
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
import { index as creditCardsIndex } from '@/routes/credit-cards';
import { store as purchaseStore } from '@/routes/card-purchases';

const props = defineProps<{
    card: any;
    purchases: any[];
    invoices: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Cartões', href: creditCardsIndex() },
            { title: 'Detalhes', href: creditCardsIndex() },
        ],
    },
});

const formOpen = ref(false);
const form = reactive({
    credit_card_id: props.card.id,
    description: '',
    total_amount_cents: 0,
    purchase_date: new Date().toISOString().slice(0, 10),
    installments_count: 1,
    category_id: null as number | null,
    notes: '',
});

const reset = () => {
    form.description = '';
    form.total_amount_cents = 0;
    form.purchase_date = new Date().toISOString().slice(0, 10);
    form.installments_count = 1;
    form.category_id = null;
    form.notes = '';
};

const submit = () => {
    router.post(
        purchaseStore.url(),
        { ...form },
        {
            preserveScroll: true,
            onSuccess: () => {
                formOpen.value = false;
                reset();
            },
        },
    );
};
</script>

<template>
    <Head :title="card.name" />
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ card.name }}</h1>
            <Button @click="formOpen = true">
                <Plus class="mr-2 size-4" />
                Nova compra
            </Button>
        </div>

        <AppCard class="space-y-2 p-4">
            <div>Dívida: {{ formatCurrency(card.debt_cents) }}</div>
            <div>Limite: {{ formatCurrency(card.limit_cents) }}</div>
        </AppCard>

        <section>
            <h2 class="mb-2 font-semibold">Compras</h2>
            <div class="space-y-2">
                <AppCard v-for="p in purchases" :key="p.id" class="p-3">
                    <div class="flex justify-between">
                        <span>{{ p.description }}</span>
                        <span>{{ formatCurrency(p.total_amount) }}</span>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ p.purchase_date }} • {{ p.installments_count }}x
                    </div>
                </AppCard>
            </div>
        </section>

        <section>
            <h2 class="mb-2 font-semibold">Faturas</h2>
            <div class="space-y-2">
                <AppCard
                    v-for="inv in invoices"
                    :key="inv.id"
                    class="flex items-center justify-between p-3"
                >
                    <div>
                        <div>{{ inv.month }} — {{ inv.status_label }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ formatCurrency(inv.total_amount) }}
                        </div>
                    </div>
                </AppCard>
            </div>
        </section>

        <Dialog v-model:open="formOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nova compra</DialogTitle>
                </DialogHeader>
                <div class="space-y-3">
                    <input
                        v-model="form.description"
                        placeholder="Descrição"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model.number="form.total_amount_cents"
                        type="number"
                        placeholder="Valor total (centavos)"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model="form.purchase_date"
                        type="date"
                        class="w-full rounded border px-3 py-2"
                    />
                    <input
                        v-model.number="form.installments_count"
                        type="number"
                        placeholder="Parcelas"
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
    </div>
</template>
