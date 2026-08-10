<script setup lang="ts">
import { Maximize2, Filter, ExternalLink, Globe, Laptop } from '@lucide/vue';
import type { Component } from 'vue';
import { formatNumber, getCountryFlag } from '@/composables/useAnalyticsFormatters';

export interface BreakdownCardItem {
    idKey: string;
    label: string;
    count: number;
    percentage: number;
    icon?: string | Component | null;
    isComponentIcon?: boolean;
    code?: string;
    path?: string;
    referrer?: string;
}

const props = withDefaults(
    defineProps<{
        title: string;
        items?: BreakdownCardItem[];
        colorScheme?: 'indigo' | 'emerald' | 'amber' | 'sky' | 'purple' | 'rose';
        totalItems?: number;
        filterKey: string;
        typeKey: string;
        siteDomain?: string;
        canFilter?: boolean;
        canExpand?: boolean;
        emptyText?: string;
    }>(),
    {
        colorScheme: 'indigo',
        canFilter: true,
        canExpand: true,
        emptyText: 'No data recorded yet.',
    }
);

const emit = defineEmits<{
    (e: 'filter', key: string, value: string): void;
    (e: 'expand', type: string, title: string): void;
}>();

const colorClasses: Record<string, { bg: string; hoverBg: string; text: string }> = {
    indigo: {
        bg: 'bg-indigo-100/70 dark:bg-indigo-500/15',
        hoverBg: 'group-hover:bg-indigo-200/80 dark:group-hover:bg-indigo-500/25',
        text: 'group-hover:text-indigo-700 dark:group-hover:text-indigo-300',
    },
    emerald: {
        bg: 'bg-emerald-100/70 dark:bg-emerald-500/15',
        hoverBg: 'group-hover:bg-emerald-200/80 dark:group-hover:bg-emerald-500/25',
        text: 'group-hover:text-emerald-700 dark:group-hover:text-emerald-300',
    },
    amber: {
        bg: 'bg-amber-100/70 dark:bg-amber-500/15',
        hoverBg: 'group-hover:bg-amber-200/80 dark:group-hover:bg-amber-500/25',
        text: 'group-hover:text-amber-700 dark:group-hover:text-amber-300',
    },
    sky: {
        bg: 'bg-sky-100/70 dark:bg-sky-500/15',
        hoverBg: 'group-hover:bg-sky-200/80 dark:group-hover:bg-sky-500/25',
        text: 'group-hover:text-sky-700 dark:group-hover:text-sky-300',
    },
    purple: {
        bg: 'bg-purple-100/70 dark:bg-purple-500/15',
        hoverBg: 'group-hover:bg-purple-200/80 dark:group-hover:bg-purple-500/25',
        text: 'group-hover:text-purple-700 dark:group-hover:text-purple-300',
    },
    rose: {
        bg: 'bg-rose-100/70 dark:bg-rose-500/15',
        hoverBg: 'group-hover:bg-rose-200/80 dark:group-hover:bg-rose-500/25',
        text: 'group-hover:text-rose-700 dark:group-hover:text-rose-300',
    },
};
</script>

<template>
    <div class="group/card rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-foreground">{{ title }}</h3>
            <div class="flex items-center gap-2">
                <span v-if="items && totalItems !== undefined" class="text-xs text-muted-foreground font-mono">
                    <template v-if="totalItems <= 10 || (items.length && items.length === totalItems)">
                        {{ totalItems }} {{ totalItems === 1 ? 'entry' : 'entries' }}
                    </template>
                    <template v-else>
                        {{ Math.min(10, items.length || totalItems) }} of {{ totalItems }} entries
                    </template>
                </span>
                <button
                    v-if="canExpand"
                    @click="emit('expand', typeKey, `${title} Breakdown`)"
                    title="Expand Details"
                    class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                >
                    <Maximize2 class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <div
                v-for="item in (items || []).slice(0, 10)"
                :key="item.idKey"
                @click="canFilter ? emit('filter', filterKey, item.label) : null"
                :title="canFilter ? `Click to filter dashboard by ${filterKey}: ${item.label}` : undefined"
                :class="[
                    'group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg transition-all overflow-hidden',
                    canFilter ? 'hover:opacity-90 cursor-pointer' : 'cursor-default'
                ]"
            >
                <!-- Percentage Background Bar -->
                <div
                    class="absolute inset-y-0 left-0 rounded-lg transition-all duration-500"
                    :class="[colorClasses[colorScheme].bg, colorClasses[colorScheme].hoverBg]"
                    :style="{ width: `${item.percentage}%` }"
                ></div>

                <span
                    class="relative z-10 truncate font-mono text-foreground font-medium transition-colors mr-2 flex items-center gap-1.5 min-w-0"
                    :class="[colorClasses[colorScheme].text]"
                >
                    <!-- Flag Icon for Country -->
                    <span v-if="typeKey === 'locations' && item.code" class="text-base leading-none select-none">{{ getCountryFlag(item.code) }}</span>
                    <span v-if="typeKey === 'locations' && item.code" class="text-[10px] font-bold px-1 py-0.5 rounded bg-muted text-muted-foreground uppercase">{{ item.code }}</span>

                    <!-- Component Icon (e.g. Lucide Device Icon) -->
                    <component
                        v-if="item.isComponentIcon && item.icon"
                        :is="item.icon"
                        class="h-3.5 w-3.5 text-amber-500 shrink-0"
                    />

                    <!-- String URL Icon (e.g. Favicon / Devicon) -->
                    <img
                        v-else-if="!item.isComponentIcon && item.icon"
                        :src="String(item.icon)"
                        :alt="item.label"
                        class="h-3.5 w-3.5 rounded-sm shrink-0 object-contain dark:invert dark:brightness-200"
                        @error="($event.target as HTMLImageElement).style.display = 'none'"
                    />

                    <!-- Default Fallback Icons -->
                    <Globe v-else-if="typeKey === 'referrers' || typeKey === 'browsers'" class="h-3 w-3 shrink-0 text-muted-foreground/60" />
                    <Laptop v-else-if="typeKey === 'os'" class="h-3 w-3 shrink-0 text-muted-foreground/60" />

                    <!-- Item Label -->
                    <span class="truncate">{{ item.label }}</span>

                    <!-- Path External Link Icon -->
                    <a
                        v-if="item.path && siteDomain"
                        :href="`https://${siteDomain}${item.path.startsWith('/') ? '' : '/'}${item.path}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click.stop
                        title="Open page in new tab"
                        class="p-0.5 rounded text-muted-foreground/60 hover:text-foreground hover:bg-muted/80 transition-colors shrink-0 inline-flex items-center justify-center"
                    >
                        <ExternalLink class="h-3 w-3" />
                    </a>

                    <!-- Filter Icon on Hover -->
                    <Filter v-if="canFilter" class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0 ml-0.5" />
                </span>

                <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">
                    <span class="text-muted-foreground/70 opacity-0 group-hover/card:opacity-100 transition-opacity mr-1.5">{{ item.percentage }}%</span>
                    {{ formatNumber(item.count) }}
                </span>
            </div>

            <p v-if="!items || items.length === 0" class="text-xs text-muted-foreground">{{ emptyText }}</p>
        </div>
    </div>
</template>
