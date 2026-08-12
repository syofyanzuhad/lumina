import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useAnalyticsFilters } from './useAnalyticsFilters';

const routerGet = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', () => ({
    router: { get: routerGet },
}));

describe('useAnalyticsFilters', () => {
    beforeEach(() => {
        routerGet.mockClear();
    });

    it('adds a filter alongside preserved site/period/tab state', () => {
        const currentFilters = ref({ path: '/pricing' });
        const period = ref('7d');
        const tab = ref('overview');

        const { addFilter } = useAnalyticsFilters({
            baseUrl: '/dashboard',
            siteId: ref(7),
            currentFilters,
            currentPeriod: period,
            currentTab: tab,
        });

        addFilter('country', 'US');

        expect(routerGet).toHaveBeenCalledWith(
            '/dashboard',
            {
                path: '/pricing',
                country: 'US',
                site_id: 7,
                period: '7d',
                tab: 'overview',
            },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('removes a filter', () => {
        const currentFilters = ref({ path: '/pricing', country: 'US' });

        const { removeFilter } = useAnalyticsFilters({
            baseUrl: '/dashboard',
            siteId: ref(7),
            currentFilters,
            currentPeriod: ref('7d'),
        });

        removeFilter('country');

        expect(routerGet).toHaveBeenCalledWith(
            '/dashboard',
            { path: '/pricing', site_id: 7, period: '7d' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('clears all filters but keeps site/period state', () => {
        const currentFilters = ref({ path: '/pricing', country: 'US' });

        const { clearFilters } = useAnalyticsFilters({
            baseUrl: '/dashboard',
            siteId: ref(7),
            currentFilters,
            currentPeriod: ref('30d'),
        });

        clearFilters();

        expect(routerGet).toHaveBeenCalledWith(
            '/dashboard',
            { site_id: 7, period: '30d' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('does nothing when the base URL is empty', () => {
        const { addFilter } = useAnalyticsFilters({ baseUrl: '' });

        addFilter('path', '/pricing');

        expect(routerGet).not.toHaveBeenCalled();
    });
});
