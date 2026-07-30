<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Eye, Users, Globe, Calendar, Sparkles, Smartphone, Laptop, Monitor, Lock, ArrowRight, ShieldCheck } from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import CustomEventsTab from '@/components/CustomEventsTab.vue';

interface SiteItem {
    id: number;
    domain: string;
    is_public?: boolean;
    share_token?: string;
    has_password?: boolean;
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
    site: SiteItem;
    requiresPassword?: boolean;
    passwordError?: string | null;
    period?: string;
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
    layout: null,
});

// Password Form
const passwordForm = useForm({
    password: '',
});

const submitPassword = () => {
    if (!props.site.share_token) return;
    passwordForm.post(`/share/${props.site.share_token}/password`, {
        preserveScroll: true,
    });
};

const hoveredDay = ref<DailyItem | null>(null);

const maxDaily = computed(() => {
    if (!props.overview?.daily_pageviews || props.overview.daily_pageviews.length === 0) {
        return 1;
    }
    const max = Math.max(...props.overview.daily_pageviews.map((d) => d.pageviews));
    return max > 0 ? max : 1;
});

const setPeriod = (newPeriod: string) => {
    if (!props.site.share_token) return;
    router.get(
        `/share/${props.site.share_token}`,
        { period: newPeriod, tab: props.activeTab },
        { preserveState: true, preserveScroll: true }
    );
};

const setTab = (newTab: string) => {
    if (!props.site.share_token) return;
    router.get(
        `/share/${props.site.share_token}`,
        { period: props.period, tab: newTab },
        { preserveState: true, preserveScroll: true }
    );
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
    <Head :title="requiresPassword ? `Protected Analytics — ${site.domain}` : `${site.domain} — Public Analytics`" />

    <div class="min-h-screen bg-background text-foreground flex flex-col font-sans antialiased">
        <!-- Mode 1: Password Gate Screen -->
        <div v-if="requiresPassword" class="flex-1 flex items-center justify-center p-4 sm:p-6">
            <div class="w-full max-w-md bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-2xl p-8 shadow-lg text-center space-y-6">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <Lock class="h-8 w-8" />
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight">Protected Dashboard</h1>
                    <p class="text-sm text-muted-foreground">
                        Enter password to view public analytics for <strong class="text-foreground font-semibold">{{ site.domain }}</strong>
                    </p>
                </div>

                <form @submit.prevent="submitPassword" class="space-y-4 text-left">
                    <div class="space-y-2">
                        <label for="password" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Dashboard Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            v-model="passwordForm.password"
                            placeholder="Enter password"
                            required
                            autofocus
                            class="w-full rounded-lg border border-sidebar-border/80 bg-background px-4 py-2.5 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 dark:bg-slate-900"
                        />
                        <p v-if="passwordError || passwordForm.errors.password" class="text-xs text-destructive mt-1 font-medium">
                            {{ passwordError || passwordForm.errors.password }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="passwordForm.processing || !passwordForm.password"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-indigo-500 disabled:opacity-50 transition-all cursor-pointer"
                    >
                        <span>Unlock Dashboard</span>
                        <ArrowRight class="h-4 w-4" />
                    </button>
                </form>

                <div class="pt-4 border-t border-sidebar-border/50 flex items-center justify-center gap-2 text-xs text-muted-foreground">
                    <Sparkles class="h-3.5 w-3.5 text-indigo-500" />
                    <span>Powered by <strong>Lumina Analytics</strong></span>
                </div>
            </div>
        </div>

        <!-- Mode 2: Read-Only Public Dashboard -->
        <div v-else class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
            <!-- Top Header & Controls -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl p-4 sm:p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <Globe class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-bold text-foreground">{{ site.domain }}</h1>
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <ShieldCheck class="h-3 w-3" />
                                Public
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-0.5">Read-only live website analytics</p>
                    </div>
                </div>

                <!-- Date Period & Theme Switcher -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        @click="setPeriod('7d')"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer',
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
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer',
                            period === '30d'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80'
                        ]"
                    >
                        Last 30 Days
                    </button>

                    <div class="ml-2 border-l border-sidebar-border/70 dark:border-sidebar-border pl-2">
                        <AppearanceTabs />
                    </div>

                    <div class="hidden md:flex items-center gap-1.5 ml-3 px-3 py-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-semibold">
                        <Sparkles class="h-3.5 w-3.5" />
                        <span>Powered by Lumina</span>
                    </div>
                </div>
            </header>

            <!-- Tab Header Controls -->
            <div class="flex items-center gap-1.5 p-1 bg-muted rounded-xl border border-sidebar-border/50 self-start">
                <button
                    @click="setTab('overview')"
                    :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer',
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
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer',
                        activeTab === 'events'
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500'
                            : 'bg-transparent text-muted-foreground hover:text-foreground hover:bg-muted/80'
                    ]"
                >
                    Custom Events
                </button>
            </div>

            <!-- Overview Content -->
            <template v-if="!activeTab || activeTab === 'overview'">
                <!-- Empty State -->
                <div v-if="overview?.total_pageviews === 0" class="rounded-xl border border-dashed border-sidebar-border/80 dark:border-sidebar-border p-12 text-center bg-card shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <Sparkles class="h-7 w-7" />
                    </div>
                    <h3 class="mt-4 text-lg font-bold">No public analytics data available</h3>
                    <p class="mt-1 text-sm text-muted-foreground max-w-md mx-auto">
                        No pageviews have been recorded for <strong class="text-foreground">{{ site.domain }}</strong> in this timeframe.
                    </p>
                </div>

                <div v-else-if="overview" class="space-y-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Pageviews</span>
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                    <Eye class="h-4 w-4" />
                                </div>
                            </div>
                            <div class="mt-3 text-3xl font-black tracking-tight text-foreground">
                                {{ formatNumber(overview.total_pageviews) }}
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">Total raw page visits recorded</div>
                        </div>

                        <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Unique Visitors</span>
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <Users class="h-4 w-4" />
                                </div>
                            </div>
                            <div class="mt-3 text-3xl font-black tracking-tight text-foreground">
                                {{ formatNumber(overview.unique_visitors) }}
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">Distinct daily hashed visitors</div>
                        </div>
                    </div>

                    <!-- Daily Chart -->
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

                    <!-- Top Pages, Referrers, Devices -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Top Pages -->
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

                        <!-- Top Referrers -->
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

                        <!-- Device Breakdown -->
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

                    <!-- Top Browsers, OS, Locations -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Top Browsers -->
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

                        <!-- Top OS -->
                        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-bold text-foreground">Top Operating Systems</h3>
                                <span v-if="overview.top_os" class="text-xs text-muted-foreground">{{ overview.top_os.length }} OS</span>
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

                        <!-- Top Locations -->
                        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-bold text-foreground">Top Locations</h3>
                                <span v-if="overview.top_countries" class="text-xs text-muted-foreground">{{ overview.top_countries.length }} countries</span>
                            </div>
                            <div class="space-y-4">
                                <div v-for="item in overview.top_countries" :key="item.code || item.name" class="space-y-1.5">
                                    <div class="flex justify-between text-xs font-medium">
                                        <span class="truncate font-mono text-foreground flex items-center gap-1.5">
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

                    <!-- Custom Events Summary -->
                    <div v-if="overview.custom_events && overview.custom_events.length > 0" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-foreground">Custom Events Overview</h3>
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

            <!-- Custom Events Content -->
            <template v-else-if="activeTab === 'events'">
                <CustomEventsTab
                    :baseUrl="`/share/${site.share_token}`"
                    :siteId="site.id"
                    :period="period || '30d'"
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
    </div>
</template>
