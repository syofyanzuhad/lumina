<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    domain: string;
}>();

const snippet = computed(() => {
    const origin = window.location.origin;

    return `<script defer data-domain="${props.domain}" src="${origin}/js/script.js"><\/script>`;
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
    <div
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
    >
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-medium">Tracking Snippet</h3>
            <p class="text-sm text-muted-foreground">
                Paste this snippet in the <code>&lt;head&gt;</code> of your
                website. It is designed to be lightweight, privacy-friendly, and
                completely cookie-free.
            </p>

            <div class="relative mt-4">
                <pre
                    class="overflow-x-auto rounded-md bg-muted p-4 text-sm"
                ><code>{{ snippet }}</code></pre>
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
</template>
