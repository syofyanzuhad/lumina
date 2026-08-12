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
    <div
        v-if="filters && Object.keys(filters).length > 0"
        class="flex flex-wrap items-center gap-2 rounded-xl border border-sidebar-border/60 bg-muted/60 p-3"
    >
        <span
            class="flex items-center gap-1 text-xs font-semibold text-muted-foreground"
        >
            <Filter class="h-3.5 w-3.5" />
            Active Filters:
        </span>
        <span
            v-for="(val, key) in filters"
            :key="key"
            class="inline-flex items-center gap-1 rounded-md border border-sidebar-border bg-background px-2.5 py-1 font-mono text-xs text-foreground shadow-2xs"
        >
            <span class="font-sans text-muted-foreground capitalize"
                >{{ key }}:</span
            >
            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{
                val
            }}</span>
            <button
                @click="emit('removeFilter', String(key))"
                class="ml-0.5 rounded-sm p-0.5 text-muted-foreground/70 transition-colors hover:text-destructive"
                title="Remove filter"
            >
                <X class="h-3 w-3" />
            </button>
        </span>
        <button
            @click="emit('clearFilters')"
            class="ml-auto text-xs font-medium text-muted-foreground underline underline-offset-2 transition-colors hover:text-foreground"
        >
            Clear all
        </button>
    </div>
</template>
