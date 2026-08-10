<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Lock, ShieldCheck, Globe, Copy, Check } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AnalyticsDashboard from '@/components/analytics/AnalyticsDashboard.vue';

interface SiteItem {
    id?: number;
    domain: string;
    share_token?: string;
    has_password?: boolean;
}

const props = defineProps<{
    site: SiteItem;
    requiresPassword?: boolean;
    passwordError?: string;
    period: string;
    activeTab?: string;
    filters?: Record<string, string>;
    // Immediate KPI props
    total_pageviews?: number;
    unique_visitors?: number;
    current_visitors?: number;
    bounce_rate?: number;
    avg_duration?: number;
    daily_pageviews?: any[];
    // Deferred breakdown props
    top_pages?: any[];
    top_referrers?: any[];
    device_breakdown?: any[];
    top_browsers?: any[];
    top_os?: any[];
    top_countries?: any[];
    utm_campaigns?: any[];
    custom_events?: any[];
    goals?: any[];
    // Custom Events Tab
    selectedEvent?: string | null;
    selectedPropertyKey?: string | null;
    custom_event_summary?: any;
    custom_events_list?: any[];
    custom_event_timeline?: any[];
    custom_event_property_keys?: string[];
    custom_event_property_breakdown?: any[];
    custom_event_logs?: any[];
}>();

defineOptions({
    layout: null,
});

const passwordForm = useForm({
    password: '',
});

const submitPassword = () => {
    if (!props.site.share_token) return;
    passwordForm.post(`/share/${props.site.share_token}/password`, {
        preserveScroll: true,
    });
};

const windowOrigin = typeof window !== 'undefined' ? window.location.origin : '';
const copiedPublicUrl = ref(false);

const copyPublicShareUrl = async () => {
    if (!props.site.share_token) return;
    const url = `${windowOrigin}/share/${props.site.share_token}`;
    try {
        await navigator.clipboard.writeText(url);
        copiedPublicUrl.value = true;
        setTimeout(() => {
            copiedPublicUrl.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy share link', err);
    }
};
</script>

<template>
    <Head :title="requiresPassword ? `Protected Analytics — ${site.domain}` : `${site.domain} — Public Analytics`" />

    <div class="min-h-screen bg-background text-foreground flex flex-col font-sans antialiased">
        <!-- Mode 1: Password Required View -->
        <div v-if="requiresPassword" class="flex-1 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-card border border-sidebar-border/80 dark:border-sidebar-border rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
                <div class="text-center space-y-2">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <Lock class="h-7 w-7" />
                    </div>
                    <h1 class="text-xl font-bold text-foreground">Protected Analytics</h1>
                    <p class="text-xs text-muted-foreground">
                        Enter password to view public analytics for <span class="font-bold text-foreground">{{ site.domain }}</span>
                    </p>
                </div>

                <form @submit.prevent="submitPassword" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label class="text-xs">Dashboard Password</Label>
                        <Input
                            type="password"
                            v-model="passwordForm.password"
                            placeholder="Enter password..."
                            class="h-10 text-sm"
                            :class="{ 'border-destructive': passwordError }"
                            required
                        />
                        <p v-if="passwordError" class="text-xs text-destructive font-medium">{{ passwordError }}</p>
                    </div>

                    <Button type="submit" class="w-full h-10 font-bold text-xs uppercase tracking-wider" :disabled="passwordForm.processing">
                        Unlock Analytics
                    </Button>
                </form>
            </div>
        </div>

        <!-- Mode 2: Read-Only Public Dashboard -->
        <div v-else class="flex-1 flex flex-col">
            <!-- Top Header -->
            <header class="border-b border-sidebar-border/80 dark:border-sidebar-border bg-card/60 backdrop-blur-md sticky top-0 z-30">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <Globe class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg font-bold text-foreground tracking-tight">{{ site.domain }}</h1>
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <ShieldCheck class="h-3 w-3" /> Public Share
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">Live public analytics view powered by Lumina Core</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <button
                            @click="copyPublicShareUrl"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-muted/80 hover:bg-muted text-muted-foreground hover:text-foreground font-mono transition-colors border border-sidebar-border/60"
                            title="Copy share link"
                        >
                            <component :is="copiedPublicUrl ? Check : Copy" class="h-3.5 w-3.5 text-emerald-500" />
                            <span>{{ copiedPublicUrl ? 'Copied!' : 'Copy Link' }}</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Dashboard Body -->
            <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6">
                <AnalyticsDashboard
                    :baseUrl="`/share/${site.share_token}`"
                    :breakdownUrl="`/share/${site.share_token}/breakdown`"
                    :site="site"
                    :period="period"
                    :activeTab="activeTab"
                    :filters="filters"
                    :total_pageviews="total_pageviews"
                    :unique_visitors="unique_visitors"
                    :current_visitors="current_visitors"
                    :bounce_rate="bounce_rate"
                    :avg_duration="avg_duration"
                    :daily_pageviews="daily_pageviews"
                    :top_pages="top_pages"
                    :top_referrers="top_referrers"
                    :device_breakdown="device_breakdown"
                    :top_browsers="top_browsers"
                    :top_os="top_os"
                    :top_countries="top_countries"
                    :utm_campaigns="utm_campaigns"
                    :custom_events="custom_events"
                    :goals="goals"
                    :showLive="false"
                    :showExport="false"
                    :showEventsTab="true"
                    :canFilter="true"
                    :canExpand="true"
                    :availablePeriods="['today', '7d', '30d', 'custom']"
                    :selectedEvent="selectedEvent"
                    :selectedPropertyKey="selectedPropertyKey"
                    :custom_event_summary="custom_event_summary"
                    :custom_events_list="custom_events_list"
                    :custom_event_timeline="custom_event_timeline"
                    :custom_event_property_keys="custom_event_property_keys"
                    :custom_event_property_breakdown="custom_event_property_breakdown"
                    :custom_event_logs="custom_event_logs"
                />
            </main>

            <!-- Powered by Lumina Footer -->
            <footer class="border-t border-sidebar-border/40 py-6 text-center text-xs text-muted-foreground">
                <p>Powered by <span class="font-bold text-foreground">Lumina Analytics</span></p>
            </footer>
        </div>
    </div>
</template>
