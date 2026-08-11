<script setup lang="ts">
import { usePage, router, Link } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import { Plus } from '@lucide/vue';
import { Select, SelectContent, SelectItem, SelectSeparator, SelectTrigger, SelectValue } from '@/components/ui/select';

const page = usePage();
const sites = computed(() => page.props.sites as { id: number; domain: string }[]);

// Ensure site_id URL parameter is present when visiting dashboard
watchEffect(() => {
    if (page.url.startsWith('/dashboard')) {
        const search = page.url.includes('?') ? page.url.split('?')[1] : '';
        const urlParams = new URLSearchParams(search);
        if (!urlParams.has('site_id') && page.props.active_site_id) {
            urlParams.set('site_id', String(page.props.active_site_id));
            const currentPath = page.url.split('?')[0];
            const newUrl = `${currentPath}?${urlParams.toString()}`;
            router.get(newUrl, {}, { preserveState: true, preserveScroll: true, replace: true });
        }
    }
});

const activeSiteId = computed({
    get: () => {
        // Parse site_id from Inertia's reactive page.url
        const search = page.url.includes('?') ? page.url.split('?')[1] : '';
        const urlParams = new URLSearchParams(search);
        const urlSiteId = urlParams.get('site_id');
        if (urlSiteId) {
            return urlSiteId;
        }
        return page.props.active_site_id ? String(page.props.active_site_id) : '';
    },
    set: (value: string) => {
        if (value) {
            const currentPath = page.url.split('?')[0];
            const search = page.url.includes('?') ? page.url.split('?')[1] : '';
            const urlParams = new URLSearchParams(search);
            urlParams.set('site_id', value);
            const newUrl = `${currentPath}?${urlParams.toString()}`;
            router.get(newUrl, {}, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    },
});
</script>

<template>
    <div v-if="sites && sites.length > 0" class="w-48">
        <Select v-model="activeSiteId">
            <SelectTrigger>
                <SelectValue placeholder="Select a site" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="site in sites" :key="site.id" :value="String(site.id)">
                    {{ site.domain }}
                </SelectItem>
                <SelectSeparator />
                <Link href="/sites/create" class="flex items-center gap-2 px-2 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent rounded-sm cursor-pointer transition-colors">
                    <Plus class="h-3.5 w-3.5" />
                    <span>Add New Site</span>
                </Link>
            </SelectContent>
        </Select>
    </div>
</template>
