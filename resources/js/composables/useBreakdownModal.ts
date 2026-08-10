import { ref, computed, type Ref, unref } from 'vue';

export interface UseBreakdownModalOptions {
    breakdownEndpoint: string | Ref<string>;
    currentPeriod?: Ref<string | undefined>;
    siteId?: number | Ref<number | undefined>;
    overview?: Ref<any>;
}

export function useBreakdownModal(options: UseBreakdownModalOptions) {
    const activeModal = ref<string | null>(null);
    const modalTitle = ref<string>('');
    const modalData = ref<any[] | null>(null);
    const isLoadingModal = ref(false);

    const openModal = async (type: string, title: string) => {
        activeModal.value = type;
        modalTitle.value = title;
        modalData.value = null;
        isLoadingModal.value = true;

        try {
            const endpoint = unref(options.breakdownEndpoint);
            const period = unref(options.currentPeriod) || '7d';
            const siteId = unref(options.siteId);

            let url = `${endpoint}?period=${period}&type=${type}&limit=50`;
            if (siteId) {
                url += `&site_id=${siteId}`;
            }

            const res = await fetch(url);
            if (res.ok) {
                const json = await res.json();
                modalData.value = json.data;
            }
        } catch (err) {
            console.error('Failed to load breakdown modal data', err);
        } finally {
            isLoadingModal.value = false;
        }
    };

    const closeModal = () => {
        activeModal.value = null;
        modalData.value = null;
    };

    const modalTotalCount = computed(() => {
        const ov = unref(options.overview);
        const list = modalData.value || (activeModal.value && ov ? ov[
            activeModal.value === 'pages' ? 'top_pages' :
            activeModal.value === 'referrers' ? 'top_referrers' :
            activeModal.value === 'browsers' ? 'top_browsers' :
            activeModal.value === 'os' ? 'top_os' :
            activeModal.value === 'locations' ? 'top_countries' :
            activeModal.value === 'devices' ? 'device_breakdown' : 'utm_campaigns'
        ] : null);

        if (!list || !Array.isArray(list)) return null;
        const total = list.reduce((sum, item) => sum + (item.count || 0), 0);
        return {
            itemCount: list.length,
            totalSum: total,
        };
    });

    return {
        activeModal,
        modalTitle,
        modalData,
        isLoadingModal,
        openModal,
        closeModal,
        modalTotalCount,
    };
}
