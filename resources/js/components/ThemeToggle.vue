<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: string;
}>();

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const label = computed(() =>
    isDark.value ? 'Ativar tema claro' : 'Ativar tema escuro',
);

const toggleTheme = () => {
    updateAppearance(isDark.value ? 'light' : 'dark');
};
</script>

<template>
    <button
        type="button"
        :aria-label="label"
        :title="label"
        :aria-pressed="isDark"
        :class="
            cn(
                'relative inline-flex size-10 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:border-transparent dark:bg-card dark:hover:bg-white/5',
                props.class,
            )
        "
        @click="toggleTheme"
    >
        <Sun v-if="isDark" class="size-5 transition-transform" />
        <Moon v-else class="size-5 transition-transform" />
    </button>
</template>
