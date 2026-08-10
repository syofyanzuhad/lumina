<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, ShieldCheck, Sparkles, ArrowRight, Lock } from '@lucide/vue';
import AnalyticsDashboard from '@/components/analytics/AnalyticsDashboard.vue';

interface SiteItem {
    id?: number;
    domain: string;
    share_token?: string;
}

const props = defineProps<{
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

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans antialiased relative overflow-hidden">
        <!-- Top Glow Orbs -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-indigo-500/10 blur-[120px] pointer-events-none rounded-full"></div>
        <div class="absolute top-40 right-10 w-96 h-96 bg-purple-500/10 blur-[100px] pointer-events-none rounded-full"></div>

        <!-- Navigation Header -->
        <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center border border-indigo-500/30">
                        <Eye class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-black text-lg tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">Lumina</span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <ShieldCheck class="h-3 w-3" /> Live Public Demo
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Privacy-first, lightweight web analytics platform</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs font-medium">
                    <Link v-if="user" href="/dashboard" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all shadow-md shadow-indigo-600/20">
                        Go to My Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="px-3 py-1.5 text-slate-300 hover:text-white transition-colors">Log in</Link>
                        <Link href="/register" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all shadow-md shadow-indigo-600/20 flex items-center gap-1.5">
                            Get Started Free <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Demo Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-8 relative z-10">
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
            <div class="rounded-2xl border border-indigo-500/30 bg-gradient-to-r from-indigo-950/60 via-slate-900/80 to-purple-950/60 p-8 text-center space-y-4 relative overflow-hidden shadow-2xl">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold">
                    <Sparkles class="h-3.5 w-3.5" /> Ready for lightweight, cookie-free analytics?
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Start tracking your application in under 5 minutes</h2>
                <p class="text-slate-400 max-w-xl mx-auto text-sm">
                    No cookies required. Under 3KB tracking script. Full data ownership with self-hosted Laravel package or cloud dashboard.
                </p>
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link href="/register" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm transition-all shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2">
                        Create Free Account <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </main>

        <footer class="border-t border-slate-800/60 py-6 text-center text-xs text-slate-500">
            <p>© {{ new Date().getFullYear() }} Lumina Analytics. Built for modern web applications.</p>
        </footer>
    </div>
</template>
