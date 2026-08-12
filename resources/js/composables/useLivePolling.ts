import { router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

export interface UseLivePollingOptions {
    only?: string[];
    interval?: number;
}

export function useLivePolling(options: UseLivePollingOptions = {}) {
    const isLive = ref(false);
    const isRefreshing = ref(false);
    let pollInterval: ReturnType<typeof setInterval> | null = null;

    // Only refresh the props that genuinely change in real time.
    // Breakdown cards (top_pages, browsers, countries…) are stable over
    // a 30-second window and are expensive to recompute — skip them.
    const LIVE_PROPS = options.only ?? [
        'total_pageviews',
        'unique_visitors',
        'current_visitors',
        'bounce_rate',
        'avg_duration',
        'daily_pageviews',
    ];

    const refreshData = () => {
        isRefreshing.value = true;
        router.reload({
            only: LIVE_PROPS,
            onFinish: () => {
                isRefreshing.value = false;
            },
        });
    };

    const stopPolling = () => {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    };

    const startPolling = () => {
        stopPolling();
        pollInterval = setInterval(() => {
            if (document.visibilityState === 'visible' && !isRefreshing.value) {
                refreshData();
            }
        }, options.interval || 30000);
    };

    const toggleLive = () => {
        isLive.value = !isLive.value;

        if (isLive.value) {
            startPolling();
        } else {
            stopPolling();
        }
    };

    const handleVisibilityChange = () => {
        if (
            document.visibilityState === 'visible' &&
            isLive.value &&
            !isRefreshing.value
        ) {
            refreshData();
        }
    };

    onMounted(() => {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    });

    onUnmounted(() => {
        stopPolling();
        document.removeEventListener(
            'visibilitychange',
            handleVisibilityChange,
        );
    });

    return {
        isLive,
        isRefreshing,
        toggleLive,
        refreshData,
    };
}
