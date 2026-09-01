import { beforeEach, describe, expect, it, vi } from 'vitest';

declare global {
    interface Window {
        lumina?: ((eventName?: string, props?: unknown) => void) & {
            q?: unknown[];
            _outboundHandler?: (event: MouseEvent) => void;
        };
    }
}

describe('tracker.js', () => {
    const fetchMock = vi.fn<typeof window.fetch>(() =>
        Promise.resolve(new Response(null, { status: 204 })),
    );
    const sendBeacon = vi.fn<typeof navigator.sendBeacon>(() => true);

    beforeEach(() => {
        vi.resetModules();
        document.head.innerHTML = '';
        document.body.innerHTML = '';
        window.localStorage.clear();
        window.sessionStorage.clear();

        if (window.lumina && window.lumina._outboundHandler) {
            document.removeEventListener(
                'click',
                window.lumina._outboundHandler as EventListener,
            );
        }

        delete window.lumina;
        fetchMock.mockClear();
        sendBeacon.mockClear();

        Object.defineProperty(window, 'fetch', {
            value: fetchMock,
            configurable: true,
            writable: true,
        });

        Object.defineProperty(navigator, 'sendBeacon', {
            value: sendBeacon,
            configurable: true,
            writable: true,
        });
    });

    async function loadTracker(): Promise<void> {
        const script = document.createElement('script');
        script.setAttribute('data-domain', 'example.com');
        script.setAttribute('data-api', '/api/collect');
        document.head.appendChild(script);

        // resetModules() + a fresh static import re-runs the IIFE against the
        // current DOM while avoiding Vite's dynamic-import restrictions.
        vi.resetModules();
        await import('./tracker.js');
    }

    it('sends identity params to the collect endpoint on pageview via fetch with keepalive', async () => {
        await loadTracker();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        const [url, init] = fetchMock.mock.calls[0];

        expect(url).toContain('/api/collect');
        expect(url).toContain('visitor=');
        expect(url).toContain('session=');
        expect(init?.keepalive).toBe(true);

        const payload = JSON.parse(init?.body as string) as Record<
            string,
            unknown
        >;
        expect(payload.domain).toBe('example.com');
        expect(payload.path).toBe('/');
    });

    it('falls back to sendBeacon if window.fetch is unavailable', async () => {
        // @ts-expect-error intentionally removing fetch for fallback testing
        delete window.fetch;

        await loadTracker();

        expect(sendBeacon).toHaveBeenCalledTimes(1);
        const [url] = sendBeacon.mock.calls[0];
        expect(url).toContain('/api/collect');
    });

    it('exposes window.lumina for custom events and includes metadata', async () => {
        await loadTracker();

        window.lumina!('signup', { plan: 'pro' });

        expect(fetchMock).toHaveBeenCalledTimes(2);
        const [, init] = fetchMock.mock.calls[1];
        const payload = JSON.parse(init?.body as string) as Record<
            string,
            unknown
        >;

        expect(payload.name).toBe('signup');
        expect(payload.metadata).toEqual({ plan: 'pro' });
    });

    it('automatically tracks outbound link clicks', async () => {
        await loadTracker();

        const link = document.createElement('a');
        link.href = 'https://external-website.org/pricing';
        link.textContent = 'External Link';
        document.body.appendChild(link);

        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        });
        link.addEventListener('click', (e) => e.preventDefault());
        link.dispatchEvent(event);

        expect(fetchMock).toHaveBeenCalledTimes(2);
        const [, init] = fetchMock.mock.calls[1];
        const payload = JSON.parse(init?.body as string) as Record<
            string,
            unknown
        >;

        expect(payload.name).toBe('Outbound Link: Click');
        expect(payload.metadata).toEqual({
            url: 'https://external-website.org/pricing',
        });
    });

    it('persists the same opaque visitor id across reloads (no cookies)', async () => {
        await loadTracker();

        const firstId = window.localStorage.getItem('lumina_visitor_id');
        expect(firstId).toBeTruthy();

        await loadTracker();

        expect(window.localStorage.getItem('lumina_visitor_id')).toBe(firstId);
        expect(document.cookie).toBe('');
    });

    it('rotates the session id after 30 minutes of inactivity', async () => {
        await loadTracker();

        const firstId = window.sessionStorage.getItem('lumina_session_id');
        expect(firstId).toBeTruthy();

        // Rewind the last-seen timestamp beyond the 30-minute inactivity
        // window, then reload the tracker as a returning visitor would.
        const now = Date.now();
        window.sessionStorage.setItem(
            'lumina_session_ts',
            String(now - 31 * 60 * 1000),
        );

        await loadTracker();

        const rotatedId = window.sessionStorage.getItem('lumina_session_id');
        expect(rotatedId).toBeTruthy();
        expect(rotatedId).not.toBe(firstId);

        // The visitor id must remain stable across the session rotation.
        expect(window.localStorage.getItem('lumina_visitor_id')).toBeTruthy();
    });

    it('keeps the same session id during active browsing', async () => {
        await loadTracker();

        const firstId = window.sessionStorage.getItem('lumina_session_id');

        // Reload well inside the 30-minute window.
        await loadTracker();

        expect(window.sessionStorage.getItem('lumina_session_id')).toBe(
            firstId,
        );
    });

    it('does not track when the visitor opted out', async () => {
        window.localStorage.setItem('lumina_ignore', 'true');

        await loadTracker();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(sendBeacon).not.toHaveBeenCalled();
    });
});
