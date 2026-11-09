<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowLeftRight,
    ArrowUpRight,
    CalendarDays,
    ChevronRight,
    CreditCard,
    PiggyBank,
    Plus,
    Sparkles,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppCard from '@/components/AppCard.vue';
import { Button } from '@/components/ui/button';
import { useQuickEntry } from '@/composables/useQuickEntry';
import { categoryIcon, categoryTint } from '@/lib/categoryIcons';
import {
    formatChange,
    formatCurrency,
    formatSignedCurrency,
} from '@/lib/money';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as goalsIndex } from '@/routes/goals';
import { index as transactionsIndex } from '@/routes/transactions';
import type {
    DashboardChartPoint,
    DashboardSummary,
    FinanceTransaction,
    User,
} from '@/types';

const props = defineProps<{
    summary: DashboardSummary;
    recentTransactions: FinanceTransaction[];
    chart: DashboardChartPoint[];
    budgets: any[];
    goals: any[];
    monthLabel: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Início',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user as User);
const firstName = computed(() => user.value.name?.split(' ')[0] ?? 'você');
const { open: openQuickEntry } = useQuickEntry();

const month = computed(() => {
    const label = props.monthLabel;

    return label.charAt(0).toUpperCase() + label.slice(1);
});

const summaryCards = computed(() => [
    {
        key: 'income',
        label: 'Receitas do mês',
        value: props.summary.income,
        hint: formatChange(props.summary.income_change),
        icon: TrendingUp,
        tone: 'primary' as const,
    },
    {
        key: 'expense',
        label: 'Despesas do mês',
        value: props.summary.expense,
        hint: formatChange(props.summary.expense_change),
        icon: TrendingDown,
        tone: 'expense' as const,
    },
    {
        key: 'savings',
        label: 'Economia do mês',
        value: props.summary.savings,
        hint:
            props.summary.income > 0
                ? `${props.summary.savings_rate}% da renda do mês`
                : 'Sem receitas registradas ainda',
        icon: PiggyBank,
        tone: 'accent' as const,
    },
]);

const insight = computed(() => {
    if (props.summary.income === 0) {
        return 'Registre suas receitas para acompanhar quanto do mês realmente sobrou.';
    }

    if (props.summary.savings_rate >= 20) {
        return 'Bom ritmo: você está guardando uma parte saudável do que entrou.';
    }

    if (props.summary.savings_rate > 0) {
        return 'Dá para melhorar: uma pequena redução nas despesas aumenta sua economia.';
    }

    return 'As despesas passaram das receitas neste mês. Vale revisar os gastos maiores.';
});

type Point = [number, number];

const chartGeometry = computed(() => {
    const data = props.chart;

    if (data.length < 2) {
        return null;
    }

    const width = 640;
    const height = 220;
    const padX = 6;
    const padY = 20;

    const values = data.flatMap((point) => [
        point.income,
        point.expense,
        point.balance,
    ]);
    const max = Math.max(1, ...values);
    const min = Math.min(0, ...values);
    const range = max - min || 1;

    const toX = (index: number) =>
        padX + (index / (data.length - 1)) * (width - padX * 2);
    const toY = (value: number) =>
        padY + (1 - (value - min) / range) * (height - padY * 2);

    const points = (key: 'income' | 'expense' | 'balance'): Point[] =>
        data.map((point, index) => [toX(index), toY(point[key])]);

    const smooth = (path: Point[]): string => {
        if (path.length === 0) {
            return '';
        }

        let d = `M ${path[0][0].toFixed(1)} ${path[0][1].toFixed(1)}`;

        for (let index = 1; index < path.length; index += 1) {
            const [previousX, previousY] = path[index - 1];
            const [currentX, currentY] = path[index];
            const controlX = (previousX + currentX) / 2;

            d += ` C ${controlX.toFixed(1)} ${previousY.toFixed(1)}, ${controlX.toFixed(1)} ${currentY.toFixed(1)}, ${currentX.toFixed(1)} ${currentY.toFixed(1)}`;
        }

        return d;
    };

    const balancePoints = points('balance');
    const lastPoint = balancePoints[balancePoints.length - 1];

    return {
        width,
        height,
        balanceLine: smooth(balancePoints),
        incomeLine: smooth(points('income')),
        expenseLine: smooth(points('expense')),
        area: `${smooth(balancePoints)} L ${toX(data.length - 1).toFixed(1)} ${height} L ${toX(0).toFixed(1)} ${height} Z`,
        gridLines: [0.2, 0.45, 0.7, 0.95].map(
            (ratio) => padY + ratio * (height - padY * 2),
        ),
        lastPoint,
        labels: [
            data[0].label,
            data[Math.floor((data.length - 1) / 2)].label,
            data[data.length - 1].label,
        ],
    };
});

const savingsWidth = computed(() =>
    Math.min(100, Math.max(0, props.summary.savings_rate)),
);
</script>

<template>
    <Head title="Início" />

    <div class="space-y-6">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Olá, {{ firstName }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Aqui está o resumo real do seu mês, direto das suas contas.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <div
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3 py-2 text-sm font-medium shadow-soft dark:border-transparent dark:shadow-none"
                >
                    <CalendarDays class="size-4 text-primary" />
                    {{ month }}
                </div>
                <Button class="hidden sm:inline-flex" @click="openQuickEntry()">
                    <Plus class="size-4" />
                    Nova transação
                </Button>
            </div>
        </header>

        <AppCard
            v-if="!summary.has_accounts"
            class="flex flex-col items-start gap-4 border-primary/20 bg-primary/5 sm:flex-row sm:items-center sm:justify-between"
            padding="lg"
        >
            <div class="flex items-start gap-3">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <Wallet class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-semibold">
                        Comece cadastrando sua primeira conta
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Seu painel passa a mostrar o saldo real assim que você
                        adicionar uma conta.
                    </p>
                </div>
            </div>
            <Button as-child class="shrink-0">
                <Link :href="accountsIndex()">Criar conta</Link>
            </Button>
        </AppCard>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <AppCard
                class="relative overflow-hidden border-transparent bg-primary text-primary-foreground shadow-float"
                padding="lg"
            >
                <div
                    class="pointer-events-none absolute -top-12 -right-12 size-40 rounded-full bg-white/10"
                />
                <div
                    class="pointer-events-none absolute -right-4 -bottom-16 size-32 rounded-full bg-white/5"
                />
                <div class="relative">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-white/80">
                            Disponível hoje
                        </span>
                        <span
                            class="flex size-9 items-center justify-center rounded-xl bg-white/15"
                        >
                            <Wallet class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight">
                        {{ formatCurrency(summary.available) }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-white/70">
                        Soma das suas contas ativas
                    </p>
                    <div
                        class="mt-4 flex items-center gap-2 border-t border-white/15 pt-3 text-xs text-white/80"
                    >
                        <CreditCard class="size-3.5" />
                        <template v-if="summary.card_debt > 0">
                            Devo no cartão:
                            <span class="font-semibold">{{
                                formatCurrency(summary.card_debt)
                            }}</span>
                        </template>
                        <template v-else> Sem fatura em aberto </template>
                    </div>
                </div>
            </AppCard>

            <AppCard
                v-for="card in summaryCards"
                :key="card.key"
                class="flex flex-col justify-between"
                padding="lg"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">
                        {{ card.label }}
                    </span>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl"
                        :class="
                            card.tone === 'expense'
                                ? 'bg-expense/10 text-expense'
                                : card.tone === 'accent'
                                  ? 'bg-brand-accent/10 text-brand-accent'
                                  : 'bg-primary/10 text-primary'
                        "
                    >
                        <component :is="card.icon" class="size-5" />
                    </span>
                </div>
                <div class="mt-4">
                    <p
                        class="text-2xl font-semibold tracking-tight"
                        :class="
                            card.key === 'savings' && card.value < 0
                                ? 'text-expense'
                                : ''
                        "
                    >
                        {{ formatCurrency(card.value) }}
                    </p>
                    <p
                        class="mt-1 flex items-center gap-1 text-xs font-medium"
                        :class="
                            card.tone === 'expense'
                                ? 'text-expense'
                                : card.tone === 'accent'
                                  ? 'text-brand-accent'
                                  : 'text-primary'
                        "
                    >
                        <component
                            :is="
                                card.key === 'expense'
                                    ? ArrowDownLeft
                                    : ArrowUpRight
                            "
                            class="size-3.5 shrink-0"
                        />
                        <span class="truncate">{{ card.hint }}</span>
                    </p>
                </div>
            </AppCard>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <AppCard class="xl:col-span-2" padding="lg">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h2 class="text-base font-semibold">Fluxo de caixa</h2>
                        <p class="text-sm text-muted-foreground">
                            Acumulado dos últimos 30 dias
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit items-center gap-2 rounded-xl bg-muted px-3 py-1.5 text-xs font-semibold text-muted-foreground"
                    >
                        <Sparkles class="size-3.5 text-brand-accent" />
                        Últimos 30 dias
                    </span>
                </div>

                <div
                    class="mt-5 flex items-center gap-4 text-xs text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="size-2 rounded-full bg-brand-accent"
                        ></span>
                        Saldo
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-2 rounded-full bg-primary"></span>
                        Receitas
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-2 rounded-full bg-expense"></span>
                        Despesas
                    </span>
                </div>

                <div v-if="chartGeometry" class="mt-3">
                    <svg
                        :viewBox="`0 0 ${chartGeometry.width} ${chartGeometry.height}`"
                        class="h-56 w-full overflow-visible"
                        preserveAspectRatio="none"
                        role="img"
                        aria-label="Gráfico de fluxo de caixa dos últimos 30 dias"
                    >
                        <defs>
                            <linearGradient
                                id="cashflow-fill"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="0%"
                                    stop-color="var(--chart-1)"
                                    stop-opacity="0.26"
                                />
                                <stop
                                    offset="100%"
                                    stop-color="var(--chart-1)"
                                    stop-opacity="0"
                                />
                            </linearGradient>
                        </defs>

                        <g
                            stroke="var(--border)"
                            stroke-width="1"
                            stroke-dasharray="4 6"
                            opacity="0.8"
                        >
                            <line
                                v-for="(line, index) in chartGeometry.gridLines"
                                :key="index"
                                x1="0"
                                :y1="line"
                                :x2="chartGeometry.width"
                                :y2="line"
                            />
                        </g>

                        <path
                            :d="chartGeometry.area"
                            fill="url(#cashflow-fill)"
                        />
                        <path
                            :d="chartGeometry.balanceLine"
                            fill="none"
                            stroke="var(--chart-1)"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                        <path
                            :d="chartGeometry.incomeLine"
                            fill="none"
                            stroke="var(--chart-2)"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-dasharray="6 6"
                            opacity="0.8"
                        />
                        <path
                            :d="chartGeometry.expenseLine"
                            fill="none"
                            stroke="var(--chart-3)"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-dasharray="6 6"
                            opacity="0.75"
                        />
                        <circle
                            v-if="chartGeometry.lastPoint"
                            :cx="chartGeometry.lastPoint[0]"
                            :cy="chartGeometry.lastPoint[1]"
                            r="5"
                            fill="var(--chart-1)"
                            stroke="var(--background)"
                            stroke-width="3"
                        />
                    </svg>

                    <div
                        class="mt-2 flex justify-between text-[10px] font-medium text-muted-foreground sm:text-xs"
                    >
                        <span
                            v-for="label in chartGeometry.labels"
                            :key="label"
                        >
                            {{ label }}
                        </span>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 flex h-56 items-center justify-center rounded-xl bg-muted/50 text-sm text-muted-foreground"
                >
                    Sem movimentações suficientes para desenhar o gráfico.
                </div>
            </AppCard>

            <AppCard class="flex flex-col" padding="lg">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-semibold">Seu mês</h2>
                        <p class="text-sm text-muted-foreground">
                            Quanto sobrou das entradas
                        </p>
                    </div>
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-brand-accent/10 text-brand-accent"
                    >
                        <PiggyBank class="size-5" />
                    </span>
                </div>

                <div class="mt-6">
                    <div class="flex items-end justify-between">
                        <p
                            class="text-2xl font-semibold tracking-tight"
                            :class="summary.savings < 0 ? 'text-expense' : ''"
                        >
                            {{ formatCurrency(summary.savings) }}
                        </p>
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ summary.savings_rate }}% da renda
                        </p>
                    </div>
                    <div
                        class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :class="
                                summary.savings < 0
                                    ? 'bg-expense'
                                    : 'bg-brand-accent'
                            "
                            :style="{ width: `${savingsWidth}%` }"
                        ></div>
                    </div>
                    <div
                        class="mt-2 flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>
                            Receitas:
                            <span class="font-semibold text-foreground">
                                {{ formatCurrency(summary.income) }}
                            </span>
                        </span>
                        <span>
                            Despesas:
                            <span class="font-semibold text-foreground">
                                {{ formatCurrency(summary.expense) }}
                            </span>
                        </span>
                    </div>
                </div>

                <div
                    class="mt-6 flex items-start gap-2 rounded-xl bg-primary/10 p-3 text-xs leading-relaxed text-primary"
                >
                    <Sparkles class="mt-0.5 size-4 shrink-0" />
                    <p>{{ insight }}</p>
                </div>

                <button
                    type="button"
                    class="mt-4 inline-flex items-center justify-center gap-1.5 rounded-xl border border-border px-3 py-2 text-sm font-medium transition-colors hover:bg-muted dark:border-transparent dark:bg-card"
                    @click="openQuickEntry()"
                >
                    <Plus class="size-4" />
                    Registrar transação
                </button>
            </AppCard>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <AppCard padding="lg">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold">
                            Orçamentos do mês
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Limites e gastos
                        </p>
                    </div>
                    <Link
                        :href="budgetsIndex()"
                        class="text-sm font-semibold text-primary hover:underline"
                    >
                        Ver todos
                    </Link>
                </div>
                <div v-if="props.budgets.length > 0" class="mt-4 space-y-3">
                    <div
                        v-for="budget in props.budgets.slice(0, 4)"
                        :key="budget.id"
                    >
                        <div
                            class="flex items-center justify-between text-xs text-muted-foreground"
                        >
                            <span>{{ budget.category?.name }}</span>
                            <span>{{ budget.percentage }}%</span>
                        </div>
                        <div
                            class="mt-1 h-2 w-full overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="
                                    budget.is_over
                                        ? 'bg-red-500'
                                        : budget.percentage >= 80
                                          ? 'bg-orange-500'
                                          : 'bg-green-500'
                                "
                                :style="{
                                    width:
                                        Math.min(100, budget.percentage) + '%',
                                }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Nenhum orçamento cadastrado.
                </p>
            </AppCard>

            <AppCard padding="lg">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold">Metas ativas</h2>
                        <p class="text-sm text-muted-foreground">
                            Progresso das suas metas
                        </p>
                    </div>
                    <Link
                        :href="goalsIndex()"
                        class="text-sm font-semibold text-primary hover:underline"
                    >
                        Ver todas
                    </Link>
                </div>
                <div v-if="props.goals.length > 0" class="mt-4 space-y-3">
                    <div
                        v-for="goal in props.goals.slice(0, 4)"
                        :key="goal.id"
                        class="flex items-center justify-between"
                    >
                        <span class="text-sm font-medium">{{ goal.name }}</span>
                        <span class="text-xs text-muted-foreground">
                            Meta: {{ goal.name }} - {{ goal.percentage }}%
                            concluído
                        </span>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    Nenhuma meta cadastrada.
                </p>
            </AppCard>
        </section>

        <AppCard padding="lg">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold">Transações recentes</h2>
                    <p class="text-sm text-muted-foreground">
                        Seus últimos lançamentos
                    </p>
                </div>
                <Link
                    :href="transactionsIndex()"
                    class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:underline"
                >
                    Ver todas
                    <ChevronRight class="size-4" />
                </Link>
            </div>

            <div
                v-if="recentTransactions.length > 0"
                class="mt-4 divide-y divide-border dark:divide-white/10"
            >
                <div
                    v-for="transaction in recentTransactions"
                    :key="transaction.id"
                    class="flex items-center gap-3 py-3.5"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                        :style="{
                            color: categoryTint(transaction.category?.color),
                            backgroundColor: `${categoryTint(transaction.category?.color)}1f`,
                        }"
                    >
                        <component
                            :is="categoryIcon(transaction.category?.icon)"
                            class="size-5"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ transaction.description }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{
                                transaction.category?.name ??
                                (transaction.type === 'income'
                                    ? 'Receita'
                                    : 'Gasto')
                            }}
                            <template v-if="transaction.account">
                                · {{ transaction.account.name }}
                            </template>
                            · {{ transaction.date_label }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p
                            class="text-sm font-semibold tabular-nums"
                            :class="
                                transaction.type === 'income'
                                    ? 'text-primary'
                                    : 'text-foreground'
                            "
                        >
                            {{
                                formatSignedCurrency(
                                    transaction.amount,
                                    transaction.type,
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="mt-4 flex flex-col items-center justify-center gap-3 rounded-2xl bg-surface-muted px-6 py-10 text-center dark:bg-white/5"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-2xl bg-background text-muted-foreground dark:bg-white/10"
                >
                    <ArrowLeftRight class="size-6" />
                </span>
                <div>
                    <p class="text-sm font-semibold">Nenhuma transação ainda</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Registre sua primeira entrada ou saída para ver os
                        números aqui.
                    </p>
                </div>
                <Button size="sm" @click="openQuickEntry()">
                    <Plus class="size-4" />
                    Registrar primeira transação
                </Button>
            </div>
        </AppCard>
    </div>
</template>
