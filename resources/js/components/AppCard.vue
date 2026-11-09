<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    class?: HTMLAttributes['class'];
    variant?: 'default' | 'muted' | 'accent' | 'ghost';
    padding?: 'none' | 'sm' | 'md' | 'lg';
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    padding: 'md',
});

const variantClasses = {
    default:
        'border border-border bg-card shadow-soft dark:border-transparent dark:bg-card dark:shadow-none',
    muted: 'border border-border bg-surface-muted dark:border-transparent dark:bg-surface-muted',
    accent: 'border border-transparent bg-brand-accent text-white shadow-float',
    ghost: 'bg-transparent',
};

const paddingClasses = {
    none: '',
    sm: 'p-4',
    md: 'p-5',
    lg: 'p-6 sm:p-7',
};

const classes = computed(() =>
    cn(
        'rounded-2xl transition-colors',
        variantClasses[props.variant],
        paddingClasses[props.padding],
        props.class,
    ),
);
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
