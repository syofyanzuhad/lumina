<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import SiteApiTokenCard from '@/components/sites/SiteApiTokenCard.vue';
import SiteDangerZoneCard from '@/components/sites/SiteDangerZoneCard.vue';
import SiteGoalsCard from '@/components/sites/SiteGoalsCard.vue';
import SiteShareCard from '@/components/sites/SiteShareCard.vue';
import SiteSnippetCard from '@/components/sites/SiteSnippetCard.vue';

const props = defineProps<{
    site: {
        id: number;
        domain: string;
        is_public?: boolean;
        share_token?: string | null;
        has_password?: boolean;
        api_token?: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Sites',
                href: '/sites',
            },
            {
                title: 'Site Details',
                href: '',
            },
        ],
    },
});
</script>

<template>
    <Head :title="site.domain" />

    <div
        class="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4 md:p-6 lg:p-8"
    >
        <Heading
            :title="site.domain"
            description="Install the tracking snippet to start collecting data."
        />

        <div class="max-w-4xl space-y-8">
            <SiteSnippetCard :domain="site.domain" />

            <SiteShareCard :site="site" />

            <SiteApiTokenCard :api-token="site.api_token" />

            <SiteGoalsCard :site-id="site.id" />

            <SiteDangerZoneCard :site="site" />
        </div>
    </div>
</template>
