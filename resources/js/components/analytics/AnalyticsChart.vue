<script setup lang="ts">
import { formatDateLabel, formatNumber } from '@/composables/useAnalyticsFormatters';
import type { DailyChartItem } from '@/composables/useAnalyticsChart';

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
}>();
</script>

<template>
    <div v-if="dailyPageviews && dailyPageviews.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="text-sm font-bold text-foreground">Traffic Overview</h3>
            <div class="flex items-center gap-4 text-xs">
                <button
                    @click="emit('toggleViews')"
                    class="flex items-center gap-1.5 font-medium transition-opacity cursor-pointer"
                    :class="showViews ? 'opacity-100' : 'opacity-40 hover:opacity-70'"
                >
                    <span class="w-3 h-3 rounded-xs bg-indigo-500 inline-block"></span>
                    <span class="text-muted-foreground">Pageviews</span>
                </button>
                <button
                    @click="emit('toggleVisitors')"
                    class="flex items-center gap-1.5 font-medium transition-opacity cursor-pointer"
                    :class="showVisitors ? 'opacity-100' : 'opacity-40 hover:opacity-70'"
                >
                    <span class="w-3 h-3 rounded-xs bg-indigo-500/35 dark:bg-indigo-400/35 border border-indigo-400 inline-block"></span>
                    <span class="text-muted-foreground">Unique Visitors</span>
                </button>
            </div>
        </div>

        <!-- Bar Chart Visualizer -->
        <div class="h-44 sm:h-52 w-full flex items-end gap-1 pt-6 pb-2 relative group/chart">
            <div
                v-for="day in dailyPageviews"
                :key="day.date"
                @mouseenter="emit('update:hoveredDay', day)"
                @mouseleave="emit('update:hoveredDay', null)"
                class="flex-1 h-full flex flex-col justify-end items-center group relative cursor-pointer"
            >
                <!-- Tooltip Popup -->
                <div
                    v-if="hoveredDay?.date === day.date"
                    class="absolute bottom-full mb-2 z-20 flex flex-col items-center pointer-events-none transition-all duration-150 transform -translate-y-1"
                >
                    <div class="bg-popover border border-sidebar-border/80 text-popover-foreground px-3 py-1.5 rounded-lg shadow-xl whitespace-nowrap text-xs space-y-0.5">
                        <div class="text-xs font-bold text-foreground">{{ formatDateLabel(day.date) }}</div>
                        <div class="flex items-center gap-2 text-[10px]">
                            <span v-if="showViews" class="text-indigo-600 dark:text-indigo-400 font-bold">{{ formatNumber(day.pageviews) }} views</span>
                            <span v-if="showViews && showVisitors" class="text-muted-foreground">•</span>
                            <span v-if="showVisitors" class="text-indigo-400/70 font-bold">{{ formatNumber(day.visitors) }} visitors</span>
                        </div>
                    </div>
                    <div class="w-2 h-2 bg-popover border-r border-b border-sidebar-border/80 rotate-45 -mt-1"></div>
                </div>

                <div class="w-full flex items-end gap-[1px] h-full justify-center">
                    <!-- Pageviews Bar -->
                    <div
                        v-if="showViews"
                        class="flex-1 rounded-t-xs bg-indigo-500 dark:bg-indigo-400 transition-all duration-200 group-hover:bg-indigo-600 dark:group-hover:bg-indigo-300 min-h-[3px]"
                        :style="{ height: `${Math.max(Math.round((day.pageviews / maxDaily) * 100), 2)}%` }"
                    ></div>
                    <!-- Unique Visitors Bar -->
                    <div
                        v-if="showVisitors"
                        class="flex-1 rounded-t-xs bg-indigo-500/35 dark:bg-indigo-400/35 transition-all duration-200 group-hover:bg-indigo-500/55 dark:group-hover:bg-indigo-400/55 min-h-[2px]"
                        :style="{ height: `${Math.max(Math.round((day.visitors / maxDaily) * 100), 2)}%` }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- X-Axis Date Range Labels -->
        <div v-if="dailyPageviews.length > 0" class="flex justify-between items-center text-[9px] sm:text-[10px] font-mono text-muted-foreground pt-2 border-t border-sidebar-border/40">
            <span>{{ formatDateLabel(dailyPageviews[0].date) }}</span>
            <span v-if="dailyPageviews.length > 2">
                {{ formatDateLabel(dailyPageviews[Math.floor(dailyPageviews.length / 2)].date) }}
            </span>
            <span>{{ formatDateLabel(dailyPageviews[dailyPageviews.length - 1].date) }}</span>
        </div>
    </div>
</template>
