import { beforeEach, describe, expect, it, vi } from 'vitest';

declare global {
    interface Window {
        lumina: ((eventName?: string, props?: unknown) => void) & {
            q?: unknown[];
        };
    }
}

type BeaconCall = [string, Blob];

describe('tracker.js', () => {
    const sendBeacon = vi.fn<typeof navigator.sendBeacon>(() => true);

    beforeEach(() => {
        vi.resetModules();
        document.head.innerHTML = '';
        document.body.innerHTML = '';
        window.localStorage.clear();
        window.sessionStorage.clear();
        sendBeacon.mockClear();

        Object.defineProperty(navigator, 'sendBeacon', {
            value: sendBeacon,
            configurable: true,
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

    it('sends identity params to the collect endpoint on pageview', async () => {
        await loadTracker();

        expect(sendBeacon).toHaveBeenCalledTimes(1);
        const [url, blob] = sendBeacon.mock.calls[0] as unknown as BeaconCall;

        expect(url).toContain('/api/collect');
        expect(url).toContain('visitor=');
        expect(url).toContain('session=');

        const payload = JSON.parse(await blob.text()) as Record<
            string,
            unknown
        >;
        expect(payload.domain).toBe('example.com');
        expect(payload.path).toBe('/');
    });

    it('exposes window.lumina for custom events and includes metadata', async () => {
        await loadTracker();

        window.lumina('signup', { plan: 'pro' });

        expect(sendBeacon).toHaveBeenCalledTimes(2);
        const [, blob] = sendBeacon.mock.calls[1] as unknown as BeaconCall;
        const payload = JSON.parse(await blob.text()) as Record<
            string,
            unknown
        >;

        expect(payload.name).toBe('signup');
        expect(payload.metadata).toEqual({ plan: 'pro' });
    });

    it('persists the same opaque visitor id across reloads (no cookies)', async () => {
        await loadTracker();

        const firstId = window.localStorage.getItem('lumina_visitor_id');
        expect(firstId).toBeTruthy();

        await loadTracker();

        expect(window.localStorage.getItem('lumina_visitor_id')).toBe(firstId);
        expect(document.cookie).toBe('');
    });

    it('does not track when the visitor opted out', async () => {
        window.localStorage.setItem('lumina_ignore', 'true');

        await loadTracker();

        expect(sendBeacon).not.toHaveBeenCalled();
    });
});
