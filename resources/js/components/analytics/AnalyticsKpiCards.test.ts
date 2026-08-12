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

    it('hides the currently online card when no value is provided', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 10,
            uniqueVisitors: 5,
        });

        expect(wrapper.text()).not.toContain('Currently Online');
    });

    it('shows the currently online card when a value is provided', () => {
        const wrapper = mountKpiCards({
            currentVisitors: 42,
            totalPageviews: 10,
            uniqueVisitors: 5,
        });

        expect(wrapper.text()).toContain('Currently Online');
        expect(wrapper.text()).toContain('42');
        expect(wrapper.text()).toContain('Active in last 5 min');
    });

    it('hides the bounce & duration card when both values are absent', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 10,
            uniqueVisitors: 5,
        });

        expect(wrapper.text()).not.toContain('Bounce & Duration');
    });

    it('renders bounce rate and average duration when provided', () => {
        const wrapper = mountKpiCards({
            totalPageviews: 10,
            uniqueVisitors: 5,
            bounceRate: 33.3,
            avgDuration: 90,
        });

        expect(wrapper.text()).toContain('Bounce & Duration');
        expect(wrapper.text()).toContain('33.3%');
        expect(wrapper.text()).toContain('90s');
    });

    it('defaults missing pageviews and visitors to zero', () => {
        const wrapper = mountKpiCards();

        expect(wrapper.text()).toContain('0');
    });
});
