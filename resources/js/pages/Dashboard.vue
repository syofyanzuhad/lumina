<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Eye, Users, Globe, Code, Calendar, Sparkles, RefreshCw, Smartphone, Laptop, Monitor } from '@lucide/vue';

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

interface Overview {
    total_pageviews: number;
    unique_visitors: number;
    top_pages: TopPage[];
    top_referrers: TopReferrer[];
    daily_pageviews: DailyItem[];
    device_breakdown?: DeviceItem[];
    custom_events: CustomEventItem[];
}

const props = defineProps<{
    sites: SiteItem[];
    activeSite: SiteItem;
    period: string;
    overview: Overview;
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
    if (!props.overview.daily_pageviews || props.overview.daily_pageviews.length === 0) {
        return 1;
    }
    const max = Math.max(...props.overview.daily_pageviews.map((d) => d.pageviews));
    return max > 0 ? max : 1;
});

const changeSite = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const siteId = target.value;
    router.get('/dashboard', { site_id: siteId, period: props.period }, { preserveState: true, preserveScroll: true });
};

const setPeriod = (newPeriod: string) => {
    router.get('/dashboard', { site_id: props.activeSite.id, period: newPeriod }, { preserveState: true, preserveScroll: true });
};

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

        <!-- Empty State View -->
        <div v-if="overview.total_pageviews === 0" class="rounded-xl border border-dashed border-sidebar-border/80 dark:border-sidebar-border p-12 text-center bg-card shadow-sm">
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
        <template v-else>
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
        </template>
    </div>
</template>
