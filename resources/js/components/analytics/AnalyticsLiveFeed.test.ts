import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AnalyticsLiveFeed from './AnalyticsLiveFeed.vue';

describe('AnalyticsLiveFeed', () => {
    it('renders currently online visitor count and session details', () => {
        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                currentVisitors: 12,
                liveVisitors: [
                    {
                        session_id: 'sess_1',
                        path: '/pricing',
                        referrer: 'https://news.ycombinator.com',
                        country_code: 'US',
                        country_name: 'United States',
                        browser: 'Chrome',
                        device: 'desktop',
                    },
                ],
            },
        });

        expect(wrapper.text()).toContain('12 users online');
        expect(wrapper.text()).toContain('/pricing');
        expect(wrapper.text()).toContain('news.ycombinator.com');
        expect(wrapper.text()).toContain('United States');
        expect(wrapper.text()).toContain('Chrome');
        expect(wrapper.text()).toContain('desktop');
        expect(wrapper.text()).toContain('just now');
        expect(wrapper.find('img[src*="dicebear"]').exists()).toBe(true);
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
