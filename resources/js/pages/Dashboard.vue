<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

</script>

<template>
    <Head :title="`${activeSite.domain} — Analytics`" />


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
