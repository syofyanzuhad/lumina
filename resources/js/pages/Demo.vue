<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import {
    BarChart3,
    Eye,
    Users,
    Globe,
    Calendar,
    Sparkles,
    RefreshCw,
    Smartphone,
    Laptop,
    Monitor,
    Download,
    ArrowRight,
    Lock,
    ExternalLink
} from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

interface SiteItem {
    id: number;
    domain: string;
}

interface TopPage {
    path: string;
    count: number;
    percentage: number;
}

interface TopReferrer {
    referrer: string;
    count: number;
    percentage: number;
}

interface DailyItem {
    date: string;
    pageviews: number;
    visitors: number;
}

interface DeviceItem {
    device: string;
    count: number;
    percentage: number;
}

interface CustomEventItem {
    name: string;
    count: number;
}

interface Overview {
    total_pageviews: number;
    unique_visitors: number;
    top_pages: TopPage[];
    top_referrers: TopReferrer[];
    daily_pageviews: DailyItem[];
    device_breakdown?: DeviceItem[];
    custom_events: CustomEventItem[];
}

const props = defineProps<{
    site: SiteItem;
    period: string;
    overview: Overview;
}>();

const isRefreshing = ref(false);
const hoveredDay = ref<DailyItem | null>(null);
const isLive = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const maxDaily = computed(() => {
    if (!props.overview.daily_pageviews || props.overview.daily_pageviews.length === 0) {
        return 1;
    }
    const max = Math.max(...props.overview.daily_pageviews.map((d) => d.pageviews));
    return max > 0 ? max : 1;
});

const setPeriod = (newPeriod: string) => {
    router.get('/demo', { period: newPeriod }, { preserveState: true, preserveScroll: true });
};

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const startPolling = () => {
    stopPolling();
    pollInterval = setInterval(() => {
        if (document.visibilityState === 'visible' && !isRefreshing.value) {
            refreshData();
        }
    }, 30000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
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

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(num);
};

const getDeviceIcon = (deviceStr: string) => {
    const lower = (deviceStr || '').toLowerCase();
    if (lower.includes('mobile')) return Smartphone;
    if (lower.includes('tablet')) return Laptop;
    return Monitor;
};

const referrerDomains: Record<string, string> = {
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

const getReferrerFavicon = (name: string): string | null => {
    const domain = referrerDomains[name];
    return domain ? `https://www.google.com/s2/favicons?domain=${domain}&sz=32` : null;
};

const getBrowserIcon = (browser: string): string | null => {
    const lower = (browser || '').toLowerCase();
    if (lower.includes('chrome') && !lower.includes('chromium')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/chrome/chrome-original.svg';
    if (lower.includes('firefox')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/firefox/firefox-original.svg';
    if (lower.includes('safari')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/safari/safari-original.svg';
    if (lower.includes('edge')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/edge/edge-original.svg';
    if (lower.includes('opera')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/opera/opera-original.svg';
    if (lower.includes('brave')) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/brave/brave-original.svg';
    return null;
};

const getOsIcon = (os: string): string | null => {
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
};
</script>

<template>
    <Head :title="`Public Live Demo Analytics — Lumina`">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-slate-950 text-slate-100 font-['Outfit',sans-serif] selection:bg-indigo-500 selection:text-white relative">
        <!-- Top Glow Orbs -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[400px] bg-gradient-to-tr from-indigo-600/15 via-violet-600/15 to-emerald-500/10 blur-[120px] pointer-events-none rounded-full"></div>

        <!-- Navigation Header -->
        <header class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 flex items-center justify-between border-b border-slate-900">
            <div class="flex items-center gap-3">
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 p-0.5 shadow-lg shadow-indigo-500/30">
                        <div class="h-full w-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <BarChart3 class="h-5 w-5 text-indigo-400" />
                        </div>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-indigo-300">
                        Lumina
                    </span>
                </Link>
                <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live Public Demo
                </span>
            </div>

            <nav class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="dashboard()"
                    class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 transition-all flex items-center gap-2"
                >
                    My Dashboard
                    <ArrowRight class="h-3.5 w-3.5" />
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="px-3.5 py-2 text-xs font-semibold text-slate-300 hover:text-white transition-colors"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-semibold text-xs shadow-lg shadow-indigo-600/25 hover:from-indigo-500 hover:to-violet-500 transition-all flex items-center gap-2"
                    >
                        Get Started
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Content Area -->
        <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
            <!-- Control Header Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-slate-900/80 border border-slate-800 rounded-2xl p-5 backdrop-blur-xl shadow-xl">
                <!-- Domain Title -->
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <Globe class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-white tracking-tight">{{ site.domain }}</h2>
                            <a :href="`https://${site.domain}`" target="_blank" rel="noopener" class="text-slate-500 hover:text-slate-300 transition-colors">
                                <ExternalLink class="h-3.5 w-3.5" />
                            </a>
                        </div>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Real-time public analytics dashboard</p>
                    </div>
                </div>

                <!-- Date Period & Controls -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="setPeriod('7d')"
                        :class="[
                            'px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            period === '7d'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                                : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'
                        ]"
                    >
                        Last 7 Days
                    </button>
                    <button
                        type="button"
                        @click="setPeriod('30d')"
                        :class="[
                            'px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            period === '30d'
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                                : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'
                        ]"
                    >
                        Last 30 Days
                    </button>

                    <!-- Live Auto-Refresh Toggle -->
                    <button
                        type="button"
                        @click="toggleLive"
                        :title="isLive ? 'Live Auto-Refresh Active (30s)' : 'Turn On Live Auto-Refresh'"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5 ml-1',
                            isLive
                                ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40'
                                : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'
                        ]"
                    >
                        <span :class="['h-2 w-2 rounded-full', isLive ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500']"></span>
                        <span>{{ isLive ? 'Live 30s' : 'Live Off' }}</span>
                    </button>

                    <!-- Refresh Button -->
                    <button
                        type="button"
                        @click="refreshData"
                        title="Refresh Data"
                        class="p-2 rounded-lg bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800 transition-all ml-1"
                    >
                        <RefreshCw :class="['h-4 w-4', { 'animate-spin': isRefreshing }]" />
                    </button>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pageviews</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <Eye class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 text-4xl font-black tracking-tight text-white">
                        {{ formatNumber(overview.total_pageviews) }}
                    </div>
                    <div class="mt-2 text-xs text-slate-400">Total raw page visits recorded</div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Unique Visitors</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 text-4xl font-black tracking-tight text-white">
                        {{ formatNumber(overview.unique_visitors) }}
                    </div>
                    <div class="mt-2 text-xs text-slate-400">Distinct daily hashed visitors</div>
                </div>
            </div>

            <!-- Daily Pageview Trends Bar Chart -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl backdrop-blur-md">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-indigo-400" />
                        <h3 class="text-sm font-bold text-white">Daily Pageview Trends</h3>
                    </div>
                    <span v-if="hoveredDay" class="text-xs font-mono text-indigo-400 font-semibold">
                        {{ hoveredDay.date }}: {{ formatNumber(hoveredDay.pageviews) }} views ({{ formatNumber(hoveredDay.visitors) }} visitors)
                    </span>
                    <span v-else class="text-xs text-slate-500">Hover bar to inspect</span>
                </div>

                <div class="flex items-end gap-2 h-44 pt-6 pb-2">
                    <div
                        v-for="day in overview.daily_pageviews"
                        :key="day.date"
                        @mouseenter="hoveredDay = day"
                        @mouseleave="hoveredDay = null"
                        class="flex-1 flex flex-col items-center group relative h-full justify-end cursor-pointer"
                    >
                        <div
                            class="w-full rounded-t-md bg-gradient-to-t from-indigo-600 to-indigo-400 transition-all duration-200 group-hover:from-indigo-500 group-hover:to-violet-300 min-h-[4px]"
                            :style="{ height: `${Math.max(Math.round((day.pageviews / maxDaily) * 100), 2)}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Details Section: Top Pages, Referrers, and Device Types -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Top Pages -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-white">Top Pages</h3>
                        <span class="text-xs text-slate-500">{{ overview.top_pages.length }} pages</span>
                    </div>
                    <div class="space-y-4">
                        <div v-for="page in overview.top_pages" :key="page.path" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-slate-200">{{ page.path }}</span>
                                <span class="text-slate-400 font-mono">{{ formatNumber(page.count) }} ({{ page.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800/60">
                                <div class="bg-indigo-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${page.percentage}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Referrers -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-white">Top Referrers</h3>
                        <span class="text-xs text-slate-500">{{ overview.top_referrers.length }} sources</span>
                    </div>
                    <div class="space-y-4">
                        <div v-for="refItem in overview.top_referrers" :key="refItem.referrer" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="truncate font-mono text-slate-200 flex items-center gap-1.5">
                                    <img
                                        v-if="getReferrerFavicon(refItem.referrer)"
                                        :src="getReferrerFavicon(refItem.referrer)!"
                                        :alt="refItem.referrer"
                                        class="h-3.5 w-3.5 rounded-sm shrink-0 object-contain"
                                        @error="($event.target as HTMLImageElement).style.display = 'none'"
                                    />
                                    <Globe v-else class="h-3 w-3 shrink-0 text-slate-500" />
                                    <span class="truncate">{{ refItem.referrer }}</span>
                                </span>
                                <span class="text-slate-400 font-mono">{{ formatNumber(refItem.count) }} ({{ refItem.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800/60">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${refItem.percentage}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Device Breakdown -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-white">Device Breakdown</h3>
                        <span v-if="overview.device_breakdown" class="text-xs text-slate-500">{{ overview.device_breakdown.length }} devices</span>
                    </div>
                    <div class="space-y-4">
                        <div v-for="dev in overview.device_breakdown" :key="dev.device" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="flex items-center gap-1.5 capitalize font-mono text-slate-200">
                                    <component :is="getDeviceIcon(dev.device)" class="h-3.5 w-3.5 text-indigo-400" />
                                    {{ dev.device }}
                                </span>
                                <span class="text-slate-400 font-mono">{{ formatNumber(dev.count) }} ({{ dev.percentage }}%)</span>
                            </div>
                            <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800/60">
                                <div class="bg-amber-400 h-2 rounded-full transition-all duration-500" :style="{ width: `${dev.percentage}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom CTA Banner -->
            <div class="mt-12 rounded-3xl border border-indigo-500/30 bg-gradient-to-r from-indigo-950/80 via-slate-900 to-violet-950/80 p-8 sm:p-10 text-center relative overflow-hidden shadow-2xl backdrop-blur-xl">
                <div class="absolute -right-10 -bottom-10 h-60 w-60 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-white">Ready for lightweight, cookie-free web analytics?</h3>
                <p class="mt-2 text-sm sm:text-base text-slate-400 max-w-xl mx-auto">
                    Track pageviews and custom events in under 2 minutes with zero infrastructure hassle.
                </p>
                <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        :href="register()"
                        class="w-full sm:w-auto px-7 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 hover:from-indigo-500 hover:to-violet-500 transition-all flex items-center justify-center gap-2"
                    >
                        Create Free Account
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
