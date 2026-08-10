<script setup lang="ts">
import { computed, toRef } from 'vue';
import { router, Deferred } from '@inertiajs/vue3';
import CustomEventsTab from '@/components/CustomEventsTab.vue';
import AnalyticsControlBar from '@/components/analytics/AnalyticsControlBar.vue';
import AnalyticsFiltersBar from '@/components/analytics/AnalyticsFiltersBar.vue';
import AnalyticsKpiCards from '@/components/analytics/AnalyticsKpiCards.vue';
import AnalyticsChart from '@/components/analytics/AnalyticsChart.vue';
import AnalyticsBreakdownCard, { type BreakdownCardItem } from '@/components/analytics/AnalyticsBreakdownCard.vue';
import AnalyticsBreakdownDrawer from '@/components/analytics/AnalyticsBreakdownDrawer.vue';

import { useAnalyticsFilters } from '@/composables/useAnalyticsFilters';
import { useAnalyticsPeriod } from '@/composables/useAnalyticsPeriod';
import { useAnalyticsChart } from '@/composables/useAnalyticsChart';
import { useLivePolling } from '@/composables/useLivePolling';
import { useBreakdownModal } from '@/composables/useBreakdownModal';
import {
    getCountryFlag,
    getDeviceIcon,
    getReferrerFavicon,
    getBrowserIcon,
    getOsIcon,
} from '@/composables/useAnalyticsFormatters';

export interface AnalyticsDashboardProps {
    baseUrl: string;
    breakdownUrl?: string;
    site: {
        id?: number;
        domain: string;
        share_token?: string;
    };
    sites?: {
        id: number;
        domain: string;
    }[];
    period: string;
    activeTab?: string;
    filters?: Record<string, string>;

    // Immediate KPI props (available on first render)
    total_pageviews?: number;
    unique_visitors?: number;
    current_visitors?: number;
    bounce_rate?: number;
    avg_duration?: number;
    daily_pageviews?: any[];

    // Deferred breakdown props (arrive after initial render)
    top_pages?: any[];
    top_referrers?: any[];
    device_breakdown?: any[];
    top_browsers?: any[];
    top_os?: any[];
    top_countries?: any[];
    utm_campaigns?: any[];
    custom_events?: any[];
    goals?: any[];

    // Feature Flags
    showLive?: boolean;
    showExport?: boolean;
    showEventsTab?: boolean;
    canFilter?: boolean;
    canExpand?: boolean;
    availablePeriods?: string[];

    // Custom Events Tab Data (Passed through)
    selectedEvent?: string | null;
    selectedPropertyKey?: string | null;
    custom_event_summary?: any;
    custom_events_list?: any[];
    custom_event_timeline?: any[];
    custom_event_property_keys?: string[];
    custom_event_property_breakdown?: any[];
    custom_event_logs?: any[];
}

const props = withDefaults(defineProps<AnalyticsDashboardProps>(), {
    activeTab: 'overview',
    showLive: false,
    showExport: false,
    showEventsTab: true,
    canFilter: true,
    canExpand: true,
    availablePeriods: () => ['today', '7d', '30d', 'custom'],
});

const baseUrlRef = toRef(props, 'baseUrl');
const siteIdRef = computed(() => props.site?.id);
const filtersRef = toRef(props, 'filters');
const periodRef = toRef(props, 'period');
const tabRef = toRef(props, 'activeTab');

// Build a synthetic overview ref for the breakdown modal fallback
const overviewRef = computed(() => ({
    top_pages: props.top_pages,
    top_referrers: props.top_referrers,
    device_breakdown: props.device_breakdown,
    top_browsers: props.top_browsers,
    top_os: props.top_os,
    top_countries: props.top_countries,
    utm_campaigns: props.utm_campaigns,
}));

// Composables setup
const { addFilter, removeFilter, clearFilters } = useAnalyticsFilters({
    baseUrl: baseUrlRef,
    siteId: siteIdRef,
    currentFilters: filtersRef,
    currentPeriod: periodRef,
    currentTab: tabRef,
});

const { customStartDate, customEndDate, setPeriod, applyCustomDateRange } = useAnalyticsPeriod({
    baseUrl: baseUrlRef,
    siteId: siteIdRef,
    currentFilters: filtersRef,
    currentTab: tabRef,
});

const dailyPageviewsRef = computed(() => props.daily_pageviews);
const {
    hoveredDay,
    showViews,
    showVisitors,
    toggleViews,
    toggleVisitors,
    maxDaily,
} = useAnalyticsChart(dailyPageviewsRef);

const { isLive, isRefreshing, toggleLive, refreshData } = useLivePolling();

const breakdownEndpointRef = computed(
    () => props.breakdownUrl || `${props.baseUrl}/breakdown`
);

const {
    activeModal,
    modalTitle,
    modalData,
    isLoadingModal,
    modalTotalCount,
    openModal,
    closeModal,
} = useBreakdownModal({
    breakdownEndpoint: breakdownEndpointRef,
    currentPeriod: periodRef,
    siteId: siteIdRef,
    overview: overviewRef,
});

const setTab = (newTab: string) => {
    const params: Record<string, any> = {
        period: props.period,
        tab: newTab,
        ...(props.filters || {}),
    };
    if (props.site?.id) params.site_id = props.site.id;
    router.get(props.baseUrl, params, { preserveState: true, preserveScroll: true });
};

// Item Mapping Helpers for Breakdown Cards
const topPagesItems = computed<BreakdownCardItem[]>(() => {
    if (!props.top_pages) return [];
    return props.top_pages.map((p: any) => ({
        idKey: p.path,
        label: p.path,
        count: p.count,
        percentage: p.percentage,
        path: p.path,
    }));
});

const topReferrersItems = computed<BreakdownCardItem[]>(() => {
    if (!props.top_referrers) return [];
    return props.top_referrers.map((r: any) => ({
        idKey: r.referrer,
        label: r.referrer,
        count: r.count,
        percentage: r.percentage,
        icon: getReferrerFavicon(r.referrer),
        isComponentIcon: false,
    }));
});

const deviceItems = computed<BreakdownCardItem[]>(() => {
    if (!props.device_breakdown) return [];
    return props.device_breakdown.map((d: any) => ({
        idKey: d.device,
        label: d.device,
        count: d.count,
        percentage: d.percentage,
        icon: getDeviceIcon(d.device),
        isComponentIcon: true,
    }));
});

const topBrowsersItems = computed<BreakdownCardItem[]>(() => {
    if (!props.top_browsers) return [];
    return props.top_browsers.map((b: any) => ({
        idKey: b.browser,
        label: b.browser,
        count: b.count,
        percentage: b.percentage,
        icon: getBrowserIcon(b.browser),
        isComponentIcon: false,
    }));
});

const topOsItems = computed<BreakdownCardItem[]>(() => {
    if (!props.top_os) return [];
    return props.top_os.map((o: any) => ({
        idKey: o.os,
        label: o.os,
        count: o.count,
        percentage: o.percentage,
        icon: getOsIcon(o.os),
        isComponentIcon: false,
    }));
});

const topCountriesItems = computed<BreakdownCardItem[]>(() => {
    if (!props.top_countries) return [];
    return props.top_countries.map((c: any) => ({
        idKey: c.code || c.name,
        label: c.name || c.code,
        count: c.count,
        percentage: c.percentage,
        code: c.code,
    }));
});
</script>

<template>
    <div class="space-y-6">
        <!-- Top Control Bar -->
        <AnalyticsControlBar
            :activeTab="activeTab"
            :period="period"
            :showEventsTab="showEventsTab"
            :showExport="showExport"
            :showLive="showLive"
            :isLive="isLive"
            :isRefreshing="isRefreshing"
            :availablePeriods="availablePeriods"
            v-model:customStartDate="customStartDate"
            v-model:customEndDate="customEndDate"
            :siteId="site?.id"
            @setTab="setTab"
            @setPeriod="setPeriod"
            @applyCustomRange="applyCustomDateRange"
            @toggleLive="toggleLive"
            @refresh="refreshData"
        />

        <!-- Active Filters Bar -->
        <AnalyticsFiltersBar
            v-if="canFilter"
            :filters="filters"
            @removeFilter="removeFilter"
            @clearFilters="clearFilters"
        />

        <!-- Overview Dashboard Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
            <!-- KPI Cards — render immediately, no defer needed -->
            <AnalyticsKpiCards
                :currentVisitors="current_visitors"
                :totalPageviews="total_pageviews"
                :uniqueVisitors="unique_visitors"
                :bounceRate="bounce_rate"
                :avgDuration="avg_duration"
            />

            <!-- Interactive Chart — render immediately -->
            <AnalyticsChart
                :dailyPageviews="daily_pageviews"
                :showViews="showViews"
                :showVisitors="showVisitors"
                :hoveredDay="hoveredDay"
                :maxDaily="maxDaily"
                @update:hoveredDay="hoveredDay = $event"
                @toggleViews="toggleViews"
                @toggleVisitors="toggleVisitors"
            />

            <!-- Breakdown Cards Row 1: deferred together for consistent render -->
            <Deferred :data="['top_pages', 'top_referrers', 'device_breakdown']">
                <template #fallback>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="i in 3" :key="i" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="h-4 w-24 bg-muted animate-pulse rounded-md"></div>
                                <div class="h-4 w-16 bg-muted animate-pulse rounded-md"></div>
                            </div>
                            <div v-for="j in 5" :key="j" class="h-8 bg-muted/60 animate-pulse rounded-lg" :style="{ opacity: 1 - j * 0.15 }"></div>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <AnalyticsBreakdownCard
                        title="Top Pages"
                        filterKey="path"
                        typeKey="pages"
                        colorScheme="indigo"
                        :items="topPagesItems"
                        :totalItems="top_pages?.length"
                        :siteDomain="site.domain"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                    <AnalyticsBreakdownCard
                        title="Top Referrers"
                        filterKey="referrer"
                        typeKey="referrers"
                        colorScheme="emerald"
                        :items="topReferrersItems"
                        :totalItems="top_referrers?.length"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                    <AnalyticsBreakdownCard
                        title="Device Types"
                        filterKey="device"
                        typeKey="devices"
                        colorScheme="amber"
                        :items="deviceItems"
                        :totalItems="device_breakdown?.length"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                </div>
            </Deferred>

            <!-- Breakdown Cards Row 2: deferred together -->
            <Deferred :data="['top_browsers', 'top_os', 'top_countries']">
                <template #fallback>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="i in 3" :key="i" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="h-4 w-28 bg-muted animate-pulse rounded-md"></div>
                                <div class="h-4 w-14 bg-muted animate-pulse rounded-md"></div>
                            </div>
                            <div v-for="j in 5" :key="j" class="h-8 bg-muted/60 animate-pulse rounded-lg" :style="{ opacity: 1 - j * 0.15 }"></div>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <AnalyticsBreakdownCard
                        title="Top Browsers"
                        filterKey="browser"
                        typeKey="browsers"
                        colorScheme="sky"
                        :items="topBrowsersItems"
                        :totalItems="top_browsers?.length"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                    <AnalyticsBreakdownCard
                        title="Top Operating Systems"
                        filterKey="os"
                        typeKey="os"
                        colorScheme="purple"
                        :items="topOsItems"
                        :totalItems="top_os?.length"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                    <AnalyticsBreakdownCard
                        title="Top Locations"
                        filterKey="country"
                        typeKey="locations"
                        colorScheme="rose"
                        :items="topCountriesItems"
                        :totalItems="top_countries?.length"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                </div>
            </Deferred>

            <!-- UTM Campaigns Card — deferred -->
            <Deferred data="utm_campaigns">
                <template #fallback>
                    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm space-y-3">
                        <div class="h-4 w-32 bg-muted animate-pulse rounded-md"></div>
                        <div v-for="i in 3" :key="i" class="h-8 bg-muted/60 animate-pulse rounded-lg"></div>
                    </div>
                </template>

                <div v-if="utm_campaigns && utm_campaigns.length > 0">
                    <AnalyticsBreakdownCard
                        title="UTM Campaigns"
                        filterKey="utm_campaign"
                        typeKey="utm"
                        colorScheme="indigo"
                        :items="utm_campaigns.map((u: any) => ({ idKey: u.campaign || u.utm_campaign, label: u.campaign || u.utm_campaign, count: u.count, percentage: u.percentage }))"
                        :totalItems="utm_campaigns.length"
                        :canFilter="canFilter"
                        :canExpand="canExpand"
                        @filter="addFilter"
                        @expand="openModal"
                    />
                </div>
            </Deferred>
        </div>

        <!-- Custom Events Tab -->
        <CustomEventsTab
            v-else-if="activeTab === 'events' && showEventsTab"
            :siteId="site.id || 0"
            :period="period"
            :baseUrl="baseUrl"
            :selectedEvent="selectedEvent"
            :selectedPropertyKey="selectedPropertyKey"
            :summary="custom_event_summary"
            :eventsList="custom_events_list"
            :timeline="custom_event_timeline"
            :propertyKeys="custom_event_property_keys"
            :propertyBreakdown="custom_event_property_breakdown"
            :logs="custom_event_logs"
        />

        <!-- Side Drawer Breakdown Modal -->
        <AnalyticsBreakdownDrawer
            :open="!!activeModal"
            :title="modalTitle"
            :type="activeModal"
            :modalData="modalData"
            :isLoading="isLoadingModal"
            :totalCount="modalTotalCount"
            :overview="overviewRef"
            :siteDomain="site.domain"
            :canFilter="canFilter"
            @close="closeModal"
            @filter="addFilter"
        />
    </div>
</template>
