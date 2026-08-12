import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsFiltersBar from './AnalyticsFiltersBar.vue';

function mountFiltersBar(props = {}) {
    return mount(AnalyticsFiltersBar, { props });
}

describe('AnalyticsFiltersBar', () => {
    it('renders nothing when there are no filters', () => {
        const wrapper = mountFiltersBar({ filters: {} });

        expect(wrapper.text()).toBe('');
    });

    it('renders nothing when filters are undefined', () => {
        const wrapper = mountFiltersBar({ filters: undefined });

        expect(wrapper.text()).toBe('');
    });

    it('renders a chip for every active filter', () => {
        const wrapper = mountFiltersBar({
            filters: { device: 'mobile', country: 'US' },
        });

        expect(wrapper.text()).toContain('Active Filters:');
        expect(wrapper.text()).toContain('device:');
        expect(wrapper.text()).toContain('mobile');
        expect(wrapper.text()).toContain('country:');
        expect(wrapper.text()).toContain('US');
    });

    it('emits removeFilter with the key when a chip is dismissed', async () => {
        const wrapper = mountFiltersBar({
            filters: { device: 'mobile', country: 'US' },
        });

        const removeButtons = wrapper.findAll('button[title="Remove filter"]');
        await removeButtons[0].trigger('click');

        expect(wrapper.emitted('removeFilter')?.[0]).toEqual(['device']);
    });

    it('emits clearFilters when Clear all is clicked', async () => {
        const wrapper = mountFiltersBar({
            filters: { device: 'mobile' },
        });

        const clearButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Clear all'));

        await clearButton!.trigger('click');

        expect(wrapper.emitted('clearFilters')).toHaveLength(1);
    });
});
