import type { Component } from 'vue';
import { Smartphone, Laptop, Monitor } from '@lucide/vue';

export function formatNumber(num: number): string {
    return new Intl.NumberFormat().format(num);
}

export function formatDateLabel(dateStr: string): string {
    if (!dateStr) return '';
    try {
        if (dateStr.includes(' ')) {
            const [datePart, timePart] = dateStr.split(' ');
            const [year, month, day] = datePart.split('-').map(Number);
            const [hour] = timePart.split(':').map(Number);
            const utcDate = new Date(Date.UTC(year, month - 1, day, hour, 0, 0));
            return new Intl.DateTimeFormat(navigator.language || 'en-US', {
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            }).format(utcDate);
        } else {
            const [year, month, day] = dateStr.split('-').map(Number);
            const utcDate = new Date(Date.UTC(year, month - 1, day, 12, 0, 0));
            return new Intl.DateTimeFormat(navigator.language || 'en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            }).format(utcDate);
        }
    } catch {
        return dateStr;
    }
}

export function getCountryFlag(code?: string): string {
    if (!code || code.length !== 2) return '🌐';
    const upper = code.toUpperCase();
    const codePoints = [...upper].map((char) => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
}

export function getDeviceIcon(deviceStr: string): Component {
    const lower = (deviceStr || '').toLowerCase();
    if (lower.includes('mobile')) return Smartphone;
    if (lower.includes('tablet')) return Laptop;
    return Monitor;
}

export const referrerDomains: Record<string, string> = {
    'Google': 'google.com',
    'Hacker News': 'news.ycombinator.com',
    'X (Twitter)': 'x.com',
    'Twitter': 'x.com',
    'GitHub': 'github.com',
    'Facebook': 'facebook.com',
    'LinkedIn': 'linkedin.com',
    'Reddit': 'reddit.com',
    'YouTube': 'youtube.com',
    'Instagram': 'instagram.com',
    'TikTok': 'tiktok.com',
    'Bing': 'bing.com',
    'DuckDuckGo': 'duckduckgo.com',
    'Slack': 'slack.com',
    'Discord': 'discord.com',
    'Medium': 'medium.com',
    'Dev.to': 'dev.to',
    'Product Hunt': 'producthunt.com',
    'Notion': 'notion.so',
    'Netlify': 'netlify.com',
    'Vercel': 'vercel.com',
};

export function getReferrerFavicon(name: string): string | null {
    const domain = referrerDomains[name];
    return domain ? `https://www.google.com/s2/favicons?domain=${domain}&sz=32` : null;
}

export function getBrowserIcon(browser: string): string | null {
    const lower = (browser || '').toLowerCase();
    if (lower.includes('chrome') && !lower.includes('chromium')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/chrome/chrome-original.svg';
    if (lower.includes('firefox')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/firefox/firefox-original.svg';
    if (lower.includes('safari')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/safari/safari-original.svg';
    if (lower.includes('edge')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/edge/edge-original.svg';
    if (lower.includes('opera')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/opera/opera-original.svg';
    if (lower.includes('brave')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/brave/brave-original.svg';
    return null;
}

export function getOsIcon(os: string): string | null {
    const lower = (os || '').toLowerCase();
    if (lower.includes('windows')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/windows11/windows11-original.svg';
    if (lower.includes('mac') || lower.includes('os x') || lower.includes('macos') || lower.includes('darwin')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/apple/apple-original.svg';
    if (lower.includes('ubuntu')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/ubuntu/ubuntu-plain.svg';
    if (lower.includes('debian')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/debian/debian-original.svg';
    if (lower.includes('linux')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/linux/linux-original.svg';
    if (lower.includes('android')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/android/android-original.svg';
    if (lower.includes('ios') || lower.includes('iphone') || lower.includes('ipad')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/apple/apple-original.svg';
    if (lower.includes('chrome')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/chrome/chrome-original.svg';
    return null;
}
