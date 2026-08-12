<script setup lang="ts">
import { Maximize2, Filter, ExternalLink, Globe, Laptop } from '@lucide/vue';
import type { Component } from 'vue';
import {
    formatNumber,
    getCountryFlag,
} from '@/composables/useAnalyticsFormatters';

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

withDefaults(
    defineProps<{
        title: string;
        items?: BreakdownCardItem[];
        colorScheme?:
            'indigo' | 'emerald' | 'amber' | 'sky' | 'purple' | 'rose';
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
    },
);

const emit = defineEmits<{
    (e: 'filter', key: string, value: string): void;
    (e: 'expand', type: string, title: string): void;
}>();

const colorClasses: Record<
    string,
    { bg: string; hoverBg: string; text: string }
> = {
    indigo: {
        bg: 'bg-indigo-100/70 dark:bg-indigo-500/15',
        hoverBg:
            'group-hover:bg-indigo-200/80 dark:group-hover:bg-indigo-500/25',
        text: 'group-hover:text-indigo-700 dark:group-hover:text-indigo-300',
    },
    emerald: {
        bg: 'bg-emerald-100/70 dark:bg-emerald-500/15',
        hoverBg:
            'group-hover:bg-emerald-200/80 dark:group-hover:bg-emerald-500/25',
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
        hoverBg:
            'group-hover:bg-purple-200/80 dark:group-hover:bg-purple-500/25',
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
    <div
        class="group/card rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
    >
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-bold text-foreground">{{ title }}</h3>
            <div class="flex items-center gap-2">
                <span
                    v-if="items && totalItems !== undefined"
                    class="font-mono text-xs text-muted-foreground"
                >
                    <template
                        v-if="
                            totalItems <= 10 ||
                            (items.length && items.length === totalItems)
                        "
                    >
                        {{ totalItems }}
                        {{ totalItems === 1 ? 'entry' : 'entries' }}
                    </template>
                    <template v-else>
                        {{ Math.min(10, items.length || totalItems) }} of
                        {{ totalItems }} entries
                    </template>
                </span>
                <button
                    v-if="canExpand"
                    @click="emit('expand', typeKey, `${title} Breakdown`)"
                    title="Expand Details"
                    class="rounded p-1 text-muted-foreground transition-colors hover:bg-muted/80 hover:text-foreground"
                >
                    <Maximize2 class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <div
                v-for="item in (items || []).slice(0, 10)"
                :key="item.idKey"
                @click="
                    canFilter ? emit('filter', filterKey, item.label) : null
                "
                :title="
                    canFilter
                        ? `Click to filter dashboard by ${filterKey}: ${item.label}`
                        : undefined
                "
                :class="[
                    'group relative flex items-center justify-between overflow-hidden rounded-lg p-2 text-xs font-medium transition-all',
                    canFilter
                        ? 'cursor-pointer hover:opacity-90'
                        : 'cursor-default',
                ]"
            >
                <!-- Percentage Background Bar -->
                <div
                    class="absolute inset-y-0 left-0 rounded-lg transition-all duration-500"
                    :class="[
                        colorClasses[colorScheme].bg,
                        colorClasses[colorScheme].hoverBg,
                    ]"
                    :style="{ width: `${item.percentage}%` }"
                ></div>

                <span
                    class="relative z-10 mr-2 flex min-w-0 items-center gap-1.5 truncate font-mono font-medium text-foreground transition-colors"
                    :class="[colorClasses[colorScheme].text]"
                >
                    <!-- Flag Icon for Country -->
                    <span
                        v-if="typeKey === 'locations' && item.code"
                        class="text-base leading-none select-none"
                        >{{ getCountryFlag(item.code) }}</span
                    >
                    <span
                        v-if="typeKey === 'locations' && item.code"
                        class="rounded bg-muted px-1 py-0.5 text-[10px] font-bold text-muted-foreground uppercase"
                        >{{ item.code }}</span
                    >

                    <!-- Component Icon (e.g. Lucide Device Icon) -->
                    <component
                        v-if="item.isComponentIcon && item.icon"
                        :is="item.icon"
                        class="h-3.5 w-3.5 shrink-0 text-amber-500"
                    />

                    <!-- String URL Icon (e.g. Favicon / Devicon) -->
                    <img
                        v-else-if="!item.isComponentIcon && item.icon"
                        :src="String(item.icon)"
                        :alt="item.label"
                        class="h-3.5 w-3.5 shrink-0 rounded-sm object-contain dark:brightness-200 dark:invert"
                        @error="
                            ($event.target as HTMLImageElement).style.display =
                                'none'
                        "
                    />

                    <!-- Default Fallback Icons -->
                    <Globe
                        v-else-if="
                            typeKey === 'referrers' || typeKey === 'browsers'
                        "
                        class="h-3 w-3 shrink-0 text-muted-foreground/60"
                    />
                    <Laptop
                        v-else-if="typeKey === 'os'"
                        class="h-3 w-3 shrink-0 text-muted-foreground/60"
                    />

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
                        class="inline-flex shrink-0 items-center justify-center rounded p-0.5 text-muted-foreground/60 transition-colors hover:bg-muted/80 hover:text-foreground"
                    >
                        <ExternalLink class="h-3 w-3" />
                    </a>

                    <!-- Filter Icon on Hover -->
                    <Filter
                        v-if="canFilter"
                        class="ml-0.5 h-3 w-3 shrink-0 opacity-0 transition-opacity group-hover:opacity-60"
                    />
                </span>

                <span
                    class="relative z-10 shrink-0 font-mono text-[11px] text-muted-foreground"
                >
                    <span
                        class="mr-1.5 text-muted-foreground/70 opacity-0 transition-opacity group-hover/card:opacity-100"
                        >{{ item.percentage }}%</span
                    >
                    {{ formatNumber(item.count) }}
                </span>
            </div>

            <p
                v-if="!items || items.length === 0"
                class="text-xs text-muted-foreground"
            >
                {{ emptyText }}
            </p>
        </div>
    </div>
</template>
