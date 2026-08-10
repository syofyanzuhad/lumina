<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update } from '@/actions/App/Http/Controllers/ActiveSiteController';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const page = usePage();
const sites = computed(() => page.props.sites as { id: number; domain: string }[]);
const activeSiteId = computed({
    get: () => page.props.active_site_id ? String(page.props.active_site_id) : '',
    set: (value: string) => {
        if (value) {
            router.put(update.url(), {
                site_id: Number(value),
            }, {
                preserveState: false,
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
