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

    const refreshData = () => {
        isRefreshing.value = true;
        router.reload({
            only: options.only || ['overview'],
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
        if (document.visibilityState === 'visible' && isLive.value && !isRefreshing.value) {
            refreshData();
        }
    };

    onMounted(() => {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    });

    onUnmounted(() => {
        stopPolling();
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    });

    return {
        isLive,
        isRefreshing,
        toggleLive,
        refreshData,
    };
}
