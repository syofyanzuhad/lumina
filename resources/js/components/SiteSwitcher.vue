<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const page = usePage();
const sites = computed(() => page.props.sites as { id: number; domain: string }[]);

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
            </SelectContent>
        </Select>
    </div>
</template>
