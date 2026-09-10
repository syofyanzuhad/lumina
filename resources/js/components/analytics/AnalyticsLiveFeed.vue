<script setup lang="ts">
import { ExternalLink, Radio } from '@lucide/vue';
import { computed } from 'vue';
import type { BreakdownCardItem } from '@/components/analytics/AnalyticsBreakdownCard.vue';
import {
    formatNumber,
    getCountryFlag,
} from '@/composables/useAnalyticsFormatters';

const props = withDefaults(
    defineProps<{
        currentVisitors?: number;
        siteDomain?: string;
        topPages?: BreakdownCardItem[];
        topReferrers?: BreakdownCardItem[];
        topCountries?: BreakdownCardItem[];
        canFilter?: boolean;
    }>(),
    {
        currentVisitors: 0,
        canFilter: true,
    },
);

const emit = defineEmits<{
    (e: 'filter', key: string, value: string): void;
}>();

// Synthesize recent active real-time visitor streams from top real-time breakdown data
const activeStream = computed(() => {
    const pages = (props.topPages || []).slice(0, 4);
    const referrers = props.topReferrers || [];
    const countries = props.topCountries || [];

    if (!pages.length) {
        return [];
    }

    return pages.map((p, idx) => {
        const refItem = referrers[idx % (referrers.length || 1)];
        const countryItem = countries[idx % (countries.length || 1)];

        return {
            id: `live-visitor-${idx}`,
            path: p.path || p.label || '/',
            count: p.count,
            referrer: refItem?.label || 'Direct',
            referrerIcon: refItem?.icon,
            countryCode: countryItem?.code,
            countryName: countryItem?.label || 'Unknown',
            countryFlag: getCountryFlag(countryItem?.code),
        };
    });
});
</script>

<template>
    <div
        class="relative flex flex-col justify-between overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm sm:p-6 dark:border-sidebar-border"
    >
        <!-- Top Glow Ambient Border -->
        <div
            class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full bg-emerald-500/10 blur-2xl"
        ></div>

        <div>
            <!-- Header with pulsing Live indicator -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"
                        ></span>
                    </span>
                    <span
                        class="text-xs font-bold tracking-wider text-foreground uppercase"
                    >
                        Live Activity
                    </span>
                </div>
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    <span class="font-mono font-bold">{{
                        formatNumber(currentVisitors)
                    }}</span>
                    <span>online</span>
                </div>
            </div>

            <p class="mt-2 text-xs text-muted-foreground">
                Real-time sessions and active destination paths
            </p>

            <!-- Active Visitor Feed List -->
            <div class="mt-4 space-y-2.5">
                <div
                    v-for="item in activeStream"
                    :key="item.id"
                    class="group flex items-center justify-between rounded-lg border border-sidebar-border/50 bg-muted/30 p-2.5 transition-all hover:border-sidebar-border hover:bg-muted/60"
                >
                    <div class="flex min-w-0 items-center gap-2.5">
                        <!-- Visitor Badge / Flag -->
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-sidebar-border/60 bg-background text-xs shadow-xs"
                            :title="item.countryName"
                        >
                            <span class="text-sm select-none">{{
                                item.countryFlag
                            }}</span>
                        </div>

                        <div class="min-w-0">
                            <!-- Page Path -->
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    v-if="canFilter"
                                    @click="emit('filter', 'path', item.path)"
                                    class="truncate text-xs font-semibold text-foreground transition-colors hover:text-indigo-600 hover:underline dark:hover:text-indigo-400"
                                    :title="`Filter by ${item.path}`"
                                >
                                    {{ item.path }}
                                </button>
                                <span
                                    v-else
                                    class="truncate text-xs font-semibold text-foreground"
                                >
                                    {{ item.path }}
                                </span>
                            </div>

                            <!-- Referrer & Location Details -->
                            <div
                                class="flex items-center gap-1.5 text-[10px] text-muted-foreground"
                            >
                                <img
                                    v-if="
                                        item.referrerIcon &&
                                        typeof item.referrerIcon === 'string'
                                    "
                                    :src="item.referrerIcon"
                                    class="h-3 w-3 shrink-0 rounded-xs"
                                    alt=""
                                />
                                <span class="truncate">{{
                                    item.referrer
                                }}</span>
                                <span>•</span>
                                <span class="truncate">{{
                                    item.countryName
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active View Count Indicator -->
                    <div
                        class="flex items-center gap-1.5 pl-2 font-mono text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <span class="relative flex h-1.5 w-1.5">
                            <span
                                class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"
                            ></span>
                        </span>
                        <span>{{ formatNumber(item.count) }}</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="activeStream.length === 0"
                    class="flex flex-col items-center justify-center py-6 text-center text-xs text-muted-foreground"
                >
                    <Radio
                        class="h-6 w-6 animate-pulse stroke-1 text-muted-foreground/50"
                    />
                    <span class="mt-2">No active sessions right now</span>
                    <span class="text-[11px] text-muted-foreground/70"
                        >Visitors will appear here in real time</span
                    >
                </div>
            </div>
        </div>

        <!-- Footer / External Visit site link -->
        <div
            v-if="siteDomain"
            class="mt-4 flex items-center justify-between border-t border-sidebar-border/50 pt-3 text-[11px] text-muted-foreground"
        >
            <span class="truncate">{{ siteDomain }}</span>
            <a
                :href="`https://${siteDomain}`"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 font-medium text-foreground transition-colors hover:text-indigo-600 dark:hover:text-indigo-400"
            >
                <span>Open site</span>
                <ExternalLink class="h-3 w-3" />
            </a>
        </div>
    </div>
</template>
