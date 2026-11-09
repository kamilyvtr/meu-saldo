<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
    variant?: 'brand' | 'inverse';
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'brand',
});

const palette = computed(() =>
    props.variant === 'inverse'
        ? {
              body: '#FFFFFF',
              flap: 'rgba(255, 255, 255, 0.72)',
              card: 'rgba(16, 185, 129, 0.9)',
              clasp: '#10B981',
              dot: '#FFFFFF',
          }
        : {
              body: '#10B981',
              flap: '#059669',
              card: '#34D399',
              clasp: '#FFFFFF',
              dot: '#10B981',
          },
);
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        fill="none"
        role="img"
        aria-hidden="true"
        :class="className"
        v-bind="$attrs"
    >
        <rect x="5" y="12" width="38" height="26" rx="8" :fill="palette.body" />
        <path
            d="M5 20.5V20C5 16.686 7.686 14 11 14H37C40.314 14 43 16.686 43 20V20.5H5Z"
            :fill="palette.flap"
        />
        <rect x="11" y="18" width="17" height="4" rx="2" :fill="palette.card" />
        <circle cx="35" cy="27" r="5" :fill="palette.clasp" />
        <circle cx="35" cy="27" r="2.25" :fill="palette.dot" />
    </svg>
</template>
