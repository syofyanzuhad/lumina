<script setup lang="ts">
import { computed } from 'vue';
import {
    formatCompactNumber,
    formatNumber,
} from '@/composables/useAnalyticsFormatters';

const props = withDefaults(
    defineProps<{
        currentVisitors?: number;
        totalPageviews?: number;
        uniqueVisitors?: number;
        bounceRate?: number;
        avgDuration?: number;
        prevTotalPageviews?: number;
        prevUniqueVisitors?: number;
        prevBounceRate?: number;
        prevAvgDuration?: number;
        showViews?: boolean;
        showVisitors?: boolean;
    }>(),
    {
        showViews: true,
        showVisitors: true,
    },
);

const emit = defineEmits<{
    (e: 'toggleViews'): void;
    (e: 'toggleVisitors'): void;
}>();

/**
 * Compute a rounded percent change between current and previous values.
 * Returns null when the previous value is absent or zero.
 */
function percentChange(current: number | undefined, previous: number | undefined): number | null {
    if (previous === undefined || previous === null || previous === 0 || current === undefined) {
        return null;
    }
    return Math.round(((current - previous) / previous) * 100);
}

const visitorsChange = computed(() => percentChange(props.uniqueVisitors, props.prevUniqueVisitors));
const pageviewsChange = computed(() => percentChange(props.totalPageviews, props.prevTotalPageviews));
const bounceRateChange = computed(() => percentChange(props.bounceRate, props.prevBounceRate));
const avgDurationChange = computed(() => percentChange(props.avgDuration, props.prevAvgDuration));

/**
 * Determine badge colour class.
 * @param change  The percent change value.
 * @param invert  When true a negative change is good (e.g. bounce rate ↓ = better).
 */
function badgeClass(change: number | null, invert = false): string {
    if (change === null || change === 0) {
        return 'text-muted-foreground';
    }
    const isPositive = change > 0;
    const isGood = invert ? !isPositive : isPositive;
    return isGood ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400';
}

function badgeLabel(change: number | null): string {
    if (change === null) return '';
    const sign = change > 0 ? '+' : '';
    return `${sign}${change}%`;
}

function badgeArrow(change: number | null): string {
    if (change === null || change === 0) return '–';
    return change > 0 ? '↑' : '↓';
}
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-x-8 gap-y-4 border-b border-sidebar-border/60 pb-6 sm:gap-x-12"
    >
        <!-- Unique Visitors Stat -->
        <button
            type="button"
            @click="emit('toggleVisitors')"
            class="group flex cursor-pointer items-start gap-2.5 text-left transition-opacity focus:outline-none"
            :class="
                showVisitors ? 'opacity-100' : 'opacity-40 hover:opacity-75'
            "
            :title="
                showVisitors
                    ? 'Hide Visitors series from chart'
                    : 'Show Visitors series on chart'
            "
        >
            <span
                class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full transition-all"
                :class="
                    showVisitors ? 'bg-purple-600' : 'bg-muted-foreground/30'
                "
            ></span>
            <div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        {{ formatCompactNumber(uniqueVisitors || 0) }}
                    </span>
                    <span
                        v-if="(uniqueVisitors || 0) > 999"
                        class="font-mono text-[11px] text-muted-foreground"
                    >
                        ({{ formatNumber(uniqueVisitors || 0) }})
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="text-xs font-medium text-muted-foreground transition-colors group-hover:text-foreground"
                    >
                        Unique Visitors
                    </span>
                    <span
                        v-if="visitorsChange !== null"
                        class="text-[11px] font-semibold tabular-nums"
                        :class="badgeClass(visitorsChange)"
                        title="vs previous period"
                    >
                        {{ badgeArrow(visitorsChange) }} {{ badgeLabel(visitorsChange) }}
                    </span>
                </div>
            </div>
        </button>

        <!-- Total Pageviews Stat -->
        <button
            type="button"
            @click="emit('toggleViews')"
            class="group flex cursor-pointer items-start gap-2.5 text-left transition-opacity focus:outline-none"
            :class="showViews ? 'opacity-100' : 'opacity-40 hover:opacity-75'"
            :title="
                showViews
                    ? 'Hide Pageviews series from chart'
                    : 'Show Pageviews series on chart'
            "
        >
            <span
                class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full transition-all"
                :class="showViews ? 'bg-indigo-500' : 'bg-muted-foreground/30'"
            ></span>
            <div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        {{ formatCompactNumber(totalPageviews || 0) }}
                    </span>
                    <span
                        v-if="(totalPageviews || 0) > 999"
                        class="font-mono text-[11px] text-muted-foreground"
                    >
                        ({{ formatNumber(totalPageviews || 0) }})
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="text-xs font-medium text-muted-foreground transition-colors group-hover:text-foreground"
                    >
                        Total Pageviews
                    </span>
                    <span
                        v-if="pageviewsChange !== null"
                        class="text-[11px] font-semibold tabular-nums"
                        :class="badgeClass(pageviewsChange)"
                        title="vs previous period"
                    >
                        {{ badgeArrow(pageviewsChange) }} {{ badgeLabel(pageviewsChange) }}
                    </span>
                </div>
            </div>
        </button>

        <!-- Bounce Rate Stat -->
        <div v-if="bounceRate !== undefined" class="flex items-start gap-2.5">
            <span
                class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full bg-amber-500"
            ></span>
            <div>
                <div class="flex items-baseline gap-1">
                    <span
                        class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        {{ bounceRate }}%
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-medium text-muted-foreground">
                        Bounce Rate
                    </span>
                    <span
                        v-if="bounceRateChange !== null"
                        class="text-[11px] font-semibold tabular-nums"
                        :class="badgeClass(bounceRateChange, true)"
                        title="vs previous period"
                    >
                        {{ badgeArrow(bounceRateChange) }} {{ badgeLabel(bounceRateChange) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Average Session Duration Stat -->
        <div v-if="avgDuration !== undefined" class="flex items-start gap-2.5">
            <span
                class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full bg-purple-500"
            ></span>
            <div>
                <div class="flex items-baseline gap-1">
                    <span
                        class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        {{ avgDuration }}s
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-medium text-muted-foreground">
                        Avg Duration
                    </span>
                    <span
                        v-if="avgDurationChange !== null"
                        class="text-[11px] font-semibold tabular-nums"
                        :class="badgeClass(avgDurationChange)"
                        title="vs previous period"
                    >
                        {{ badgeArrow(avgDurationChange) }} {{ badgeLabel(avgDurationChange) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Currently Online Stat (if live feed not visible) -->
        <div
            v-if="currentVisitors !== undefined"
            class="flex items-start gap-2.5 sm:ml-auto"
        >
            <span
                class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full bg-emerald-500"
            ></span>
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        {{ formatNumber(currentVisitors) }}
                    </span>
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                        ></span>
                    </span>
                </div>
                <span class="text-xs font-medium text-muted-foreground">
                    Currently Online
                </span>
            </div>
        </div>
    </div>
</template>
