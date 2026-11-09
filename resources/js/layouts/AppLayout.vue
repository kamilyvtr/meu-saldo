<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BottomNav from '@/components/BottomNav.vue';
import QuickEntry from '@/components/finance/QuickEntry.vue';
import Sidebar from '@/components/Sidebar.vue';
import Topbar from '@/components/Topbar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem, User } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user as User);
</script>

<template>
    <div class="min-h-dvh bg-background text-foreground">
        <Sidebar />

        <div class="min-h-dvh lg:pl-[272px]">
            <Topbar :user="user" :breadcrumbs="breadcrumbs" />

            <main
                class="mx-auto w-full max-w-[1440px] px-4 pt-6 pb-[calc(7.5rem+env(safe-area-inset-bottom))] sm:px-6 lg:px-8 lg:pb-10"
            >
                <slot />
            </main>
        </div>

        <BottomNav />
        <QuickEntry />
        <Toaster position="top-center" />
    </div>
</template>
