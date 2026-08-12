import { router } from '@inertiajs/vue3';
import { ref, unref } from 'vue';
import type { Ref } from 'vue';

export interface UseAnalyticsPeriodOptions {
    baseUrl: string | Ref<string>;
    siteId?: number | Ref<number | undefined>;
    currentFilters?: Ref<Record<string, string> | undefined>;
    currentTab?: Ref<string | undefined>;
}

export function useAnalyticsPeriod(options: UseAnalyticsPeriodOptions) {
    const customStartDate = ref(
        new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0],
    );
    const customEndDate = ref(new Date().toISOString().split('T')[0]);

    const getBaseUrl = () => unref(options.baseUrl);

    const setPeriod = (newPeriod: string) => {
        const url = getBaseUrl();

        if (!url) {
            return;
        }

        const siteId = unref(options.siteId);
        const tab = unref(options.currentTab);
        const filters = unref(options.currentFilters) || {};

        const params: Record<string, any> = {
            period: newPeriod,
            ...filters,
        };

        if (siteId) {
            params.site_id = siteId;
        }

        if (tab) {
            params.tab = tab;
        }

        router.get(url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const applyCustomDateRange = () => {
        const url = getBaseUrl();

        if (!url || !customStartDate.value || !customEndDate.value) {
            return;
        }

        const siteId = unref(options.siteId);
        const tab = unref(options.currentTab);
        const filters = unref(options.currentFilters) || {};

        const params: Record<string, any> = {
            period: 'custom',
            start_date: customStartDate.value,
            end_date: customEndDate.value,
            ...filters,
        };

        if (siteId) {
            params.site_id = siteId;
        }

        if (tab) {
            params.tab = tab;
        }

        router.get(url, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return {
        customStartDate,
        customEndDate,
        setPeriod,
        applyCustomDateRange,
    };
}
