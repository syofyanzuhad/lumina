import { afterEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useBreakdownModal } from './useBreakdownModal';

describe('useBreakdownModal', () => {
    afterEach(() => {
        // Always restore the global fetch stub and console spies so a failed
        // test can never leak state into the next one.
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('is closed with no data initially', () => {
        const { activeModal, modalData, isLoadingModal } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
        });

        expect(activeModal.value).toBeNull();
        expect(modalData.value).toBeNull();
        expect(isLoadingModal.value).toBe(false);
    });

    it('builds the fetch URL from endpoint, period, type and site id', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ data: [{ label: 'A' }] }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const { openModal } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
            currentPeriod: ref('30d'),
            siteId: ref(7),
        });

        await openModal('pages', 'Top Pages');

        expect(fetchMock).toHaveBeenCalledWith(
            '/api/breakdown?period=30d&type=pages&limit=50&site_id=7',
        );
    });

    it('defaults the period to 7d and omits the site id when absent', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ data: [] }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const { openModal } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
        });

        await openModal('referrers', 'Top Referrers');

        expect(fetchMock).toHaveBeenCalledWith(
            '/api/breakdown?period=7d&type=referrers&limit=50',
        );
    });

    it('stores the fetched rows and clears the loading state', async () => {
        const rows = [{ label: '/home', count: 5 }];
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ data: rows }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const {
            activeModal,
            modalTitle,
            modalData,
            isLoadingModal,
            openModal,
        } = useBreakdownModal({ breakdownEndpoint: '/api/breakdown' });

        await openModal('pages', 'Top Pages');

        expect(activeModal.value).toBe('pages');
        expect(modalTitle.value).toBe('Top Pages');
        // ref() wraps objects in a reactive proxy, so compare by value.
        expect(modalData.value).toEqual(rows);
        expect(isLoadingModal.value).toBe(false);
    });

    it('resets data to null while a new load is in flight', async () => {
        let resolveFetch!: (v: unknown) => void;
        const fetchMock = vi.fn().mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveFetch = resolve;
                }),
        );
        vi.stubGlobal('fetch', fetchMock);

        const { modalData, isLoadingModal, openModal } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
        });

        modalData.value = [{ label: 'stale' }];

        const promise = openModal('pages', 'Top Pages');

        expect(modalData.value).toBeNull();
        expect(isLoadingModal.value).toBe(true);

        resolveFetch({ ok: true, json: async () => ({ data: [] }) });
        await promise;
    });

    it('logs and degrades gracefully when the request fails', async () => {
        const consoleError = vi
            .spyOn(console, 'error')
            .mockImplementation(() => {});

        const fetchMock = vi.fn().mockRejectedValue(new Error('network down'));
        vi.stubGlobal('fetch', fetchMock);

        const { activeModal, modalData, isLoadingModal, openModal } =
            useBreakdownModal({ breakdownEndpoint: '/api/breakdown' });

        await openModal('pages', 'Top Pages');

        expect(consoleError).toHaveBeenCalled();
        expect(modalData.value).toBeNull();
        expect(isLoadingModal.value).toBe(false);
        expect(activeModal.value).toBe('pages');
    });

    it('leaves data empty when the response is not ok', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: false,
            json: async () => ({ data: [] }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const { modalData, isLoadingModal, openModal } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
        });

        await openModal('pages', 'Top Pages');

        expect(modalData.value).toBeNull();
        expect(isLoadingModal.value).toBe(false);
    });

    it('closeModal resets the modal state', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ data: [{ label: 'A' }] }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const { activeModal, modalData, openModal, closeModal } =
            useBreakdownModal({ breakdownEndpoint: '/api/breakdown' });

        await openModal('pages', 'Top Pages');
        expect(modalData.value).not.toBeNull();

        closeModal();

        expect(activeModal.value).toBeNull();
        expect(modalData.value).toBeNull();
    });

    it('computes totals from the overview payload when no modal data is loaded', () => {
        const overview = ref({
            top_pages: [{ count: 3 }, { count: 4 }],
            top_countries: [{ count: 10 }],
        });

        const pages = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
            overview,
        });
        pages.activeModal.value = 'pages';

        expect(pages.modalTotalCount.value).toEqual({
            itemCount: 2,
            totalSum: 7,
        });

        const locations = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
            overview,
        });
        locations.activeModal.value = 'locations';

        expect(locations.modalTotalCount.value).toEqual({
            itemCount: 1,
            totalSum: 10,
        });
    });

    it('computes totals from the loaded modal data when present', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ data: [{ count: 2 }, { count: 8 }] }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const { modalTotalCount, openModal } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
            overview: ref({}),
        });

        await openModal('pages', 'Top Pages');

        expect(modalTotalCount.value).toEqual({
            itemCount: 2,
            totalSum: 10,
        });
    });

    it('returns null totals when there is no data or matching overview key', () => {
        const { modalTotalCount } = useBreakdownModal({
            breakdownEndpoint: '/api/breakdown',
            overview: ref({}),
        });

        expect(modalTotalCount.value).toBeNull();
    });
});
