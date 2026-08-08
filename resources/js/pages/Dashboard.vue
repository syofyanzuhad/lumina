<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Eye, Users, Globe, Code, Calendar, Sparkles, RefreshCw, Smartphone, Laptop, Monitor, Download, Maximize2, CalendarDays, Filter } from '@lucide/vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
    filters?: Record<string, string>;
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

const showViews = ref(true);
const showVisitors = ref(false);

const toggleViews = () => {
    showViews.value = !showViews.value;
};

const toggleVisitors = () => {
    showVisitors.value = !showVisitors.value;
};


const viewsMax = computed(() => {
    if (!props.overview?.daily_pageviews?.length) return 1;
    const m = Math.max(...props.overview.daily_pageviews.map((d) => d.pageviews));
    return m > 0 ? m : 1;
});

const visitorsMax = computed(() => {
    if (!props.overview?.daily_pageviews?.length) return 1;
    const m = Math.max(...props.overview.daily_pageviews.map((d) => d.visitors));
    return m > 0 ? m : 1;
});

const maxDaily = computed(() => {
    const vals: number[] = [];
    if (showViews.value) vals.push(viewsMax.value);
    if (showVisitors.value) vals.push(visitorsMax.value);
    if (!vals.length) return 1;
    return Math.max(...vals);
});

const addFilter = (key: string, value: string) => {
    const current = { ...props.filters };
    current[key] = value;
    router.get('/dashboard', { site_id: props.activeSite.id, period: props.period, tab: props.activeTab, ...current }, { preserveState: true, preserveScroll: true });
};

const removeFilter = (key: string) => {
    const current = { ...props.filters };
    delete current[key];
    router.get('/dashboard', { site_id: props.activeSite.id, period: props.period, tab: props.activeTab, ...current }, { preserveState: true, preserveScroll: true });
};

const clearFilters = () => {
    router.get('/dashboard', { site_id: props.activeSite.id, period: props.period, tab: props.activeTab }, { preserveState: true, preserveScroll: true });
};

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

const isCustomDateModalOpen = ref(false);
const customStartDate = ref(new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0]);
const customEndDate = ref(new Date().toISOString().split('T')[0]);

const applyCustomDateRange = () => {
    if (!customStartDate.value || !customEndDate.value) return;
    isCustomDateModalOpen.value = false;
    router.get('/dashboard', {
        site_id: props.activeSite.id,
        period: 'custom',
        start_date: customStartDate.value,
        end_date: customEndDate.value,
        tab: props.activeTab,
    }, { preserveState: true, preserveScroll: true });
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
                    <button
                        type="button"
                        @click="isCustomDateModalOpen = true"
                        :title="period === 'custom' ? 'Custom Date Range Active' : 'Select Custom Date Range'"
                        :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md transition-all flex items-center gap-1',
                            period === 'custom'
                                ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                : 'text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        <CalendarDays class="h-3.5 w-3.5" />
                        <span>Custom</span>
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


            <!-- Active Filters Bar -->
            <div v-if="filters && Object.keys(filters).length > 0" class="flex flex-wrap items-center gap-2 px-1">
                <span class="flex items-center gap-1 text-xs text-muted-foreground font-medium">
                    <Filter class="h-3 w-3" /> Filters:
                </span>
                <span
                    v-for="(val, key) in filters"
                    :key="key"
                    class="flex items-center gap-1.5 text-xs font-semibold bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-full border border-indigo-500/20"
                >
                    {{ key }}: {{ val }}
                    <button @click="removeFilter(key as string)" class="ml-0.5 hover:text-red-500 transition-colors" title="Remove filter">✕</button>
                </span>
                <button @click="clearFilters" class="text-xs text-muted-foreground hover:text-foreground underline transition-colors">Clear all</button>
            </div>

            <!-- Interactive Daily Pageviews Bar Chart -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-indigo-500" />
                            <h3 class="text-sm font-bold text-foreground">Daily Pageview Trends</h3>
                        </div>

                        <!-- Interactive Legend Pills -->
                        <div class="flex items-center gap-2 ml-2">
                            <button
                                @click="toggleViews"
                                :class="[
                                    'flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border transition-all',
                                    showViews
                                        ? 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border-indigo-500/30'
                                        : 'bg-muted text-muted-foreground border-sidebar-border/50 opacity-60'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-sm bg-indigo-500"></span>
                                Pageviews
                            </button>
                            <button
                                @click="toggleVisitors"
                                :class="[
                                    'flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border transition-all',
                                    showVisitors
                                        ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'
                                        : 'bg-muted text-muted-foreground border-sidebar-border/50 opacity-60'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-sm bg-indigo-400/40"></span>
                                Visitors
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-end gap-1.5 h-44 pt-6 pb-2 relative">
                    <div
                        v-for="day in overview.daily_pageviews"
                        :key="day.date"
                        @mouseenter="hoveredDay = day"
                        @mouseleave="hoveredDay = null"
                        class="flex-1 flex flex-col items-center group relative h-full justify-end cursor-pointer"
                    >
                        <!-- Floating Tooltip Card on Hover -->
                        <div
                            class="absolute -top-14 left-1/2 -translate-x-1/2 hidden group-hover:flex flex-col items-center z-30 pointer-events-none"
                        >
                            <div class="bg-popover text-popover-foreground border border-sidebar-border/80 shadow-md rounded-lg px-2.5 py-1 text-[11px] font-mono font-medium whitespace-nowrap space-y-0.5 text-center">
                                <div class="text-xs font-bold text-foreground">{{ day.date }}</div>
                                <div class="flex items-center gap-2 text-[10px]">
                                    <span v-if="showViews" class="text-indigo-600 dark:text-indigo-400 font-bold">{{ formatNumber(day.pageviews) }} views</span>
                                    <span v-if="showViews && showVisitors" class="text-muted-foreground">•</span>
                                    <span v-if="showVisitors" class="text-indigo-400/70 font-bold">{{ formatNumber(day.visitors) }} visitors</span>
                                </div>
                            </div>
                            <!-- Tooltip Arrow Pointer -->
                            <div class="w-2 h-2 bg-popover border-r border-b border-sidebar-border/80 rotate-45 -mt-1"></div>
                        </div>

                        <div class="w-full flex items-end gap-[1px] h-full justify-center">
                            <!-- Pageviews Bar -->
                            <div
                                v-if="showViews"
                                class="flex-1 rounded-t-xs bg-indigo-500 dark:bg-indigo-400 transition-all duration-200 group-hover:bg-indigo-600 dark:group-hover:bg-indigo-300 min-h-[3px]"
                                :style="{ height: `${Math.max(Math.round((day.pageviews / maxDaily) * 100), 2)}%` }"
                            ></div>
                            <!-- Unique Visitors Bar (same color, lower opacity) -->
                            <div
                                v-if="showVisitors"
                                class="flex-1 rounded-t-xs bg-indigo-500/35 dark:bg-indigo-400/35 transition-all duration-200 group-hover:bg-indigo-500/55 dark:group-hover:bg-indigo-400/55 min-h-[2px]"
                                :style="{ height: `${Math.max(Math.round((day.visitors / maxDaily) * 100), 2)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- X-Axis Date Range Labels -->
                <div v-if="overview.daily_pageviews.length > 0" class="flex justify-between items-center text-[10px] font-mono text-muted-foreground pt-2 border-t border-sidebar-border/40">
                    <span>{{ overview.daily_pageviews[0].date }}</span>
                    <span v-if="overview.daily_pageviews.length > 2">
                        {{ overview.daily_pageviews[Math.floor(overview.daily_pageviews.length / 2)].date }}
                    </span>
                    <span>{{ overview.daily_pageviews[overview.daily_pageviews.length - 1].date }}</span>
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

                    <div class="space-y-2">
                        <div
                            v-for="page in overview.top_pages"
                            :key="page.path"
                            @click="addFilter('path', page.path)"
                            :title="`Click to filter dashboard by path: ${page.path}`"
                            class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                        >
                            <div
                                class="absolute inset-y-0 left-0 bg-indigo-100/70 dark:bg-indigo-500/15 rounded-lg transition-all duration-500 group-hover:bg-indigo-200/80 dark:group-hover:bg-indigo-500/25"
                                :style="{ width: `${page.percentage}%` }"
                            ></div>
                            <span class="relative z-10 truncate font-mono text-foreground font-medium group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors mr-2 flex items-center gap-1">
                                <span class="truncate">{{ page.path }}</span>
                                <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0" />
                            </span>
                            <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(page.count) }} <span class="text-muted-foreground/70">({{ page.percentage }}%)</span></span>
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

                    <div class="space-y-2">
                        <div
                            v-for="refItem in overview.top_referrers"
                            :key="refItem.referrer"
                            @click="addFilter('referrer', refItem.referrer)"
                            :title="`Click to filter dashboard by referrer: ${refItem.referrer}`"
                            class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                        >
                            <div
                                class="absolute inset-y-0 left-0 bg-emerald-100/70 dark:bg-emerald-500/15 rounded-lg transition-all duration-500 group-hover:bg-emerald-200/80 dark:group-hover:bg-emerald-500/25"
                                :style="{ width: `${refItem.percentage}%` }"
                            ></div>
                            <span class="relative z-10 truncate font-mono text-foreground font-medium group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors mr-2 flex items-center gap-1">
                                <span class="truncate">{{ refItem.referrer }}</span>
                                <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0" />
                            </span>
                            <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(refItem.count) }} <span class="text-muted-foreground/70">({{ refItem.percentage }}%)</span></span>
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

                    <div class="space-y-2">
                        <div
                            v-for="dev in overview.device_breakdown"
                            :key="dev.device"
                            @click="addFilter('device', dev.device)"
                            :title="`Click to filter dashboard by device: ${dev.device}`"
                            class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                        >
                            <div
                                class="absolute inset-y-0 left-0 bg-amber-100/70 dark:bg-amber-500/15 rounded-lg transition-all duration-500 group-hover:bg-amber-200/80 dark:group-hover:bg-amber-500/25"
                                :style="{ width: `${dev.percentage}%` }"
                            ></div>
                            <span class="relative z-10 flex items-center gap-1.5 capitalize font-mono text-foreground font-medium group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors mr-2">
                                <component :is="getDeviceIcon(dev.device)" class="h-3.5 w-3.5 text-amber-500" />
                                {{ dev.device }}
                                <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0 ml-0.5" />
                            </span>
                            <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(dev.count) }} <span class="text-muted-foreground/70">({{ dev.percentage }}%)</span></span>
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

                    <div class="space-y-2">
                        <div
                            v-for="item in overview.top_browsers"
                            :key="item.browser"
                            @click="addFilter('browser', item.browser)"
                            :title="`Click to filter dashboard by browser: ${item.browser}`"
                            class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                        >
                            <div
                                class="absolute inset-y-0 left-0 bg-sky-100/70 dark:bg-sky-500/15 rounded-lg transition-all duration-500 group-hover:bg-sky-200/80 dark:group-hover:bg-sky-500/25"
                                :style="{ width: `${item.percentage}%` }"
                            ></div>
                            <span class="relative z-10 truncate font-mono text-foreground font-medium group-hover:text-sky-700 dark:group-hover:text-sky-300 transition-colors mr-2 flex items-center gap-1">
                                <span class="truncate">{{ item.browser }}</span>
                                <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0" />
                            </span>
                            <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(item.count) }} <span class="text-muted-foreground/70">({{ item.percentage }}%)</span></span>
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

                    <div class="space-y-2">
                        <div
                            v-for="item in overview.top_os"
                            :key="item.os"
                            @click="addFilter('os', item.os)"
                            :title="`Click to filter dashboard by OS: ${item.os}`"
                            class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                        >
                            <div
                                class="absolute inset-y-0 left-0 bg-purple-100/70 dark:bg-purple-500/15 rounded-lg transition-all duration-500 group-hover:bg-purple-200/80 dark:group-hover:bg-purple-500/25"
                                :style="{ width: `${item.percentage}%` }"
                            ></div>
                            <span class="relative z-10 truncate font-mono text-foreground font-medium group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors mr-2 flex items-center gap-1">
                                <span class="truncate">{{ item.os }}</span>
                                <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0" />
                            </span>
                            <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(item.count) }} <span class="text-muted-foreground/70">({{ item.percentage }}%)</span></span>
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

                    <div class="space-y-2">
                        <div
                            v-for="item in overview.top_countries"
                            :key="item.code || item.name"
                            @click="addFilter('country', item.code || item.name)"
                            :title="`Click to filter dashboard by country: ${item.name || item.code}`"
                            class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                        >
                            <div
                                class="absolute inset-y-0 left-0 bg-rose-100/70 dark:bg-rose-500/15 rounded-lg transition-all duration-500 group-hover:bg-rose-200/80 dark:group-hover:bg-rose-500/25"
                                :style="{ width: `${item.percentage}%` }"
                            ></div>
                            <span class="relative z-10 truncate font-mono text-foreground font-medium group-hover:text-rose-700 dark:group-hover:text-rose-300 transition-colors mr-2 flex items-center gap-1.5">
                                <span class="text-base leading-none select-none">{{ getCountryFlag(item.code) }}</span>
                                <span v-if="item.code" class="text-[10px] font-bold px-1 py-0.5 rounded bg-muted text-muted-foreground uppercase">{{ item.code }}</span>
                                <span class="truncate">{{ item.name || item.code }}</span>
                                <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0" />
                            </span>
                            <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(item.count) }} <span class="text-muted-foreground/70">({{ item.percentage }}%)</span></span>
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

                <div class="space-y-2">
                    <div
                        v-for="campaign in overview.utm_campaigns"
                        :key="campaign.campaign"
                        @click="addFilter('utm_campaign', campaign.campaign)"
                        :title="`Click to filter dashboard by campaign: ${campaign.campaign}`"
                        class="group relative flex justify-between items-center text-xs font-medium p-2 rounded-lg hover:opacity-90 cursor-pointer transition-all overflow-hidden"
                    >
                        <div
                            class="absolute inset-y-0 left-0 bg-purple-100/70 dark:bg-purple-500/15 rounded-lg transition-all duration-500 group-hover:bg-purple-200/80 dark:group-hover:bg-purple-500/25"
                            :style="{ width: `${campaign.percentage}%` }"
                        ></div>
                        <span class="relative z-10 truncate font-mono text-foreground font-medium group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors mr-2 flex items-center gap-1">
                            <span class="truncate">{{ campaign.campaign }}</span>
                            <Filter class="h-3 w-3 opacity-0 group-hover:opacity-60 transition-opacity shrink-0" />
                        </span>
                        <span class="relative z-10 shrink-0 text-muted-foreground font-mono text-[11px]">{{ formatNumber(campaign.count) }} <span class="text-muted-foreground/70">({{ campaign.percentage }}%)</span></span>
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

                <div class="mt-6 space-y-3">
                    <!-- Top Pages Modal -->
                    <template v-if="activeModal === 'pages' && overview?.top_pages">
                        <div
                            v-for="(page, idx) in overview.top_pages"
                            :key="page.path"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-indigo-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <span class="font-mono font-bold text-foreground text-xs truncate">{{ page.path }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-indigo-600 dark:text-indigo-400">{{ page.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${page.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(page.count) }} views</span>
                                <button
                                    @click="addFilter('path', page.path); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Top Referrers Modal -->
                    <template v-if="activeModal === 'referrers' && overview?.top_referrers">
                        <div
                            v-for="(refItem, idx) in overview.top_referrers"
                            :key="refItem.referrer"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-emerald-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <span class="font-mono font-bold text-foreground text-xs truncate">{{ refItem.referrer }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-emerald-600 dark:text-emerald-400">{{ refItem.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${refItem.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(refItem.count) }} visits</span>
                                <button
                                    @click="addFilter('referrer', refItem.referrer); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-emerald-600 dark:text-emerald-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Device Breakdown Modal -->
                    <template v-if="activeModal === 'devices' && overview?.device_breakdown">
                        <div
                            v-for="(dev, idx) in overview.device_breakdown"
                            :key="dev.device"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-amber-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <component :is="getDeviceIcon(dev.device)" class="h-4 w-4 text-amber-500 shrink-0" />
                                    <span class="font-mono font-bold text-foreground text-xs capitalize truncate">{{ dev.device }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-amber-600 dark:text-amber-400">{{ dev.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${dev.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(dev.count) }} sessions</span>
                                <button
                                    @click="addFilter('device', dev.device); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-amber-600 dark:text-amber-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Top Browsers Modal -->
                    <template v-if="activeModal === 'browsers' && overview?.top_browsers">
                        <div
                            v-for="(item, idx) in overview.top_browsers"
                            :key="item.browser"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-sky-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-sky-500/10 text-sky-600 dark:text-sky-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <span class="font-mono font-bold text-foreground text-xs truncate">{{ item.browser }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-sky-600 dark:text-sky-400">{{ item.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-sky-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(item.count) }} sessions</span>
                                <button
                                    @click="addFilter('browser', item.browser); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-sky-600 dark:text-sky-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Top OS Modal -->
                    <template v-if="activeModal === 'os' && overview?.top_os">
                        <div
                            v-for="(item, idx) in overview.top_os"
                            :key="item.os"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-purple-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <span class="font-mono font-bold text-foreground text-xs truncate">{{ item.os }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-purple-600 dark:text-purple-400">{{ item.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-purple-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(item.count) }} sessions</span>
                                <button
                                    @click="addFilter('os', item.os); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-purple-600 dark:text-purple-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Top Locations Modal -->
                    <template v-if="activeModal === 'locations' && overview?.top_countries">
                        <div
                            v-for="(item, idx) in overview.top_countries"
                            :key="item.code || item.name"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-rose-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <span class="text-xl leading-none select-none shrink-0">{{ getCountryFlag(item.code) }}</span>
                                    <span v-if="item.code" class="text-[10px] font-black px-1.5 py-0.5 rounded bg-muted text-muted-foreground uppercase shrink-0">{{ item.code }}</span>
                                    <span class="font-mono font-bold text-foreground text-xs truncate">{{ item.name || item.code }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-rose-600 dark:text-rose-400">{{ item.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-rose-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${item.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(item.count) }} visitors</span>
                                <button
                                    @click="addFilter('country', item.code || item.name); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-rose-600 dark:text-rose-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- UTM Campaigns Modal -->
                    <template v-if="activeModal === 'utm' && overview?.utm_campaigns">
                        <div
                            v-for="(campaign, idx) in overview.utm_campaigns"
                            :key="campaign.campaign"
                            class="rounded-xl border border-sidebar-border/60 bg-card p-4 space-y-3 hover:border-purple-500/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 text-[11px] font-black">#{{ idx + 1 }}</span>
                                    <span class="font-mono font-bold text-purple-600 dark:text-purple-400 text-xs truncate">{{ campaign.campaign }}</span>
                                </div>
                                <span class="shrink-0 text-[13px] font-black text-purple-600 dark:text-purple-400">{{ campaign.percentage }}%</span>
                            </div>
                            <div class="w-full bg-muted h-2.5 rounded-full overflow-hidden">
                                <div class="bg-purple-500 h-2.5 rounded-full transition-all duration-700" :style="{ width: `${campaign.percentage}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-mono bg-muted text-muted-foreground px-2 py-0.5 rounded-md font-semibold">{{ formatNumber(campaign.count) }} visits</span>
                                <button
                                    @click="addFilter('utm_campaign', campaign.campaign); activeModal = null"
                                    class="text-[11px] font-semibold flex items-center gap-1 text-purple-600 dark:text-purple-400 hover:underline transition-colors"
                                >
                                    <Filter class="h-3 w-3" /> Filter to this
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Custom Date Range Dialog -->
        <Dialog v-model:open="isCustomDateModalOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Custom Date Range</DialogTitle>
                    <DialogDescription>
                        Select a start and end date to filter analytics for {{ activeSite.domain }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-2 gap-4 py-4">
                    <div class="space-y-2">
                        <Label for="start-date">Start Date</Label>
                        <Input
                            id="start-date"
                            type="date"
                            v-model="customStartDate"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="end-date">End Date</Label>
                        <Input
                            id="end-date"
                            type="date"
                            v-model="customEndDate"
                        />
                    </div>
                </div>

                <DialogFooter class="sm:justify-between">
                    <Button variant="outline" @click="isCustomDateModalOpen = false">Cancel</Button>
                    <Button @click="applyCustomDateRange">Apply Range</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
