<script setup lang="ts">
import { Eye, Users, Gauge } from '@lucide/vue';
import { formatNumber } from '@/composables/useAnalyticsFormatters';

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
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
        <!-- Currently Online Card -->
        <div
            v-if="currentVisitors !== undefined"
            class="relative flex items-center justify-between overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-4.5 shadow-sm transition-all dark:border-sidebar-border"
        >
            <div
                class="absolute top-0 bottom-0 left-0 w-1 bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.6)]"
            ></div>
            <div>
                <p
                    class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Currently Online
                </p>
                <div class="mt-1.5 flex items-baseline gap-2">
                    <span
                        class="font-mono text-2xl font-extrabold text-foreground sm:text-3xl"
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
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Active in last 5 min
                </p>
            </div>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
            >
                <Users class="h-5 w-5" />
            </div>
        </div>

        <!-- Total Pageviews Card (Interactive Series Toggle) -->
        <button
            type="button"
            @click="emit('toggleViews')"
            class="group relative flex cursor-pointer items-center justify-between overflow-hidden rounded-xl border p-4.5 text-left shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            :class="[
                showViews
                    ? 'border-indigo-500/50 bg-card dark:border-indigo-500/40'
                    : 'border-sidebar-border/50 bg-card/60 opacity-60 hover:opacity-90 dark:border-sidebar-border/40',
            ]"
            :title="
                showViews
                    ? 'Hide Pageviews series from chart'
                    : 'Show Pageviews series on chart'
            "
        >
            <div
                class="absolute top-0 bottom-0 left-0 w-1 transition-colors"
                :class="
                    showViews
                        ? 'bg-indigo-500 shadow-[0_0_12px_rgba(99,102,241,0.6)]'
                        : 'bg-muted-foreground/30'
                "
            ></div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-2 w-2 rounded-full transition-all"
                        :class="
                            showViews
                                ? 'bg-indigo-500 shadow-[0_0_6px_rgba(99,102,241,0.8)]'
                                : 'bg-muted-foreground/40'
                        "
                    ></span>
                    <p
                        class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Total Pageviews
                    </p>
                </div>
                <p
                    class="mt-1.5 font-mono text-2xl font-extrabold text-foreground sm:text-3xl"
                >
                    {{ formatNumber(totalPageviews || 0) }}
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    <span
                        class="inline-flex items-center font-medium transition-colors"
                        :class="
                            showViews
                                ? 'text-indigo-600 dark:text-indigo-400'
                                : 'text-muted-foreground'
                        "
                    >
                        {{
                            showViews ? '● On chart (bars)' : '○ Click to plot'
                        }}
                    </span>
                </p>
            </div>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-all"
                :class="
                    showViews
                        ? 'bg-indigo-500/15 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400'
                        : 'bg-muted text-muted-foreground'
                "
            >
                <Eye class="h-5 w-5" />
            </div>
        </button>

        <!-- Unique Visitors Card (Interactive Series Toggle) -->
        <button
            type="button"
            @click="emit('toggleVisitors')"
            class="group relative flex cursor-pointer items-center justify-between overflow-hidden rounded-xl border p-4.5 text-left shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
            :class="[
                showVisitors
                    ? 'border-cyan-500/50 bg-card dark:border-cyan-500/40'
                    : 'border-sidebar-border/50 bg-card/60 opacity-60 hover:opacity-90 dark:border-sidebar-border/40',
            ]"
            :title="
                showVisitors
                    ? 'Hide Visitors trend from chart'
                    : 'Show Visitors trend on chart'
            "
        >
            <div
                class="absolute top-0 bottom-0 left-0 w-1 transition-colors"
                :class="
                    showVisitors
                        ? 'bg-cyan-500 shadow-[0_0_12px_rgba(6,182,212,0.6)]'
                        : 'bg-muted-foreground/30'
                "
            ></div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-2 w-2 rounded-full transition-all"
                        :class="
                            showVisitors
                                ? 'bg-cyan-400 shadow-[0_0_6px_rgba(6,182,212,0.8)]'
                                : 'bg-muted-foreground/40'
                        "
                    ></span>
                    <p
                        class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Unique Visitors
                    </p>
                </div>
                <p
                    class="mt-1.5 font-mono text-2xl font-extrabold text-foreground sm:text-3xl"
                >
                    {{ formatNumber(uniqueVisitors || 0) }}
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    <span
                        class="inline-flex items-center font-medium transition-colors"
                        :class="
                            showVisitors
                                ? 'text-cyan-600 dark:text-cyan-400'
                                : 'text-muted-foreground'
                        "
                    >
                        {{
                            showVisitors
                                ? '● On chart (trend)'
                                : '○ Click to plot'
                        }}
                    </span>
                </p>
            </div>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-all"
                :class="
                    showVisitors
                        ? 'bg-cyan-500/15 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-400'
                        : 'bg-muted text-muted-foreground'
                "
            >
                <Users class="h-5 w-5" />
            </div>
        </button>

        <!-- Bounce Rate & Duration Card -->
        <div
            v-if="bounceRate !== undefined || avgDuration !== undefined"
            class="relative flex items-center justify-between overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-4.5 shadow-sm dark:border-sidebar-border"
        >
            <div
                class="absolute top-0 bottom-0 left-0 w-1 bg-purple-500 shadow-[0_0_12px_rgba(168,85,247,0.6)]"
            ></div>
            <div>
                <p
                    class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Bounce & Duration
                </p>
                <div class="mt-1.5 flex items-baseline gap-3">
                    <span
                        class="font-mono text-xl font-extrabold text-foreground sm:text-2xl"
                    >
                        {{ bounceRate !== undefined ? bounceRate : 0 }}%
                    </span>
                    <span
                        class="font-mono text-xs font-semibold text-muted-foreground"
                    >
                        {{ avgDuration !== undefined ? avgDuration : 0 }}s avg
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Single page / session length
                </p>
            </div>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400"
            >
                <Gauge class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
