export function hashStringToColor(str: string): string {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }

    const hue = Math.abs(hash % 360);
    const saturation = 65 + (Math.abs(hash) % 20);
    const lightness = 45 + (Math.abs(hash >> 8) % 15);

    return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
}

export function getUserColor(name: string, customColor?: string | null): string {
    if (customColor) {
        return customColor;
    }
    return hashStringToColor(name);
}

/**
 * Parse a CSS color string to RGB values.
 * Supports hex (#rgb, #rrggbb) and hsl(h, s%, l%).
 */
function parseColorToRgb(color: string): [number, number, number] | null {
    // Hex color
    const hexMatch = color.match(/^#([0-9a-f]{3,6})$/i);
    if (hexMatch) {
        let hex = hexMatch[1];
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        return [r, g, b];
    }

    // HSL color
    const hslMatch = color.match(/^hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)$/);
    if (hslMatch) {
        const h = parseInt(hslMatch[1]) / 360;
        const s = parseInt(hslMatch[2]) / 100;
        const l = parseInt(hslMatch[3]) / 100;
        return hslToRgb(h, s, l);
    }

    return null;
}

function hslToRgb(h: number, s: number, l: number): [number, number, number] {
    if (s === 0) {
        const v = Math.round(l * 255);
        return [v, v, v];
    }

    const hue2rgb = (p: number, q: number, t: number) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;

    return [
        Math.round(hue2rgb(p, q, h + 1 / 3) * 255),
        Math.round(hue2rgb(p, q, h) * 255),
        Math.round(hue2rgb(p, q, h - 1 / 3) * 255),
    ];
}

/**
 * Calculate relative luminance per WCAG 2.0.
 */
function getRelativeLuminance(r: number, g: number, b: number): number {
    const [rs, gs, bs] = [r, g, b].map((c) => {
        const s = c / 255;
        return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

/**
 * Calculate WCAG contrast ratio between two luminance values.
 */
function getContrastRatio(l1: number, l2: number): number {
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
}

// Light background luminance (~#ffffff = 1.0, typical chat bg ~0.95)
const LIGHT_BG_LUMINANCE = 0.95;
// Dark background luminance (~#1a1a2e = 0.02, typical dark chat bg)
const DARK_BG_LUMINANCE = 0.03;
// WCAG AA minimum for normal text
const MIN_CONTRAST = 3.0;

/**
 * Check if a color needs a pill background for readability.
 * Returns the appropriate pill background color or null if not needed.
 */
export function needsPillBackground(color: string, isDarkMode: boolean): string | null {
    const rgb = parseColorToRgb(color);
    if (!rgb) return null;

    const luminance = getRelativeLuminance(...rgb);
    const bgLuminance = isDarkMode ? DARK_BG_LUMINANCE : LIGHT_BG_LUMINANCE;
    const contrast = getContrastRatio(luminance, bgLuminance);

    if (contrast >= MIN_CONTRAST) {
        return null; // Sufficient contrast, no pill needed
    }

    // Return a contrasting pill background
    if (isDarkMode) {
        // Color is too dark for dark bg -> give it a light pill
        return 'rgba(255, 255, 255, 0.15)';
    } else {
        // Color is too light for light bg -> give it a dark pill
        return 'rgba(0, 0, 0, 0.08)';
    }
}

export function useChatColor() {
    return { hashStringToColor, getUserColor, needsPillBackground };
}
