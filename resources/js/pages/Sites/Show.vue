<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectGroup, SelectItem } from '@/components/ui/select';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Copy, Check, Plus, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref, onMounted } from 'vue';
import Heading from '@/components/Heading.vue';
import { toast } from 'vue-sonner';

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
