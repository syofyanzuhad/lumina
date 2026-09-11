<script setup lang="ts">
import {
    formatCompactNumber,
    formatNumber,
} from '@/composables/useAnalyticsFormatters';

withDefaults(
    defineProps<{
        currentVisitors?: number;
        totalPageviews?: number;
        uniqueVisitors?: number;
        bounceRate?: number;
        avgDuration?: number;
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
                <span class="text-xs font-medium text-muted-foreground">
                    Bounce Rate
                </span>
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
                <span class="text-xs font-medium text-muted-foreground">
                    Avg Duration
                </span>
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
