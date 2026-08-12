<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, Filter, Activity, BarChart2, ListTodo } from '@lucide/vue';
import { computed, ref } from 'vue';

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
    <div class="flex flex-col gap-6">
        <!-- Header Controls -->
        <div
            class="flex flex-col justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm sm:flex-row sm:items-center dark:border-sidebar-border"
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
                    class="min-w-[200px] rounded-md border-0 bg-card py-1.5 pr-8 pl-3 text-xs font-semibold text-foreground ring-1 ring-sidebar-border ring-inset focus:ring-2 focus:ring-indigo-600 dark:bg-slate-900 dark:text-slate-100"
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
            class="rounded-xl border border-dashed border-sidebar-border/80 bg-card p-12 text-center shadow-sm dark:border-sidebar-border"
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
                class="mx-auto mt-6 max-w-2xl overflow-x-auto rounded-lg border border-sidebar-border/50 bg-muted/60 p-4 text-left font-mono text-xs dark:bg-slate-950"
            >
                window.lumina('purchase', { plan: 'pro', amount: 29.99 });
            </div>
        </div>

        <template v-else>
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm transition-all hover:shadow-md dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Total Custom Events</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
                        >
                            <Activity class="h-4 w-4" />
                        </div>
                    </div>
                    <div
                        class="mt-3 text-3xl font-black tracking-tight text-foreground"
                    >
                        {{ formatNumber(summary.total_custom_events) }}
                    </div>
                </div>

                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm transition-all hover:shadow-md dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Unique Event Types</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <ListTodo class="h-4 w-4" />
                        </div>
                    </div>
                    <div
                        class="mt-3 text-3xl font-black tracking-tight text-foreground"
                    >
                        {{ formatNumber(summary.unique_event_names) }}
                    </div>
                </div>

                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm transition-all hover:shadow-md dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Most Frequent Event</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 dark:text-amber-400"
                        >
                            <BarChart2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div
                        class="mt-3 h-9 truncate font-mono text-2xl leading-9 font-black tracking-tight text-foreground"
                    >
                        {{ summary.top_event_name || '-' }}
                    </div>
                </div>
            </div>

            <!-- Custom Event Timeline -->
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-indigo-500" />
                        <h3 class="text-sm font-bold text-foreground">
                            Event Frequency Over Time
                        </h3>
                    </div>
                    <span
                        v-if="hoveredDay"
                        class="font-mono text-xs text-indigo-600 dark:text-indigo-400"
                    >
                        {{ hoveredDay.date }}:
                        {{ formatNumber(hoveredDay.count) }} occurrences
                    </span>
                    <span v-else class="text-xs text-muted-foreground"
                        >Hover bar to inspect</span
                    >
                </div>

                <div class="flex h-44 items-end gap-1.5 pt-6 pb-2">
                    <div
                        v-if="timeline && timeline.length === 0"
                        class="flex h-full w-full items-center justify-center border-b border-muted"
                    >
                        <span class="text-xs text-muted-foreground"
                            >No events in this period</span
                        >
                    </div>
                    <div
                        v-else
                        v-for="day in timeline"
                        :key="day.date"
                        @mouseenter="hoveredDay = day"
                        @mouseleave="hoveredDay = null"
                        class="group relative flex h-full flex-1 cursor-pointer flex-col items-center justify-end"
                    >
                        <div
                            class="min-h-[3px] w-full rounded-t-md bg-indigo-500 transition-all duration-200 group-hover:bg-indigo-600 dark:bg-indigo-400 dark:group-hover:bg-indigo-300"
                            :style="{
                                height: `${Math.max(Math.round((day.count / maxDaily) * 100), 2)}%`,
                            }"
                        ></div>
                        <div
                            class="absolute bottom-full z-10 mb-2 hidden rounded bg-slate-900 px-2.5 py-1 font-mono text-xs whitespace-nowrap text-white shadow-lg group-hover:block dark:bg-slate-100 dark:text-slate-900"
                        >
                            {{ day.date }}: {{ day.count }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Breakdown -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Top Events List -->
                <div
                    class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-foreground">
                            Top Custom Events
                        </h3>
                        <span class="text-xs text-muted-foreground"
                            >{{ eventsList?.length || 0 }} events</span
                        >
                    </div>
                    <div class="max-h-[400px] space-y-3 overflow-y-auto pr-2">
                        <div
                            v-for="evt in eventsList"
                            :key="evt.name"
                            @click="selectEvent(evt.name)"
                            :class="[
                                'cursor-pointer space-y-1.5 rounded-lg border p-2 transition-all',
                                selectedEvent === evt.name
                                    ? 'border-indigo-500 bg-indigo-500/5'
                                    : 'border-transparent hover:bg-muted/50',
                            ]"
                        >
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span
                                    class="font-mono font-semibold text-indigo-600 dark:text-indigo-400"
                                    >{{ evt.name }}</span
                                >
                                <span class="font-mono text-muted-foreground"
                                    >{{ formatNumber(evt.count) }} ({{
                                        evt.percentage
                                    }}%)</span
                                >
                            </div>
                            <div
                                class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-500"
                                    :style="{ width: `${evt.percentage}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Breakdown -->
                <div
                    class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
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

                        <!-- Value Distribution bars -->
                        <div
                            v-if="
                                selectedPropertyKey &&
                                propertyBreakdown &&
                                propertyBreakdown.length > 0
                            "
                            class="space-y-3"
                        >
                            <div
                                v-for="prop in propertyBreakdown"
                                :key="prop.value"
                                class="space-y-1.5"
                            >
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="max-w-[200px] truncate pr-4 font-mono text-foreground"
                                        >{{ prop.value }}</span
                                    >
                                    <span
                                        class="shrink-0 font-mono text-muted-foreground"
                                        >{{ formatNumber(prop.count) }} ({{
                                            prop.percentage
                                        }}%)</span
                                    >
                                </div>
                                <div
                                    class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-1.5 rounded-full bg-sky-600 dark:bg-sky-500"
                                        :style="{
                                            width: `${prop.percentage}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Event Logs -->
            <div
                class="flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
            >
                <div class="border-b border-sidebar-border/50 p-6 pb-4">
                    <h3 class="text-sm font-bold text-foreground">
                        Recent Custom Event Logs
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead
                            class="bg-muted/30 text-xs text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="px-6 py-3 font-semibold">
                                    Timestamp
                                </th>
                                <th class="px-6 py-3 font-semibold">
                                    Event Name
                                </th>
                                <th class="px-6 py-3 font-semibold">Path</th>
                                <th class="px-6 py-3 font-semibold">Visitor</th>
                                <th class="px-6 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/50">
                            <template v-for="log in logs" :key="log.id">
                                <tr class="transition-colors hover:bg-muted/30">
                                    <td
                                        class="px-6 py-3 font-mono text-[11px] text-muted-foreground"
                                    >
                                        {{ log.created_at }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <span
                                            class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                                            >{{ log.event_name }}</span
                                        >
                                    </td>
                                    <td
                                        class="max-w-[150px] truncate px-6 py-3 text-xs"
                                    >
                                        {{ log.path || '—' }}
                                    </td>
                                    <td
                                        class="px-6 py-3 font-mono text-xs text-muted-foreground"
                                    >
                                        {{
                                            log.visitor_hash?.substring(0, 8) ||
                                            '—'
                                        }}
                                    </td>
                                    <td class="px-6 py-3">
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
                                    <td colspan="5" class="px-6 py-4">
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
                                    class="px-6 py-8 text-center text-xs text-muted-foreground"
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
