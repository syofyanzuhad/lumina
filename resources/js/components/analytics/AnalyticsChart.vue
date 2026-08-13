<script setup lang="ts">
import type { DailyChartItem } from '@/composables/useAnalyticsChart';
import {
    formatDateLabel,
    formatNumber,
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
            <h3 class="text-sm font-bold text-foreground">Traffic Overview</h3>
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

        <!-- Bar Chart Visualizer -->
        <div
            class="group/chart relative flex h-44 w-full items-end gap-1 pt-6 pb-2 sm:h-52"
        >
            <div
                v-for="day in dailyPageviews"
                :key="day.date"
                @mouseenter="emit('update:hoveredDay', day)"
                @mouseleave="emit('update:hoveredDay', null)"
                @click="emit('selectDay', day.date)"
                class="group relative flex h-full flex-1 cursor-pointer flex-col items-center justify-end"
            >
                <!-- Tooltip Popup -->
                <div
                    v-if="hoveredDay?.date === day.date"
                    class="pointer-events-none absolute bottom-full z-20 mb-2 flex -translate-y-1 transform flex-col items-center transition-all duration-150"
                >
                    <div
                        class="space-y-0.5 rounded-lg border border-sidebar-border/80 bg-popover px-3 py-1.5 text-xs whitespace-nowrap text-popover-foreground shadow-xl"
                    >
                        <div class="text-xs font-bold text-foreground">
                            {{ formatDateLabel(day.date) }}
                        </div>
                        <div class="flex items-center gap-2 text-[10px]">
                            <span
                                v-if="showViews"
                                class="font-bold text-indigo-600 dark:text-indigo-400"
                                >{{ formatNumber(day.pageviews) }} views</span
                            >
                            <span
                                v-if="showViews && showVisitors"
                                class="text-muted-foreground"
                                >•</span
                            >
                            <span
                                v-if="showVisitors"
                                class="font-bold text-indigo-400/70"
                                >{{ formatNumber(day.visitors) }} visitors</span
                            >
                        </div>
                    </div>
                    <div
                        class="-mt-1 h-2 w-2 rotate-45 border-r border-b border-sidebar-border/80 bg-popover"
                    ></div>
                </div>

                <div
                    class="flex h-full w-full items-end justify-center gap-[1px]"
                >
                    <!-- Pageviews Bar -->
                    <div
                        v-if="showViews"
                        class="min-h-[3px] flex-1 rounded-t-xs bg-indigo-500 transition-all duration-200 group-hover:bg-indigo-600 dark:bg-indigo-400 dark:group-hover:bg-indigo-300"
                        :style="{
                            height: `${Math.max(Math.round((day.pageviews / maxDaily) * 100), 2)}%`,
                        }"
                    ></div>
                    <!-- Unique Visitors Bar -->
                    <div
                        v-if="showVisitors"
                        class="min-h-[2px] flex-1 rounded-t-xs bg-indigo-500/35 transition-all duration-200 group-hover:bg-indigo-500/55 dark:bg-indigo-400/35 dark:group-hover:bg-indigo-400/55"
                        :style="{
                            height: `${Math.max(Math.round((day.visitors / maxDaily) * 100), 2)}%`,
                        }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- X-Axis Date Range Labels -->
        <div
            v-if="dailyPageviews.length > 0"
            class="flex items-center justify-between border-t border-sidebar-border/40 pt-2 font-mono text-[9px] text-muted-foreground sm:text-[10px]"
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
            <span>{{
                formatDateLabel(dailyPageviews[dailyPageviews.length - 1].date)
            }}</span>
        </div>
    </div>
</template>
