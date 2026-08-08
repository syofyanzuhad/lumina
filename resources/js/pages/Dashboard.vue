<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Eye, Users, Globe, Code, Calendar, Sparkles, RefreshCw, Smartphone, Laptop, Monitor, Download, Maximize2 } from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import CustomEventsTab from '@/components/CustomEventsTab.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

interface SiteItem {
    id: number;
    domain: string;
}

interface TopPage {
    path: string;
    count: number;
    percentage: number;
}

interface TopReferrer {
    referrer: string;
    count: number;
    percentage: number;
}

interface DailyItem {
    date: string;
    pageviews: number;
    visitors: number;
}

interface DeviceItem {
    device: string;
    count: number;
    percentage: number;
}

interface CustomEventItem {
    name: string;
    count: number;
}

interface TopBrowser {
    browser: string;
    count: number;
    percentage: number;
}

interface TopOS {
    os: string;
    count: number;
    percentage: number;
}

interface TopCountry {
    code: string;
    name: string;
    count: number;
    percentage: number;
}

interface GoalTrendItem {
    date: string;
    completions: number;
}

interface GoalItem {
    id: number;
    name: string;
    target_type: string;
    target_value: string;
    completions: number;
    conversion_rate: number;
    trend: GoalTrendItem[];
}

interface UtmCampaignItem {
    campaign: string;
    count: number;
    percentage: number;
}

interface Overview {
    total_pageviews: number;
    unique_visitors: number;
    current_visitors?: number;
    bounce_rate?: number;
    avg_duration?: number;
    top_pages: TopPage[];
    top_referrers: TopReferrer[];
    daily_pageviews: DailyItem[];
    device_breakdown?: DeviceItem[];
    top_browsers?: TopBrowser[];
    top_os?: TopOS[];
    top_countries?: TopCountry[];
    utm_campaigns?: UtmCampaignItem[];
    custom_events: CustomEventItem[];
    goals?: GoalItem[];
}

const props = defineProps<{
    sites: SiteItem[];
    activeSite: SiteItem;
    period: string;
    activeTab?: string;
    overview?: Overview;
    selectedEvent?: string | null;
    selectedPropertyKey?: string | null;
    custom_event_summary?: any;
    custom_events_list?: any[];
    custom_event_timeline?: any[];
    custom_event_property_keys?: string[];
    custom_event_property_breakdown?: any[];
    custom_event_logs?: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
        ],
    },
});

let pollingInterval: any = null;

onMounted(() => {
    pollingInterval = setInterval(() => {
        router.reload({
            only: ['overview'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 15000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

const isRefreshing = ref(false);
const hoveredDay = ref<DailyItem | null>(null);

const maxDaily = computed(() => {
    if (!props.overview?.daily_pageviews || props.overview.daily_pageviews.length === 0) {
        return 1;
    }
    const max = Math.max(...props.overview.daily_pageviews.map((d) => d.pageviews));
    return max > 0 ? max : 1;
});

const changeSite = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const siteId = target.value;
    router.get('/dashboard', { site_id: siteId, period: props.period, tab: props.activeTab }, { preserveState: true, preserveScroll: true });
};

const setPeriod = (newPeriod: string) => {
    router.get('/dashboard', { site_id: props.activeSite.id, period: newPeriod, tab: props.activeTab }, { preserveState: true, preserveScroll: true });
};

const setTab = (newTab: string) => {
    router.get('/dashboard', { site_id: props.activeSite.id, period: props.period, tab: newTab }, { preserveState: true, preserveScroll: true });
};

const isLive = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const startPolling = () => {
    stopPolling();
    pollInterval = setInterval(() => {
        if (document.visibilityState === 'visible' && !isRefreshing.value) {
            refreshData();
        }
    }, 30000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

const toggleLive = () => {
    isLive.value = !isLive.value;
    if (isLive.value) {
        startPolling();
    } else {
        stopPolling();
    }
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible' && isLive.value && !isRefreshing.value) {
        refreshData();
    }
};

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    stopPolling();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(num);
};

const getCountryFlag = (code?: string): string => {
    if (!code || code.length !== 2) return '🌐';
    const upper = code.toUpperCase();
    const codePoints = [...upper].map((char) => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
};

const getDeviceIcon = (deviceStr: string) => {
    const lower = (deviceStr || '').toLowerCase();
    if (lower.includes('mobile')) return Smartphone;
    if (lower.includes('tablet')) return Laptop;
    return Monitor;
};

const activeModal = ref<string | null>(null);
const modalTitle = ref<string>('');

const openModal = (type: string, title: string) => {
    activeModal.value = type;
    modalTitle.value = title;
};
</script>

<template>
    <Head :title="`${activeSite.domain} — Analytics`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 sm:p-6">
        <!-- Top Control Bar -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl p-4 shadow-sm">
            <!-- Tab Switcher -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1 p-1 bg-muted rounded-lg border border-sidebar-border/50">
                    <button
                        @click="setTab('overview')"
                        :class="[
                            'px-3 py-1 text-xs font-semibold rounded-md transition-all',
                            (!activeTab || activeTab === 'overview')
                                ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                : 'bg-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        Overview
                    </button>
                    <button
                        @click="setTab('events')"
                        :class="[
                            'px-3 py-1 text-xs font-semibold rounded-md transition-all',
                            activeTab === 'events'
                                ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                : 'bg-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        Custom Events
                    </button>
                </div>
            </div>

            <!-- Date Period & Refresh Controls -->
            <div class="flex items-center gap-2">
                <!-- Unified Date Period Segment -->
                <div class="flex items-center gap-0.5 p-1 bg-muted rounded-lg border border-sidebar-border/50">
                    <button
                        type="button"
                        @click="setPeriod('today')"
                        :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all',
                            period === 'today'
                                ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                : 'text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        @click="setPeriod('7d')"
                        :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all',
                            period === '7d'
                                ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                : 'text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        7d
                    </button>
                    <button
                        type="button"
                        @click="setPeriod('30d')"
                        :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all',
                            period === '30d'
                                ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                : 'text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        30d
                    </button>
                </div>

                <div class="hidden sm:block ml-1">
                    <AppearanceTabs />
                </div>

                <!-- Export Menu Dropdown -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            title="Export Data"
                            class="p-1.5 rounded-lg bg-muted text-muted-foreground hover:text-foreground transition-all hover:bg-muted/80 flex items-center gap-1 border border-sidebar-border/50"
                        >
                            <Download class="h-3.5 w-3.5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel>Export Pageviews</DropdownMenuLabel>
                        <DropdownMenuItem as-child>
                            <a :href="`/sites/${activeSite.id}/export?type=pageviews&format=csv&period=${period}`" target="_blank" class="w-full cursor-pointer">
                                Pageviews (CSV)
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="`/sites/${activeSite.id}/export?type=pageviews&format=json&period=${period}`" target="_blank" class="w-full cursor-pointer">
                                Pageviews (JSON)
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuLabel>Export Custom Events</DropdownMenuLabel>
                        <DropdownMenuItem as-child>
                            <a :href="`/sites/${activeSite.id}/export?type=events&format=csv&period=${period}`" target="_blank" class="w-full cursor-pointer">
                                Custom Events (CSV)
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="`/sites/${activeSite.id}/export?type=events&format=json&period=${period}`" target="_blank" class="w-full cursor-pointer">
                                Custom Events (JSON)
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuLabel>Export Overview Summary</DropdownMenuLabel>
                        <DropdownMenuItem as-child>
                            <a :href="`/sites/${activeSite.id}/export?type=summary&format=csv&period=${period}`" target="_blank" class="w-full cursor-pointer">
                                Summary (CSV)
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="`/sites/${activeSite.id}/export?type=summary&format=json&period=${period}`" target="_blank" class="w-full cursor-pointer">
                                Summary (JSON)
                            </a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Live Auto-Refresh Toggle -->
                <button
                    type="button"
                    @click="toggleLive"
                    :title="isLive ? 'Live Auto-Refresh Active (30s)' : 'Turn On Live Auto-Refresh (30s)'"
                    :class="[
                        'px-2.5 py-1 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 border',
                        isLive
                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                            : 'bg-muted text-muted-foreground border-sidebar-border/50 hover:bg-muted/80'
                    ]"
                >
                    <span :class="['h-2 w-2 rounded-full', isLive ? 'bg-emerald-500 animate-pulse' : 'bg-muted-foreground/50']"></span>
                    <span>{{ isLive ? 'Live' : 'Off' }}</span>
                </button>

                <!-- Refresh Data Button -->
                <button
                    type="button"
                    @click="refreshData"
                    title="Refresh Data"
                    class="p-1.5 rounded-lg bg-muted text-muted-foreground hover:text-foreground transition-all hover:bg-muted/80 border border-sidebar-border/50"
                >
                    <RefreshCw :class="['h-3.5 w-3.5', { 'animate-spin': isRefreshing }]" />
                </button>
            </div>
        </div>

        <template v-if="!activeTab || activeTab === 'overview'">
            <!-- Empty State View -->
            <div v-if="overview?.total_pageviews === 0" class="rounded-xl border border-dashed border-sidebar-border/80 dark:border-sidebar-border p-12 text-center bg-card shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <Sparkles class="h-7 w-7" />
                </div>
                <h3 class="mt-4 text-lg font-bold">No tracking data collected yet</h3>
                <p class="mt-1 text-sm text-muted-foreground max-w-md mx-auto">
                    Install the tracking snippet on <strong class="text-foreground">{{ activeSite.domain }}</strong> to start receiving real-time pageviews and visitor metrics.
                </p>
                <div class="mt-6 flex justify-center gap-3">
                    <Link
                        :href="`/sites/${activeSite.id}`"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        <Code class="h-4 w-4" />
                        Get Tracking Snippet
                    </Link>
                </div>
            </div>

            <!-- Analytics Overview Dashboard -->
            <div v-else-if="overview" class="space-y-6">
                <!-- KPI Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Currently Online Card -->
                    <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Currently Online</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 relative">
                                <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                                <Users class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-3 text-3xl font-black tracking-tight text-foreground">
                            {{ formatNumber(overview.current_visitors ?? 0) }}
                        </div>
                        <div class="mt-2 flex items-center text-xs text-muted-foreground">
                            <span>Active in last 5 minutes</span>
                        </div>
                    </div>

                    <!-- Pageviews Card -->
                    <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Pageviews</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                <Eye class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-3 text-3xl font-black tracking-tight text-foreground">
                            {{ formatNumber(overview.total_pageviews) }}
                        </div>
                        <div class="mt-2 flex items-center text-xs text-muted-foreground">
                            <span>Total raw page visits recorded</span>
                        </div>
                    </div>

                    <!-- Unique Visitors Card -->
                    <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Unique Visitors</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                <Users class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-3 text-3xl font-black tracking-tight text-foreground">
                            {{ formatNumber(overview.unique_visitors) }}
                        </div>
                        <div class="mt-2 flex items-center text-xs text-muted-foreground">
                            <span>Distinct daily hashed visitors</span>
                        </div>
                    </div>

                    <!-- Bounce Rate & Avg Duration Card -->
                    <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Bounce / Duration</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                <Sparkles class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-3">
                            <div class="text-3xl font-black tracking-tight text-foreground">
                                {{ overview.bounce_rate ?? 0 }}%
                            </div>
                            <div class="text-sm font-semibold text-muted-foreground font-mono">
                                {{ overview.avg_duration ?? 0 }}s avg
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-xs text-muted-foreground">
                            <span>Single-page visits & session duration</span>
                        </div>
                    </div>
                </div>


            <!-- Interactive Daily Pageviews Bar Chart -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-indigo-500" />
                        <h3 class="text-sm font-bold text-foreground">Daily Pageview Trends</h3>
                    </div>
                    <span v-if="hoveredDay" class="text-xs font-mono text-indigo-600 dark:text-indigo-400">
                        {{ hoveredDay.date }}: {{ formatNumber(hoveredDay.pageviews) }} views ({{ formatNumber(hoveredDay.visitors) }} visitors)
                    </span>
                    <span v-else class="text-xs text-muted-foreground">Hover bar to inspect</span>
                </div>

                <div class="flex items-end gap-1.5 h-44 pt-6 pb-2">
                    <div
                        v-for="day in overview.daily_pageviews"
                        :key="day.date"
                        @mouseenter="hoveredDay = day"
                        @mouseleave="hoveredDay = null"
                        class="flex-1 flex flex-col items-center group relative h-full justify-end cursor-pointer"
                    >
                        <div
                            class="w-full rounded-t-md bg-indigo-500 dark:bg-indigo-400 transition-all duration-200 group-hover:bg-indigo-600 dark:group-hover:bg-indigo-300 min-h-[3px]"
                            :style="{ height: `${Math.max(Math.round((day.pageviews / maxDaily) * 100), 2)}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Details Section: Top Pages, Referrers, and Devices -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Top Pages Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground">Top Pages</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-muted-foreground">{{ overview.top_pages.length }} entries</span>
                            <button
                                @click="openModal('pages', 'Top Pages Breakdown')"
                                title="Expand Details"
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="page in overview.top_pages" :key="page.path" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-foreground">{{ page.path }}</span>
                                <span class="text-muted-foreground font-mono">{{ formatNumber(page.count) }} ({{ page.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${page.percentage}%` }"></div>
                            </div>
                        </div>

                        <p v-if="overview.top_pages.length === 0" class="text-xs text-muted-foreground">No pageviews recorded yet.</p>
                    </div>
                </div>

                <!-- Top Referrers Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground">Top Referrers</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-muted-foreground">{{ overview.top_referrers.length }} entries</span>
                            <button
                                @click="openModal('referrers', 'Top Referrers Breakdown')"
                                title="Expand Details"
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="refItem in overview.top_referrers" :key="refItem.referrer" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-foreground">{{ refItem.referrer }}</span>
                                <span class="text-muted-foreground font-mono">{{ formatNumber(refItem.count) }} ({{ refItem.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-emerald-600 dark:bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${refItem.percentage}%` }"></div>
                            </div>
                        </div>

                        <p v-if="overview.top_referrers.length === 0" class="text-xs text-muted-foreground">No external referrers.</p>
                    </div>
                </div>

                <!-- Device Breakdown Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground">Device Types</h3>
                        <div class="flex items-center gap-2">
                            <span v-if="overview.device_breakdown" class="text-xs text-muted-foreground">{{ overview.device_breakdown.length }} devices</span>
                            <button
                                @click="openModal('devices', 'Device Breakdown')"
                                title="Expand Details"
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="dev in overview.device_breakdown" :key="dev.device" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="flex items-center gap-1.5 capitalize font-mono text-foreground">
                                    <component :is="getDeviceIcon(dev.device)" class="h-3.5 w-3.5 text-indigo-500" />
                                    {{ dev.device }}
                                </span>
                                <span class="text-muted-foreground font-mono">{{ formatNumber(dev.count) }} ({{ dev.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-amber-500 dark:bg-amber-400 h-2 rounded-full transition-all duration-500" :style="{ width: `${dev.percentage}%` }"></div>
                            </div>
                        </div>

                        <p v-if="!overview.device_breakdown || overview.device_breakdown.length === 0" class="text-xs text-muted-foreground">No device data available.</p>
                    </div>
                </div>
            </div>

            <!-- Details Section 2: Top Browsers, Top OS, and Top Locations -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Top Browsers Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground">Top Browsers</h3>
                        <div class="flex items-center gap-2">
                            <span v-if="overview.top_browsers" class="text-xs text-muted-foreground">{{ overview.top_browsers.length }} browsers</span>
                            <button
                                @click="openModal('browsers', 'Top Browsers Breakdown')"
                                title="Expand Details"
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in overview.top_browsers" :key="item.browser" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-foreground">{{ item.browser }}</span>
                                <span class="text-muted-foreground font-mono">{{ formatNumber(item.count) }} ({{ item.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-sky-600 dark:bg-sky-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                        </div>

                        <p v-if="!overview.top_browsers || overview.top_browsers.length === 0" class="text-xs text-muted-foreground">No browser data available.</p>
                    </div>
                </div>

                <!-- Top OS Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground">Top Operating Systems</h3>
                        <div class="flex items-center gap-2">
                            <span v-if="overview.top_os" class="text-xs text-muted-foreground">{{ overview.top_os.length }} OS</span>
                            <button
                                @click="openModal('os', 'Operating Systems Breakdown')"
                                title="Expand Details"
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in overview.top_os" :key="item.os" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-foreground">{{ item.os }}</span>
                                <span class="text-muted-foreground font-mono">{{ formatNumber(item.count) }} ({{ item.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-purple-600 dark:bg-purple-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                        </div>

                        <p v-if="!overview.top_os || overview.top_os.length === 0" class="text-xs text-muted-foreground">No OS data available.</p>
                    </div>
                </div>

                <!-- Top Locations Card -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground">Top Locations</h3>
                        <div class="flex items-center gap-2">
                            <span v-if="overview.top_countries" class="text-xs text-muted-foreground">{{ overview.top_countries.length }} countries</span>
                            <button
                                @click="openModal('locations', 'Geographic Locations Breakdown')"
                                title="Expand Details"
                                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in overview.top_countries" :key="item.code || item.name" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-foreground flex items-center gap-1.5">
                                    <span class="text-base leading-none select-none">{{ getCountryFlag(item.code) }}</span>
                                    <span v-if="item.code" class="text-[10px] font-bold px-1 py-0.5 rounded bg-muted text-muted-foreground uppercase">{{ item.code }}</span>
                                    {{ item.name || item.code }}
                                </span>
                                <span class="text-muted-foreground font-mono">{{ formatNumber(item.count) }} ({{ item.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-rose-600 dark:bg-rose-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                        </div>

                        <p v-if="!overview.top_countries || overview.top_countries.length === 0" class="text-xs text-muted-foreground">No location data available.</p>
                    </div>
                </div>
            </div>

            <!-- UTM Campaigns Card (if present) -->
            <div v-if="overview.utm_campaigns && overview.utm_campaigns.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-foreground">UTM Campaigns</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground">{{ overview.utm_campaigns.length }} campaigns</span>
                        <button
                            @click="openModal('utm', 'UTM Campaigns Breakdown')"
                            title="Expand Details"
                            class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted/80 transition-colors"
                        >
                            <Maximize2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="campaign in overview.utm_campaigns" :key="campaign.campaign" class="space-y-1.5">
                        <div class="flex justify-between text-xs font-medium">
                            <span class="truncate font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ campaign.campaign }}</span>
                            <span class="text-muted-foreground font-mono">{{ formatNumber(campaign.count) }} ({{ campaign.percentage }}%)</span>
                        </div>
                        <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${campaign.percentage}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Goals Performance -->
            <div v-if="overview.goals && overview.goals.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="goal in overview.goals" :key="goal.id" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm flex flex-col h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-foreground truncate" :title="goal.name">{{ goal.name }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-muted text-muted-foreground uppercase">{{ goal.target_type === 'path' ? 'Path' : 'Event' }}</span>
                    </div>

                    <div class="flex items-baseline justify-between mb-6">
                        <div class="text-3xl font-black tracking-tight text-foreground">{{ formatNumber(goal.completions) }}</div>
                        <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ goal.conversion_rate }}% CV</div>
                    </div>

                    <div class="mt-auto h-16 flex items-end gap-1 w-full relative">
                        <div
                            v-for="(day, idx) in goal.trend"
                            :key="idx"
                            class="flex-1 rounded-t-sm bg-indigo-500/80 dark:bg-indigo-400/80 hover:bg-indigo-600 transition-colors min-h-[2px]"
                            :style="{ height: `${Math.max(Math.round((day.completions / Math.max(1, ...goal.trend.map(t => t.completions))) * 100), 2)}%` }"
                            :title="`${day.date}: ${day.completions}`"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Custom Events Table -->
            <div v-if="overview.custom_events && overview.custom_events.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-foreground">Custom Events</h3>
                    <span class="text-xs text-muted-foreground">{{ overview.custom_events.length }} events</span>
                </div>

                <div class="divide-y divide-sidebar-border/50">
                    <div v-for="eventItem in overview.custom_events" :key="eventItem.name" class="flex items-center justify-between py-2.5 text-xs font-medium">
                        <span class="font-mono bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded-md">{{ eventItem.name }}</span>
                        <span class="font-bold font-mono text-foreground">{{ formatNumber(eventItem.count) }}</span>
                    </div>
                </div>
            </div>
        </div>
        </template>

        <template v-else-if="activeTab === 'events'">
            <CustomEventsTab
                :siteId="activeSite.id"
                :period="period"
                :selectedEvent="selectedEvent"
                :selectedPropertyKey="selectedPropertyKey"
                :summary="custom_event_summary"
                :eventsList="custom_events_list"
                :timeline="custom_event_timeline"
                :propertyKeys="custom_event_property_keys"
                :propertyBreakdown="custom_event_property_breakdown"
                :logs="custom_event_logs"
            />
        </template>

        <!-- Side Drawer Modal for Detailed Breakdown -->
        <Sheet :open="!!activeModal" @update:open="(val) => { if (!val) activeModal = null; }">
            <SheetContent side="right" class="w-full sm:max-w-lg overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>{{ modalTitle }}</SheetTitle>
                    <SheetDescription>
                        Complete detailed breakdown for {{ activeSite.domain }}
                    </SheetDescription>
                </SheetHeader>

                <div class="mt-6 space-y-4">
                    <!-- Top Pages Modal -->
                    <template v-if="activeModal === 'pages' && overview?.top_pages">
                        <div v-for="page in overview.top_pages" :key="page.path" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-foreground truncate pr-2">{{ page.path }}</span>
                                <span class="font-mono text-muted-foreground shrink-0">{{ formatNumber(page.count) }} views ({{ page.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full" :style="{ width: `${page.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Top Referrers Modal -->
                    <template v-if="activeModal === 'referrers' && overview?.top_referrers">
                        <div v-for="refItem in overview.top_referrers" :key="refItem.referrer" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-foreground truncate pr-2">{{ refItem.referrer }}</span>
                                <span class="font-mono text-muted-foreground shrink-0">{{ formatNumber(refItem.count) }} visits ({{ refItem.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-emerald-600 dark:bg-emerald-500 h-2 rounded-full" :style="{ width: `${refItem.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Device Breakdown Modal -->
                    <template v-if="activeModal === 'devices' && overview?.device_breakdown">
                        <div v-for="dev in overview.device_breakdown" :key="dev.device" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-foreground capitalize flex items-center gap-2">
                                    <component :is="getDeviceIcon(dev.device)" class="h-4 w-4 text-indigo-500" />
                                    {{ dev.device }}
                                </span>
                                <span class="font-mono text-muted-foreground">{{ formatNumber(dev.count) }} ({{ dev.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-amber-500 dark:bg-amber-400 h-2 rounded-full" :style="{ width: `${dev.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Top Browsers Modal -->
                    <template v-if="activeModal === 'browsers' && overview?.top_browsers">
                        <div v-for="item in overview.top_browsers" :key="item.browser" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-foreground">{{ item.browser }}</span>
                                <span class="font-mono text-muted-foreground">{{ formatNumber(item.count) }} ({{ item.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-sky-600 dark:bg-sky-500 h-2 rounded-full" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Top OS Modal -->
                    <template v-if="activeModal === 'os' && overview?.top_os">
                        <div v-for="item in overview.top_os" :key="item.os" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-foreground">{{ item.os }}</span>
                                <span class="font-mono text-muted-foreground">{{ formatNumber(item.count) }} ({{ item.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-purple-600 dark:bg-purple-500 h-2 rounded-full" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- Top Locations Modal -->
                    <template v-if="activeModal === 'locations' && overview?.top_countries">
                        <div v-for="item in overview.top_countries" :key="item.code || item.name" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-foreground flex items-center gap-2">
                                    <span class="text-lg leading-none select-none">{{ getCountryFlag(item.code) }}</span>
                                    {{ item.name || item.code }}
                                </span>
                                <span class="font-mono text-muted-foreground">{{ formatNumber(item.count) }} ({{ item.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-rose-600 dark:bg-rose-500 h-2 rounded-full" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>

                    <!-- UTM Campaigns Modal -->
                    <template v-if="activeModal === 'utm' && overview?.utm_campaigns">
                        <div v-for="campaign in overview.utm_campaigns" :key="campaign.campaign" class="p-3 rounded-lg border border-sidebar-border/50 bg-muted/30 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ campaign.campaign }}</span>
                                <span class="font-mono text-muted-foreground">{{ formatNumber(campaign.count) }} ({{ campaign.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-muted h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full" :style="{ width: `${campaign.percentage}%` }"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
