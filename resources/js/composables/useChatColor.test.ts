import { describe, expect, it } from 'vitest';
import { getUserColor, hashStringToColor } from './useChatColor';

describe('useChatColor', () => {
    describe('hashStringToColor', () => {
        it('returns an HSL color string', () => {
            const color = hashStringToColor('TestUser');
            expect(color).toMatch(/^hsl\(\d+, \d+%, \d+%\)$/);
        });

        it('returns consistent color for same name', () => {
            const color1 = hashStringToColor('Alice');
            const color2 = hashStringToColor('Alice');
            expect(color1).toBe(color2);
        });

        it('returns different colors for different names', () => {
            const color1 = hashStringToColor('Alice');
            const color2 = hashStringToColor('Bob');
            expect(color1).not.toBe(color2);
        });
    });

    describe('getUserColor', () => {
        it('returns custom color when provided', () => {
            const color = getUserColor('Alice', '#ff5500');
            expect(color).toBe('#ff5500');
        });

        it('returns hash color when no custom color', () => {
            const color = getUserColor('Alice', null);
            expect(color).toMatch(/^hsl\(/);
        });

        it('returns hash color when custom color is undefined', () => {
            const color = getUserColor('Alice', undefined);
            expect(color).toMatch(/^hsl\(/);
        });

        it('returns hash color when custom color is empty string', () => {
            const color = getUserColor('Alice', '');
            expect(color).toMatch(/^hsl\(/);
        });
    });
});
