<script setup lang="ts">
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useGoals } from '@/composables/useGoals';

const props = defineProps<{
    siteId: number;
}>();

const {
    goals,
    isLoading,
    isError,
    isModalOpen,
    isDeleteModalOpen,
    editingGoal,
    form,
    fetchGoals,
    openCreate,
    openEdit,
    confirmDelete,
    saveGoal,
    deleteGoal,
} = useGoals(props.siteId);

onMounted(() => {
    fetchGoals();
});
</script>

<template>
    <!-- Goals Management Section -->
    <div
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
    >
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium">Goals Management</h3>
                    <p class="text-sm text-muted-foreground">
                        Track conversions for specific paths or custom events.
                    </p>
                </div>
                <Button @click="openCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Goal
                </Button>
            </div>

            <div
                v-if="isLoading"
                class="py-12 text-center text-sm text-muted-foreground"
            >
                Loading goals...
            </div>

            <div
                v-else-if="isError"
                class="py-12 text-center text-sm text-destructive"
            >
                Failed to load goals. Please try again.
            </div>

            <div
                v-else-if="goals.length === 0"
                class="rounded-lg border-2 border-dashed py-12 text-center"
            >
                <h4 class="text-base font-medium">No goals created yet</h4>
                <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
                    Set up goals to track conversions for specific paths or
                    custom events. Create your first goal.
                </p>
                <Button variant="outline" class="mt-4" @click="openCreate">
                    Create Goal
                </Button>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="goal in goals"
                    :key="goal.id"
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ goal.name }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="capitalize">{{
                                goal.target_type === 'path' ? 'Path' : 'Event'
                            }}</span
                            >: {{ goal.target_value }}
                        </p>
                    </div>
                    <div class="ml-4 flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            @click="openEdit(goal)"
                        >
                            <Pencil class="h-4 w-4" />
                            <span class="sr-only">Edit</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            @click="confirmDelete(goal)"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Delete</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Goal Form Modal -->
    <Dialog v-model:open="isModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    editingGoal ? 'Edit Goal' : 'Create Goal'
                }}</DialogTitle>
                <DialogDescription>
                    Define a conversion goal for this site.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Signups"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="type">Type</Label>
                    <Select v-model="form.target_type">
                        <SelectTrigger id="type">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="path">Page Path</SelectItem>
                                <SelectItem value="custom_event"
                                    >Custom Event</SelectItem
                                >
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div class="space-y-2">
                    <Label for="value">Target Value</Label>
                    <Input
                        id="value"
                        v-model="form.target_value"
                        :placeholder="
                            form.target_type === 'path'
                                ? '/thank-you'
                                : 'signup_completed'
                        "
                    />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isModalOpen = false"
                    >Cancel</Button
                >
                <Button @click="saveGoal">Save Goal</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Goal Confirmation Modal -->
    <Dialog v-model:open="isDeleteModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Goal</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete this goal? This action
                    cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4">
                <Button variant="outline" @click="isDeleteModalOpen = false"
                    >Cancel</Button
                >
                <Button variant="destructive" @click="deleteGoal"
                    >Delete</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
