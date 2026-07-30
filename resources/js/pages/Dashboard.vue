<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Eye, Users, Globe, Code, Calendar, Sparkles, RefreshCw, Smartphone, Laptop, Monitor, Download } from '@lucide/vue';
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

interface Overview {
    total_pageviews: number;
    unique_visitors: number;
    top_pages: TopPage[];
    top_referrers: TopReferrer[];
    daily_pageviews: DailyItem[];
    device_breakdown?: DeviceItem[];
    top_browsers?: TopBrowser[];
    top_os?: TopOS[];
    top_countries?: TopCountry[];
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
</script>

<template>
    <Head :title="`${activeSite.domain} — Analytics`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 sm:p-6">
        <!-- Top Control Bar -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl p-4 shadow-sm">
            <!-- Active Site Switcher -->
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <Globe class="h-5 w-5" />
                </div>
                <div>
                    <label for="site-select" class="block text-xs font-medium text-muted-foreground">Active Site</label>
                    <select
                        id="site-select"
                        :value="activeSite.id"
                        @change="changeSite"
                        class="mt-0.5 block w-full rounded-md border-0 py-1 pl-2 pr-8 text-sm font-semibold ring-1 ring-inset ring-sidebar-border focus:ring-2 focus:ring-indigo-600 dark:bg-slate-900 dark:text-slate-100"
                    >
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.domain }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Date Period & Refresh Controls -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="setPeriod('7d')"
                    :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                        period === '7d'
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                    ]"
                >
                    Last 7 Days
                </button>
                <button
                    type="button"
                    @click="setPeriod('30d')"
                    :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                        period === '30d'
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                    ]"
                >
                    Last 30 Days
                </button>

                <div class="hidden sm:block ml-2 border-l border-sidebar-border/70 dark:border-sidebar-border pl-2">
                    <AppearanceTabs />
                </div>

                <!-- Export Menu Dropdown -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            title="Export Data"
                            class="p-2 rounded-lg bg-muted text-muted-foreground hover:text-foreground transition-all hover:bg-muted/80 ml-1 flex items-center gap-1"
                        >
                            <Download class="h-4 w-4" />
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
                    :title="isLive ? 'Live Auto-Refresh Active (30s)' : 'Turn On Live Auto-Refresh'"
                    :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 ml-1',
                        isLive
                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 shadow-xs'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                    ]"
                >
                    <span :class="['h-2 w-2 rounded-full', isLive ? 'bg-emerald-500 animate-pulse' : 'bg-muted-foreground/50']"></span>
                    <span>{{ isLive ? 'Live 30s' : 'Live Off' }}</span>
                </button>

                <!-- Refresh Data Button -->
                <button
                    type="button"
                    @click="refreshData"
                    title="Refresh Data"
                    class="p-2 rounded-lg bg-muted text-muted-foreground hover:text-foreground transition-all hover:bg-muted/80 ml-1"
                >
                    <RefreshCw :class="['h-4 w-4', { 'animate-spin': isRefreshing }]" />
                </button>
            </div>
        </div>

        <!-- Tab Header Controls -->
        <div class="flex items-center gap-1.5 p-1 bg-muted rounded-xl border border-sidebar-border/50 self-start">
            <button
                @click="setTab('overview')"
                :class="[
                    'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                    (!activeTab || activeTab === 'overview')
                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500'
                        : 'bg-transparent text-muted-foreground hover:text-foreground hover:bg-muted/80'
                ]"
            >
                Overview
            </button>
            <button
                @click="setTab('events')"
                :class="[
                    'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                    activeTab === 'events'
                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500'
                        : 'bg-transparent text-muted-foreground hover:text-foreground hover:bg-muted/80'
                ]"
            >
                Custom Events
            </button>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
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
                        <span class="text-xs text-muted-foreground">{{ overview.top_pages.length }} entries</span>
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
                        <span class="text-xs text-muted-foreground">{{ overview.top_referrers.length }} entries</span>
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
                        <span v-if="overview.device_breakdown" class="text-xs text-muted-foreground">{{ overview.device_breakdown.length }} devices</span>
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
                        <span v-if="overview.top_browsers" class="text-xs text-muted-foreground">{{ overview.top_browsers.length }} browsers</span>
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
                        <span v-if="overview.top_os" class="text-xs text-muted-foreground">{{ overview.top_os.length }} operating systems</span>
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
                        <span v-if="overview.top_countries" class="text-xs text-muted-foreground">{{ overview.top_countries.length }} countries</span>
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
    </div>
</template>
