import { router } from '@inertiajs/vue3';
import { type Ref, unref } from 'vue';

export interface UseAnalyticsFiltersOptions {
    baseUrl: string | Ref<string>;
    siteId?: number | Ref<number | undefined>;
    currentFilters?: Ref<Record<string, string> | undefined>;
    currentPeriod?: Ref<string | undefined>;
    currentTab?: Ref<string | undefined>;
}

export function useAnalyticsFilters(options: UseAnalyticsFiltersOptions) {
    const getBaseUrl = () => unref(options.baseUrl);

    const buildParams = (filters: Record<string, string>) => {
        const params: Record<string, any> = { ...filters };
        const siteId = unref(options.siteId);
        const period = unref(options.currentPeriod);
        const tab = unref(options.currentTab);

        if (siteId) params.site_id = siteId;
        if (period) params.period = period;
        if (tab) params.tab = tab;

        return params;
    };

    const addFilter = (key: string, value: string) => {
        const url = getBaseUrl();
        if (!url) return;

        const current = { ...(unref(options.currentFilters) || {}) };
        current[key] = value;

        router.get(url, buildParams(current), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const removeFilter = (key: string) => {
        const url = getBaseUrl();
        if (!url) return;

        const current = { ...(unref(options.currentFilters) || {}) };
        delete current[key];

        router.get(url, buildParams(current), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        const url = getBaseUrl();
        if (!url) return;

        router.get(url, buildParams({}), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return {
        addFilter,
        removeFilter,
        clearFilters,
    };
}
