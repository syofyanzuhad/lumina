<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Eye,
    Users,
    Shield,
    Zap,
    Package,
    ArrowRight,
    BarChart3,
    Sparkles,
    CheckCircle2,
    Copy,
    Check,
    Terminal,
    ShieldCheck,
    HelpCircle,
    XCircle,
    Sun,
    Moon,
    Target,
    Download,
    Share2,
    Smartphone,
} from '@lucide/vue';
import { ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard, login, register } from '@/routes';

const { appearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    const nextTheme = appearance.value === 'dark' ? 'light' : 'dark';
    updateAppearance(nextTheme);
};

const customDomain = ref('mywebsite.com');
const isCopied = ref(false);

const copySnippet = () => {
    const d = customDomain.value || 'mywebsite.com';
    const snippet =
        `<` +
        `script defer data-domain="${d}" src="https://uselumina.laravel.cloud/js/script.js"><` +
        `/script>`;
    navigator.clipboard.writeText(snippet);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
};
</script>

<template>
    <Head title="Lumina — Lightweight Web Analytics for Laravel">
        <meta
            name="description"
            content="Self-hosted, cookie-free web analytics for Laravel applications under 2KB script payload. Track pageviews, custom events, and conversion goals with zero infrastructure hassle."
        />
        <meta
            name="keywords"
            content="Laravel analytics, web analytics, self-hosted analytics, cookie-free analytics, privacy friendly analytics, Plausible alternative, GDPR compliant analytics"
        />
        <link rel="canonical" href="https://uselumina.laravel.cloud" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta
            property="og:title"
            content="Lumina — Lightweight Web Analytics for Laravel"
        />
        <meta
            property="og:description"
            content="Self-hosted web analytics native to Laravel. < 2KB tracker payload, 100% cookie-free GDPR privacy, and conversion goal tracking."
        />
        <meta property="og:url" content="https://uselumina.laravel.cloud" />
        <meta property="og:site_name" content="Lumina Analytics" />
        <meta
            property="og:image"
            content="https://uselumina.laravel.cloud/og-image.jpg"
        />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            content="Lumina — Lightweight Web Analytics for Laravel"
        />
        <meta
            name="twitter:description"
            content="Self-hosted web analytics native to Laravel. < 2KB tracker payload, 100% cookie-free GDPR privacy, and conversion goal tracking."
        />
        <meta
            name="twitter:image"
            content="https://uselumina.laravel.cloud/og-image.jpg"
        />

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin=""
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="relative min-h-screen overflow-hidden bg-slate-50 font-['Outfit',sans-serif] text-slate-900 transition-colors duration-300 selection:bg-indigo-500 selection:text-white dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Background Gradient Orbs -->
        <div
            class="pointer-events-none absolute top-0 left-1/2 h-[500px] w-[1000px] -translate-x-1/2 rounded-full bg-gradient-to-tr from-indigo-500/20 via-violet-500/20 to-amber-500/10 blur-[120px] dark:from-indigo-600/20 dark:via-violet-600/20"
        ></div>
        <div
            class="pointer-events-none absolute right-0 bottom-0 h-[600px] w-[600px] rounded-full bg-indigo-500/10 blur-[140px] dark:bg-indigo-900/10"
        ></div>

        <!-- Navigation Header -->
        <header
            class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-3">
                <AppLogoIcon class="h-9 w-9" />
                <span
                    class="bg-gradient-to-r from-slate-900 via-slate-700 to-indigo-600 bg-clip-text text-xl font-extrabold tracking-tight text-transparent dark:from-white dark:via-slate-200 dark:to-indigo-300"
                >
                    Lumina
                </span>
                <span
                    class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2.5 py-0.5 text-[10px] font-bold tracking-widest text-indigo-600 uppercase dark:text-indigo-400"
                >
                    Analytics
                </span>
            </div>

            <nav class="flex items-center gap-3">
                <!-- GitHub Repository Link -->
                <a
                    href="https://github.com/syofyanzuhad/lumina"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center justify-center rounded-xl border border-slate-200 bg-white/80 p-2.5 text-slate-600 shadow-xs backdrop-blur-md transition-all hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:text-white"
                    title="View Source on GitHub"
                >
                    <svg
                        class="h-4 w-4 fill-current text-slate-700 dark:text-slate-300"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                        ></path>
                    </svg>
                </a>

                <!-- Darkmode Toggle Button -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white/80 p-2.5 text-slate-600 shadow-xs backdrop-blur-md transition-all hover:text-indigo-600 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:text-white"
                    :title="
                        appearance === 'dark'
                            ? 'Switch to Light Mode'
                            : 'Switch to Dark Mode'
                    "
                >
                    <Sun
                        v-if="appearance === 'dark'"
                        class="h-4 w-4 text-amber-400"
                    />
                    <Moon v-else class="h-4 w-4 text-indigo-600" />
                </button>

                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition-all hover:bg-indigo-500 hover:shadow-indigo-500/40"
                >
                    Go to Dashboard
                    <ArrowRight class="h-4 w-4" />
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition-all hover:from-indigo-500 hover:to-violet-500"
                    >
                        Get Started Free
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <main
            class="relative z-10 mx-auto max-w-7xl px-6 pt-12 pb-24 text-center"
        >
            <!-- Badge -->
            <div
                class="mb-8 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-xs font-semibold text-indigo-700 shadow-xs backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/90 dark:text-indigo-300"
            >
                <Sparkles
                    class="h-3.5 w-3.5 animate-pulse text-amber-500 dark:text-amber-400"
                />
                <span
                    >Latest Update: Privacy-First Identity, Session Analytics &
                    Zero-Latency Tracking</span
                >
            </div>

            <!-- Main Heading -->
            <h1
                class="mx-auto max-w-4xl text-4xl leading-[1.1] font-black tracking-tight text-slate-900 sm:text-6xl lg:text-7xl dark:text-white"
            >
                Lightweight Analytics, <br />
                <span
                    class="bg-gradient-to-r from-indigo-600 via-violet-600 to-amber-500 bg-clip-text text-transparent dark:from-indigo-400 dark:via-violet-300 dark:to-amber-300"
                >
                    Zero Infrastructure Hassle.
                </span>
            </h1>

            <p
                class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed font-normal text-slate-600 sm:text-xl dark:text-slate-400"
            >
                Self-hosted web analytics for your Laravel applications. Track
                pageviews, custom events & goal conversions with a minified
                <strong class="text-slate-800 dark:text-slate-200"
                    >&lt; 2KB script</strong
                >, 100% cookie-free privacy, and password-protected sharing.
            </p>

            <!-- CTA Buttons -->
            <div
                class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-[length:200%_auto] px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-600/30 transition-all duration-500 hover:bg-right sm:w-auto"
                >
                    Launch Dashboard
                    <ArrowRight class="h-5 w-5" />
                </Link>
                <Link
                    href="/demo"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-7 py-4 text-base font-semibold text-slate-800 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500 hover:shadow-md sm:w-auto dark:border-indigo-500/30 dark:bg-slate-900/90 dark:text-white"
                >
                    <BarChart3
                        class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                    />
                    View Live Demo
                </Link>
                <a
                    href="https://github.com/syofyanzuhad/lumina"
                    target="_blank"
                    rel="noopener"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-7 py-4 text-base font-semibold text-slate-700 shadow-xs backdrop-blur-md transition-all hover:border-slate-300 hover:text-slate-900 sm:w-auto dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white"
                >
                    <svg
                        class="h-5 w-5 fill-current text-slate-700 dark:text-slate-300"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                        ></path>
                    </svg>
                    Source Code
                </a>
            </div>

            <!-- Metric Highlights Banner -->
            <div
                class="mx-auto mt-14 grid max-w-4xl grid-cols-2 gap-6 border-t border-slate-200 pt-10 md:grid-cols-4 dark:border-slate-900"
            >
                <div
                    class="rounded-xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-xs dark:border-slate-800/60 dark:bg-slate-900/40"
                >
                    <div
                        class="text-2xl font-black text-indigo-600 dark:text-indigo-400"
                    >
                        &lt; 2 KB
                    </div>
                    <div
                        class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Gzipped Tracker Payload
                    </div>
                </div>
                <div
                    class="rounded-xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-xs dark:border-slate-800/60 dark:bg-slate-900/40"
                >
                    <div
                        class="text-2xl font-black text-emerald-600 dark:text-emerald-400"
                    >
                        100%
                    </div>
                    <div
                        class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Cookie-Free Privacy
                    </div>
                </div>
                <div
                    class="rounded-xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-xs dark:border-slate-800/60 dark:bg-slate-900/40"
                >
                    <div
                        class="text-2xl font-black text-violet-600 dark:text-violet-400"
                    >
                        Goals
                    </div>
                    <div
                        class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Conversion Tracking
                    </div>
                </div>
                <div
                    class="rounded-xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-xs dark:border-slate-800/60 dark:bg-slate-900/40"
                >
                    <div
                        class="text-2xl font-black text-amber-600 dark:text-amber-400"
                    >
                        CSV & JSON
                    </div>
                    <div
                        class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Data Streaming Export
                    </div>
                </div>
            </div>

            <!-- Live Mock Dashboard Preview -->
            <div
                class="relative mx-auto mt-16 max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-6 text-left shadow-2xl backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/80"
            >
                <div
                    class="pointer-events-none absolute top-0 right-0 h-80 w-80 rounded-full bg-indigo-500/10 blur-[90px]"
                ></div>

                <!-- Mock Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800/80"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-3 w-3 animate-ping rounded-full bg-emerald-500"
                        ></div>
                        <span
                            class="text-sm font-bold text-slate-800 dark:text-slate-200"
                            >yourdomain.com</span
                        >
                        <span class="font-mono text-xs text-slate-500"
                            >Live Traffic Preview</span
                        >
                    </div>
                    <div class="flex gap-2">
                        <span
                            class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-bold text-white"
                            >Last 30 Days</span
                        >
                    </div>
                </div>

                <!-- Mock Cards -->
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400"
                        >
                            <span>TOTAL PAGEVIEWS</span>
                            <Eye
                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
                        <div
                            class="mt-2 text-3xl font-black text-slate-900 dark:text-white"
                        >
                            128,490
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400"
                        >
                            <span>UNIQUE VISITORS</span>
                            <Users
                                class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                        <div
                            class="mt-2 text-3xl font-black text-slate-900 dark:text-white"
                        >
                            42,110
                        </div>
                    </div>
                </div>

                <!-- Mock Trend Bar Chart -->
                <div
                    class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60"
                >
                    <div
                        class="mb-3 text-xs font-semibold text-slate-500 dark:text-slate-400"
                    >
                        DAILY PAGEVIEW TRENDS
                    </div>
                    <div class="flex h-24 items-end gap-1.5">
                        <div
                            v-for="h in [
                                30, 45, 60, 40, 75, 90, 65, 80, 100, 70, 85, 95,
                                110, 80, 90,
                            ]"
                            :key="h"
                            class="h-full flex-1 rounded-t bg-gradient-to-t from-indigo-600 to-indigo-400"
                            :style="{ height: `${h}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- What's New in v1.1 Feature Grid -->
            <div class="mx-auto mt-28 max-w-6xl text-left">
                <div class="mb-12 text-center">
                    <span
                        class="font-mono text-xs font-bold tracking-widest text-indigo-600 uppercase dark:text-indigo-400"
                        >Latest Release</span
                    >
                    <h2
                        class="mt-1 text-3xl font-extrabold text-slate-900 dark:text-white"
                    >
                        What's New in Lumina
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        Major updates focused on privacy-first identity, real
                        session metrics, zero-latency tracking, conversion
                        analytics, and enhanced sharing.
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- Feature Update 1 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
                        >
                            <Target class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Conversion Goals
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Define path pageviews or custom events as goals to
                            track conversion counts & percentages seamlessly
                            across custom timeframes.
                        </p>
                    </div>

                    <!-- Feature Update 2 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <Download class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            CSV & JSON Exports
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Stream raw events or aggregated metrics directly
                            into CSV or JSON files for custom BI tools and
                            external reporting.
                        </p>
                    </div>

                    <!-- Feature Update 3 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400"
                        >
                            <Share2 class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Password Share Links
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Share live dashboards with clients via secure unique
                            share tokens with optional bcrypt password
                            authentication.
                        </p>
                    </div>

                    <!-- Feature Update 4 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400"
                        >
                            <Smartphone class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Detailed Device Tech
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Granular detection for browser versions, operating
                            system releases, and device breakdown down to exact
                            builds.
                        </p>
                    </div>

                    <!-- Feature Update 5 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400"
                        >
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Opaque Visitor Identity
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Visitor & session IDs are generated client-side and
                            stored in localStorage / sessionStorage — never
                            cookies. No raw IPs are stored, with a stable-salt
                            hash fallback.
                        </p>
                    </div>

                    <!-- Feature Update 6 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400"
                        >
                            <Users class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Real Session Metrics
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Bounce rate and average visit duration are computed
                            from true 30-minute sessions — not approximated from
                            daily aggregates.
                        </p>
                    </div>

                    <!-- Feature Update 7 -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all hover:border-indigo-500/50 dark:border-slate-800/80 dark:bg-slate-900/40"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400"
                        >
                            <Zap class="h-6 w-6" />
                        </div>
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Zero-Latency Tracking
                        </h3>
                        <p
                            class="text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Tracking runs in terminable middleware after the
                            response is sent, with atomic rate limiting and
                            cached site lookups — zero impact on page speed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Core Feature Cards -->
            <div
                class="mx-auto mt-24 grid max-w-6xl grid-cols-1 gap-8 text-left md:grid-cols-3"
            >
                <!-- Feature 1 -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white/80 p-8 shadow-xs backdrop-blur-md transition-all hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-900/40 dark:hover:border-slate-700"
                >
                    <div
                        class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
                    >
                        <Zap class="h-6 w-6" />
                    </div>
                    <h3
                        class="mb-2 text-xl font-bold text-slate-900 dark:text-white"
                    >
                        Ultra-Fast & Asynchronous
                    </h3>
                    <p
                        class="text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        Vanilla JS tracker bundled under 2 KB gzipped (~1.1 KB
                        today). Zero dependencies, non-blocking asynchronous
                        execution, and zero page latency impact.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white/80 p-8 shadow-xs backdrop-blur-md transition-all hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-900/40 dark:hover:border-slate-700"
                >
                    <div
                        class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <Shield class="h-6 w-6" />
                    </div>
                    <h3
                        class="mb-2 text-xl font-bold text-slate-900 dark:text-white"
                    >
                        GDPR Privacy by Default
                    </h3>
                    <p
                        class="text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        IP addresses are never saved directly. Visitors are
                        identified by opaque client-side IDs (localStorage /
                        sessionStorage, never cookies); without JS, a
                        stable-salt
                        <code class="text-indigo-600 dark:text-indigo-300"
                            >sha256</code
                        >
                        fallback preserves cross-day uniques without storing raw
                        IPs.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white/80 p-8 shadow-xs backdrop-blur-md transition-all hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-900/40 dark:hover:border-slate-700"
                >
                    <div
                        class="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400"
                    >
                        <Package class="h-6 w-6" />
                    </div>
                    <h3
                        class="mb-2 text-xl font-bold text-slate-900 dark:text-white"
                    >
                        Dual Mode Integration
                    </h3>
                    <p
                        class="text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        Use Lumina as a standalone Vue 3 / Inertia web
                        application or embed
                        <code class="text-indigo-600 dark:text-indigo-300"
                            >lumina/core</code
                        >
                        package directly inside your existing Laravel app
                        layout.
                    </p>
                </div>
            </div>

            <!-- Interactive Script Snippet Generator -->
            <div
                class="relative mx-auto mt-28 max-w-4xl overflow-hidden rounded-3xl border border-indigo-500/30 bg-white/90 p-8 text-left shadow-2xl backdrop-blur-xl sm:p-12 dark:bg-slate-900/80"
            >
                <div
                    class="pointer-events-none absolute top-0 right-0 h-72 w-72 rounded-full bg-indigo-600/10 blur-[100px]"
                ></div>

                <div class="mb-2 flex items-center gap-3">
                    <Terminal
                        class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                    />
                    <span
                        class="font-mono text-xs font-bold tracking-widest text-indigo-600 uppercase dark:text-indigo-400"
                        >Simple 1-Line Setup</span
                    >
                </div>

                <h2
                    class="text-2xl font-extrabold text-slate-900 sm:text-3xl dark:text-white"
                >
                    Generate Your Tracking Snippet
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Enter your site's domain to generate your custom Lumina
                    tracking snippet instantly.
                </p>

                <div class="mt-6 space-y-4">
                    <div>
                        <label
                            for="domain-input"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300"
                            >Target Website Domain</label
                        >
                        <input
                            id="domain-input"
                            v-model="customDomain"
                            type="text"
                            placeholder="mywebsite.com"
                            class="w-full max-w-md rounded-xl border border-slate-300 bg-slate-100 px-4 py-2.5 font-mono text-sm text-slate-900 placeholder-slate-400 outline-hidden focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:placeholder-slate-600"
                        />
                    </div>

                    <div
                        class="relative flex items-center justify-between gap-4 overflow-x-auto rounded-xl border border-slate-200 bg-slate-900 p-4 font-mono text-xs text-indigo-300 shadow-inner dark:border-slate-800"
                    >
                        <code class="truncate"
                            >&lt;script defer data-domain="{{
                                customDomain || 'mywebsite.com'
                            }}"
                            src="https://uselumina.laravel.cloud/js/script.js"&gt;&lt;/script&gt;</code
                        >
                        <button
                            type="button"
                            @click="copySnippet"
                            class="flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-md transition-all hover:bg-indigo-500"
                        >
                            <component
                                :is="isCopied ? Check : Copy"
                                class="h-3.5 w-3.5"
                            />
                            <span>{{
                                isCopied ? 'Copied!' : 'Copy Code'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lumina vs Traditional Analytics Comparison Table -->
            <div class="mx-auto mt-28 max-w-5xl text-left">
                <div class="mb-12 text-center">
                    <span
                        class="font-mono text-xs font-bold tracking-widest text-emerald-600 uppercase dark:text-emerald-400"
                        >Why Switch?</span
                    >
                    <h2
                        class="mt-1 text-3xl font-extrabold text-slate-900 dark:text-white"
                    >
                        Lumina vs. Traditional Analytics
                    </h2>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white/90 shadow-xl backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/60"
                >
                    <div
                        class="grid grid-cols-3 border-b border-slate-200 bg-slate-100 p-4 text-xs font-bold tracking-wider text-slate-500 uppercase dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                    >
                        <div>Feature</div>
                        <div
                            class="font-extrabold text-indigo-600 dark:text-indigo-400"
                        >
                            Lumina Analytics
                        </div>
                        <div>Google Analytics (GA4)</div>
                    </div>

                    <div
                        class="divide-y divide-slate-200 text-sm dark:divide-slate-800/60"
                    >
                        <div class="grid grid-cols-3 items-center p-4">
                            <span
                                class="font-medium text-slate-800 dark:text-slate-300"
                                >Script Payload Size</span
                            >
                            <span
                                class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >&lt; 2 KB</span
                            >
                            <span
                                class="font-mono text-slate-500 dark:text-slate-400"
                                >45+ KB</span
                            >
                        </div>
                        <div class="grid grid-cols-3 items-center p-4">
                            <span
                                class="font-medium text-slate-800 dark:text-slate-300"
                                >Cookies Used</span
                            >
                            <span
                                class="flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                <CheckCircle2
                                    class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                />
                                None (0 cookies)
                            </span>
                            <span
                                class="flex items-center gap-1 text-slate-500 dark:text-slate-400"
                            >
                                <XCircle
                                    class="h-4 w-4 text-rose-500 dark:text-rose-400"
                                />
                                Multiple Persistent Cookies
                            </span>
                        </div>
                        <div class="grid grid-cols-3 items-center p-4">
                            <span
                                class="font-medium text-slate-800 dark:text-slate-300"
                                >GDPR Cookie Banner</span
                            >
                            <span
                                class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >NOT Required</span
                            >
                            <span
                                class="font-mono text-slate-500 dark:text-slate-400"
                                >Strictly Required</span
                            >
                        </div>
                        <div class="grid grid-cols-3 items-center p-4">
                            <span
                                class="font-medium text-slate-800 dark:text-slate-300"
                                >Data Ownership</span
                            >
                            <span
                                class="font-bold text-indigo-600 dark:text-indigo-400"
                                >100% Self-Hosted</span
                            >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Third-Party Server (Google)</span
                            >
                        </div>
                        <div class="grid grid-cols-3 items-center p-4">
                            <span
                                class="font-medium text-slate-800 dark:text-slate-300"
                                >Laravel Integration</span
                            >
                            <span
                                class="font-bold text-indigo-600 dark:text-indigo-400"
                                >Native Package & Inertia</span
                            >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Generic Script</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mx-auto mt-28 max-w-4xl text-left">
                <div class="mb-12 text-center">
                    <HelpCircle
                        class="mx-auto mb-2 h-8 w-8 text-indigo-600 dark:text-indigo-400"
                    />
                    <h2
                        class="text-3xl font-extrabold text-slate-900 dark:text-white"
                    >
                        Frequently Asked Questions
                    </h2>
                </div>

                <div class="space-y-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-xs backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Does Lumina require a GDPR consent banner on my
                            site?
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            No! Lumina sets no cookies and stores only opaque
                            random IDs in localStorage / sessionStorage — never
                            personal data. Raw IPs are never saved; when
                            client-side IDs are unavailable, an irreversible
                            stable-salt hash is used instead. That keeps Lumina
                            100% GDPR, CCPA, and PECR compliant without annoying
                            cookie consent banners.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-xs backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Can I track custom events and conversion goals?
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            Yes! You can fire custom events directly from
                            JavaScript:
                            <code
                                class="font-mono text-xs text-indigo-600 dark:text-indigo-300"
                                >window.lumina('newsletter_signup', { plan:
                                'pro' })</code
                            >. You can define conversion goals on path views or
                            custom events to monitor completion rates.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-xs backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <h3
                            class="mb-2 text-lg font-bold text-slate-900 dark:text-white"
                        >
                            How do I export my analytics data?
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            You can export raw event logs or aggregated metrics
                            into CSV or JSON formats at any time directly from
                            your dashboard or via API endpoints.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="relative z-10 flex flex-col items-center justify-center gap-2 border-t border-slate-200 py-8 text-center text-xs text-slate-500 sm:flex-row dark:border-slate-900"
        >
            <p>Built with ❤️ for the Laravel Community.</p>
            <span class="hidden sm:inline">•</span>
            <a
                href="https://github.com/syofyanzuhad/lumina"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-1.5 font-medium underline decoration-slate-300 underline-offset-4 transition-colors hover:text-indigo-600 dark:decoration-slate-700 dark:hover:text-indigo-400"
            >
                <svg
                    class="h-3.5 w-3.5 fill-current text-slate-600 dark:text-slate-400"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                    ></path>
                </svg>
                GitHub Repository (MIT License)
            </a>
        </footer>
    </div>
</template>
