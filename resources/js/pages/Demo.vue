<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, ShieldCheck, Sparkles, ArrowRight } from '@lucide/vue';
import AnalyticsDashboard from '@/components/analytics/AnalyticsDashboard.vue';

interface SiteItem {
    id?: number;
    domain: string;
    share_token?: string;
}

defineProps<{
    site: SiteItem;
    period: string;
    overview: any;
    user?: any;
}>();

defineOptions({
    layout: null as any,
});
</script>

<template>
    <Head title="Live Public Demo — Lumina Analytics" />

    <div
        class="relative flex min-h-screen flex-col overflow-hidden bg-slate-950 font-sans text-slate-100 antialiased"
    >
        <!-- Top Glow Orbs -->
        <div
            class="pointer-events-none absolute top-0 left-1/2 h-96 w-full max-w-7xl -translate-x-1/2 rounded-full bg-indigo-500/10 blur-[120px]"
        ></div>
        <div
            class="pointer-events-none absolute top-40 right-10 h-96 w-96 rounded-full bg-purple-500/10 blur-[100px]"
        ></div>

        <!-- Navigation Header -->
        <header
            class="sticky top-0 z-30 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/20 text-indigo-400"
                    >
                        <Eye class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-lg font-black tracking-tight text-transparent"
                                >Lumina</span
                            >
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-400"
                            >
                                <ShieldCheck class="h-3 w-3" /> Live Public Demo
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">
                            Privacy-first, lightweight web analytics platform
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs font-medium">
                    <Link
                        v-if="user"
                        href="/dashboard"
                        class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-500"
                    >
                        Go to My Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-3 py-1.5 text-slate-300 transition-colors hover:text-white"
                            >Log in</Link
                        >
                        <Link
                            href="/register"
                            class="flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-500"
                        >
                            Get Started Free <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Demo Content -->
        <main
            class="relative z-10 mx-auto w-full max-w-7xl flex-1 space-y-8 p-4 sm:p-6"
        >
            <AnalyticsDashboard
                :baseUrl="`/share/${site.share_token || 'demo'}`"
                :site="site"
                :period="period"
                activeTab="overview"
                :overview="overview"
                :showLive="true"
                :showExport="false"
                :showEventsTab="false"
                :canFilter="true"
                :canExpand="true"
                :availablePeriods="['7d', '30d']"
            />

            <!-- Bottom CTA Banner -->
            <div
                class="relative space-y-4 overflow-hidden rounded-2xl border border-indigo-500/30 bg-gradient-to-r from-indigo-950/60 via-slate-900/80 to-purple-950/60 p-8 text-center shadow-2xl"
            >
                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-400"
                >
                    <Sparkles class="h-3.5 w-3.5" /> Ready for lightweight,
                    cookie-free analytics?
                </div>
                <h2
                    class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl"
                >
                    Start tracking your application in under 5 minutes
                </h2>
                <p class="mx-auto max-w-xl text-sm text-slate-400">
                    No cookies required. Under 3KB tracking script. Full data
                    ownership with self-hosted Laravel package or cloud
                    dashboard.
                </p>
                <div
                    class="flex flex-col items-center justify-center gap-3 pt-2 sm:flex-row"
                >
                    <Link
                        href="/register"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 transition-all hover:bg-indigo-500 sm:w-auto"
                    >
                        Create Free Account <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </main>

        <footer
            class="border-t border-slate-800/60 py-6 text-center text-xs text-slate-500"
        >
            <p>
                © {{ new Date().getFullYear() }} Lumina Analytics. Built for
                modern web applications.
            </p>
        </footer>
    </div>
</template>
