import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsKpiCards from './AnalyticsKpiCards.vue';

function mountKpiCards(props = {}) {
    return mount(AnalyticsKpiCards, { props });
}

describe('AnalyticsKpiCards', () => {
    it('renders formatted pageviews and unique visitors', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 1234567,
            uniqueVisitors: 12345,
        });

        expect(wrapper.text()).toContain('1,234,567');
        expect(wrapper.text()).toContain('12,345');
        expect(wrapper.text()).toContain('Total Pageviews');
        expect(wrapper.text()).toContain('Unique Visitors');
    });

    it('hides the currently online stat when no value is provided', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 10,
            uniqueVisitors: 5,
        });

        expect(wrapper.text()).not.toContain('Currently Online');
    });

    it('shows the currently online stat when a value is provided', () => {
        const wrapper = mountKpiCards({
            currentVisitors: 42,
            totalPageviews: 10,
            uniqueVisitors: 5,
        });

        expect(wrapper.text()).toContain('Currently Online');
        expect(wrapper.text()).toContain('42');
    });

    it('hides the bounce rate and duration stats when both values are absent', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 10,
            uniqueVisitors: 5,
        });

        expect(wrapper.text()).not.toContain('Bounce Rate');
        expect(wrapper.text()).not.toContain('Avg Duration');
    });

    it('renders bounce rate and average duration when provided', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 10,
            uniqueVisitors: 5,
            bounceRate: 33.3,
            avgDuration: 90,
        });

        expect(wrapper.text()).toContain('Bounce Rate');
        expect(wrapper.text()).toContain('Avg Duration');
        expect(wrapper.text()).toContain('33.3%');
        expect(wrapper.text()).toContain('90s');
    });

    it('defaults missing pageviews and visitors to zero', () => {
        const wrapper = mountKpiCards();

        expect(wrapper.text()).toContain('0');
    });

    it('emits toggleViews and toggleVisitors when the respective cards are clicked', async () => {
        const wrapper = mountKpiCards({
            totalPageviews: 100,
            uniqueVisitors: 50,
        });

        const buttons = wrapper.findAll('button');
        const viewsBtn = buttons.find((b) =>
            b.text().includes('Total Pageviews'),
        );
        const visitorsBtn = buttons.find((b) =>
            b.text().includes('Unique Visitors'),
        );

        expect(viewsBtn).toBeTruthy();
        expect(visitorsBtn).toBeTruthy();

        await viewsBtn!.trigger('click');
        expect(wrapper.emitted('toggleViews')).toHaveLength(1);

        await visitorsBtn!.trigger('click');
        expect(wrapper.emitted('toggleVisitors')).toHaveLength(1);
    });

    it('shows percent-change badges when previous period values are provided', () => {
        const wrapper = mountKpiCards({
            uniqueVisitors: 1100,
            totalPageviews: 2200,
            bounceRate: 45,
            avgDuration: 120,
            prevUniqueVisitors: 1000,
            prevTotalPageviews: 2000,
            prevBounceRate: 50,
            prevAvgDuration: 100,
        });

        // +10% visitors
        expect(wrapper.text()).toContain('+10%');
        // +10% pageviews
        expect(wrapper.text()).toContain('+10%');
        // -10% bounce rate
        expect(wrapper.text()).toContain('-10%');
        // +20% avg duration
        expect(wrapper.text()).toContain('+20%');
    });

    it('hides percent-change badges when no previous period values are provided', () => {
        const wrapper = mountKpiCards({
            uniqueVisitors: 1000,
            totalPageviews: 2000,
            bounceRate: 50,
            avgDuration: 90,
        });

        expect(wrapper.text()).not.toMatch(/[+-]\d+%/);
    });

    it('hides percent-change badge when previous value is zero', () => {
        const wrapper = mountKpiCards({
            uniqueVisitors: 500,
            totalPageviews: 1000,
            prevUniqueVisitors: 0,
            prevTotalPageviews: 0,
        });

        expect(wrapper.text()).not.toMatch(/[+-]\d+%/);
    });
});
