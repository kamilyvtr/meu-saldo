<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Loader2, Plus, Wallet } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { digitsToCents, formatAmount, sanitizeAmountDigits } from '@/lib/money';
import { store, update } from '@/routes/accounts';
import type {
    AccountTypeOption,
    FinanceAccount,
    FinanceAccountType,
} from '@/types';

const props = defineProps<{
    open: boolean;
    account: FinanceAccount | null;
    types: AccountTypeOption[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const colorPresets = [
    '#10b981',
    '#0ea5e9',
    '#8b5cf6',
    '#f97316',
    '#ef4444',
    '#eab308',
];

const form = useForm({
    name: '',
    type: 'checking' as FinanceAccountType,
    institution: '',
    color: '#10b981',
    is_active: true,
});

const balanceDigits = ref('');

const balanceDisplay = computed(() =>
    balanceDigits.value ? formatAmount(digitsToCents(balanceDigits.value)) : '',
);

const isEditing = computed(() => props.account !== null);

const title = computed(() => (isEditing.value ? 'Editar conta' : 'Nova conta'));

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        form.clearErrors();

        if (props.account) {
            form.name = props.account.name;
            form.type = props.account.type;
            form.institution = props.account.institution ?? '';
            form.color = props.account.color ?? '#10b981';
            form.is_active = props.account.is_active;
            balanceDigits.value = String(props.account.balance);
        } else {
            form.reset();
            form.type = 'checking';
            form.color = '#10b981';
            form.is_active = true;
            balanceDigits.value = '';
        }
    },
);

const handleBalanceInput = (event: Event) => {
    const target = event.target as HTMLInputElement;

    balanceDigits.value = sanitizeAmountDigits(target.value);
    target.value = balanceDigits.value
        ? formatAmount(digitsToCents(balanceDigits.value))
        : '';
};

const close = () => emit('update:open', false);

const submit = () => {
    form.transform((data) => ({
        ...data,
        institution: data.institution.trim() || null,
        balance_cents: digitsToCents(balanceDigits.value),
        color: data.color || null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => close(),
    };

    if (props.account) {
        form.put(update.url(props.account.id), options);
    } else {
        form.post(store.url(), options);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="max-h-[92dvh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Wallet class="size-5 text-primary" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Atualize os dados e o saldo atual desta conta.'
                            : 'Cadastre uma conta para começar a acompanhar seu dinheiro.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <label for="account-name" class="text-sm font-medium">
                        Nome da conta
                    </label>
                    <input
                        id="account-name"
                        v-model="form.name"
                        type="text"
                        maxlength="80"
                        required
                        autocomplete="off"
                        placeholder="Ex.: Conta principal"
                        class="h-11 w-full rounded-xl border border-border bg-background px-3.5 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <label for="account-type" class="text-sm font-medium">
                        Tipo
                    </label>
                    <select
                        id="account-type"
                        v-model="form.type"
                        class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                    >
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-2">
                    <label
                        for="account-institution"
                        class="text-sm font-medium"
                    >
                        Instituição
                        <span class="text-[11px] text-muted-foreground">
                            (opcional)
                        </span>
                    </label>
                    <input
                        id="account-institution"
                        v-model="form.institution"
                        type="text"
                        maxlength="120"
                        autocomplete="off"
                        placeholder="Ex.: Banco do Brasil"
                        class="h-11 w-full rounded-xl border border-border bg-background px-3.5 text-sm transition-colors outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10"
                    />
                    <InputError :message="form.errors.institution" />
                </div>

                <div class="grid gap-2">
                    <label for="account-balance" class="text-sm font-medium">
                        {{ isEditing ? 'Saldo atual' : 'Saldo inicial' }}
                    </label>
                    <div
                        class="flex h-11 items-center rounded-xl border border-border bg-background px-3.5 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-white/10"
                    >
                        <span class="mr-2 text-sm text-muted-foreground">
                            R$
                        </span>
                        <input
                            id="account-balance"
                            :value="balanceDisplay"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="0,00"
                            class="w-full bg-transparent text-sm tabular-nums outline-none"
                            @input="handleBalanceInput"
                        />
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Para cartão de crédito, informe o valor da fatura em
                        aberto como negativo.
                    </p>
                    <InputError :message="(form.errors as any).balance_cents" />
                </div>

                <div class="grid gap-2">
                    <span class="text-sm font-medium">Cor</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="color in colorPresets"
                            :key="color"
                            type="button"
                            class="flex size-9 items-center justify-center rounded-full ring-offset-2 ring-offset-background transition-all focus-visible:outline-none"
                            :class="
                                form.color === color
                                    ? 'ring-2 ring-foreground'
                                    : 'hover:scale-105'
                            "
                            :style="{ backgroundColor: color }"
                            :aria-label="`Selecionar cor ${color}`"
                            @click="form.color = color"
                        >
                            <Check
                                v-if="form.color === color"
                                class="size-4 text-white"
                                stroke-width="3"
                            />
                        </button>
                        <input
                            v-model="form.color"
                            type="color"
                            class="size-9 cursor-pointer rounded-full border border-border bg-transparent p-0.5 dark:border-white/10"
                            aria-label="Escolher outra cor"
                        />
                    </div>
                    <InputError :message="form.errors.color" />
                </div>

                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border border-border px-3.5 py-3 text-left transition-colors hover:bg-muted dark:border-white/10"
                    @click="form.is_active = !form.is_active"
                >
                    <div>
                        <p class="text-sm font-medium">Conta ativa</p>
                        <p class="text-xs text-muted-foreground">
                            Contas inativas não entram no "Disponível hoje".
                        </p>
                    </div>
                    <span
                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors"
                        :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                    >
                        <span
                            class="size-5 rounded-full bg-white shadow transition-transform"
                            :class="
                                form.is_active
                                    ? 'translate-x-5.5'
                                    : 'translate-x-0.5'
                            "
                        />
                    </span>
                </button>

                <DialogFooter class="gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="close"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2
                            v-if="form.processing"
                            class="size-4 animate-spin"
                        />
                        <Plus v-else-if="!isEditing" class="size-4" />
                        <Check v-else class="size-4" />
                        {{ isEditing ? 'Salvar alterações' : 'Criar conta' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
