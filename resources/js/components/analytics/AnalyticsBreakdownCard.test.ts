import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsBreakdownCard from './AnalyticsBreakdownCard.vue';

const items = [
    { idKey: '1', label: '/home', count: 100, percentage: 50 },
    { idKey: '2', label: '/pricing', count: 50, percentage: 25 },
];

function mountCard(props = {}) {
    return mount(AnalyticsBreakdownCard, {
        props: {
            title: 'Top Pages',
            items,
            filterKey: 'path',
            typeKey: 'pages',
            ...props,
        },
    });
}

describe('AnalyticsBreakdownCard', () => {
    it('renders the title and each item with its count', () => {
        const wrapper = mountCard();

        expect(wrapper.text()).toContain('Top Pages');
        expect(wrapper.text()).toContain('/home');
        expect(wrapper.text()).toContain('100');
        expect(wrapper.text()).toContain('/pricing');
    });

    it('limits the list to ten items', () => {
        const many = Array.from({ length: 15 }, (_, i) => ({
            idKey: String(i),
            label: `/page-${i}`,
            count: i,
            percentage: i,
        }));

        const wrapper = mountCard({ items: many });

        expect(wrapper.text()).toContain('/page-0');
        expect(wrapper.text()).not.toContain('/page-14');
    });

    it('emits filter with the filterKey and label when an item is clicked', async () => {
        const wrapper = mountCard();

        const item = wrapper
            .findAll(
                '[class*="group relative flex items-center justify-between"]',
            )
            .find((el) => el.text().includes('/home'));

        await item!.trigger('click');

        expect(wrapper.emitted('filter')?.[0]).toEqual(['path', '/home']);
    });

    it('does not emit filter when canFilter is false', async () => {
        const wrapper = mountCard({ canFilter: false });

        const item = wrapper
            .findAll(
                '[class*="group relative flex items-center justify-between"]',
            )
            .find((el) => el.text().includes('/home'));

        await item!.trigger('click');

        expect(wrapper.emitted('filter')).toBeUndefined();
    });

    it('emits expand with the typeKey and title from the expand button', async () => {
        const wrapper = mountCard();

        const expandButton = wrapper.find('button[title="Expand Details"]');
        await expandButton.trigger('click');

        expect(wrapper.emitted('expand')?.[0]).toEqual([
            'pages',
            'Top Pages Breakdown',
        ]);
    });

    it('renders the empty state text when there are no items', () => {
        const wrapper = mountCard({ items: [] });

        expect(wrapper.text()).toContain('No data recorded yet.');
    });

    it('uses a custom empty text when provided', () => {
        const wrapper = mountCard({
            items: [],
            emptyText: 'Nothing here yet',
        });

        expect(wrapper.text()).toContain('Nothing here yet');
    });

    it('renders the country flag and code for location items', () => {
        const wrapper = mountCard({
            typeKey: 'locations',
            items: [
                {
                    idKey: 'us',
                    label: 'United States',
                    count: 10,
                    percentage: 100,
                    code: 'us',
                },
            ],
        });

        expect(wrapper.text()).toContain('🇺🇸');
        expect(wrapper.text()).toContain('us');
        expect(wrapper.text()).toContain('United States');
    });

    it('shows the entry count when totalItems is provided', () => {
        const wrapper = mountCard({ totalItems: 2 });

        expect(wrapper.text()).toContain('2 entries');
    });
});
