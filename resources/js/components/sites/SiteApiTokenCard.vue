<script setup lang="ts">
import { Copy } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    apiToken?: string | null;
}>();

const copyApiToken = async () => {
    try {
        await navigator.clipboard.writeText(props.apiToken || '');
        toast.success('API token copied to clipboard');
    } catch (err) {
        console.error('Failed to copy API token', err);
    }
};
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
    >
        <div class="space-y-4 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium">Public Stats API Token</h3>
                    <p class="text-sm text-muted-foreground">
                        Use this API token to fetch stats programmatically via
                        <code>/api/v1/stats</code>.
                    </p>
                </div>
            </div>

            <div v-if="apiToken" class="space-y-4">
                <div class="flex gap-2">
                    <Input
                        :model-value="apiToken"
                        readonly
                        class="flex-1 bg-muted/80 font-mono text-sm font-semibold text-foreground select-all"
                    />
                    <Button variant="secondary" @click="copyApiToken">
                        <Copy class="mr-2 h-4 w-4" />
                        Copy
                    </Button>
                </div>
            </div>
            <div v-else class="text-sm text-muted-foreground">
                No API token generated yet.
            </div>
        </div>
    </div>
</template>
