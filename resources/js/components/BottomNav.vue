<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    ChartColumn,
    CreditCard,
    Ellipsis,
    LayoutDashboard,
    PiggyBank,
    ReceiptText,
    Repeat,
    Settings,
    Target,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import FAB from '@/components/FAB.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useQuickEntry } from '@/composables/useQuickEntry';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as creditCardsIndex } from '@/routes/credit-cards';
import { index as goalsIndex } from '@/routes/goals';
import { index as invoicesIndex } from '@/routes/invoices';
import { edit as editProfile } from '@/routes/profile';
import { index as recurrencesIndex } from '@/routes/recurrences/index';
import { index as transactionsIndex } from '@/routes/transactions';
import type { NavItem } from '@/types';

type BottomNavItem = NavItem & {
    soon?: boolean;
    menu?: boolean;
};

const { isCurrentOrParentUrl } = useCurrentUrl();
const { open: openQuickEntry } = useQuickEntry();

const moreOpen = ref(false);

const items: BottomNavItem[] = [
    {
        title: 'Início',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Transações',
        href: transactionsIndex(),
        icon: ArrowLeftRight,
    },
    {
        title: 'Mais',
        href: '#',
        icon: Ellipsis,
        menu: true,
    },
    {
        title: 'Ajustes',
        href: editProfile(),
        icon: Settings,
    },
];

const secondaryItems: BottomNavItem[] = [
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
        icon: ReceiptText,
    },
    {
        title: 'Orçamentos',
        href: budgetsIndex(),
        icon: Target,
    },
    {
        title: 'Metas',
        href: goalsIndex(),
        icon: PiggyBank,
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
];

const isActive = (item: BottomNavItem) =>
    !item.soon && item.href !== '#' && isCurrentOrParentUrl(item.href);

const hasActiveSecondaryItem = computed(() =>
    secondaryItems.some((item) => isActive(item)),
);

const notifySoon = (label: string) => {
    toast.info('Em breve', {
        description: `${label} será liberado na próxima etapa.`,
    });
};
</script>

<template>
    <Sheet v-model:open="moreOpen">
        <nav
            class="fixed inset-x-0 bottom-0 z-40 lg:hidden"
            aria-label="Navegação mobile"
        >
            <div class="relative mx-auto max-w-lg px-3">
                <FAB
                    class="absolute -top-7 left-1/2 z-50 -translate-x-1/2"
                    @click="openQuickEntry()"
                />

                <div
                    class="relative z-10 grid grid-cols-5 items-end rounded-t-[28px] border-t border-border bg-background/95 px-1 pt-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] shadow-[0_-8px_30px_rgba(0,0,0,0.06)] backdrop-blur-xl dark:border-white/10 dark:bg-black/90 dark:shadow-none"
                >
                    <template v-for="(item, index) in items" :key="item.title">
                        <div
                            v-if="index === 2"
                            class="h-12"
                            aria-hidden="true"
                        ></div>

                        <!-- Botão "Mais": abre o menu com os links secundários -->
                        <SheetTrigger v-if="item.menu" as-child>
                            <button
                                type="button"
                                :aria-current="
                                    hasActiveSecondaryItem ? 'page' : undefined
                                "
                                class="flex flex-col items-center justify-center gap-1 rounded-xl py-1.5 text-[11px] font-medium transition-colors"
                                :class="
                                    hasActiveSecondaryItem
                                        ? 'text-primary'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                            >
                                <component
                                    :is="item.icon"
                                    class="size-5"
                                    :stroke-width="
                                        hasActiveSecondaryItem ? 2.3 : 1.9
                                    "
                                />
                                <span>{{ item.title }}</span>
                                <span
                                    class="size-1 rounded-full"
                                    :class="
                                        hasActiveSecondaryItem
                                            ? 'bg-primary'
                                            : 'bg-transparent'
                                    "
                                />
                            </button>
                        </SheetTrigger>

                        <Link
                            v-else-if="!item.soon"
                            :href="item.href"
                            :aria-current="isActive(item) ? 'page' : undefined"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl py-1.5 text-[11px] font-medium transition-colors"
                            :class="
                                isActive(item)
                                    ? 'text-primary'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <component
                                :is="item.icon"
                                class="size-5"
                                :stroke-width="isActive(item) ? 2.3 : 1.9"
                            />
                            <span>{{ item.title }}</span>
                            <span
                                class="size-1 rounded-full"
                                :class="
                                    isActive(item)
                                        ? 'bg-primary'
                                        : 'bg-transparent'
                                "
                            />
                        </Link>

                        <button
                            v-else
                            type="button"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl py-1.5 text-[11px] font-medium text-muted-foreground transition-colors hover:text-foreground"
                            @click="notifySoon(item.title)"
                        >
                            <component
                                :is="item.icon"
                                class="size-5"
                                :stroke-width="1.9"
                            />
                            <span>{{ item.title }}</span>
                            <span class="size-1 rounded-full bg-transparent" />
                        </button>
                    </template>
                </div>
            </div>
        </nav>

        <SheetContent
            side="bottom"
            class="max-h-[85dvh] overflow-y-auto rounded-t-[28px] pb-[calc(1.5rem+env(safe-area-inset-bottom))]"
        >
            <SheetHeader class="px-5 pt-5 text-left">
                <SheetTitle>Mais opções</SheetTitle>
                <SheetDescription>
                    Acesse contas, cartões, faturas, orçamentos, metas e
                    recorrências.
                </SheetDescription>
            </SheetHeader>

            <div class="grid grid-cols-2 gap-2 px-4 pb-2 sm:grid-cols-3">
                <template
                    v-for="item in secondaryItems"
                    :key="item.title"
                >
                    <Link
                        v-if="!item.soon"
                        :href="item.href"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        class="flex items-center gap-3 rounded-2xl border border-border bg-surface-muted p-3 text-sm font-medium transition-colors hover:border-primary/40 hover:bg-primary/5 dark:border-white/10 dark:bg-card"
                        :class="
                            isActive(item)
                                ? 'border-primary/50 text-primary dark:border-primary/40'
                                : 'text-foreground'
                        "
                        @click="moreOpen = false"
                    >
                        <component
                            :is="item.icon"
                            class="size-5 shrink-0"
                            :stroke-width="isActive(item) ? 2.3 : 1.9"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>

                    <button
                        v-else
                        type="button"
                        class="flex items-center gap-3 rounded-2xl border border-border bg-surface-muted p-3 text-left text-sm font-medium text-muted-foreground opacity-70 dark:border-white/10 dark:bg-card"
                        @click="notifySoon(item.title)"
                    >
                        <component
                            :is="item.icon"
                            class="size-5 shrink-0"
                            :stroke-width="1.9"
                        />
                        <span class="truncate">{{ item.title }}</span>
                        <span
                            class="ml-auto rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                        >
                            Em breve
                        </span>
                    </button>
                </template>
            </div>
        </SheetContent>
    </Sheet>
</template>
