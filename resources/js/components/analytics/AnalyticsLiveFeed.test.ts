import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsLiveFeed from './AnalyticsLiveFeed.vue';

describe('AnalyticsLiveFeed', () => {
    it('renders currently online visitor count', () => {
        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                currentVisitors: 12,
                topPages: [
                    {
                        idKey: '/pricing',
                        label: '/pricing',
                        path: '/pricing',
                        count: 45,
                        percentage: 60,
                    },
                ],
                topReferrers: [
                    {
                        idKey: 'Google',
                        label: 'Google',
                        count: 30,
                        percentage: 50,
                    },
                ],
                topCountries: [
                    {
                        idKey: 'US',
                        label: 'United States',
                        code: 'US',
                        count: 40,
                        percentage: 50,
                    },
                ],
            },
        });

        expect(wrapper.text()).toContain('Live Activity');
        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('/pricing');
        expect(wrapper.text()).toContain('Google');
        expect(wrapper.text()).toContain('United States');
    });

    it('emits filter event when path is clicked', async () => {
        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                currentVisitors: 5,
                topPages: [
                    {
                        idKey: '/docs',
                        label: '/docs',
                        path: '/docs',
                        count: 10,
                        percentage: 100,
                    },
                ],
            },
        });

        const pathBtn = wrapper.find('button');
        expect(pathBtn.exists()).toBe(true);

        await pathBtn.trigger('click');
        expect(wrapper.emitted('filter')).toBeTruthy();
        expect(wrapper.emitted('filter')?.[0]).toEqual(['path', '/docs']);
    });

    it('shows empty state when there are no active paths', () => {
        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                currentVisitors: 0,
                topPages: [],
            },
        });

        expect(wrapper.text()).toContain('No active sessions right now');
    });
});
