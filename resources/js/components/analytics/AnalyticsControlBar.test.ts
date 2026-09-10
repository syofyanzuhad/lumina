import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsControlBar from './AnalyticsControlBar.vue';

function mountControlBar(props = {}) {
    return mount(AnalyticsControlBar, {
        props: {
            period: '30d',
            ...props,
        },
        global: {
            stubs: {
                AppearanceTabs: true,
                DropdownMenu: { template: '<div><slot /></div>' },
                DropdownMenuGroup: { template: '<div><slot /></div>' },
                DropdownMenuTrigger: { template: '<div><slot /></div>' },
                DropdownMenuContent: { template: '<div><slot /></div>' },
                DropdownMenuLabel: { template: '<div><slot /></div>' },
                DropdownMenuSeparator: true,
                DropdownMenuItem: { template: '<div><slot /></div>' },
                Link: {
                    props: ['href'],
                    template: '<a :href="href"><slot /></a>',
                },
                Button: { template: '<button><slot /></button>' },
                Input: true,
                Label: true,
            },
        },
    });
}

describe('AnalyticsControlBar', () => {
    it('emits setPeriod when a date segment is clicked', async () => {
        const wrapper = mountControlBar({ period: '30d' });

        const buttons = wrapper.findAll('button');

        const todayButton = buttons.find((b) => b.text().includes('Today'));
        expect(todayButton).toBeTruthy();

        await todayButton!.trigger('click');
        expect(wrapper.emitted('setPeriod')?.[0]).toEqual(['today']);

        const weekButton = buttons.find((b) => b.text().trim() === '7d');
        await weekButton!.trigger('click');
        expect(wrapper.emitted('setPeriod')?.[1]).toEqual(['7d']);
    });

    it('emits setTab when a tab is clicked', async () => {
        const wrapper = mountControlBar({ activeTab: 'overview' });

        const eventsButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Custom Events'));
        expect(eventsButton).toBeTruthy();

        await eventsButton!.trigger('click');
        expect(wrapper.emitted('setTab')?.[0]).toEqual(['events']);
    });

    it('emits refresh when the refresh button is clicked', async () => {
        const wrapper = mountControlBar();

        const refreshButton = wrapper
            .findAll('button')
            .find((b) => b.attributes('title') === 'Refresh Analytics');
        expect(refreshButton).toBeTruthy();

        await refreshButton!.trigger('click');
        expect(wrapper.emitted('refresh')).toHaveLength(1);
    });

    it('emits toggleLive when live mode is enabled', async () => {
        const wrapper = mountControlBar({ showLive: true, isLive: false });

        const liveButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Live Off'));
        expect(liveButton).toBeTruthy();

        await liveButton!.trigger('click');
        expect(wrapper.emitted('toggleLive')).toHaveLength(1);
    });

    it('hides the custom events tab when showEventsTab is false', () => {
        const wrapper = mountControlBar({ showEventsTab: false });

        expect(wrapper.text()).not.toContain('Custom Events');
    });

    it('renders settings and export links in the dropdown menu', () => {
        const wrapper = mountControlBar({ siteId: 42, showExport: true });

        const settingsButton = wrapper
            .findAll('button')
            .find((b) => b.attributes('title') === 'Settings & Export');
        expect(settingsButton).toBeTruthy();
        expect(settingsButton!.text()).toContain('Settings');

        const html = wrapper.html();
        expect(html).toContain('/sites/42');
        expect(html).toContain(
            '/sites/42/export?type=pageviews&amp;format=csv',
        );
        expect(html).toContain('/sites/42/export?type=events&amp;format=json');
    });

    it('omits export items from the dropdown menu when showExport is false', () => {
        const wrapper = mountControlBar({ siteId: 42, showExport: false });

        const html = wrapper.html();
        expect(html).toContain('/sites/42');
        expect(html).not.toContain('/sites/42/export');
    });
});
