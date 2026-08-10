<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AnalyticsDashboard from '@/components/analytics/AnalyticsDashboard.vue';

interface SiteItem {
    id: number;
    domain: string;
}

const props = defineProps<{
    sites: SiteItem[];
    activeSite: SiteItem;
    period: string;
    activeTab?: string;
    filters?: Record<string, string>;
    // Immediate KPI props
    total_pageviews?: number;
    unique_visitors?: number;
    current_visitors?: number;
    bounce_rate?: number;
    avg_duration?: number;
    daily_pageviews?: any[];
    // Deferred breakdown props
    top_pages?: any[];
    top_referrers?: any[];
    device_breakdown?: any[];
    top_browsers?: any[];
    top_os?: any[];
    top_countries?: any[];
    utm_campaigns?: any[];
    custom_events?: any[];
    goals?: any[];
    // Custom Events Tab
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

const changeSite = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const siteId = target.value;
    router.get('/dashboard', { site_id: siteId, period: props.period, tab: props.activeTab }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`${activeSite.domain} — Analytics`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 sm:p-6">
        <!-- Site Switcher Bar -->
        <div v-if="sites.length > 1" class="flex items-center justify-between bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl p-3 sm:p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Select Site:</span>
                <select
                    :value="activeSite.id"
                    @change="changeSite"
                    class="h-8 text-xs font-semibold bg-background border border-sidebar-border rounded-lg px-2.5 text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                >
                    <option v-for="site in sites" :key="site.id" :value="site.id">
                        {{ site.domain }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Main Analytics Dashboard Component -->
        <AnalyticsDashboard
            baseUrl="/dashboard"
            breakdownUrl="/dashboard/breakdown"
            :site="activeSite"
            :sites="sites"
            :period="period"
            :activeTab="activeTab"
            :filters="filters"
            :total_pageviews="total_pageviews"
            :unique_visitors="unique_visitors"
            :current_visitors="current_visitors"
            :bounce_rate="bounce_rate"
            :avg_duration="avg_duration"
            :daily_pageviews="daily_pageviews"
            :top_pages="top_pages"
            :top_referrers="top_referrers"
            :device_breakdown="device_breakdown"
            :top_browsers="top_browsers"
            :top_os="top_os"
            :top_countries="top_countries"
            :utm_campaigns="utm_campaigns"
            :custom_events="custom_events"
            :goals="goals"
            :showLive="true"
            :showExport="true"
            :showEventsTab="true"
            :canFilter="true"
            :canExpand="true"
            :availablePeriods="['today', '7d', '30d', 'custom']"
            :selectedEvent="selectedEvent"
            :selectedPropertyKey="selectedPropertyKey"
            :custom_event_summary="custom_event_summary"
            :custom_events_list="custom_events_list"
            :custom_event_timeline="custom_event_timeline"
            :custom_event_property_keys="custom_event_property_keys"
            :custom_event_property_breakdown="custom_event_property_breakdown"
            :custom_event_logs="custom_event_logs"
        />
    </div>
</template>
