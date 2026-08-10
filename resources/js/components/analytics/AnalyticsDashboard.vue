<script setup lang="ts">
import { computed, toRef } from 'vue';
import { router } from '@inertiajs/vue3';
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
    overview?: any;
    filters?: Record<string, string>;

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
const overviewRef = toRef(props, 'overview');

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

const dailyPageviewsRef = computed(() => props.overview?.daily_pageviews);
const {
    hoveredDay,
    showViews,
    showVisitors,
    toggleViews,
    toggleVisitors,
    maxDaily,
} = useAnalyticsChart(dailyPageviewsRef);

const { isLive, isRefreshing, toggleLive, refreshData } = useLivePolling({
    only: ['overview'],
});

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
    if (!props.overview?.top_pages) return [];
    return props.overview.top_pages.map((p: any) => ({
        idKey: p.path,
        label: p.path,
        count: p.count,
        percentage: p.percentage,
        path: p.path,
    }));
});

const topReferrersItems = computed<BreakdownCardItem[]>(() => {
    if (!props.overview?.top_referrers) return [];
    return props.overview.top_referrers.map((r: any) => ({
        idKey: r.referrer,
        label: r.referrer,
        count: r.count,
        percentage: r.percentage,
        icon: getReferrerFavicon(r.referrer),
        isComponentIcon: false,
    }));
});

const deviceItems = computed<BreakdownCardItem[]>(() => {
    if (!props.overview?.device_breakdown) return [];
    return props.overview.device_breakdown.map((d: any) => ({
        idKey: d.device,
        label: d.device,
        count: d.count,
        percentage: d.percentage,
        icon: getDeviceIcon(d.device),
        isComponentIcon: true,
    }));
});

const topBrowsersItems = computed<BreakdownCardItem[]>(() => {
    if (!props.overview?.top_browsers) return [];
    return props.overview.top_browsers.map((b: any) => ({
        idKey: b.browser,
        label: b.browser,
        count: b.count,
        percentage: b.percentage,
        icon: getBrowserIcon(b.browser),
        isComponentIcon: false,
    }));
});

const topOsItems = computed<BreakdownCardItem[]>(() => {
    if (!props.overview?.top_os) return [];
    return props.overview.top_os.map((o: any) => ({
        idKey: o.os,
        label: o.os,
        count: o.count,
        percentage: o.percentage,
        icon: getOsIcon(o.os),
        isComponentIcon: false,
    }));
});

const topCountriesItems = computed<BreakdownCardItem[]>(() => {
    if (!props.overview?.top_countries) return [];
    return props.overview.top_countries.map((c: any) => ({
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
        <div v-if="activeTab === 'overview' && overview" class="space-y-6">
            <!-- KPI Cards -->
            <AnalyticsKpiCards
                :currentVisitors="overview.current_visitors"
                :totalPageviews="overview.total_pageviews"
                :uniqueVisitors="overview.total_visitors"
                :bounceRate="overview.bounce_rate"
                :avgDuration="overview.avg_duration"
            />

            <!-- Interactive Chart -->
            <AnalyticsChart
                :dailyPageviews="overview.daily_pageviews"
                :showViews="showViews"
                :showVisitors="showVisitors"
                :hoveredDay="hoveredDay"
                :maxDaily="maxDaily"
                @update:hoveredDay="hoveredDay = $event"
                @toggleViews="toggleViews"
                @toggleVisitors="toggleVisitors"
            />

            <!-- Detail Grid Row 1: Top Pages, Top Referrers, Device Types -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <AnalyticsBreakdownCard
                    title="Top Pages"
                    filterKey="path"
                    typeKey="pages"
                    colorScheme="indigo"
                    :items="topPagesItems"
                    :totalItems="overview.top_pages?.length"
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
                    :totalItems="overview.top_referrers?.length"
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
                    :totalItems="overview.device_breakdown?.length"
                    :canFilter="canFilter"
                    :canExpand="canExpand"
                    @filter="addFilter"
                    @expand="openModal"
                />
            </div>

            <!-- Detail Grid Row 2: Top Browsers, Top OS, Top Locations -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <AnalyticsBreakdownCard
                    title="Top Browsers"
                    filterKey="browser"
                    typeKey="browsers"
                    colorScheme="sky"
                    :items="topBrowsersItems"
                    :totalItems="overview.top_browsers?.length"
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
                    :totalItems="overview.top_os?.length"
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
                    :totalItems="overview.top_countries?.length"
                    :canFilter="canFilter"
                    :canExpand="canExpand"
                    @filter="addFilter"
                    @expand="openModal"
                />
            </div>

            <!-- UTM Campaigns Card (If available) -->
            <div v-if="overview.utm_campaigns && overview.utm_campaigns.length > 0">
                <AnalyticsBreakdownCard
                    title="UTM Campaigns"
                    filterKey="utm_campaign"
                    typeKey="utm"
                    colorScheme="indigo"
                    :items="overview.utm_campaigns.map((u: any) => ({ idKey: u.utm_campaign, label: u.utm_campaign, count: u.count, percentage: u.percentage }))"
                    :totalItems="overview.utm_campaigns.length"
                    :canFilter="canFilter"
                    :canExpand="canExpand"
                    @filter="addFilter"
                    @expand="openModal"
                />
            </div>
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
            :overview="overview"
            :siteDomain="site.domain"
            :canFilter="canFilter"
            @close="closeModal"
            @filter="addFilter"
        />
    </div>
</template>
