<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    ChartColumn,
    CreditCard,
    LayoutDashboard,
    Repeat,
    Settings,
    ShieldCheck,
    Target,
    Wallet,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import BrandLogo from '@/components/BrandLogo.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as creditCardsIndex } from '@/routes/credit-cards';
import { index as goalsIndex } from '@/routes/goals';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as recurrencesIndex } from '@/routes/recurrences/index';
import { edit as editProfile } from '@/routes/profile';
import { index as transactionsIndex } from '@/routes/transactions';
import type { NavItem } from '@/types';

type SidebarItem = NavItem & {
    soon?: boolean;
};

type SidebarSection = {
    label: string;
    items: SidebarItem[];
};

const { isCurrentOrParentUrl } = useCurrentUrl();

const sections: SidebarSection[] = [
    {
        label: 'Visão geral',
        items: [
            {
                title: 'Início',
                href: dashboard(),
                icon: LayoutDashboard,
            },
        ],
    },
    {
        label: 'Gestão',
        items: [
            {
                title: 'Transações',
                href: transactionsIndex(),
                icon: ArrowLeftRight,
            },
            {
                title: 'Contas',
                href: accountsIndex(),
                icon: Wallet,
            },
            {
                title: 'Cartões',
                href: creditCardsIndex(),
                icon: CreditCard,
            },
            {
                title: 'Faturas',
                href: invoicesIndex(),
                icon: CreditCard,
            },
            {
                title: 'Orçamentos',
                href: budgetsIndex(),
                icon: Target,
            },
            {
                title: 'Metas',
                href: goalsIndex(),
                icon: Target,
            },
            {
                title: 'Recorrências',
                href: recurrencesIndex(),
                icon: Repeat,
            },
            {
                title: 'Relatórios',
                href: '#',
                icon: ChartColumn,
                soon: true,
            },
        ],
    },
    {
        label: 'Sistema',
        items: [
            {
                title: 'Ajustes',
                href: editProfile(),
                icon: Settings,
            },
        ],
    },
];

const isActive = (item: SidebarItem) =>
    !item.soon && isCurrentOrParentUrl(item.href);

const notifySoon = (label: string) => {
    toast.info('Em breve', {
        description: `${label} será liberado na próxima etapa.`,
    });
};
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 hidden w-[272px] flex-col border-r border-sidebar-border bg-sidebar lg:flex dark:border-white/10 dark:bg-black"
        aria-label="Navegação principal"
    >
        <div class="flex h-16 shrink-0 items-center px-5">
            <Link
                :href="dashboard()"
                class="rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                <BrandLogo />
            </Link>
        </div>

        <div class="no-scrollbar flex-1 overflow-y-auto px-3 py-4">
            <div
                v-for="section in sections"
                :key="section.label"
                class="mb-6 last:mb-0"
            >
                <p
                    class="px-3 pb-2 text-[10px] font-semibold tracking-[0.16em] text-muted-foreground uppercase"
                >
                    {{ section.label }}
                </p>

                <nav class="space-y-1">
                    <template v-for="item in section.items" :key="item.title">
                        <Link
                            v-if="!item.soon"
                            :href="item.href"
                            :aria-current="isActive(item) ? 'page' : undefined"
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                            :class="
                                isActive(item)
                                    ? 'bg-primary/10 text-primary dark:bg-primary/15'
                                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                            "
                        >
                            <component
                                :is="item.icon"
                                class="size-5 shrink-0"
                                :stroke-width="isActive(item) ? 2.2 : 1.8"
                            />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>

                        <button
                            v-else
                            type="button"
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                            @click="notifySoon(item.title)"
                        >
                            <component
                                :is="item.icon"
                                class="size-5 shrink-0"
                                :stroke-width="1.8"
                            />
                            <span class="truncate">{{ item.title }}</span>
                            <span
                                class="ml-auto rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                            >
                                Em breve
                            </span>
                        </button>
                    </template>
                </nav>
            </div>
        </div>

        <div class="shrink-0 p-3">
            <div
                class="rounded-2xl border border-border bg-surface-muted p-4 dark:border-transparent dark:bg-card"
            >
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <ShieldCheck class="size-4 text-primary" />
                    Privacidade primeiro
                </div>
                <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                    Seus dados financeiros nunca são armazenados em cache.
                </p>
            </div>
        </div>
    </aside>
</template>
