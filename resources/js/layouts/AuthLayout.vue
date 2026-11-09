<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, ShieldCheck, Smartphone } from '@lucide/vue';
import BrandLogo from '@/components/BrandLogo.vue';
import { home } from '@/routes';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const year = new Date().getFullYear();

const highlights = [
    {
        icon: CheckCircle2,
        title: 'Visão clara do seu mês',
        description: 'Saldo, receitas e despesas em um só lugar.',
    },
    {
        icon: ShieldCheck,
        title: 'Privacidade primeiro',
        description: 'Seus dados financeiros nunca são armazenados em cache.',
    },
    {
        icon: Smartphone,
        title: 'Feito para o celular',
        description: 'Acesso rápido, confortável e mobile-first.',
    },
];
</script>

<template>
    <div class="grid min-h-dvh bg-background lg:grid-cols-2">
        <aside
            class="relative hidden overflow-hidden bg-[#07130F] p-10 text-white lg:flex lg:flex-col"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(16,185,129,0.34),transparent_44%),radial-gradient(circle_at_82%_78%,rgba(139,92,246,0.26),transparent_46%)]"
            />
            <div
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_bottom_right,rgba(0,0,0,0.12),transparent_45%,rgba(0,0,0,0.4))]"
            />

            <div class="relative z-10 flex h-full flex-col">
                <Link
                    :href="home()"
                    class="w-fit rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    <BrandLogo inverse />
                </Link>

                <div class="my-auto max-w-md py-12">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-white/70 backdrop-blur"
                    >
                        Finanças pessoais, sem complicação
                    </span>

                    <h2
                        class="mt-5 text-4xl leading-[1.08] font-semibold tracking-tight text-balance"
                    >
                        Seu dinheiro organizado, do seu jeito.
                    </h2>

                    <p class="mt-4 text-base leading-relaxed text-white/65">
                        Acompanhe seu saldo, receitas e despesas com uma
                        experiência simples, segura e feita para o dia a dia.
                    </p>

                    <div
                        class="mt-8 rounded-3xl border border-white/10 bg-white/[0.06] p-5 shadow-2xl backdrop-blur-md"
                    >
                        <p class="text-xs font-medium text-white/55">
                            Saldo total
                        </p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight">
                            R$ 12.480,00
                        </p>
                        <div
                            class="mt-4 h-2 w-full overflow-hidden rounded-full bg-white/10"
                        >
                            <div
                                class="h-full w-[65.5%] rounded-full bg-primary"
                            ></div>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-xs text-white/50"
                        >
                            <span>Meta do mês</span>
                            <span class="font-semibold text-primary"
                                >65,5%</span
                            >
                        </div>
                    </div>

                    <div class="mt-8 grid gap-4">
                        <div
                            v-for="highlight in highlights"
                            :key="highlight.title"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/15 text-primary"
                            >
                                <component
                                    :is="highlight.icon"
                                    class="size-4"
                                />
                            </span>
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ highlight.title }}
                                </p>
                                <p class="text-xs text-white/55">
                                    {{ highlight.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="relative z-10 text-xs text-white/35">
                    Meu Saldo · Controle financeiro pessoal
                </p>
            </div>
        </aside>

        <main
            class="flex min-h-dvh flex-col items-center justify-center px-5 py-10 sm:px-8"
        >
            <div class="w-full max-w-sm">
                <div class="mb-8 flex justify-center lg:hidden">
                    <BrandLogo />
                </div>

                <div class="mb-8 space-y-2 text-center lg:text-left">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />

                <p class="mt-8 text-center text-xs text-muted-foreground">
                    © {{ year }} Meu Saldo. Todos os direitos reservados.
                </p>
            </div>
        </main>
    </div>
</template>
