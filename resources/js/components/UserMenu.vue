<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ChevronDown, LogOut, Palette, UserRound } from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import type { User } from '@/types';

defineProps<{
    user: User;
}>();

const { getInitials } = useInitials();

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="flex items-center gap-2 rounded-xl border border-transparent p-1 pr-2 transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none dark:hover:bg-white/5"
                aria-label="Abrir menu do usuário"
            >
                <span
                    class="flex size-8 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary"
                >
                    {{ getInitials(user.name) }}
                </span>
                <span
                    class="hidden max-w-32 truncate text-sm font-medium sm:block"
                >
                    {{ user.name.split(' ')[0] }}
                </span>
                <ChevronDown
                    class="hidden size-4 text-muted-foreground sm:block"
                />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            :side-offset="8"
            class="w-64 rounded-2xl border-border bg-popover p-1.5 shadow-float dark:border-white/10"
        >
            <DropdownMenuLabel class="p-0 font-normal">
                <div class="flex items-center gap-3 px-2 py-2">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary"
                    >
                        {{ getInitials(user.name) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ user.name }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </DropdownMenuLabel>

            <DropdownMenuSeparator class="bg-border dark:bg-white/10" />

            <DropdownMenuGroup>
                <DropdownMenuItem :as-child="true" class="rounded-lg py-2">
                    <Link
                        class="block w-full cursor-pointer"
                        :href="editProfile()"
                    >
                        <UserRound class="mr-2 size-4" />
                        Perfil
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem :as-child="true" class="rounded-lg py-2">
                    <Link
                        class="block w-full cursor-pointer"
                        :href="editAppearance()"
                    >
                        <Palette class="mr-2 size-4" />
                        Aparência
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>

            <DropdownMenuSeparator class="bg-border dark:bg-white/10" />

            <DropdownMenuItem
                :as-child="true"
                variant="destructive"
                class="rounded-lg py-2"
            >
                <Link
                    class="block w-full cursor-pointer"
                    :href="logout()"
                    as="button"
                    @click="handleLogout"
                    data-test="logout-button"
                >
                    <LogOut class="mr-2 size-4" />
                    Sair
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
