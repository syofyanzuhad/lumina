<script setup lang="ts">
import { Filter, X } from '@lucide/vue';

defineProps<{
    filters?: Record<string, string>;
}>();

const emit = defineEmits<{
    (e: 'removeFilter', key: string): void;
    (e: 'toggleOperator', key: string): void;
    (e: 'clearFilters'): void;
}>();

const isNegated = (val: string) => val.startsWith('!');
const displayValue = (val: string) =>
    val.startsWith('!') ? val.slice(1) : val;
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
            class="inline-flex items-center gap-1 rounded-md border bg-background px-2 py-1 font-mono text-xs shadow-2xs transition-colors"
            :class="
                isNegated(String(val))
                    ? 'border-rose-200/80 bg-rose-50/40 dark:border-rose-900/60 dark:bg-rose-950/20'
                    : 'border-sidebar-border text-foreground'
            "
        >
            <span class="font-sans text-muted-foreground capitalize"
                >{{ key }}:</span
            >
            <button
                type="button"
                @click="emit('toggleOperator', String(key))"
                :title="`Click to switch to ${isNegated(String(val)) ? 'is (include)' : 'is not (exclude)'}`"
                class="cursor-pointer rounded px-1 font-sans text-[10px] font-semibold transition-colors"
                :class="
                    isNegated(String(val))
                        ? 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-300'
                        : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-300'
                "
            >
                {{ isNegated(String(val)) ? 'is not' : 'is' }}
            </button>
            <span
                class="font-bold"
                :class="
                    isNegated(String(val))
                        ? 'text-rose-600 line-through decoration-rose-400/50 dark:text-rose-400'
                        : 'text-indigo-600 dark:text-indigo-400'
                "
            >
                {{ displayValue(String(val)) }}
            </span>
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
