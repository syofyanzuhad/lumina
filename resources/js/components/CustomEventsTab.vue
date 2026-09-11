<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Activity, Calendar, Filter } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    formatCompactNumber,
    formatDateLabel,
} from '@/composables/useAnalyticsFormatters';

interface CustomEventSummary {
    total_custom_events: number;
    unique_event_names: number;
    top_event_name: string | null;
}

interface CustomEventItem {
    name: string;
    count: number;
    percentage: number;
    last_seen: string;
}

interface TimelineItem {
    date: string;
    count: number;
}

interface PropertyBreakdown {
    value: string;
    count: number;
    percentage: number;
}

interface LogItem {
    id: number;
    created_at: string;
    path: string;
    visitor_hash: string;
    device_type: string;
    browser: string;
    os: string;
    country_name: string;
    country_code: string;
    event_name: string;
    props: any;
}

const props = defineProps<{
    siteId: number;
    period: string;
    baseUrl?: string;
    selectedEvent?: string | null;
    selectedPropertyKey?: string | null;
    summary?: CustomEventSummary;
    eventsList?: CustomEventItem[];
    timeline?: TimelineItem[];
    propertyKeys?: string[];
    propertyBreakdown?: PropertyBreakdown[];
    logs?: LogItem[];
}>();

const formatNumber = (num: number) => new Intl.NumberFormat().format(num);

const maxDaily = computed(() => {
    if (!props.timeline || props.timeline.length === 0) {
        return 1;
    }

    const max = Math.max(...props.timeline.map((d) => d.count));

    return max > 0 ? max : 1;
});

const hoveredDay = ref<TimelineItem | null>(null);
const expandedLogs = ref<Set<number>>(new Set());

const toggleLog = (id: number) => {
    if (expandedLogs.value.has(id)) {
        expandedLogs.value.delete(id);
    } else {
        expandedLogs.value.add(id);
    }
};

const getTargetUrl = () => props.baseUrl || '/dashboard';

const handleEventChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const eventName = target.value;
    router.get(
        getTargetUrl(),
        {
            tab: 'events',
            site_id: props.siteId,
            period: props.period,
            event: eventName === 'all' ? undefined : eventName,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const selectEvent = (eventName: string) => {
    router.get(
        getTargetUrl(),
        {
            tab: 'events',
            site_id: props.siteId,
            period: props.period,
            event: eventName,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const selectPropertyKey = (key: string) => {
    router.get(
        getTargetUrl(),
        {
            tab: 'events',
            site_id: props.siteId,
            period: props.period,
            event: props.selectedEvent,
            property: key,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <div class="flex flex-col">
        <!-- Header Controls: Borderless with hairline divider -->
        <div
            class="flex flex-col justify-between gap-4 border-b border-sidebar-border/60 pb-5 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-2">
                <Filter class="h-4 w-4 text-muted-foreground" />
                <label
                    for="event-filter"
                    class="text-sm font-semibold text-foreground"
                    >Filter by event</label
                >
            </div>
            <div>
                <select
                    id="event-filter"
                    :value="selectedEvent || 'all'"
                    @change="handleEventChange"
                    class="min-w-[200px] rounded-lg border border-sidebar-border/80 bg-background py-1.5 pr-8 pl-3 font-mono text-xs font-semibold text-foreground shadow-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-sidebar-border dark:bg-card"
                >
                    <option value="all">All Custom Events</option>
                    <option
                        v-for="evt in eventsList"
                        :key="evt.name"
                        :value="evt.name"
                    >
                        {{ evt.name }}
                    </option>
                </select>
            </div>
        </div>

        <div
            v-if="!summary || summary.total_custom_events === 0"
            class="p-12 text-center"
        >
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
            >
                <Activity class="h-7 w-7" />
            </div>
            <h3 class="mt-4 text-lg font-bold">No custom events tracked yet</h3>
            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                Use window.lumina('event_name', { props }) to start tracking
                custom actions.
            </p>
            <div
                class="mx-auto mt-6 max-w-2xl overflow-x-auto rounded-lg border border-sidebar-border/50 bg-muted/40 p-4 text-left font-mono text-xs dark:bg-slate-950"
            >
                window.lumina('purchase', { plan: 'pro', amount: 29.99 });
            </div>
        </div>

        <template v-else>
            <!-- KPI Summary Stat Row: Borderless with colored accent bars -->
            <div
                class="flex flex-wrap items-center gap-x-8 gap-y-4 border-b border-sidebar-border/60 py-6 sm:gap-x-12"
            >
                <!-- Total Custom Events -->
                <div class="flex items-start gap-2.5 text-left">
                    <span
                        class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full bg-indigo-500"
                    ></span>
                    <div>
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                            >
                                {{
                                    formatCompactNumber(
                                        summary.total_custom_events,
                                    )
                                }}
                            </span>
                            <span
                                v-if="summary.total_custom_events > 999"
                                class="font-mono text-[11px] text-muted-foreground"
                            >
                                ({{
                                    formatNumber(summary.total_custom_events)
                                }})
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Total Custom Events
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Unique Event Types -->
                <div class="flex items-start gap-2.5 text-left">
                    <span
                        class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full bg-emerald-500"
                    ></span>
                    <div>
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                            >
                                {{
                                    formatCompactNumber(
                                        summary.unique_event_names,
                                    )
                                }}
                            </span>
                            <span
                                v-if="summary.unique_event_names > 999"
                                class="font-mono text-[11px] text-muted-foreground"
                            >
                                ({{ formatNumber(summary.unique_event_names) }})
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Unique Event Types
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Most Frequent Event -->
                <div class="flex items-start gap-2.5 text-left">
                    <span
                        class="mt-1 inline-block h-6 w-1 shrink-0 rounded-full bg-amber-500"
                    ></span>
                    <div>
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="truncate font-mono text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                            >
                                {{ summary.top_event_name || '—' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Most Frequent Event
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Event Timeline Chart: Cardless with hairline bottom border -->
            <div class="border-b border-sidebar-border/60 py-6">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-indigo-500" />
                        <h3
                            class="text-sm font-bold tracking-tight text-foreground"
                        >
                            Event Frequency Over Time
                        </h3>
                    </div>
                    <span
                        v-if="hoveredDay"
                        class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400"
                    >
                        {{ formatDateLabel(hoveredDay.date) }}:
                        {{ formatNumber(hoveredDay.count) }} occurrences
                    </span>
                    <span v-else class="text-xs text-muted-foreground"
                        >Hover bar to inspect</span
                    >
                </div>

                <!-- Chart Container with Y-Axis and Gridlines -->
                <div class="relative flex h-48 w-full gap-2 pt-6 pb-2 sm:h-56">
                    <!-- Y-Axis Value Labels -->
                    <div
                        class="pointer-events-none flex h-full w-8 flex-col justify-between text-right font-mono text-[10px] text-muted-foreground select-none sm:w-10 sm:text-[11px]"
                    >
                        <span>{{ formatCompactNumber(maxDaily) }}</span>
                        <span>{{
                            formatCompactNumber(Math.round(maxDaily * 0.75))
                        }}</span>
                        <span>{{
                            formatCompactNumber(Math.round(maxDaily * 0.5))
                        }}</span>
                        <span>{{
                            formatCompactNumber(Math.round(maxDaily * 0.25))
                        }}</span>
                        <span>0</span>
                    </div>

                    <!-- Chart Canvas & Bars -->
                    <div
                        class="group/chart relative flex h-full flex-1 items-end gap-1"
                    >
                        <!-- Horizontal Gridlines -->
                        <div
                            class="pointer-events-none absolute inset-0 flex flex-col justify-between"
                        >
                            <div
                                class="w-full border-t border-dashed border-sidebar-border/50 dark:border-sidebar-border/30"
                            ></div>
                            <div
                                class="w-full border-t border-dashed border-sidebar-border/40 dark:border-sidebar-border/25"
                            ></div>
                            <div
                                class="w-full border-t border-dashed border-sidebar-border/30 dark:border-sidebar-border/20"
                            ></div>
                            <div
                                class="w-full border-t border-dashed border-sidebar-border/30 dark:border-sidebar-border/20"
                            ></div>
                            <div
                                class="w-full border-t border-sidebar-border/70 dark:border-sidebar-border/60"
                            ></div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="timeline && timeline.length === 0"
                            class="relative z-10 flex h-full w-full items-center justify-center"
                        >
                            <span class="text-xs text-muted-foreground"
                                >No events in this period</span
                            >
                        </div>

                        <!-- Bars -->
                        <div
                            v-else
                            v-for="day in timeline"
                            :key="day.date"
                            @mouseenter="hoveredDay = day"
                            @mouseleave="hoveredDay = null"
                            class="group relative z-10 flex h-full flex-1 cursor-pointer flex-col items-center justify-end"
                        >
                            <!-- Tooltip -->
                            <div
                                v-if="hoveredDay?.date === day.date"
                                class="pointer-events-none absolute bottom-full z-30 mb-2 flex -translate-y-1 transform flex-col items-center transition-all duration-150"
                            >
                                <div
                                    class="space-y-1 rounded-lg border border-sidebar-border/80 bg-popover px-3 py-2 text-xs whitespace-nowrap text-popover-foreground shadow-2xl"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-xs font-bold text-foreground"
                                    >
                                        <span>{{
                                            formatDateLabel(day.date)
                                        }}</span>
                                    </div>
                                    <div class="text-[10px]">
                                        <span
                                            class="font-bold text-indigo-600 dark:text-indigo-400"
                                        >
                                            {{ formatNumber(day.count) }}
                                            occurrences
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="-mt-1 h-2 w-2 rotate-45 border-r border-b border-sidebar-border/80 bg-popover"
                                ></div>
                            </div>

                            <div
                                class="min-h-[3px] w-full rounded-t-sm bg-indigo-500/80 transition-all duration-200 group-hover:bg-indigo-600 dark:bg-indigo-400 dark:group-hover:bg-indigo-300"
                                :style="{
                                    height: `${Math.max(Math.round((day.count / maxDaily) * 100), 2)}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- X-Axis Date Range Labels -->
                <div
                    v-if="timeline && timeline.length > 0"
                    class="flex items-center justify-between border-t border-sidebar-border/40 pt-2 pl-8 font-mono text-[9px] text-muted-foreground sm:pl-10 sm:text-[10px]"
                >
                    <span>{{ formatDateLabel(timeline[0].date) }}</span>
                    <span v-if="timeline.length > 2">
                        {{
                            formatDateLabel(
                                timeline[Math.floor(timeline.length / 2)].date,
                            )
                        }}
                    </span>
                    <span>{{
                        formatDateLabel(timeline[timeline.length - 1].date)
                    }}</span>
                </div>
            </div>

            <!-- Two-Column Breakdown: Separated by vertical line divider -->
            <div
                class="grid grid-cols-1 divide-y divide-sidebar-border/60 border-b border-sidebar-border/60 lg:grid-cols-2 lg:divide-x lg:divide-y-0"
            >
                <!-- Top Events List -->
                <div class="p-4 sm:p-5 lg:p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-foreground">
                            Top Custom Events
                        </h3>
                        <span class="font-mono text-xs text-muted-foreground"
                            >{{ eventsList?.length || 0 }}
                            {{
                                (eventsList?.length || 0) === 1
                                    ? 'event'
                                    : 'events'
                            }}</span
                        >
                    </div>
                    <div class="max-h-[400px] space-y-2 overflow-y-auto pr-1">
                        <div
                            v-for="evt in eventsList"
                            :key="evt.name"
                            @click="selectEvent(evt.name)"
                            :class="[
                                'group relative flex cursor-pointer items-center justify-between space-y-1.5 overflow-hidden rounded-lg p-2 text-xs font-medium transition-all',
                                selectedEvent === evt.name
                                    ? 'border border-indigo-500 bg-indigo-500/10'
                                    : 'border border-transparent hover:opacity-90',
                            ]"
                        >
                            <!-- Percentage Fill Background Bar -->
                            <div
                                class="absolute inset-y-0 left-0 rounded-lg bg-indigo-100/70 transition-all duration-500 group-hover:bg-indigo-200/80 dark:bg-indigo-500/15 dark:group-hover:bg-indigo-500/25"
                                :style="{ width: `${evt.percentage}%` }"
                            ></div>

                            <div
                                class="relative z-10 flex min-w-0 items-center gap-2 font-mono font-medium text-foreground transition-colors group-hover:text-indigo-700 dark:group-hover:text-indigo-300"
                            >
                                <span
                                    class="font-mono font-semibold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ evt.name }}
                                </span>
                            </div>

                            <span
                                class="relative z-10 shrink-0 font-mono text-xs text-muted-foreground"
                            >
                                {{ formatNumber(evt.count) }} ({{
                                    evt.percentage
                                }}%)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Property Breakdown -->
                <div class="p-4 sm:p-5 lg:p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-foreground">
                            Property Value Breakdown
                        </h3>
                    </div>

                    <div v-if="!selectedEvent">
                        <p class="text-sm text-muted-foreground">
                            Select an event from the list to inspect its
                            properties.
                        </p>
                    </div>
                    <div v-else-if="!propertyKeys || propertyKeys.length === 0">
                        <p class="text-sm text-muted-foreground">
                            No metadata properties recorded for
                            <span class="font-mono text-indigo-500">{{
                                selectedEvent
                            }}</span
                            >.
                        </p>
                    </div>
                    <div v-else class="space-y-6">
                        <!-- Key Selector Tabs -->
                        <div>
                            <label
                                class="mb-2 block text-xs font-semibold text-muted-foreground"
                                >Select metadata key:</label
                            >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="key in propertyKeys"
                                    :key="key"
                                    @click="selectPropertyKey(key)"
                                    :class="[
                                        'rounded-md border px-2.5 py-1 font-mono text-xs font-semibold transition-all',
                                        selectedPropertyKey === key
                                            ? 'border-sky-600 bg-sky-600 text-white dark:border-sky-500 dark:bg-sky-500'
                                            : 'border-sidebar-border bg-card text-foreground hover:border-sky-500',
                                    ]"
                                >
                                    {{ key }}
                                </button>
                            </div>
                        </div>

                        <!-- Value Distribution bars with fill background -->
                        <div
                            v-if="
                                selectedPropertyKey &&
                                propertyBreakdown &&
                                propertyBreakdown.length > 0
                            "
                            class="space-y-2"
                        >
                            <div
                                v-for="prop in propertyBreakdown"
                                :key="prop.value"
                                class="group relative flex items-center justify-between overflow-hidden rounded-lg p-2 text-xs font-medium transition-all"
                            >
                                <!-- Percentage Background Bar -->
                                <div
                                    class="absolute inset-y-0 left-0 rounded-lg bg-sky-100/70 transition-all duration-500 group-hover:bg-sky-200/80 dark:bg-sky-500/15 dark:group-hover:bg-sky-500/25"
                                    :style="{ width: `${prop.percentage}%` }"
                                ></div>

                                <span
                                    class="relative z-10 max-w-[200px] truncate font-mono text-foreground group-hover:text-sky-700 dark:group-hover:text-sky-300"
                                >
                                    {{ prop.value }}
                                </span>

                                <span
                                    class="relative z-10 shrink-0 font-mono text-xs text-muted-foreground"
                                >
                                    {{ formatNumber(prop.count) }} ({{
                                        prop.percentage
                                    }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Event Logs: Cardless border-b with clean table -->
            <div class="py-6">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-foreground">
                        Recent Custom Event Logs
                    </h3>
                </div>
                <div class="overflow-x-auto border-t border-sidebar-border/60">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead
                            class="bg-muted/30 text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="px-4 py-3 font-semibold sm:px-6">
                                    Timestamp
                                </th>
                                <th class="px-4 py-3 font-semibold sm:px-6">
                                    Event Name
                                </th>
                                <th class="px-4 py-3 font-semibold sm:px-6">
                                    Path
                                </th>
                                <th class="px-4 py-3 font-semibold sm:px-6">
                                    Visitor
                                </th>
                                <th class="px-4 py-3 font-semibold sm:px-6">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/50">
                            <template v-for="log in logs" :key="log.id">
                                <tr class="transition-colors hover:bg-muted/30">
                                    <td
                                        class="px-4 py-3 font-mono text-[11px] text-muted-foreground sm:px-6"
                                    >
                                        {{ log.created_at }}
                                    </td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <span
                                            class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                                            >{{ log.event_name }}</span
                                        >
                                    </td>
                                    <td
                                        class="max-w-[150px] truncate px-4 py-3 text-xs sm:px-6"
                                    >
                                        {{ log.path || '—' }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-mono text-xs text-muted-foreground sm:px-6"
                                    >
                                        {{
                                            log.visitor_hash?.substring(0, 8) ||
                                            '—'
                                        }}
                                    </td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <button
                                            @click="toggleLog(log.id)"
                                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                        >
                                            View Raw Payload
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="expandedLogs.has(log.id)"
                                    class="bg-muted/10"
                                >
                                    <td colspan="5" class="px-4 py-4 sm:px-6">
                                        <div
                                            class="overflow-x-auto rounded-lg border border-sidebar-border/50 bg-muted/60 p-4 font-mono text-xs text-foreground dark:bg-slate-950"
                                        >
                                            <pre>{{
                                                JSON.stringify(
                                                    log.props,
                                                    null,
                                                    2,
                                                )
                                            }}</pre>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="!logs || logs.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center text-xs text-muted-foreground sm:px-6"
                                >
                                    No recent events
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
