<script setup lang="ts">
import { Filter, X } from '@lucide/vue';

defineProps<{
    filters?: Record<string, string>;
}>();

const emit = defineEmits<{
    (e: 'removeFilter', key: string): void;
    (e: 'clearFilters'): void;
}>();
</script>

<template>
    <div v-if="filters && Object.keys(filters).length > 0" class="flex flex-wrap items-center gap-2 bg-muted/60 border border-sidebar-border/60 rounded-xl p-3">
        <span class="text-xs font-semibold text-muted-foreground flex items-center gap-1">
            <Filter class="h-3.5 w-3.5" />
            Active Filters:
        </span>
        <span
            v-for="(val, key) in filters"
            :key="key"
            class="inline-flex items-center gap-1 text-xs font-mono bg-background border border-sidebar-border px-2.5 py-1 rounded-md text-foreground shadow-2xs"
        >
            <span class="text-muted-foreground capitalize font-sans">{{ key }}:</span>
            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ val }}</span>
            <button
                @click="emit('removeFilter', String(key))"
                class="hover:text-destructive text-muted-foreground/70 transition-colors ml-0.5 p-0.5 rounded-sm"
                title="Remove filter"
            >
                <X class="h-3 w-3" />
            </button>
        </span>
        <button
            @click="emit('clearFilters')"
            class="text-xs text-muted-foreground hover:text-foreground underline underline-offset-2 ml-auto font-medium transition-colors"
        >
            Clear all
        </button>
    </div>
</template>
