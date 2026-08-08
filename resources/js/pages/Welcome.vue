<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, Users, Shield, Zap, Package, ArrowRight, BarChart3, Globe, Code, Sparkles, CheckCircle2, Copy, Check, Terminal, ShieldCheck, HelpCircle, XCircle, Sun, Moon, Target, Download, Share2, Smartphone } from '@lucide/vue';
import { dashboard, login, register } from '@/routes';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    const nextTheme = appearance.value === 'dark' ? 'light' : 'dark';
    updateAppearance(nextTheme);
};

const customDomain = ref('mywebsite.com');
const isCopied = ref(false);

const copySnippet = () => {
    const d = customDomain.value || 'mywebsite.com';
    const snippet = `<` + `script defer data-domain="${d}" src="https://uselumina.laravel.cloud/js/script.js"><` + `/script>`;
    navigator.clipboard.writeText(snippet);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
};
</script>

<template>
    <Head title="Lumina — Lightweight Web Analytics for Laravel">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-['Outfit',sans-serif] selection:bg-indigo-500 selection:text-white relative overflow-hidden transition-colors duration-300">
        <!-- Background Gradient Orbs -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] bg-gradient-to-tr from-indigo-500/20 via-violet-500/20 to-amber-500/10 blur-[120px] pointer-events-none rounded-full dark:from-indigo-600/20 dark:via-violet-600/20"></div>
        <div class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-indigo-500/10 dark:bg-indigo-900/10 blur-[140px] pointer-events-none rounded-full"></div>

        <!-- Navigation Header -->
        <header class="relative z-10 max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 p-0.5 shadow-lg shadow-indigo-500/30">
                    <div class="h-full w-full bg-white dark:bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <BarChart3 class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
                <span class="text-xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-slate-700 to-indigo-600 dark:from-white dark:via-slate-200 dark:to-indigo-300">
                    Lumina
                </span>
                <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                    Analytics
                </span>
            </div>

            <nav class="flex items-center gap-3">
                <!-- GitHub Repository Link -->
                <a
                    href="https://github.com/syofyanzuhad/lumina"
                    target="_blank"
                    rel="noopener"
                    class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all shadow-xs backdrop-blur-md flex items-center justify-center"
                    title="View Source on GitHub"
                >
                    <svg class="h-4 w-4 fill-current text-slate-700 dark:text-slate-300" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
                    </svg>
                </a>

                <!-- Darkmode Toggle Button -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white transition-all shadow-xs backdrop-blur-md cursor-pointer"
                    :title="appearance === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                >
                    <Sun v-if="appearance === 'dark'" class="h-4 w-4 text-amber-400" />
                    <Moon v-else class="h-4 w-4 text-indigo-600" />
                </button>

                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 hover:shadow-indigo-500/40 transition-all flex items-center gap-2"
                >
                    Go to Dashboard
                    <ArrowRight class="h-4 w-4" />
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-semibold text-sm shadow-lg shadow-indigo-600/25 hover:from-indigo-500 hover:to-violet-500 transition-all flex items-center gap-2"
                    >
                        Get Started Free
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="relative z-10 max-w-7xl mx-auto px-6 pt-12 pb-24 text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 dark:bg-slate-900/90 border border-indigo-200 dark:border-slate-800 text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-8 backdrop-blur-md shadow-xs">
                <Sparkles class="h-3.5 w-3.5 text-amber-500 dark:text-amber-400 animate-pulse" />
                <span>Latest Update: Conversion Goals, CSV/JSON Data Exports & Public Share Password Protection</span>
            </div>

            <!-- Main Heading -->
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-slate-900 dark:text-white max-w-4xl mx-auto leading-[1.1]">
                Lightweight Analytics, <br />
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 via-violet-600 to-amber-500 dark:from-indigo-400 dark:via-violet-300 dark:to-amber-300">
                    Zero Infrastructure Hassle.
                </span>
            </h1>

            <p class="mt-6 text-lg sm:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                Self-hosted web analytics for your Laravel applications. Track pageviews, custom events & goal conversions with a minified <strong class="text-slate-800 dark:text-slate-200">&lt; 2KB script</strong>, 100% cookie-free privacy, and password-protected sharing.
            </p>

            <!-- CTA Buttons -->
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-[length:200%_auto] hover:bg-right text-white font-bold text-base shadow-xl shadow-indigo-600/30 transition-all duration-500 flex items-center justify-center gap-2"
                >
                    Launch Dashboard
                    <ArrowRight class="h-5 w-5" />
                </Link>
                <Link
                    href="/demo"
                    class="w-full sm:w-auto px-7 py-4 rounded-xl bg-white dark:bg-slate-900/90 border border-indigo-200 dark:border-indigo-500/30 hover:border-indigo-500 text-slate-800 dark:text-white font-semibold text-base backdrop-blur-md transition-all flex items-center justify-center gap-2 shadow-sm hover:shadow-md"
                >
                    <BarChart3 class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    View Live Demo
                </Link>
                <a
                    href="https://github.com/syofyanzuhad/lumina"
                    target="_blank"
                    rel="noopener"
                    class="w-full sm:w-auto px-7 py-4 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-semibold text-base backdrop-blur-md transition-all flex items-center justify-center gap-2 shadow-xs"
                >
                    <svg class="h-5 w-5 fill-current text-slate-700 dark:text-slate-300" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
                    </svg>
                    Source Code
                </a>
            </div>

            <!-- Metric Highlights Banner -->
            <div class="mt-14 pt-10 border-t border-slate-200 dark:border-slate-900 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                <div class="p-4 rounded-xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800/60 backdrop-blur-xs">
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">&lt; 2 KB</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Gzipped Tracker Payload</div>
                </div>
                <div class="p-4 rounded-xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800/60 backdrop-blur-xs">
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">100%</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Cookie-Free Privacy</div>
                </div>
                <div class="p-4 rounded-xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800/60 backdrop-blur-xs">
                    <div class="text-2xl font-black text-violet-600 dark:text-violet-400">Goals</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Conversion Tracking</div>
                </div>
                <div class="p-4 rounded-xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800/60 backdrop-blur-xs">
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400">CSV & JSON</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Data Streaming Export</div>
                </div>
            </div>

            <!-- Live Mock Dashboard Preview -->
            <div class="mt-16 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/80 p-6 shadow-2xl backdrop-blur-xl max-w-5xl mx-auto text-left relative overflow-hidden">
                <div class="absolute top-0 right-0 w-80 h-80 bg-indigo-500/10 blur-[90px] pointer-events-none rounded-full"></div>
                
                <!-- Mock Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800/80">
                    <div class="flex items-center gap-3">
                        <div class="h-3 w-3 rounded-full bg-emerald-500 animate-ping"></div>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">yourdomain.com</span>
                        <span class="text-xs font-mono text-slate-500">Live Traffic Preview</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white">Last 30 Days</span>
                    </div>
                </div>

                <!-- Mock Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 p-5">
                        <div class="flex justify-between items-center text-xs text-slate-500 dark:text-slate-400 font-semibold">
                            <span>TOTAL PAGEVIEWS</span>
                            <Eye class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-2">128,490</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 p-5">
                        <div class="flex justify-between items-center text-xs text-slate-500 dark:text-slate-400 font-semibold">
                            <span>UNIQUE VISITORS</span>
                            <Users class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-2">42,110</div>
                    </div>
                </div>

                <!-- Mock Trend Bar Chart -->
                <div class="mt-6 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-3">DAILY PAGEVIEW TRENDS</div>
                    <div class="flex items-end gap-1.5 h-24">
                        <div v-for="h in [30, 45, 60, 40, 75, 90, 65, 80, 100, 70, 85, 95, 110, 80, 90]" :key="h" class="flex-1 bg-gradient-to-t from-indigo-600 to-indigo-400 rounded-t h-full" :style="{ height: `${h}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- What's New in v1.1 Feature Grid -->
            <div class="mt-28 text-left max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <span class="text-xs font-mono uppercase tracking-widest text-indigo-600 dark:text-indigo-400 font-bold">Latest Release</span>
                    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1">What's New in Lumina</h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mt-2">Major updates focused on conversion analytics, data export portability, enhanced sharing, and device detection.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Feature Update 1 -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md hover:border-indigo-500/50 transition-all shadow-sm">
                        <div class="h-12 w-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
                            <Target class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Conversion Goals</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">
                            Define path pageviews or custom events as goals to track conversion counts & percentages seamlessly across custom timeframes.
                        </p>
                    </div>

                    <!-- Feature Update 2 -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md hover:border-indigo-500/50 transition-all shadow-sm">
                        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
                            <Download class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">CSV & JSON Exports</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">
                            Stream raw events or aggregated metrics directly into CSV or JSON files for custom BI tools and external reporting.
                        </p>
                    </div>

                    <!-- Feature Update 3 -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md hover:border-indigo-500/50 transition-all shadow-sm">
                        <div class="h-12 w-12 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center mb-4">
                            <Share2 class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Password Share Links</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">
                            Share live dashboards with clients via secure unique share tokens with optional bcrypt password authentication.
                        </p>
                    </div>

                    <!-- Feature Update 4 -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md hover:border-indigo-500/50 transition-all shadow-sm">
                        <div class="h-12 w-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-4">
                            <Smartphone class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Detailed Device Tech</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">
                            Granular detection for browser versions, operating system releases, and device breakdown down to exact builds.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Core Feature Cards -->
            <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-8 text-left max-w-6xl mx-auto">
                <!-- Feature 1 -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-8 backdrop-blur-md hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-xs">
                    <div class="h-12 w-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-6">
                        <Zap class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Ultra-Fast & Asynchronous</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Vanilla JS tracker bundled under 592 bytes gzipped. Zero dependencies, non-blocking asynchronous execution, and zero page latency impact.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-8 backdrop-blur-md hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-xs">
                    <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-6">
                        <Shield class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">GDPR Privacy by Default</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        IP addresses are never saved directly. Visitor hash uses a rotating 24-hour daily salt (<code class="text-indigo-600 dark:text-indigo-300">sha256</code>) preventing cross-day user tracking.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/40 p-8 backdrop-blur-md hover:border-slate-300 dark:hover:border-slate-700 transition-all shadow-xs">
                    <div class="h-12 w-12 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center mb-6">
                        <Package class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Dual Mode Integration</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Use Lumina as a standalone Vue 3 / Inertia web application or embed <code class="text-indigo-600 dark:text-indigo-300">lumina/core</code> package directly inside your existing Laravel app layout.
                    </p>
                </div>
            </div>

            <!-- Interactive Script Snippet Generator -->
            <div class="mt-28 rounded-3xl border border-indigo-500/30 bg-white/90 dark:bg-slate-900/80 p-8 sm:p-12 shadow-2xl backdrop-blur-xl text-left max-w-4xl mx-auto relative overflow-hidden">
                <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-600/10 blur-[100px] pointer-events-none rounded-full"></div>
                
                <div class="flex items-center gap-3 mb-2">
                    <Terminal class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    <span class="text-xs font-mono uppercase tracking-widest text-indigo-600 dark:text-indigo-400 font-bold">Simple 1-Line Setup</span>
                </div>

                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">Generate Your Tracking Snippet</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Enter your site's domain to generate your custom Lumina tracking snippet instantly.</p>

                <div class="mt-6 space-y-4">
                    <div>
                        <label for="domain-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Target Website Domain</label>
                        <input
                            id="domain-input"
                            v-model="customDomain"
                            type="text"
                            placeholder="mywebsite.com"
                            class="w-full max-w-md rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-4 py-2.5 text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-hidden"
                        />
                    </div>

                    <div class="relative rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-900 text-indigo-300 p-4 font-mono text-xs overflow-x-auto flex items-center justify-between gap-4 shadow-inner">
                        <code class="truncate">&lt;script defer data-domain="{{ customDomain || 'mywebsite.com' }}" src="https://uselumina.laravel.cloud/js/script.js"&gt;&lt;/script&gt;</code>
                        <button
                            type="button"
                            @click="copySnippet"
                            class="shrink-0 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center gap-1.5 transition-all shadow-md cursor-pointer"
                        >
                            <component :is="isCopied ? Check : Copy" class="h-3.5 w-3.5" />
                            <span>{{ isCopied ? 'Copied!' : 'Copy Code' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lumina vs Traditional Analytics Comparison Table -->
            <div class="mt-28 text-left max-w-5xl mx-auto">
                <div class="text-center mb-12">
                    <span class="text-xs font-mono uppercase tracking-widest text-emerald-600 dark:text-emerald-400 font-bold">Why Switch?</span>
                    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1">Lumina vs. Traditional Analytics</h2>
                </div>

                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/60 overflow-hidden backdrop-blur-md shadow-xl">
                    <div class="grid grid-cols-3 bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <div>Feature</div>
                        <div class="text-indigo-600 dark:text-indigo-400 font-extrabold">Lumina Analytics</div>
                        <div>Google Analytics (GA4)</div>
                    </div>

                    <div class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                        <div class="grid grid-cols-3 p-4 items-center">
                            <span class="font-medium text-slate-800 dark:text-slate-300">Script Payload Size</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">&lt; 2 KB</span>
                            <span class="text-slate-500 dark:text-slate-400 font-mono">45+ KB</span>
                        </div>
                        <div class="grid grid-cols-3 p-4 items-center">
                            <span class="font-medium text-slate-800 dark:text-slate-300">Cookies Used</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" /> None (0 cookies)
                            </span>
                            <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                <XCircle class="h-4 w-4 text-rose-500 dark:text-rose-400" /> Multiple Persistent Cookies
                            </span>
                        </div>
                        <div class="grid grid-cols-3 p-4 items-center">
                            <span class="font-medium text-slate-800 dark:text-slate-300">GDPR Cookie Banner</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">NOT Required</span>
                            <span class="text-slate-500 dark:text-slate-400 font-mono">Strictly Required</span>
                        </div>
                        <div class="grid grid-cols-3 p-4 items-center">
                            <span class="font-medium text-slate-800 dark:text-slate-300">Data Ownership</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">100% Self-Hosted</span>
                            <span class="text-slate-500 dark:text-slate-400">Third-Party Server (Google)</span>
                        </div>
                        <div class="grid grid-cols-3 p-4 items-center">
                            <span class="font-medium text-slate-800 dark:text-slate-300">Laravel Integration</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">Native Package & Inertia</span>
                            <span class="text-slate-500 dark:text-slate-400">Generic Script</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-28 text-left max-w-4xl mx-auto">
                <div class="text-center mb-12">
                    <HelpCircle class="h-8 w-8 text-indigo-600 dark:text-indigo-400 mx-auto mb-2" />
                    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white">Frequently Asked Questions</h2>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md shadow-xs">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Does Lumina require a GDPR consent banner on my site?</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                            No! Lumina does not store cookies or personal identifiers in local storage. IPs are hashed daily using a 24-hour rotating salt, making it 100% GDPR, CCPA, and PECR compliant out of the box without annoying cookie consent banners.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md shadow-xs">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Can I track custom events and conversion goals?</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                            Yes! You can fire custom events directly from JavaScript: <code class="text-indigo-600 dark:text-indigo-300 font-mono text-xs">window.lumina('newsletter_signup', { plan: 'pro' })</code>. You can define conversion goals on path views or custom events to monitor completion rates.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/40 p-6 backdrop-blur-md shadow-xs">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">How do I export my analytics data?</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                            You can export raw event logs or aggregated metrics into CSV or JSON formats at any time directly from your dashboard or via API endpoints.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-slate-200 dark:border-slate-900 py-8 text-center text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-center gap-2">
            <p>Built with ❤️ for the Laravel Community.</p>
            <span class="hidden sm:inline">•</span>
            <a
                href="https://github.com/syofyanzuhad/lumina"
                target="_blank"
                rel="noopener"
                class="hover:text-indigo-600 dark:hover:text-indigo-400 underline decoration-slate-300 dark:decoration-slate-700 underline-offset-4 transition-colors font-medium flex items-center gap-1.5"
            >
                <svg class="h-3.5 w-3.5 fill-current text-slate-600 dark:text-slate-400" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"></path>
                </svg>
                GitHub Repository (MIT License)
            </a>
        </footer>
    </div>
</template>
