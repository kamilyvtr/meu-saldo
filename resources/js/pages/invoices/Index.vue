<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { index as invoicesIndex, pay, close } from '@/routes/invoices';

const props = defineProps<{
    invoices: any[];
    accounts: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Faturas', href: invoicesIndex() },
        ],
    },
});

const payTarget = ref<any>(null);
const selectedAccount = ref<number | null>(null);
const payDate = ref(new Date().toISOString().slice(0, 10));
const isPaying = ref(false);

const openPay = (inv: any) => {
    payTarget.value = inv;
    selectedAccount.value = props.accounts[0]?.id ?? null;
};

const confirmPay = () => {
    if (!payTarget.value || !selectedAccount.value) return;
    isPaying.value = true;
    router.post(
        pay.url(payTarget.value.id),
        {
            account_id: selectedAccount.value,
            paid_date: payDate.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => (payTarget.value = null),
            onFinish: () => (isPaying.value = false),
        },
    );
};

const handleClose = (inv: any) => {
    router.post(close.url(inv.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Faturas" />
    <div class="space-y-6">
        <h1 class="text-xl font-semibold">Faturas</h1>

        <div class="space-y-3">
            <AppCard
                v-for="inv in invoices"
                :key="inv.id"
                class="flex items-center justify-between p-4"
            >
                <div>
                    <div class="font-medium">
                        {{ inv.credit_card.name }} — {{ inv.month }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ inv.status_label }} •
                        {{ formatCurrency(inv.total_amount) }}
                        <span v-if="inv.due_date">
                            • vence {{ inv.due_date }}</span
                        >
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="inv.status === 'open'"
                        variant="outline"
                        size="sm"
                        @click="handleClose(inv)"
                    >
                        Fechar
                    </Button>
                    <Button
                        v-if="inv.status !== 'paid'"
                        size="sm"
                        @click="openPay(inv)"
                    >
                        Pagar
                    </Button>
                </div>
            </AppCard>
        </div>

        <Dialog
            :open="!!payTarget"
            @update:open="(v) => !v && (payTarget = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Pagar fatura</DialogTitle>
                </DialogHeader>
                <div class="space-y-3">
                    <select
                        v-model.number="selectedAccount"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option v-for="a in accounts" :key="a.id" :value="a.id">
                            {{ a.name }}
                        </option>
                    </select>
                    <input
                        v-model="payDate"
                        type="date"
                        class="w-full rounded border px-3 py-2"
                    />
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button :disabled="isPaying" @click="confirmPay"
                        >Pagar</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
