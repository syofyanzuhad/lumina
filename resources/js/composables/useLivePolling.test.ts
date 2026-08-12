import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';

const { reloadMock } = vi.hoisted(() => ({
    reloadMock: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { reload: reloadMock },
}));

import { useLivePolling } from './useLivePolling';

function mountPolling(options: Parameters<typeof useLivePolling>[0] = {}) {
    let api!: ReturnType<typeof useLivePolling>;

    const wrapper = mount(
        defineComponent({
            setup() {
                api = useLivePolling(options);

                return () => h('div');
            },
        }),
    );

    return { wrapper, api };
}

describe('useLivePolling', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        reloadMock.mockClear();
        reloadMock.mockImplementation((options) => options?.onFinish?.());
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('is off by default', () => {
        const { wrapper, api } = mountPolling();

        expect(api.isLive.value).toBe(false);

        wrapper.unmount();
    });

    it('reloads only the live props when polling fires', () => {
        const { wrapper, api } = mountPolling({ interval: 1000 });

        api.toggleLive();

        expect(api.isLive.value).toBe(true);

        vi.advanceTimersByTime(1000);

        expect(reloadMock).toHaveBeenCalledTimes(1);
        expect(reloadMock.mock.calls[0][0]).toMatchObject({
            only: [
                'total_pageviews',
                'unique_visitors',
                'current_visitors',
                'bounce_rate',
                'avg_duration',
                'daily_pageviews',
            ],
        });
        expect(typeof reloadMock.mock.calls[0][0].onFinish).toBe('function');

        wrapper.unmount();
    });

    it('uses the custom only list when provided', () => {
        const { wrapper, api } = mountPolling({
            only: ['total_pageviews'],
            interval: 1000,
        });

        api.toggleLive();
        vi.advanceTimersByTime(1000);

        expect(reloadMock.mock.calls[0][0]).toMatchObject({
            only: ['total_pageviews'],
        });

        wrapper.unmount();
    });

    it('stops polling when toggled off', () => {
        const { wrapper, api } = mountPolling({ interval: 1000 });

        api.toggleLive();
        api.toggleLive();

        expect(api.isLive.value).toBe(false);

        vi.advanceTimersByTime(5000);

        expect(reloadMock).not.toHaveBeenCalled();

        wrapper.unmount();
    });

    it('skips the reload while the tab is hidden', () => {
        const { wrapper, api } = mountPolling({ interval: 1000 });

        // jsdom exposes visibilityState as a prototype getter, so define an
        // own property that we can flip and always remove afterwards.
        try {
            Object.defineProperty(document, 'visibilityState', {
                value: 'hidden',
                configurable: true,
            });

            api.toggleLive();
            vi.advanceTimersByTime(3000);

            expect(reloadMock).not.toHaveBeenCalled();

            Object.defineProperty(document, 'visibilityState', {
                value: 'visible',
                configurable: true,
            });

            vi.advanceTimersByTime(1000);

            expect(reloadMock).toHaveBeenCalledTimes(1);
        } finally {
            delete (document as { visibilityState?: string }).visibilityState;
            wrapper.unmount();
        }
    });

    it('refreshData reports the refreshing state through onFinish', () => {
        const { wrapper, api } = mountPolling();

        expect(api.isRefreshing.value).toBe(false);

        api.refreshData();

        expect(reloadMock).toHaveBeenCalledTimes(1);
        expect(api.isRefreshing.value).toBe(false);

        wrapper.unmount();
    });
});
