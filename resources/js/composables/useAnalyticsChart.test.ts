import { describe, expect, it } from 'vitest';
import { ref } from 'vue';
import { useAnalyticsChart } from './useAnalyticsChart';

const sampleData = [
    { date: '2026-08-01', pageviews: 100, visitors: 40 },
    { date: '2026-08-02', pageviews: 250, visitors: 120 },
    { date: '2026-08-03', pageviews: 80, visitors: 30 },
];

describe('useAnalyticsChart', () => {
    it('starts with views visible and visitors hidden', () => {
        const { showViews, showVisitors } = useAnalyticsChart(ref(sampleData));

        expect(showViews.value).toBe(true);
        expect(showVisitors.value).toBe(false);
    });

    it('toggles the visibility of each series', () => {
        const { showViews, showVisitors, toggleViews, toggleVisitors } =
            useAnalyticsChart(ref(sampleData));

        toggleViews();
        expect(showViews.value).toBe(false);

        toggleVisitors();
        expect(showVisitors.value).toBe(true);
    });

    it('computes the per-series maximums from the data', () => {
        const { viewsMax, visitorsMax } = useAnalyticsChart(ref(sampleData));

        expect(viewsMax.value).toBe(250);
        expect(visitorsMax.value).toBe(120);
    });

    it('maxDaily follows the largest visible series', () => {
        const { maxDaily, toggleVisitors } = useAnalyticsChart(ref(sampleData));

        // Only views visible -> 250.
        expect(maxDaily.value).toBe(250);

        toggleVisitors();
        // Both visible -> max(250, 120) = 250.
        expect(maxDaily.value).toBe(250);
    });

    it('maxDaily tracks the visitors series once it exceeds views', () => {
        const data = ref([{ date: '2026-08-01', pageviews: 10, visitors: 60 }]);

        const { maxDaily, toggleVisitors } = useAnalyticsChart(data);

        toggleVisitors();

        expect(maxDaily.value).toBe(60);
    });

    it('guards against empty and all-zero data with a fallback of 1', () => {
        const empty = useAnalyticsChart(ref(undefined));
        const zeros = useAnalyticsChart(
            ref([{ date: '2026-08-01', pageviews: 0, visitors: 0 }]),
        );

        expect(empty.maxDaily.value).toBe(1);
        expect(empty.viewsMax.value).toBe(1);

        expect(zeros.maxDaily.value).toBe(1);
        expect(zeros.viewsMax.value).toBe(1);
    });

    it('returns a fallback of 1 when every series is hidden', () => {
        const { maxDaily, toggleViews, toggleVisitors } = useAnalyticsChart(
            ref(sampleData),
        );

        toggleViews(); // views off
        toggleVisitors(); // visitors on
        toggleVisitors(); // visitors off

        expect(maxDaily.value).toBe(1);
    });

    it('tracks the hovered day', () => {
        const { hoveredDay } = useAnalyticsChart(ref(sampleData));

        expect(hoveredDay.value).toBeNull();

        hoveredDay.value = sampleData[1];

        // ref() wraps objects in a reactive proxy, so compare by value.
        expect(hoveredDay.value).toEqual(sampleData[1]);
    });

    it('recomputes maximums reactively when data changes', () => {
        const data = ref(sampleData);
        const { viewsMax } = useAnalyticsChart(data);

        expect(viewsMax.value).toBe(250);

        data.value = [{ date: '2026-08-04', pageviews: 500, visitors: 200 }];

        expect(viewsMax.value).toBe(500);
    });
});
