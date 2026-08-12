<script setup lang="ts">
import { Eye, Users } from '@lucide/vue';
import { formatNumber } from '@/composables/useAnalyticsFormatters';

defineProps<{
    currentVisitors?: number;
    totalPageviews?: number;
    uniqueVisitors?: number;
    bounceRate?: number;
    avgDuration?: number;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
        <!-- Currently Online Card (Optional) -->
        <div
            v-if="currentVisitors !== undefined"
            class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Currently Online
                </p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span
                        class="font-mono text-3xl font-extrabold text-foreground"
                        >{{ formatNumber(currentVisitors) }}</span
                    >
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
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

        <!-- Total Pageviews Card -->
        <div
            class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Total Pageviews
                </p>
                <p
                    class="mt-2 font-mono text-3xl font-extrabold text-foreground"
                >
                    {{ formatNumber(totalPageviews || 0) }}
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Total pages requested
                </p>
            </div>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
            >
                <Eye class="h-5 w-5" />
            </div>
        </div>

        <!-- Unique Visitors Card -->
        <div
            class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Unique Visitors
                </p>
                <p
                    class="mt-2 font-mono text-3xl font-extrabold text-foreground"
                >
                    {{ formatNumber(uniqueVisitors || 0) }}
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Distinct users tracked
                </p>
            </div>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400"
            >
                <Users class="h-5 w-5" />
            </div>
        </div>

        <!-- Bounce Rate & Duration (Optional) -->
        <div
            v-if="bounceRate !== undefined || avgDuration !== undefined"
            class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Bounce & Duration
                </p>
                <div class="mt-2 flex items-baseline gap-3">
                    <span
                        class="font-mono text-2xl font-extrabold text-foreground"
                        >{{ bounceRate !== undefined ? bounceRate : 0 }}%</span
                    >
                    <span
                        class="font-mono text-sm font-semibold text-muted-foreground"
                        >{{ avgDuration !== undefined ? avgDuration : 0 }}s
                        avg</span
                    >
                </div>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Single page visits / session length
                </p>
            </div>
        </div>
    </div>
</template>
