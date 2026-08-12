<script setup lang="ts">
import { Filter, ExternalLink, Globe, Laptop } from '@lucide/vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    formatNumber,
    getCountryFlag,
    getDeviceIcon,
    getReferrerFavicon,
    getBrowserIcon,
    getOsIcon,
} from '@/composables/useAnalyticsFormatters';

const props = defineProps<{
    open: boolean;
    title: string;
    type: string | null;
    modalData: any[] | null;
    isLoading: boolean;
    totalCount: { itemCount: number; totalSum: number } | null;
    overview?: any;
    siteDomain?: string;
    canFilter?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'filter', key: string, value: string): void;
}>();

const getItemFilterKey = (type: string) => {
    switch (type) {
        case 'pages':
            return 'path';
        case 'referrers':
            return 'referrer';
        case 'devices':
            return 'device';
        case 'browsers':
            return 'browser';
        case 'os':
            return 'os';
        case 'locations':
            return 'country';
        case 'utm':
            return 'utm_campaign';
        default:
            return type;
    }
};

const getItemLabel = (item: any, type: string) => {
    switch (type) {
        case 'pages':
            return item.path;
        case 'referrers':
            return item.referrer;
        case 'devices':
            return item.device;
        case 'browsers':
            return item.browser;
        case 'os':
            return item.os;
        case 'locations':
            return item.name || item.code;
        case 'utm':
            return item.utm_campaign;
        default:
            return item.name || item.label || '';
    }
};

const getFallbackData = (type: string) => {
    if (!props.overview) {
        return [];
    }

    switch (type) {
        case 'pages':
            return props.overview.top_pages || [];
        case 'referrers':
            return props.overview.top_referrers || [];
        case 'devices':
            return props.overview.device_breakdown || [];
        case 'browsers':
            return props.overview.top_browsers || [];
        case 'os':
            return props.overview.top_os || [];
        case 'locations':
            return props.overview.top_countries || [];
        case 'utm':
            return props.overview.utm_campaigns || [];
        default:
            return [];
    }
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('close')">
        <SheetContent class="flex w-full flex-col overflow-y-auto sm:max-w-md">
            <SheetHeader class="space-y-1">
                <SheetTitle>{{ title }}</SheetTitle>
                <SheetDescription
                    >Detailed breakdown for selected period</SheetDescription
                >
                <div
                    v-if="totalCount"
                    class="pt-2 font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400"
                >
                    Total ({{ totalCount.itemCount }} items):
                    {{ formatNumber(totalCount.totalSum) }} sum
                </div>
            </SheetHeader>

            <div class="mt-6 flex-1 space-y-2">
                <div
                    v-if="isLoading"
                    class="py-8 text-center text-xs text-muted-foreground"
                >
                    Loading breakdown data...
                </div>

                <div v-else-if="type">
                    <div
                        v-for="item in modalData || getFallbackData(type)"
                        :key="getItemLabel(item, type)"
                        @click="
                            canFilter
                                ? emit(
                                      'filter',
                                      getItemFilterKey(type),
                                      getItemLabel(item, type),
                                  )
                                : null
                        "
                        :class="[
                            'group relative flex items-center justify-between overflow-hidden rounded-lg border border-transparent p-2.5 text-xs font-medium transition-all hover:border-sidebar-border',
                            canFilter
                                ? 'cursor-pointer hover:opacity-90'
                                : 'cursor-default',
                        ]"
                    >
                        <div
                            class="absolute inset-y-0 left-0 rounded-lg bg-indigo-500/10 transition-all duration-300 group-hover:bg-indigo-500/20 dark:bg-indigo-500/15"
                            :style="{ width: `${item.percentage}%` }"
                        ></div>

                        <span
                            class="relative z-10 mr-2 flex min-w-0 items-center gap-1.5 truncate font-mono font-medium text-foreground"
                        >
                            <!-- Flag -->
                            <span
                                v-if="type === 'locations' && item.code"
                                class="text-base leading-none select-none"
                                >{{ getCountryFlag(item.code) }}</span
                            >
                            <span
                                v-if="type === 'locations' && item.code"
                                class="rounded bg-muted px-1 py-0.5 text-[10px] font-bold text-muted-foreground uppercase"
                                >{{ item.code }}</span
                            >

                            <!-- Device Icon -->
                            <component
                                v-if="type === 'devices'"
                                :is="getDeviceIcon(item.device)"
                                class="h-3.5 w-3.5 shrink-0 text-amber-500"
                            />

                            <!-- Referrer Favicon -->
                            <img
                                v-else-if="
                                    type === 'referrers' &&
                                    getReferrerFavicon(item.referrer)
                                "
                                :src="getReferrerFavicon(item.referrer)!"
                                :alt="item.referrer"
                                class="h-3.5 w-3.5 shrink-0 rounded-sm object-contain"
                                @error="
                                    (
                                        $event.target as HTMLImageElement
                                    ).style.display = 'none'
                                "
                            />

                            <!-- Browser Icon -->
                            <img
                                v-else-if="
                                    type === 'browsers' &&
                                    getBrowserIcon(item.browser)
                                "
                                :src="getBrowserIcon(item.browser)!"
                                :alt="item.browser"
                                class="h-3.5 w-3.5 shrink-0 object-contain dark:brightness-200 dark:invert"
                                @error="
                                    (
                                        $event.target as HTMLImageElement
                                    ).style.display = 'none'
                                "
                            />

                            <!-- OS Icon -->
                            <img
                                v-else-if="type === 'os' && getOsIcon(item.os)"
                                :src="getOsIcon(item.os)!"
                                :alt="item.os"
                                class="h-3.5 w-3.5 shrink-0 object-contain dark:brightness-200 dark:invert"
                                @error="
                                    (
                                        $event.target as HTMLImageElement
                                    ).style.display = 'none'
                                "
                            />

                            <!-- Fallbacks -->
                            <Globe
                                v-else-if="
                                    type === 'referrers' || type === 'browsers'
                                "
                                class="h-3 w-3 shrink-0 text-muted-foreground/60"
                            />
                            <Laptop
                                v-else-if="type === 'os'"
                                class="h-3 w-3 shrink-0 text-muted-foreground/60"
                            />

                            <span class="truncate">{{
                                getItemLabel(item, type)
                            }}</span>

                            <a
                                v-if="type === 'pages' && siteDomain"
                                :href="`https://${siteDomain}${item.path.startsWith('/') ? '' : '/'}${item.path}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                @click.stop
                                class="inline-flex shrink-0 items-center justify-center rounded p-0.5 text-muted-foreground/60 transition-colors hover:bg-muted/80 hover:text-foreground"
                            >
                                <ExternalLink class="h-3 w-3" />
                            </a>

                            <Filter
                                v-if="canFilter"
                                class="ml-0.5 h-3 w-3 shrink-0 opacity-0 transition-opacity group-hover:opacity-60"
                            />
                        </span>

                        <span
                            class="relative z-10 shrink-0 font-mono text-xs text-muted-foreground"
                        >
                            <span class="mr-1.5 text-muted-foreground/70"
                                >{{ item.percentage }}%</span
                            >
                            {{ formatNumber(item.count) }}
                        </span>
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
