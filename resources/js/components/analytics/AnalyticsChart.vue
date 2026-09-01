<script setup lang="ts">
import type { DailyChartItem } from '@/composables/useAnalyticsChart';
import {
    formatCompactNumber,
    formatDateLabel,
    formatNumber,
    isCurrentPeriod,
} from '@/composables/useAnalyticsFormatters';

defineProps<{
    dailyPageviews?: DailyChartItem[];
    showViews: boolean;
    showVisitors: boolean;
    hoveredDay: DailyChartItem | null;
    maxDaily: number;
}>();

const emit = defineEmits<{
    (e: 'update:hoveredDay', day: DailyChartItem | null): void;
    (e: 'toggleViews'): void;
    (e: 'toggleVisitors'): void;
    (e: 'selectDay', date: string): void;
}>();
</script>

<template>
    <div
        v-if="dailyPageviews && dailyPageviews.length > 0"
        class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
    >
        <div
            class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-foreground">
                    Traffic Overview
                </h3>
                <span
                    v-if="dailyPageviews.some((d) => isCurrentPeriod(d.date))"
                    class="hidden items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-600 sm:inline-flex dark:text-emerald-400"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                    ></span>
                    Live
                </span>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <button
                    @click="emit('toggleViews')"
                    class="flex cursor-pointer items-center gap-1.5 font-medium transition-opacity"
                    :class="
                        showViews
                            ? 'opacity-100'
                            : 'opacity-40 hover:opacity-70'
                    "
                >
                    <span
                        class="inline-block h-3 w-3 rounded-xs bg-indigo-500"
                    ></span>
                    <span class="text-muted-foreground">Pageviews</span>
                </button>
                <button
                    @click="emit('toggleVisitors')"
                    class="flex cursor-pointer items-center gap-1.5 font-medium transition-opacity"
                    :class="
                        showVisitors
                            ? 'opacity-100'
                            : 'opacity-40 hover:opacity-70'
                    "
                >
                    <span
                        class="inline-block h-3 w-3 rounded-xs border border-indigo-400 bg-indigo-500/35 dark:bg-indigo-400/35"
                    ></span>
                    <span class="text-muted-foreground">Unique Visitors</span>
                </button>
            </div>
        </div>

        <!-- Chart Container with Y-Axis Legend and Gridlines -->
        <div class="relative flex h-48 w-full gap-2 pt-6 pb-2 sm:h-56">
            <!-- Y-Axis Value Labels -->
            <div
                class="pointer-events-none flex h-full w-8 flex-col justify-between text-right font-mono text-[10px] text-muted-foreground/70 select-none sm:w-10 sm:text-[11px]"
            >
                <span>{{ formatCompactNumber(maxDaily) }}</span>
                <span>{{ formatCompactNumber(Math.round(maxDaily / 2)) }}</span>
                <span>0</span>
            </div>

            <!-- Chart Canvas & Bars -->
            <div
                class="group/chart relative flex h-full flex-1 items-end gap-1"
            >
                <!-- Background Horizontal Gridlines -->
                <div
                    class="pointer-events-none absolute inset-0 flex flex-col justify-between"
                >
                    <!-- Top Guideline (100%) -->
                    <div
                        class="w-full border-t border-dashed border-sidebar-border/60 dark:border-sidebar-border/40"
                    ></div>
                    <!-- Mid Guideline (50%) -->
                    <div
                        class="w-full border-t border-dashed border-sidebar-border/40 dark:border-sidebar-border/25"
                    ></div>
                    <!-- Bottom Guideline (0%) -->
                    <div
                        class="w-full border-t border-sidebar-border/70 dark:border-sidebar-border/60"
                    ></div>
                </div>

                <!-- Interactive Bar Columns -->
                <div
                    v-for="day in dailyPageviews"
                    :key="day.date"
                    @mouseenter="emit('update:hoveredDay', day)"
                    @mouseleave="emit('update:hoveredDay', null)"
                    @click="emit('selectDay', day.date)"
                    class="group relative z-10 flex h-full flex-1 cursor-pointer flex-col items-center justify-end"
                >
                    <!-- Tooltip Popup -->
                    <div
                        v-if="hoveredDay?.date === day.date"
                        class="pointer-events-none absolute bottom-full z-30 mb-2 flex -translate-y-1 transform flex-col items-center transition-all duration-150"
                    >
                        <div
                            class="space-y-1 rounded-lg border border-sidebar-border/80 bg-popover px-3 py-2 text-xs whitespace-nowrap text-popover-foreground shadow-xl"
                        >
                            <div
                                class="flex items-center gap-1.5 text-xs font-bold text-foreground"
                            >
                                <span>{{ formatDateLabel(day.date) }}</span>
                                <span
                                    v-if="isCurrentPeriod(day.date)"
                                    class="rounded bg-emerald-500/15 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    In progress
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-[10px]">
                                <span
                                    v-if="showViews"
                                    class="font-bold text-indigo-600 dark:text-indigo-400"
                                    >{{
                                        formatNumber(day.pageviews)
                                    }}
                                    views</span
                                >
                                <span
                                    v-if="showViews && showVisitors"
                                    class="text-muted-foreground"
                                    >•</span
                                >
                                <span
                                    v-if="showVisitors"
                                    class="font-bold text-indigo-400/80"
                                    >{{
                                        formatNumber(day.visitors)
                                    }}
                                    visitors</span
                                >
                            </div>
                        </div>
                        <div
                            class="-mt-1 h-2 w-2 rotate-45 border-r border-b border-sidebar-border/80 bg-popover"
                        ></div>
                    </div>

                    <!-- Visual Indicator for Current Day / Ongoing Period -->
                    <div
                        v-if="isCurrentPeriod(day.date)"
                        class="pointer-events-none absolute -top-3 z-10 hidden items-center justify-center sm:flex"
                        title="Current in-progress period"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-emerald-500 ring-2 ring-background"
                        ></span>
                    </div>

                    <div
                        class="flex h-full w-full items-end justify-center gap-[1px]"
                    >
                        <!-- Pageviews Bar -->
                        <div
                            v-if="showViews"
                            class="min-h-[3px] flex-1 rounded-t-xs transition-all duration-200"
                            :class="[
                                isCurrentPeriod(day.date)
                                    ? 'border-t border-emerald-400 bg-gradient-to-t from-indigo-500 to-indigo-400 group-hover:from-indigo-600 group-hover:to-indigo-500 dark:from-indigo-500 dark:to-indigo-400'
                                    : 'bg-indigo-500 group-hover:bg-indigo-600 dark:bg-indigo-400 dark:group-hover:bg-indigo-300',
                            ]"
                            :style="{
                                height: `${Math.max(Math.round((day.pageviews / maxDaily) * 100), 2)}%`,
                            }"
                        ></div>
                        <!-- Unique Visitors Bar -->
                        <div
                            v-if="showVisitors"
                            class="min-h-[2px] flex-1 rounded-t-xs transition-all duration-200"
                            :class="[
                                isCurrentPeriod(day.date)
                                    ? 'border-t border-emerald-400/70 bg-indigo-500/45 group-hover:bg-indigo-500/65 dark:bg-indigo-400/45 dark:group-hover:bg-indigo-400/65'
                                    : 'bg-indigo-500/35 group-hover:bg-indigo-500/55 dark:bg-indigo-400/35 dark:group-hover:bg-indigo-400/55',
                            ]"
                            :style="{
                                height: `${Math.max(Math.round((day.visitors / maxDaily) * 100), 2)}%`,
                            }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- X-Axis Date Range Labels -->
        <div
            v-if="dailyPageviews.length > 0"
            class="flex items-center justify-between border-t border-sidebar-border/40 pt-2 pl-8 font-mono text-[9px] text-muted-foreground sm:pl-10 sm:text-[10px]"
        >
            <span>{{ formatDateLabel(dailyPageviews[0].date) }}</span>
            <span v-if="dailyPageviews.length > 2">
                {{
                    formatDateLabel(
                        dailyPageviews[Math.floor(dailyPageviews.length / 2)]
                            .date,
                    )
                }}
            </span>
            <span class="flex items-center gap-1">
                <span>{{
                    formatDateLabel(
                        dailyPageviews[dailyPageviews.length - 1].date,
                    )
                }}</span>
                <span
                    v-if="
                        isCurrentPeriod(
                            dailyPageviews[dailyPageviews.length - 1].date,
                        )
                    "
                    class="font-sans text-[9px] text-emerald-600 dark:text-emerald-400"
                    >{{
                        dailyPageviews[0].date.includes(' ')
                            ? '(Now)'
                            : '(Today)'
                    }}</span
                >
            </span>
        </div>
    </div>
</template>
