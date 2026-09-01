<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    ExternalLink,
    Lock,
    RefreshCw,
    Share2,
    Unlock,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    site: {
        id: number;
        domain: string;
        is_public?: boolean;
        share_token?: string | null;
        has_password?: boolean;
    };
}>();

const shareForm = useForm({
    is_public: props.site.is_public ?? false,
    share_password: '',
    clear_password: false,
});

watch(
    () => props.site.is_public,
    (val) => {
        shareForm.is_public = val ?? false;
    },
);

const copiedShareUrl = ref(false);

const shareUrl = computed(() => {
    if (!props.site.share_token) {
        return '';
    }

    return `${window.location.origin}/share/${props.site.share_token}`;
});

const copyShareUrl = async () => {
    if (!shareUrl.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copiedShareUrl.value = true;
        toast.success('Share link copied to clipboard');
        setTimeout(() => {
            copiedShareUrl.value = false;
        }, 2000);
    } catch {
        toast.error('Failed to copy share link');
    }
};

const togglePublicSharing = () => {
    shareForm.is_public = !shareForm.is_public;
    shareForm.put(`/sites/${props.site.id}/share`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                shareForm.is_public
                    ? 'Public dashboard enabled'
                    : 'Public dashboard disabled',
            );
        },
        onError: () => {
            shareForm.is_public = props.site.is_public ?? false;
            toast.error('Failed to update public sharing setting');
        },
    });
};

const saveSharePassword = () => {
    if (!shareForm.share_password) {
        return;
    }

    shareForm.clear_password = false;
    shareForm.put(`/sites/${props.site.id}/share`, {
        preserveScroll: true,
        onSuccess: () => {
            shareForm.share_password = '';
            toast.success('Share password set successfully');
        },
        onError: () => {
            toast.error('Failed to set share password');
        },
    });
};

const removeSharePassword = () => {
    shareForm.clear_password = true;
    shareForm.put(`/sites/${props.site.id}/share`, {
        preserveScroll: true,
        onSuccess: () => {
            shareForm.clear_password = false;
            shareForm.share_password = '';
            toast.success('Share password removed');
        },
        onError: () => {
            shareForm.clear_password = false;
            toast.error('Failed to remove share password');
        },
    });
};

const isRegeneratingToken = ref(false);
const regenerateToken = () => {
    router.post(
        `/sites/${props.site.id}/share/regenerate`,
        {},
        {
            preserveScroll: true,
            onStart: () => {
                isRegeneratingToken.value = true;
            },
            onFinish: () => {
                isRegeneratingToken.value = false;
            },
            onSuccess: () => {
                toast.success('Share link regenerated successfully');
            },
            onError: () => {
                toast.error('Failed to regenerate share link');
            },
        },
    );
};
</script>

<template>
    <!-- Public Sharing Card -->
    <div
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
    >
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Share2 class="h-5 w-5 text-indigo-500" />
                        <h3 class="text-lg font-medium">Public Sharing</h3>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Allow anyone with the link to view this site's read-only
                        analytics dashboard.
                    </p>
                </div>
                <Button
                    type="button"
                    :variant="props.site.is_public ? 'default' : 'outline'"
                    :class="
                        props.site.is_public
                            ? 'bg-indigo-600 text-white hover:bg-indigo-500'
                            : ''
                    "
                    @click="togglePublicSharing"
                    :disabled="shareForm.processing"
                >
                    {{
                        props.site.is_public
                            ? 'Public Enabled'
                            : 'Enable Sharing'
                    }}
                </Button>
            </div>

            <div
                v-if="props.site.is_public && props.site.share_token"
                class="space-y-6 border-t border-sidebar-border/50 pt-4"
            >
                <!-- Share URL Display & Copy -->
                <div class="space-y-2">
                    <Label for="share-url">Share URL</Label>
                    <div class="flex gap-2">
                        <Input
                            id="share-url"
                            :model-value="shareUrl"
                            readonly
                            class="flex-1 bg-muted/80 font-mono text-sm font-semibold text-foreground select-all"
                        />
                        <Button variant="secondary" @click="copyShareUrl">
                            <Check
                                v-if="copiedShareUrl"
                                class="mr-2 h-4 w-4 text-green-500"
                            />
                            <Copy v-else class="mr-2 h-4 w-4" />
                            {{ copiedShareUrl ? 'Copied' : 'Copy' }}
                        </Button>
                        <a
                            :href="`/share/${props.site.share_token}`"
                            target="_blank"
                            class="inline-flex"
                        >
                            <Button
                                variant="outline"
                                type="button"
                                title="View Public Dashboard"
                            >
                                <ExternalLink class="h-4 w-4" />
                            </Button>
                        </a>
                    </div>
                </div>

                <!-- Regenerate Token Button -->
                <div
                    class="flex items-center justify-between rounded-lg border bg-muted/40 p-4"
                >
                    <div>
                        <h4 class="text-sm font-medium">Regenerate Token</h4>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Invalidates old links immediately and generates a
                            new random token.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="regenerateToken"
                        :disabled="isRegeneratingToken"
                    >
                        <RefreshCw
                            :class="[
                                'mr-2 h-4 w-4',
                                { 'animate-spin': isRegeneratingToken },
                            ]"
                        />
                        Regenerate Token
                    </Button>
                </div>

                <!-- Password Protection Section -->
                <div class="space-y-4 border-t border-sidebar-border/50 pt-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Lock
                                v-if="props.site.has_password"
                                class="h-4 w-4 text-emerald-500"
                            />
                            <Unlock
                                v-else
                                class="h-4 w-4 text-muted-foreground"
                            />
                            <h4 class="text-sm font-medium">
                                Password Protection
                            </h4>
                        </div>
                        <span
                            v-if="props.site.has_password"
                            class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            Password Protected
                        </span>
                    </div>

                    <div
                        v-if="props.site.has_password"
                        class="flex items-center justify-between rounded-lg border bg-muted/40 p-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Public access to this dashboard requires a password.
                        </p>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="removeSharePassword"
                            :disabled="shareForm.processing"
                        >
                            Remove Password
                        </Button>
                    </div>

                    <div v-else class="flex gap-2">
                        <Input
                            type="password"
                            v-model="shareForm.share_password"
                            placeholder="Set access password (min 4 chars)"
                            class="max-w-md"
                        />
                        <Button
                            variant="secondary"
                            @click="saveSharePassword"
                            :disabled="
                                shareForm.processing ||
                                !shareForm.share_password
                            "
                        >
                            Set Password
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
