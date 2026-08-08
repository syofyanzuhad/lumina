<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectGroup, SelectItem } from '@/components/ui/select';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Copy, Check, Plus, Pencil, Trash2, Share2, RefreshCw, Lock, Unlock, ExternalLink } from '@lucide/vue';
import { computed, ref, onMounted, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { toast } from 'vue-sonner';

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

const snippet = computed(() => {
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

// Public Sharing Management
const shareForm = useForm({
    is_public: props.site.is_public ?? false,
    share_password: '',
    clear_password: false,
});

watch(() => props.site.is_public, (val) => {
    shareForm.is_public = val ?? false;
});

const copiedShareUrl = ref(false);

const shareUrl = computed(() => {
    if (!props.site.share_token) return '';
    return `${window.location.origin}/share/${props.site.share_token}`;
});

const copyShareUrl = async () => {
    if (!shareUrl.value) return;
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copiedShareUrl.value = true;
        toast.success('Share link copied to clipboard');
        setTimeout(() => {
            copiedShareUrl.value = false;
        }, 2000);
    } catch (err) {
        toast.error('Failed to copy share link');
    }
};

const togglePublicSharing = () => {
    shareForm.is_public = !shareForm.is_public;
    shareForm.put(`/sites/${props.site.id}/share`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(shareForm.is_public ? 'Public dashboard enabled' : 'Public dashboard disabled');
        },
        onError: () => {
            shareForm.is_public = props.site.is_public ?? false;
            toast.error('Failed to update public sharing setting');
        },
    });
};

const saveSharePassword = () => {
    if (!shareForm.share_password) return;
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
    router.post(`/sites/${props.site.id}/share/regenerate`, {}, {
        preserveScroll: true,
        onStart: () => { isRegeneratingToken.value = true; },
        onFinish: () => { isRegeneratingToken.value = false; },
        onSuccess: () => {
            toast.success('Share link regenerated successfully');
        },
        onError: () => {
            toast.error('Failed to regenerate share link');
        },
    });
};

// Goals Management
type Goal = {
    id: number;
    name: string;
    target_type: 'path' | 'custom_event';
    target_value: string;
};

const goals = ref<Goal[]>([]);
const isLoadingGoals = ref(true);
const isErrorGoals = ref(false);
const isGoalModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const editingGoal = ref<Goal | null>(null);
const goalToDelete = ref<Goal | null>(null);

const goalForm = ref({
    name: '',
    target_type: 'path' as 'path' | 'custom_event',
    target_value: '',
});

const fetchApi = async (method: string, url: string, data?: any) => {
    const token = decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '');
    const res = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-XSRF-TOKEN': token,
        },
        body: data ? JSON.stringify(data) : undefined,
    });
    
    if (!res.ok) {
        throw new Error('API Error');
    }
    
    if (res.status === 204) return null;
    return await res.json();
};

const fetchGoals = async () => {
    isLoadingGoals.value = true;
    isErrorGoals.value = false;
    try {
        const data = await fetchApi('GET', `/sites/${props.site.id}/goals`);
        goals.value = data;
    } catch (err) {
        isErrorGoals.value = true;
        toast.error('Failed to load goals. Please try again.');
    } finally {
        isLoadingGoals.value = false;
    }
};

onMounted(() => {
    fetchGoals();
});

const openCreateGoal = () => {
    editingGoal.value = null;
    goalForm.value = { name: '', target_type: 'path', target_value: '' };
    isGoalModalOpen.value = true;
};

const openEditGoal = (goal: Goal) => {
    editingGoal.value = goal;
    goalForm.value = {
        name: goal.name,
        target_type: goal.target_type,
        target_value: goal.target_value,
    };
    isGoalModalOpen.value = true;
};

const confirmDeleteGoal = (goal: Goal) => {
    goalToDelete.value = goal;
    isDeleteModalOpen.value = true;
};

const saveGoal = async () => {
    try {
        if (editingGoal.value) {
            await fetchApi('PUT', `/sites/${props.site.id}/goals/${editingGoal.value.id}`, goalForm.value);
            toast.success('Goal updated successfully');
        } else {
            await fetchApi('POST', `/sites/${props.site.id}/goals`, goalForm.value);
            toast.success('Goal created successfully');
        }
        isGoalModalOpen.value = false;
        fetchGoals();
    } catch (err) {
        toast.error('Failed to save goal');
    }
};

const deleteGoal = async () => {
    if (!goalToDelete.value) return;
    try {
        await fetchApi('DELETE', `/sites/${props.site.id}/goals/${goalToDelete.value.id}`);
        toast.success('Goal deleted successfully');
        isDeleteModalOpen.value = false;
        fetchGoals();
    } catch (err) {
        toast.error('Failed to delete goal');
    }
};
</script>

<template>
    <Head :title="site.domain" />

    <div class="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4 md:p-6 lg:p-8">
        <Heading :title="site.domain" description="Install the tracking snippet to start collecting data." />

        <div class="max-w-4xl space-y-8">
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

            <!-- Public Sharing Card -->
            <div class="bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Share2 class="h-5 w-5 text-indigo-500" />
                                <h3 class="text-lg font-medium">Public Sharing</h3>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Allow anyone with the link to view this site's read-only analytics dashboard.
                            </p>
                        </div>
                        <Button
                            type="button"
                            :variant="props.site.is_public ? 'default' : 'outline'"
                            :class="props.site.is_public ? 'bg-indigo-600 hover:bg-indigo-500 text-white' : ''"
                            @click="togglePublicSharing"
                            :disabled="shareForm.processing"
                        >
                            {{ props.site.is_public ? 'Public Enabled' : 'Enable Sharing' }}
                        </Button>
                    </div>

                    <div v-if="props.site.is_public && props.site.share_token" class="space-y-6 pt-4 border-t border-sidebar-border/50">
                        <!-- Share URL Display & Copy -->
                        <div class="space-y-2">
                            <Label for="share-url">Share URL</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="share-url"
                                    :model-value="shareUrl"
                                    readonly
                                    class="font-mono text-sm bg-muted/80 text-foreground font-semibold flex-1 select-all"
                                />
                                <Button variant="secondary" @click="copyShareUrl">
                                    <Check v-if="copiedShareUrl" class="h-4 w-4 text-green-500 mr-2" />
                                    <Copy v-else class="h-4 w-4 mr-2" />
                                    {{ copiedShareUrl ? 'Copied' : 'Copy' }}
                                </Button>
                                <a :href="`/share/${props.site.share_token}`" target="_blank" class="inline-flex">
                                    <Button variant="outline" type="button" title="View Public Dashboard">
                                        <ExternalLink class="h-4 w-4" />
                                    </Button>
                                </a>
                            </div>
                        </div>

                        <!-- Regenerate Token Button -->
                        <div class="flex items-center justify-between bg-muted/40 p-4 rounded-lg border">
                            <div>
                                <h4 class="text-sm font-medium">Regenerate Token</h4>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Invalidates old links immediately and generates a new random token.
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="regenerateToken"
                                :disabled="isRegeneratingToken"
                            >
                                <RefreshCw :class="['h-4 w-4 mr-2', { 'animate-spin': isRegeneratingToken }]" />
                                Regenerate Token
                            </Button>
                        </div>

                        <!-- Password Protection Section -->
                        <div class="space-y-4 pt-4 border-t border-sidebar-border/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Lock v-if="props.site.has_password" class="h-4 w-4 text-emerald-500" />
                                    <Unlock v-else class="h-4 w-4 text-muted-foreground" />
                                    <h4 class="text-sm font-medium">Password Protection</h4>
                                </div>
                                <span v-if="props.site.has_password" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    Password Protected
                                </span>
                            </div>

                            <div v-if="props.site.has_password" class="flex items-center justify-between bg-muted/40 p-4 rounded-lg border">
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
                                    :disabled="shareForm.processing || !shareForm.share_password"
                                >
                                    Set Password
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Token Card -->
            <div class="bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl overflow-hidden">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium">Public Stats API Token</h3>
                            <p class="text-sm text-muted-foreground">
                                Use this API token to fetch stats programmatically via <code>/api/v1/stats</code>.
                            </p>
                        </div>
                    </div>

                    <div v-if="props.site.api_token" class="space-y-4">
                        <div class="flex gap-2">
                            <Input
                                :model-value="props.site.api_token"
                                readonly
                                class="font-mono text-sm bg-muted/80 text-foreground font-semibold flex-1 select-all"
                            />
                            <Button variant="secondary" @click="navigator.clipboard.writeText(props.site.api_token || ''); toast.success('API token copied to clipboard');">
                                <Copy class="h-4 w-4 mr-2" />
                                Copy
                            </Button>
                        </div>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        No API token generated yet.
                    </div>
                </div>
            </div>

            <!-- Goals Management Section -->
            <div class="bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-medium">Goals Management</h3>
                            <p class="text-sm text-muted-foreground">Track conversions for specific paths or custom events.</p>
                        </div>
                        <Button @click="openCreateGoal">
                            <Plus class="h-4 w-4 mr-2" />
                            Create Goal
                        </Button>
                    </div>

                    <div v-if="isLoadingGoals" class="py-12 text-center text-sm text-muted-foreground">
                        Loading goals...
                    </div>
                    
                    <div v-else-if="isErrorGoals" class="py-12 text-center text-sm text-destructive">
                        Failed to load goals. Please try again.
                    </div>

                    <div v-else-if="goals.length === 0" class="py-12 text-center border-2 border-dashed rounded-lg">
                        <h4 class="text-base font-medium">No goals created yet</h4>
                        <p class="text-sm text-muted-foreground mt-2 max-w-sm mx-auto">
                            Set up goals to track conversions for specific paths or custom events. Create your first goal.
                        </p>
                        <Button variant="outline" class="mt-4" @click="openCreateGoal">
                            Create Goal
                        </Button>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="goal in goals" :key="goal.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium truncate">{{ goal.name }}</p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    <span class="capitalize">{{ goal.target_type === 'path' ? 'Path' : 'Event' }}</span>: {{ goal.target_value }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <Button variant="ghost" size="icon" @click="openEditGoal(goal)">
                                    <Pencil class="h-4 w-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>
                                <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive hover:bg-destructive/10" @click="confirmDeleteGoal(goal)">
                                    <Trash2 class="h-4 w-4" />
                                    <span class="sr-only">Delete</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Goal Form Modal -->
    <Dialog v-model:open="isGoalModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ editingGoal ? 'Edit Goal' : 'Create Goal' }}</DialogTitle>
                <DialogDescription>
                    Define a conversion goal for this site.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="goalForm.name" placeholder="e.g. Signups" />
                </div>
                <div class="space-y-2">
                    <Label for="type">Type</Label>
                    <Select v-model="goalForm.target_type">
                        <SelectTrigger id="type">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="path">Page Path</SelectItem>
                                <SelectItem value="custom_event">Custom Event</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div class="space-y-2">
                    <Label for="value">Target Value</Label>
                    <Input id="value" v-model="goalForm.target_value" :placeholder="goalForm.target_type === 'path' ? '/thank-you' : 'signup_completed'" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isGoalModalOpen = false">Cancel</Button>
                <Button @click="saveGoal">Save Goal</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <Dialog v-model:open="isDeleteModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Goal</DialogTitle>
                <DialogDescription>
                    Delete Goal: Are you sure you want to delete this goal? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4">
                <Button variant="outline" @click="isDeleteModalOpen = false">Cancel</Button>
                <Button variant="destructive" @click="deleteGoal">Delete</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
