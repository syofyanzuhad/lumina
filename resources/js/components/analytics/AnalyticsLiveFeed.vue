<script setup lang="ts">
import { Clock, ExternalLink, Radio } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    formatNumber,
    formatRelativeTimeCompact,
    getBrowserIcon,
    getCountryFlag,
    getDeviceIcon,
    getReferrerFavicon,
} from '@/composables/useAnalyticsFormatters';

export interface LiveSessionItem {
    session_id: string;
    path: string;
    referrer?: string;
    country_code?: string;
    country_name?: string;
    browser?: string;
    os?: string;
    device?: string;
    created_at?: string;
}

const props = withDefaults(
    defineProps<{
        currentVisitors?: number;
        siteDomain?: string;
        liveVisitors?: LiveSessionItem[];
        canFilter?: boolean;
        loading?: boolean;
    }>(),
    {
        currentVisitors: 0,
        canFilter: true,
        loading: false,
    },
);

const emit = defineEmits<{
    (e: 'filter', key: string, value: string): void;
}>();

// Ticker to recompute relative timestamps every 5 seconds
const now = ref(Date.now());
let tickerInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    tickerInterval = setInterval(() => {
        now.value = Date.now();
    }, 5000);
});

onUnmounted(() => {
    if (tickerInterval) {
        clearInterval(tickerInterval);
        tickerInterval = null;
    }
});

// Deterministic playful avatar generation based on session/visitor hash
function getAvatarUrl(seed: string): string {
    const cleanSeed = encodeURIComponent(seed || 'anonymous');

    return `https://api.dicebear.com/9.x/micah/svg?seed=${cleanSeed}&backgroundColor=ffdfbf,ffd5dc,d1d4f9,c0aede,b6e3f4`;
}

// Extract clean host or platform for referrer
function formatSource(ref?: string): string {
    if (!ref || ref === 'Direct' || ref === 'Direct / None') {
        return 'Direct';
    }

    try {
        if (ref.includes('://')) {
            const host = new URL(ref).hostname;

            return host.replace(/^www\./, '');
        }
    } catch {
        // Fallback to raw string
    }

    return ref;
}

// Normalize the active live sessions — strictly from liveVisitors data
const sessions = computed(() => {
    if (!props.liveVisitors || props.liveVisitors.length === 0) {
        return [];
    }

    return props.liveVisitors.map((v, idx) => ({
        id: v.session_id || `live-${idx}`,
        path: v.path || '/',
        referrer: formatSource(v.referrer),
        referrerFavicon: getReferrerFavicon(v.referrer || ''),
        countryCode: v.country_code,
        countryName: v.country_name || 'Unknown',
        countryFlag: getCountryFlag(v.country_code),
        browser: v.browser || 'Unknown',
        browserIcon: getBrowserIcon(v.browser || ''),
        device: v.device || 'desktop',
        deviceIcon: getDeviceIcon(v.device || 'desktop'),
        avatar: getAvatarUrl(v.session_id || `visitor-${idx}`),
        timeAgo: formatRelativeTimeCompact(v.created_at, now.value),
    }));
});
</script>

<template>
    <div
        class="relative flex h-full flex-col justify-between overflow-hidden p-4 sm:p-5"
    >
        <div class="flex min-h-0 flex-1 flex-col">
            <!-- Header with pulsing Live indicator -->
            <div
                class="flex shrink-0 items-center justify-between border-b border-sidebar-border/40 pb-3"
            >
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                        ></span>
                    </span>
                    <span
                        class="text-xs font-bold tracking-tight text-foreground"
                    >
                        {{ formatNumber(currentVisitors) }}
                        {{ currentVisitors === 1 ? 'user' : 'users' }} online
                    </span>
                </div>

                <div
                    class="font-mono text-[10px] tracking-wider text-muted-foreground uppercase"
                >
                    Live Session Feed
                </div>
            </div>

            <!-- Loading Skeleton State -->
            <div
                v-if="loading"
                class="mt-3.5 max-h-[300px] flex-1 space-y-3 overflow-hidden pr-0.5 sm:max-h-[340px]"
            >
                <div
                    v-for="i in 3"
                    :key="i"
                    class="flex items-start gap-3 rounded-lg border border-sidebar-border/30 bg-muted/20 p-2.5"
                >
                    <!-- Avatar Skeleton -->
                    <div
                        class="h-9 w-9 shrink-0 animate-pulse rounded-full bg-muted/70"
                    ></div>

                    <!-- Metadata Skeleton -->
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex items-center justify-between">
                            <div
                                class="h-3.5 w-24 animate-pulse rounded-xs bg-muted/70"
                            ></div>
                            <div
                                class="h-2.5 w-10 animate-pulse rounded-xs bg-muted/50"
                            ></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                class="h-2.5 w-16 animate-pulse rounded-xs bg-muted/50"
                            ></div>
                            <div
                                class="h-2.5 w-12 animate-pulse rounded-xs bg-muted/50"
                            ></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                class="h-2.5 w-14 animate-pulse rounded-xs bg-muted/50"
                            ></div>
                            <div
                                class="h-2.5 w-12 animate-pulse rounded-xs bg-muted/50"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active User Sessions List (Scrollable to stay exactly in sync with chart) -->
            <div
                v-else
                class="mt-3.5 max-h-[300px] flex-1 space-y-3 overflow-y-auto pr-0.5 sm:max-h-[340px]"
            >
                <div
                    v-for="item in sessions"
                    :key="item.id"
                    class="group flex items-start gap-3 rounded-lg border border-sidebar-border/30 bg-muted/20 p-2.5 transition-all hover:border-sidebar-border/70 hover:bg-muted/40"
                >
                    <!-- User Placeholder Avatar -->
                    <div
                        class="relative h-9 w-9 shrink-0 overflow-hidden rounded-full border border-sidebar-border/70 bg-muted shadow-xs"
                    >
                        <img
                            :src="item.avatar"
                            :alt="item.countryName"
                            class="h-full w-full object-cover select-none"
                            loading="lazy"
                        />
                    </div>

                    <!-- Session Metadata & Page -->
                    <div class="min-w-0 flex-1">
                        <!-- Visited Page Path & Time -->
                        <div class="flex items-center justify-between gap-1.5">
                            <button
                                type="button"
                                v-if="canFilter"
                                @click="emit('filter', 'path', item.path)"
                                class="truncate text-xs font-bold text-foreground transition-colors hover:text-indigo-600 hover:underline dark:hover:text-indigo-400"
                                :title="`Filter by ${item.path}`"
                            >
                                {{ item.path }}
                            </button>
                            <span
                                v-else
                                class="truncate text-xs font-bold text-foreground"
                            >
                                {{ item.path }}
                            </span>

                            <!-- Compact Time & Active Dot -->
                            <div
                                class="flex shrink-0 items-center gap-1.5 text-[10px] text-muted-foreground/80"
                            >
                                <Clock
                                    class="h-2.5 w-2.5 shrink-0 opacity-70"
                                />
                                <span class="font-mono whitespace-nowrap">{{
                                    item.timeAgo
                                }}</span>
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500/80"
                                    title="Active session"
                                ></span>
                            </div>
                        </div>

                        <!-- Location & Source Row -->
                        <div
                            class="mt-1 flex items-center gap-1.5 text-[11px] text-muted-foreground"
                        >
                            <span
                                class="text-xs select-none"
                                :title="item.countryName"
                            >
                                {{ item.countryFlag }}
                            </span>
                            <span
                                class="truncate font-medium text-foreground/80"
                            >
                                {{ item.countryName }}
                            </span>
                            <span class="text-muted-foreground/60">•</span>
                            <span class="truncate text-muted-foreground">
                                {{ item.referrer }}
                            </span>
                        </div>

                        <!-- Device & Browser Badges -->
                        <div
                            class="mt-1.5 flex items-center gap-2 text-[10px] text-muted-foreground"
                        >
                            <!-- Browser -->
                            <div class="flex items-center gap-1">
                                <img
                                    v-if="item.browserIcon"
                                    :src="item.browserIcon"
                                    class="h-3 w-3 shrink-0"
                                    alt=""
                                />
                                <span class="capitalize">{{
                                    item.browser
                                }}</span>
                            </div>

                            <span class="text-muted-foreground/40">•</span>

                            <!-- Device -->
                            <div class="flex items-center gap-1">
                                <component
                                    :is="item.deviceIcon"
                                    class="h-3 w-3 shrink-0 text-muted-foreground"
                                />
                                <span class="capitalize">{{
                                    item.device
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="sessions.length === 0"
                    class="flex flex-col items-center justify-center py-8 text-center text-xs text-muted-foreground"
                >
                    <Radio
                        class="h-6 w-6 animate-pulse stroke-1 text-muted-foreground/50"
                    />
                    <span class="mt-2 font-medium"
                        >No active sessions right now</span
                    >
                    <span class="text-[11px] text-muted-foreground/70"
                        >Live visitors will appear here automatically</span
                    >
                </div>
            </div>
        </div>

        <!-- Footer / Site Domain Link -->
        <div
            v-if="siteDomain"
            class="mt-4 flex items-center justify-between border-t border-sidebar-border/40 pt-3 text-[11px] text-muted-foreground"
        >
            <span class="truncate font-mono">{{ siteDomain }}</span>
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
