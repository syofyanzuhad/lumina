<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Copy, Check } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    site: {
        id: number;
        domain: string;
    };
}>();

const snippet = computed(() => {
    // We will assume the tracker will be available at /js/script.js on this dashboard's domain.
    const origin = window.location.origin;
    return `<script defer data-domain="${props.site.domain}" src="${origin}/js/script.js"><\/script>`;
});

const copied = ref(false);

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(snippet.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy', err);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Sites', href: '/sites' }, { title: site.domain, href: `/sites/${site.id}` }]">
        <Head :title="site.domain" />

        <div class="px-4 py-6 md:px-8 max-w-3xl mx-auto space-y-6 w-full">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">{{ site.domain }}</h2>
                <p class="text-muted-foreground">Install the tracking snippet to start collecting data.</p>
            </div>

            <div class="bg-card border rounded-lg overflow-hidden">
                <div class="p-6 space-y-4">
                    <h3 class="text-lg font-medium">Tracking Snippet</h3>
                    <p class="text-sm text-muted-foreground">
                        Paste this snippet in the <code>&lt;head&gt;</code> of your website. It is designed to be lightweight, privacy-friendly, and completely cookie-free.
                    </p>

                    <div class="relative mt-4">
                        <pre class="bg-muted p-4 rounded-md overflow-x-auto text-sm"><code>{{ snippet }}</code></pre>
                        <Button 
                            size="icon" 
                            variant="secondary" 
                            class="absolute top-2 right-2 h-8 w-8" 
                            @click="copyToClipboard"
                        >
                            <Check v-if="copied" class="h-4 w-4 text-green-500" />
                            <Copy v-else class="h-4 w-4" />
                            <span class="sr-only">Copy snippet</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
