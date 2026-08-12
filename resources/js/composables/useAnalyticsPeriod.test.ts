import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useAnalyticsPeriod } from './useAnalyticsPeriod';

const routerGet = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', () => ({
    router: { get: routerGet },
}));

describe('useAnalyticsPeriod', () => {
    beforeEach(() => {
        routerGet.mockClear();
    });

    it('navigates with the new period and preserved site/tab state', () => {
        const siteId = ref(7);
        const tab = ref('overview');
        const filters = ref({ path: '/pricing' });

        const { setPeriod } = useAnalyticsPeriod({
            baseUrl: '/dashboard',
            siteId,
            currentTab: tab,
            currentFilters: filters,
        });

        setPeriod('7d');

        expect(routerGet).toHaveBeenCalledWith(
            '/dashboard',
            { period: '7d', path: '/pricing', site_id: 7, tab: 'overview' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('omits site_id and tab when they are not provided', () => {
        const { setPeriod } = useAnalyticsPeriod({ baseUrl: '/dashboard' });

        setPeriod('30d');

        expect(routerGet).toHaveBeenCalledWith(
            '/dashboard',
            { period: '30d' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('applies a custom range with start and end dates', () => {
        const { customStartDate, customEndDate, applyCustomDateRange } =
            useAnalyticsPeriod({
                baseUrl: '/dashboard',
                siteId: ref(3),
            });

        customStartDate.value = '2026-07-01';
        customEndDate.value = '2026-07-31';
        applyCustomDateRange();

        expect(routerGet).toHaveBeenCalledWith(
            '/dashboard',
            {
                period: 'custom',
                start_date: '2026-07-01',
                end_date: '2026-07-31',
                site_id: 3,
            },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('does nothing when the base URL is empty', () => {
        const { setPeriod } = useAnalyticsPeriod({ baseUrl: '' });

        setPeriod('7d');

        expect(routerGet).not.toHaveBeenCalled();
    });
});
