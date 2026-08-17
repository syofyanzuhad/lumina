<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    Download,
    ExternalLink,
    RefreshCw,
    Settings,
    Sparkles,
} from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

withDefaults(
    defineProps<{
        activeTab?: string;
        period: string;
        showEventsTab?: boolean;
        showExport?: boolean;
        showLive?: boolean;
        isLive?: boolean;
        isRefreshing?: boolean;
        availablePeriods?: string[];
        customStartDate?: string;
        customEndDate?: string;
        siteId?: number;
        siteDomain?: string;
        showVisitSite?: boolean;
    }>(),
    {
        activeTab: 'overview',
        showEventsTab: true,
        showExport: false,
        showLive: false,
        isLive: false,
        isRefreshing: false,
        availablePeriods: () => ['today', '7d', '30d', 'custom'],
        showVisitSite: true,
    },
);

const emit = defineEmits<{
    (e: 'setTab', tab: string): void;
    (e: 'setPeriod', period: string): void;
    (e: 'update:customStartDate', val: string): void;
    (e: 'update:customEndDate', val: string): void;
    (e: 'applyCustomRange'): void;
    (e: 'toggleLive'): void;
    (e: 'refresh'): void;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-2.5 rounded-xl border border-sidebar-border/70 bg-card p-2.5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-4 dark:border-sidebar-border"
    >
        <!-- Tab Switcher -->
        <div class="flex items-center justify-between gap-2 sm:justify-start">
            <div
                v-if="showEventsTab"
                class="flex items-center gap-0.5 rounded-lg border border-sidebar-border/50 bg-muted p-0.5 sm:p-1"
            >
                <button
                    @click="emit('setTab', 'overview')"
                    :class="[
                        'rounded-md px-2.5 py-1 text-xs font-semibold transition-all sm:px-3',
                        activeTab === 'overview'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    Overview
                </button>
                <button
                    @click="emit('setTab', 'events')"
                    :class="[
                        'flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold transition-all sm:px-3',
                        activeTab === 'events'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    <Sparkles class="h-3 w-3 text-amber-500" />
                    Custom Events
                </button>
            </div>

            <!-- Mobile Appearance Selector -->
            <div class="sm:hidden">
                <AppearanceTabs />
            </div>
        </div>

        <!-- Date Period Segment & Action Controls -->
        <div
            class="flex flex-wrap items-center justify-between gap-1.5 sm:flex-nowrap sm:justify-end sm:gap-2"
        >
            <!-- Date Segment Buttons -->
            <div
                class="flex items-center gap-0.5 rounded-lg border border-sidebar-border/50 bg-muted p-0.5 text-xs sm:p-1"
            >
                <button
                    v-if="availablePeriods.includes('today')"
                    @click="emit('setPeriod', 'today')"
                    :class="[
                        'rounded-md px-2 py-1 font-semibold transition-all sm:px-2.5',
                        period === 'today'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    Today
                </button>
                <button
                    v-if="availablePeriods.includes('7d')"
                    @click="emit('setPeriod', '7d')"
                    :class="[
                        'rounded-md px-2 py-1 font-semibold transition-all sm:px-2.5',
                        period === '7d'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    7d
                </button>
                <button
                    v-if="availablePeriods.includes('30d')"
                    @click="emit('setPeriod', '30d')"
                    :class="[
                        'rounded-md px-2 py-1 font-semibold transition-all sm:px-2.5',
                        period === '30d'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    30d
                </button>

                <!-- Custom Range Dropdown -->
                <DropdownMenu v-if="availablePeriods.includes('custom')">
                    <DropdownMenuTrigger as-child>
                        <button
                            :class="[
                                'flex items-center gap-1 rounded-md px-2 py-1 font-semibold transition-all sm:px-2.5',
                                period === 'custom'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <CalendarDays class="h-3 w-3" />
                            <span>Custom</span>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-72 space-y-3 p-4">
                        <DropdownMenuLabel
                            class="p-0 text-xs font-bold text-foreground"
                            >Custom Date Range</DropdownMenuLabel
                        >
                        <DropdownMenuSeparator />
                        <div class="space-y-2">
                            <div>
                                <Label class="text-[11px] text-muted-foreground"
                                    >Start Date</Label
                                >
                                <Input
                                    type="date"
                                    :value="customStartDate"
                                    @input="
                                        emit(
                                            'update:customStartDate',
                                            ($event.target as HTMLInputElement)
                                                .value,
                                        )
                                    "
                                    class="mt-1 h-8 text-xs"
                                />
                            </div>
                            <div>
                                <Label class="text-[11px] text-muted-foreground"
                                    >End Date</Label
                                >
                                <Input
                                    type="date"
                                    :value="customEndDate"
                                    @input="
                                        emit(
                                            'update:customEndDate',
                                            ($event.target as HTMLInputElement)
                                                .value,
                                        )
                                    "
                                    class="mt-1 h-8 text-xs"
                                />
                            </div>
                        </div>
                        <Button
                            size="sm"
                            class="h-8 w-full text-xs font-semibold"
                            @click="emit('applyCustomRange')"
                        >
                            Apply Range
                        </Button>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Desktop Appearance Tabs -->
            <div class="hidden sm:block">
                <AppearanceTabs />
            </div>

            <!-- Export Menu Dropdown -->
            <DropdownMenu v-if="showExport">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1.5 text-xs font-medium sm:h-9"
                    >
                        <Download class="h-3.5 w-3.5" />
                        <span class="hidden sm:inline">Export</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuLabel class="text-xs"
                        >Export Analytics Data</DropdownMenuLabel
                    >
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <a
                            :href="`/sites/${siteId}/export?type=pageviews&format=csv`"
                            download
                            class="cursor-pointer text-xs"
                        >
                            Pageviews (CSV)
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <a
                            :href="`/sites/${siteId}/export?type=pageviews&format=json`"
                            download
                            class="cursor-pointer text-xs"
                        >
                            Pageviews (JSON)
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <a
                            :href="`/sites/${siteId}/export?type=events&format=csv`"
                            download
                            class="cursor-pointer text-xs"
                        >
                            Events (CSV)
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <a
                            :href="`/sites/${siteId}/export?type=events&format=json`"
                            download
                            class="cursor-pointer text-xs"
                        >
                            Events (JSON)
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Live Auto-Refresh Toggle -->
            <Button
                v-if="showLive"
                variant="outline"
                size="sm"
                @click="emit('toggleLive')"
                :class="[
                    'h-8 gap-1.5 text-xs font-medium transition-all sm:h-9',
                    isLive
                        ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                        : '',
                ]"
            >
                <span class="relative flex h-2 w-2">
                    <span
                        v-if="isLive"
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                    ></span>
                    <span
                        :class="[
                            'relative inline-flex h-2 w-2 rounded-full',
                            isLive
                                ? 'bg-emerald-500'
                                : 'bg-muted-foreground/40',
                        ]"
                    ></span>
                </span>
                <span>{{ isLive ? 'Live On' : 'Live Off' }}</span>
            </Button>

            <!-- Visit Site Link Button -->
            <Button
                v-if="siteDomain && showVisitSite"
                variant="outline"
                size="sm"
                as-child
                class="h-8 gap-1.5 p-2 text-xs font-medium sm:h-9 sm:px-3"
                title="Visit Site"
            >
                <a :href="`https://${siteDomain}`" target="_blank" rel="noopener noreferrer">
                    <ExternalLink class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">Visit Site</span>
                </a>
            </Button>

            <!-- Settings Link Button -->
            <Button
                variant="outline"
                size="sm"
                as-child
                class="h-8 gap-1.5 p-2 text-xs font-medium sm:h-9 sm:px-3"
                title="Dashboard Settings"
            >
                <Link :href="siteId ? `/sites/${siteId}` : '/settings/profile'">
                    <Settings class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">Settings</span>
                </Link>
            </Button>

            <!-- Manual Refresh Button -->
            <Button
                variant="outline"
                size="sm"
                @click="emit('refresh')"
                :disabled="isRefreshing"
                class="h-8 p-2 text-xs font-medium sm:h-9 sm:px-3"
                title="Refresh Analytics"
            >
                <RefreshCw
                    :class="['h-3.5 w-3.5', isRefreshing ? 'animate-spin' : '']"
                />
            </Button>
        </div>
    </div>
</template>
