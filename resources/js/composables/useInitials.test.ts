import { describe, expect, it } from 'vitest';
import { getInitials, useInitials } from './useInitials';

describe('getInitials', () => {
    it('returns empty string for empty input', () => {
        expect(getInitials()).toBe('');
        expect(getInitials('')).toBe('');
        expect(getInitials('   ')).toBe('');
    });

    it('returns the first letter for a single name', () => {
        expect(getInitials('ada')).toBe('A');
    });

    it('combines first and last initials', () => {
        expect(getInitials('Ada Lovelace')).toBe('AL');
    });

    it('handles more than two names by using first and last', () => {
        expect(getInitials('Augusta Ada King')).toBe('AK');
    });

    it('handles unicode names via code points', () => {
        expect(getInitials('Émile Durkheim')).toBe('ÉD');
    });
});

describe('useInitials', () => {
    it('exposes getInitials', () => {
        const { getInitials } = useInitials();

        expect(getInitials('Grace Hopper')).toBe('GH');
    });
});
