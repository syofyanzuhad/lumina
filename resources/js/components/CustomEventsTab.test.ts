import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { routerGet } = vi.hoisted(() => ({
    routerGet: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { get: routerGet },
}));

import CustomEventsTab from './CustomEventsTab.vue';

const summary = {
    total_custom_events: 12,
    unique_event_names: 2,
    top_event_name: 'purchase',
};

const eventsList = [
    { name: 'purchase', count: 8, percentage: 66.7, last_seen: '2026-08-01' },
    { name: 'signup', count: 4, percentage: 33.3, last_seen: '2026-08-02' },
];

const timeline = [
    { date: '2026-08-01', count: 5 },
    { date: '2026-08-02', count: 7 },
];

const logs = [
    {
        id: 1,
        created_at: '2026-08-02 10:00:00',
        path: '/checkout',
        visitor_hash: 'abc12345',
        device_type: 'desktop',
        browser: 'Chrome',
        os: 'macOS',
        country_name: 'United States',
        country_code: 'us',
        event_name: 'purchase',
        props: { plan: 'pro', amount: 29 },
    },
];

function mountTab(props = {}) {
    return mount(CustomEventsTab, {
        props: {
            siteId: 7,
            period: '30d',
            summary,
            eventsList,
            timeline,
            logs,
            ...props,
        },
    });
}

describe('CustomEventsTab', () => {
    beforeEach(() => {
        routerGet.mockClear();
    });

    it('shows the empty state when there is no summary', () => {
        const wrapper = mountTab({ summary: undefined });

        expect(wrapper.text()).toContain('No custom events tracked yet');
        expect(wrapper.text()).toContain("window.lumina('purchase'");
    });

    it('renders the KPI summary cards', () => {
        const wrapper = mountTab();

        expect(wrapper.text()).toContain('Total Custom Events');
        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('Unique Event Types');
        expect(wrapper.text()).toContain('2');
        expect(wrapper.text()).toContain('Most Frequent Event');
        expect(wrapper.text()).toContain('purchase');
    });

    it('navigates with the chosen event when the filter changes', async () => {
        const wrapper = mountTab();

        const select = wrapper.find('#event-filter');
        await select.setValue('signup');

        expect(routerGet).toHaveBeenCalledTimes(1);
        expect(routerGet.mock.calls[0][0]).toBe('/dashboard');
        expect(routerGet.mock.calls[0][1]).toEqual({
            tab: 'events',
            site_id: 7,
            period: '30d',
            event: 'signup',
        });
        expect(routerGet.mock.calls[0][2]).toEqual({
            preserveState: true,
            preserveScroll: true,
        });
    });

    it('omits the event param when resetting the filter to all', async () => {
        const wrapper = mountTab({ selectedEvent: 'purchase' });

        const select = wrapper.find('#event-filter');
        await select.setValue('all');

        expect(routerGet.mock.calls[0][1]).toEqual({
            tab: 'events',
            site_id: 7,
            period: '30d',
            event: undefined,
        });
    });

    it('navigates when an event in the top list is clicked', async () => {
        const wrapper = mountTab();

        const eventCard = wrapper
            .findAll('[class*="cursor-pointer space-y-1.5"]')
            .find((el) => el.text().includes('purchase'));

        await eventCard!.trigger('click');

        expect(routerGet).toHaveBeenCalledTimes(1);
        expect(routerGet.mock.calls[0][1].event).toBe('purchase');
    });

    it('navigates with the property key when a key tab is clicked', async () => {
        const wrapper = mountTab({
            selectedEvent: 'purchase',
            propertyKeys: ['plan', 'amount'],
            propertyBreakdown: [{ value: 'pro', count: 5, percentage: 62.5 }],
        });

        const keyButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'plan');

        await keyButton!.trigger('click');

        expect(routerGet).toHaveBeenCalledTimes(1);
        expect(routerGet.mock.calls[0][1]).toMatchObject({
            tab: 'events',
            site_id: 7,
            event: 'purchase',
            property: 'plan',
        });
    });

    it('expands and collapses the raw payload of a log row', async () => {
        const wrapper = mountTab();

        expect(wrapper.text()).not.toContain('"plan"');

        const expandButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('View Raw Payload'));

        await expandButton!.trigger('click');

        expect(wrapper.text()).toContain('"plan"');
        expect(wrapper.text()).toContain('"pro"');

        await expandButton!.trigger('click');

        expect(wrapper.text()).not.toContain('"plan"');
    });

    it('highlights the selected event in the list', () => {
        const wrapper = mountTab({ selectedEvent: 'purchase' });

        expect(wrapper.html()).toContain('border-indigo-500');
    });

    it('truncates the visitor hash in the logs table', () => {
        const wrapper = mountTab();

        expect(wrapper.text()).toContain('abc12345');
        expect(wrapper.text()).not.toContain('abcdef');
    });
});
