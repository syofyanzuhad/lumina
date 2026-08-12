import { Laptop, Monitor, Smartphone } from '@lucide/vue';
import { describe, expect, it } from 'vitest';
import {
    formatNumber,
    getBrowserIcon,
    getCountryFlag,
    getDeviceIcon,
    getOsIcon,
    getReferrerFavicon,
} from './useAnalyticsFormatters';

describe('formatNumber', () => {
    it('formats numbers with locale separators', () => {
        expect(formatNumber(0)).toBe('0');
        expect(formatNumber(1000)).toBe('1,000');
        expect(formatNumber(1234567)).toBe('1,234,567');
    });
});

describe('getCountryFlag', () => {
    it('returns a globe for missing or malformed codes', () => {
        expect(getCountryFlag()).toBe('🌐');
        expect(getCountryFlag('')).toBe('🌐');
        expect(getCountryFlag('USA')).toBe('🌐');
    });

    it('returns the regional indicator flag for a two-letter code', () => {
        expect(getCountryFlag('us')).toBe('🇺🇸');
        expect(getCountryFlag('DE')).toBe('🇩🇪');
    });
});

describe('getDeviceIcon', () => {
    it('maps mobile strings to the Smartphone icon', () => {
        expect(getDeviceIcon('Mobile')).toBe(Smartphone);
    });

    it('maps tablet strings to the Laptop icon', () => {
        expect(getDeviceIcon('Tablet')).toBe(Laptop);
    });

    it('defaults everything else to Monitor', () => {
        expect(getDeviceIcon('Desktop')).toBe(Monitor);
        expect(getDeviceIcon('')).toBe(Monitor);
    });
});

describe('getReferrerFavicon', () => {
    it('returns null for unknown platforms', () => {
        expect(getReferrerFavicon('Mystery Site')).toBeNull();
    });

    it('returns a favicon URL for known platforms', () => {
        expect(getReferrerFavicon('Google')).toContain('google.com');
        expect(getReferrerFavicon('GitHub')).toContain('github.com');
    });
});

describe('getBrowserIcon', () => {
    it('returns null for unknown browsers', () => {
        expect(getBrowserIcon('Lynx')).toBeNull();
    });

    it('returns an icon URL for known browsers', () => {
        expect(getBrowserIcon('Chrome')).toContain('chrome');
        expect(getBrowserIcon('Firefox')).toContain('firefox');
        expect(getBrowserIcon('Safari')).toContain('safari');
    });

    it('treats Chromium as distinct from Chrome', () => {
        expect(getBrowserIcon('Chromium')).toBeNull();
    });
});

describe('getOsIcon', () => {
    it('returns null for unknown OS', () => {
        expect(getOsIcon('BeOS')).toBeNull();
    });

    it('returns an icon URL for known OS families', () => {
        expect(getOsIcon('Windows 11')).toContain('windows');
        expect(getOsIcon('macOS')).toContain('apple');
        expect(getOsIcon('Ubuntu')).toContain('ubuntu');
        expect(getOsIcon('Android')).toContain('android');
        expect(getOsIcon('iOS')).toContain('apple');
    });
});
