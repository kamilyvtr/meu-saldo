import {
    Banknote,
    Car,
    CircleEllipsis,
    CirclePlus,
    Clapperboard,
    Gamepad2,
    GraduationCap,
    HeartPulse,
    Home,
    Laptop,
    ShoppingBag,
    Tag,
    TrendingUp,
    Utensils,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

const categoryIcons: Record<string, LucideIcon> = {
    utensils: Utensils,
    home: Home,
    car: Car,
    'heart-pulse': HeartPulse,
    'graduation-cap': GraduationCap,
    'gamepad-2': Gamepad2,
    clapperboard: Clapperboard,
    'shopping-bag': ShoppingBag,
    'circle-ellipsis': CircleEllipsis,
    banknote: Banknote,
    laptop: Laptop,
    'trending-up': TrendingUp,
    'circle-plus': CirclePlus,
};

/**
 * Resolve a stored category icon name into a Lucide component.
 */
export function categoryIcon(name: string | null | undefined): LucideIcon {
    if (!name) {
        return Tag;
    }

    return categoryIcons[name] ?? Tag;
}

/**
 * Pick a readable text color for a category chip.
 */
export function categoryTint(color: string | null | undefined): string {
    return color ?? '#71717a';
}
