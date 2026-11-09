<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

withDefaults(
    defineProps<{
        inverse?: boolean;
        compact?: boolean;
    }>(),
    {
        inverse: false,
        compact: false,
    },
);
</script>

<template>
    <div class="flex items-center gap-2.5">
        <AppLogoIcon
            :variant="inverse ? 'inverse' : 'brand'"
            class="size-9 shrink-0 drop-shadow-sm"
        />
        <div v-if="!compact" class="min-w-0">
            <p
                class="truncate text-[17px] leading-tight font-semibold tracking-tight"
                :class="inverse ? 'text-white' : 'text-foreground'"
            >
                Meu Saldo
            </p>
            <p
                class="truncate text-[11px] leading-tight font-medium"
                :class="inverse ? 'text-white/65' : 'text-muted-foreground'"
            >
                Finanças pessoais
            </p>
        </div>
    </div>
</template>
