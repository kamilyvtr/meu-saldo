<script setup lang="ts">
import { Plus, Search } from '@lucide/vue';
import { toast } from 'vue-sonner';
import BrandLogo from '@/components/BrandLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserMenu from '@/components/UserMenu.vue';
import { useQuickEntry } from '@/composables/useQuickEntry';
import type { BreadcrumbItem, User } from '@/types';

withDefaults(
    defineProps<{
        user: User;
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { open: openQuickEntry } = useQuickEntry();

const notifySoon = (label: string) => {
    toast.info('Em breve', {
        description: `${label} será liberado na próxima etapa.`,
    });
};
</script>

<template>
    <header
        class="pt-safe sticky top-0 z-20 border-b border-border bg-background/85 backdrop-blur-xl dark:border-white/10 dark:bg-black/75"
    >
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
            <div class="lg:hidden">
                <BrandLogo compact />
            </div>

            <form
                class="relative hidden w-full max-w-md md:block"
                role="search"
                @submit.prevent="notifySoon('Busca')"
            >
                <Search
                    class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    type="search"
                    aria-label="Buscar transações, contas e relatórios"
                    placeholder="Buscar transações, contas..."
                    class="h-10 w-full rounded-xl border border-border bg-surface-muted pr-16 pl-10 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-transparent dark:bg-card"
                />
                <kbd
                    class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 rounded-md border border-border bg-background px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground dark:border-white/10 dark:bg-white/5"
                >
                    ⌘K
                </kbd>
            </form>

            <div
                v-if="breadcrumbs.length > 0"
                class="hidden min-w-0 items-center xl:flex"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                <button
                    type="button"
                    class="hidden items-center gap-2 rounded-xl bg-primary px-3.5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:inline-flex"
                    @click="openQuickEntry()"
                >
                    <Plus class="size-4" stroke-width="2.5" />
                    Nova transação
                </button>

                <UserMenu :user="user" />
            </div>
        </div>
    </header>
</template>
