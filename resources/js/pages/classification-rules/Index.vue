<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2, X } from '@lucide/vue';
import { reactive, ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import {
    destroy,
    index as rulesIndex,
    store,
    update,
} from '@/routes/classification-rules';
import type { FinanceCategory } from '@/types';

type Rule = {
    id: number;
    field: string;
    operator: string;
    pattern: string;
    priority: number;
    category: {
        id: number;
        name: string;
        type: string;
        color: string | null;
        icon: string | null;
    } | null;
};

const props = defineProps<{
    rules: Rule[];
    categories: FinanceCategory[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Regras de classificação', href: rulesIndex() },
        ],
    },
});

const formOpen = ref(false);
const editTarget = ref<Rule | null>(null);
const deleteTarget = ref<Rule | null>(null);
const isDeleting = ref(false);

const form = reactive({
    field: 'description',
    operator: 'contains',
    pattern: '',
    category_id: null as number | null,
    priority: 0,
});

const openCreate = () => {
    editTarget.value = null;
    form.field = 'description';
    form.operator = 'contains';
    form.pattern = '';
    form.category_id = props.categories[0]?.id ?? null;
    form.priority = 0;
    formOpen.value = true;
};

const openEdit = (rule: Rule) => {
    editTarget.value = rule;
    form.field = rule.field;
    form.operator = rule.operator;
    form.pattern = rule.pattern;
    form.category_id = rule.category?.id ?? null;
    form.priority = rule.priority;
    formOpen.value = true;
};

const submit = () => {
    const payload = {
        field: form.field,
        operator: form.operator,
        pattern: form.pattern,
        category_id: form.category_id,
        priority: form.priority,
    };

    if (editTarget.value) {
        router.put(update.url(editTarget.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                formOpen.value = false;
            },
        });
    } else {
        router.post(store.url(), payload, {
            preserveScroll: true,
            onSuccess: () => {
                formOpen.value = false;
            },
        });
    }
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
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
    <Head title="Regras de classificação" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Regras de classificação
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Ensine o app a reconhecer seus lançamentos automaticamente.
                </p>
            </div>
            <Button class="self-start sm:self-auto" @click="openCreate">
                <Plus class="size-4" />
                Nova regra
            </Button>
        </header>

        <AppCard padding="none">
            <div
                v-if="rules.length > 0"
                class="divide-y divide-border dark:divide-white/10"
            >
                <div
                    v-for="rule in rules"
                    :key="rule.id"
                    class="flex items-center gap-3 px-4 py-3.5 sm:px-5"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ rule.pattern }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ rule.operator }} · prioridade
                            {{ rule.priority }} ·
                            {{ rule.category?.name ?? 'Sem categoria' }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-0.5">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="openEdit(rule)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            @click="deleteTarget = rule"
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
                <p class="text-base font-semibold">Nenhuma regra cadastrada</p>
                <p class="text-sm text-muted-foreground">
                    Crie regras para sugerir categorias automaticamente.
                </p>
            </div>
        </AppCard>

        <Dialog
            :open="formOpen"
            @update:open="(v) => (v ? null : (formOpen = false))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        editTarget ? 'Editar regra' : 'Nova regra'
                    }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label>Campo</Label>
                        <Input v-model="form.field" disabled />
                    </div>
                    <div class="grid gap-2">
                        <Label>Operador</Label>
                        <Select v-model="form.operator">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="contains">Contém</SelectItem>
                                <SelectItem value="equals">Igual</SelectItem>
                                <SelectItem value="regex">Regex</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Padrão</Label>
                        <Input v-model="form.pattern" placeholder="Ex.: Uber" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Categoria</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Prioridade</Label>
                        <Input
                            v-model.number="form.priority"
                            type="number"
                            min="0"
                        />
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button @click="submit">{{
                        editTarget ? 'Salvar' : 'Criar'
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="deleteTarget !== null"
            @update:open="(v) => (v ? null : (deleteTarget = null))"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Excluir regra</DialogTitle>
                    <DialogDescription
                        >Esta ação não pode ser desfeita.</DialogDescription
                    >
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
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
