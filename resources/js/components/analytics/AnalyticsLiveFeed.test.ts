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
                liveVisitors: [
                    {
                        session_id: 'sess_docs',
                        path: '/docs',
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

    it('shows empty state when there are no active sessions', () => {
        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                currentVisitors: 0,
                liveVisitors: [],
            },
        });

        expect(wrapper.text()).toContain('No active sessions right now');
    });

    it('renders animated skeleton placeholders when loading is true', () => {
        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                loading: true,
                currentVisitors: 0,
            },
        });

        expect(wrapper.findAll('.animate-pulse').length).toBeGreaterThan(0);
        expect(wrapper.text()).not.toContain('No active sessions right now');
    });

    it('dynamically updates relative time as ticker elapses', async () => {
        const { vi } = await import('vitest');
        vi.useFakeTimers();

        const now = Date.now();
        vi.setSystemTime(now);

        const wrapper = mount(AnalyticsLiveFeed, {
            props: {
                currentVisitors: 1,
                liveVisitors: [
                    {
                        session_id: 'sess_live',
                        path: '/features',
                        created_at: new Date(now - 5000).toISOString(),
                    },
                ],
            },
        });

        // Initially 5 seconds ago -> "just now" (< 10s)
        expect(wrapper.text()).toContain('just now');

        // Advance by 10s (total elapsed: 15s)
        vi.advanceTimersByTime(10000);
        await wrapper.vm.$nextTick();

        // Should now reflect updated relative time
        expect(wrapper.text()).toContain('15s ago');

        // Advance to 65s
        vi.advanceTimersByTime(50000);
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('1m ago');

        wrapper.unmount();
        vi.useRealTimers();
    });
});
