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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Currently Online Card (Optional) -->
        <div v-if="currentVisitors !== undefined" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Currently Online</p>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-3xl font-extrabold font-mono text-foreground">{{ formatNumber(currentVisitors) }}</span>
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                </div>
                <p class="text-[11px] text-muted-foreground mt-1">Active in last 5 min</p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <Users class="h-5 w-5" />
            </div>
        </div>

        <!-- Total Pageviews Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Pageviews</p>
                <p class="text-3xl font-extrabold font-mono text-foreground mt-2">{{ formatNumber(totalPageviews || 0) }}</p>
                <p class="text-[11px] text-muted-foreground mt-1">Total pages requested</p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <Eye class="h-5 w-5" />
            </div>
        </div>

        <!-- Unique Visitors Card -->
        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Unique Visitors</p>
                <p class="text-3xl font-extrabold font-mono text-foreground mt-2">{{ formatNumber(uniqueVisitors || 0) }}</p>
                <p class="text-[11px] text-muted-foreground mt-1">Distinct users tracked</p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center shrink-0">
                <Users class="h-5 w-5" />
            </div>
        </div>

        <!-- Bounce Rate & Duration (Optional) -->
        <div v-if="bounceRate !== undefined || avgDuration !== undefined" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Bounce & Duration</p>
                <div class="flex items-baseline gap-3 mt-2">
                    <span class="text-2xl font-extrabold font-mono text-foreground">{{ bounceRate !== undefined ? bounceRate : 0 }}%</span>
                    <span class="text-sm font-semibold font-mono text-muted-foreground">{{ avgDuration !== undefined ? avgDuration : 0 }}s avg</span>
                </div>
                <p class="text-[11px] text-muted-foreground mt-1">Single page visits / session length</p>
            </div>
        </div>
    </div>
</template>
