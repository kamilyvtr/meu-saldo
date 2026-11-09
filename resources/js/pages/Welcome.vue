<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BarChart3,
    CheckCircle2,
    ShieldCheck,
    Smartphone,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppCard from '@/components/AppCard.vue';
import BrandLogo from '@/components/BrandLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { dashboard, login } from '@/routes';
import type { User } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user as User | null);
const isAuthenticated = computed(() => Boolean(user.value));
const year = new Date().getFullYear();

const features = [
    {
        icon: Wallet,
        title: 'Visão completa',
        description:
            'Saldo, receitas e despesas organizados em uma visão simples e clara.',
    },
    {
        icon: ShieldCheck,
        title: 'Seguro por padrão',
        description:
            'Autenticação protegida e dados financeiros sempre fora do cache.',
    },
    {
        icon: Smartphone,
        title: 'Feito para o celular',
        description:
            'Navegação confortável, safe areas do iPhone e atalhos na medida.',
    },
];
</script>

<template>
    <Head title="Bem-vindo" />

    <div
        class="relative min-h-dvh overflow-hidden bg-background text-foreground"
    >
        <div
            class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[520px] bg-[radial-gradient(ellipse_at_top,rgba(16,185,129,0.16),transparent_62%)] dark:bg-[radial-gradient(ellipse_at_top,rgba(16,185,129,0.18),transparent_55%)]"
        />

        <header
            class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 sm:px-8"
        >
            <BrandLogo />

            <div class="flex items-center gap-2">
                <ThemeToggle />
                <Link
                    :href="isAuthenticated ? dashboard() : login()"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    {{ isAuthenticated ? 'Ir para o painel' : 'Entrar' }}
                    <ArrowUpRight class="size-4" />
                </Link>
            </div>
        </header>

        <main
            class="mx-auto grid max-w-6xl gap-12 px-5 pt-8 pb-20 sm:px-8 lg:grid-cols-2 lg:items-center lg:pt-16"
        >
            <section>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1 text-xs font-semibold text-muted-foreground shadow-soft dark:border-transparent dark:shadow-none"
                >
                    <span class="size-1.5 rounded-full bg-primary"></span>
                    Controle financeiro pessoal
                </span>

                <h1
                    class="mt-5 text-4xl leading-[1.06] font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                >
                    Seu dinheiro organizado,
                    <span class="text-primary">do seu jeito.</span>
                </h1>

                <p
                    class="mt-5 max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    O Meu Saldo reúne saldo, receitas, despesas e metas em uma
                    experiência leve, segura e mobile-first. Sem planilhas
                    complicadas.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="isAuthenticated ? dashboard() : login()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        {{
                            isAuthenticated
                                ? 'Abrir meu painel'
                                : 'Entrar na minha conta'
                        }}
                        <ArrowUpRight class="size-4" />
                    </Link>
                    <a
                        href="#recursos"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-5 py-3 text-sm font-semibold transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:border-transparent"
                    >
                        Conhecer recursos
                    </a>
                </div>

                <div
                    class="mt-8 flex items-start gap-2 text-xs leading-relaxed text-muted-foreground"
                >
                    <ShieldCheck class="mt-0.5 size-4 shrink-0 text-primary" />
                    <p>
                        Privacidade primeiro. Nenhum dado financeiro é
                        armazenado em cache pelo aplicativo.
                    </p>
                </div>
            </section>

            <section class="relative">
                <div
                    class="pointer-events-none absolute -inset-6 -z-10 rounded-[40px] bg-[radial-gradient(circle_at_60%_30%,rgba(139,92,246,0.16),transparent_55%)] blur-2xl"
                />

                <AppCard padding="lg" class="relative overflow-hidden">
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Saldo total
                            </p>
                            <p
                                class="mt-1 text-3xl font-semibold tracking-tight"
                            >
                                R$ 12.480,00
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary"
                        >
                            <ArrowUpRight class="size-3.5" />
                            8,2%
                        </span>
                    </div>

                    <div class="mt-6">
                        <svg
                            viewBox="0 0 520 150"
                            class="h-36 w-full"
                            preserveAspectRatio="none"
                            role="img"
                            aria-label="Prévia demonstrativa do gráfico de fluxo de caixa"
                        >
                            <defs>
                                <linearGradient
                                    id="welcome-chart-fill"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="var(--chart-1)"
                                        stop-opacity="0.3"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="var(--chart-1)"
                                        stop-opacity="0"
                                    />
                                </linearGradient>
                            </defs>
                            <path
                                d="M0 118 C 42 110, 64 82, 104 90 S 168 58, 216 66 S 280 34, 328 46 S 404 20, 456 30 S 500 16, 520 24 L520 150 L0 150 Z"
                                fill="url(#welcome-chart-fill)"
                            />
                            <path
                                d="M0 118 C 42 110, 64 82, 104 90 S 168 58, 216 66 S 280 34, 328 46 S 404 20, 456 30 S 500 16, 520 24"
                                fill="none"
                                stroke="var(--chart-1)"
                                stroke-width="3"
                                stroke-linecap="round"
                            />
                            <circle
                                cx="328"
                                cy="46"
                                r="5"
                                fill="var(--chart-1)"
                                stroke="var(--background)"
                                stroke-width="3"
                            />
                        </svg>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Wallet class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">Salário</p>
                                <p class="text-xs text-muted-foreground">
                                    Receita · 05 nov
                                </p>
                            </div>
                            <p
                                class="text-sm font-semibold text-primary tabular-nums"
                            >
                                +R$ 8.250,00
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-9 items-center justify-center rounded-xl bg-expense/10 text-expense"
                            >
                                <BarChart3 class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">Supermercado</p>
                                <p class="text-xs text-muted-foreground">
                                    Alimentação · 04 nov
                                </p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums">
                                -R$ 342,90
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-5 flex items-center gap-2 rounded-xl bg-primary/10 px-3 py-2.5 text-xs font-medium text-primary"
                    >
                        <CheckCircle2 class="size-4 shrink-0" />
                        Meta do mês: 65,5% concluída
                    </div>
                </AppCard>
            </section>
        </main>

        <section
            id="recursos"
            class="mx-auto max-w-6xl scroll-mt-20 px-5 pb-20 sm:px-8"
        >
            <div class="mb-6">
                <h2 class="text-2xl font-semibold tracking-tight">
                    Tudo o que você precisa, sem excesso.
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Uma base sólida para cuidar do seu dinheiro com clareza.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <AppCard
                    v-for="feature in features"
                    :key="feature.title"
                    padding="lg"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <component :is="feature.icon" class="size-5" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold">
                        {{ feature.title }}
                    </h3>
                    <p
                        class="mt-1 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ feature.description }}
                    </p>
                </AppCard>
            </div>
        </section>

        <footer class="border-t border-border dark:border-white/10">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-8"
            >
                <p>© {{ year }} Meu Saldo</p>
                <p>Feito para suas finanças.</p>
            </div>
        </footer>
    </div>
</template>
