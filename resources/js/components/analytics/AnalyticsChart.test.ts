import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsChart from './AnalyticsChart.vue';

const days = [
    { date: '2026-08-01', pageviews: 100, visitors: 40 },
    { date: '2026-08-02', pageviews: 250, visitors: 120 },
    { date: '2026-08-03', pageviews: 80, visitors: 30 },
];

function mountChart(props = {}) {
    return mount(AnalyticsChart, {
        props: {
            dailyPageviews: days,
            showViews: true,
            showVisitors: false,
            hoveredDay: null,
            maxDaily: 250,
            ...props,
        },
    });
}

describe('AnalyticsChart', () => {
    it('renders nothing when there is no data', () => {
        const wrapper = mountChart({ dailyPageviews: [] });

        expect(wrapper.text()).toBe('');
    });

    it('renders a bar for every day', () => {
        const wrapper = mountChart();

        expect(wrapper.text()).toContain('Traffic Overview');
        expect(wrapper.findAll('[class*="items-end gap-1"] > *')).toHaveLength(
            3,
        );
    });

    it('emits toggleViews and toggleVisitors from the legend buttons', async () => {
        const wrapper = mountChart();

        const buttons = wrapper.findAll('button');
        const viewsButton = buttons.find((b) => b.text().includes('Pageviews'));
        const visitorsButton = buttons.find((b) =>
            b.text().includes('Unique Visitors'),
        );

        await viewsButton!.trigger('click');
        await visitorsButton!.trigger('click');

        expect(wrapper.emitted('toggleViews')).toHaveLength(1);
        expect(wrapper.emitted('toggleVisitors')).toHaveLength(1);
    });

    it('emits the hovered day on mouseenter and clears on mouseleave', async () => {
        const wrapper = mountChart();

        const bars = wrapper.findAll('[class*="items-end gap-1"] > *');
        await bars[1].trigger('mouseenter');

        expect(wrapper.emitted('update:hoveredDay')?.[0]).toEqual([days[1]]);

        await bars[1].trigger('mouseleave');

        expect(wrapper.emitted('update:hoveredDay')?.[1]).toEqual([null]);
    });

    it('shows the tooltip for the hovered day with formatted values', () => {
        const wrapper = mountChart({ hoveredDay: days[1] });

        expect(wrapper.text()).toContain('Aug 2');
        expect(wrapper.text()).toContain('250 views');
    });

    it('shows visitors in the tooltip when the visitors series is enabled', () => {
        const wrapper = mountChart({
            hoveredDay: days[1],
            showVisitors: true,
        });

        expect(wrapper.text()).toContain('250 views');
        expect(wrapper.text()).toContain('120 visitors');
    });

    it('renders first, middle and last date labels on the x-axis', () => {
        const wrapper = mountChart();

        expect(wrapper.text()).toContain('Aug 1');
        expect(wrapper.text()).toContain('Aug 2');
        expect(wrapper.text()).toContain('Aug 3');
    });

    it('clamps bar heights to a minimum even when maxDaily is zero', () => {
        const wrapper = mountChart({ maxDaily: 0 });

        expect(wrapper.text()).toContain('Traffic Overview');
    });

    it('emits selectDay event with the date when a bar is clicked', async () => {
        const wrapper = mountChart();

        const bars = wrapper.findAll('[class*="items-end gap-1"] > *');
        await bars[1].trigger('click');

        expect(wrapper.emitted('selectDay')?.[0]).toEqual(['2026-08-02']);
    });
});
