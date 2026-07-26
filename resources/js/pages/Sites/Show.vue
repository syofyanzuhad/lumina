<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Copy, Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';

const props = defineProps<{
    site: {
        id: number;
        domain: string;
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
                title: 'Site Details', // Or could use domain, but defineOptions is statically analyzed usually.
                href: '',
            },
        ],
    },
});

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
    <Head :title="site.domain" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <Heading :title="site.domain" description="Install the tracking snippet to start collecting data." />

        <div class="max-w-3xl">
            <div class="bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl overflow-hidden">
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
    </div>
</template>
