<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        label?: string;
        class?: string;
    }>(),
    {
        label: 'Adicionar transação',
    },
);

const emit = defineEmits<{
    click: [];
}>();
</script>

<template>
    <button
        type="button"
        :aria-label="props.label"
        :title="props.label"
        :class="
            cn(
                'group inline-flex size-14 items-center justify-center rounded-full bg-expense text-white shadow-float ring-4 ring-background transition-transform duration-200 hover:scale-105 focus-visible:ring-primary/40 focus-visible:outline-none active:scale-95',
                props.class,
            )
        "
        @click="emit('click')"
    >
        <Plus
            class="size-7 transition-transform duration-200 group-hover:rotate-90"
            stroke-width="2.25"
        />
    </button>
</template>
