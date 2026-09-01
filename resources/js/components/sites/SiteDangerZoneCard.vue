<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
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

const props = defineProps<{
    site: {
        id: number;
        domain: string;
    };
}>();

const isDeleteSiteModalOpen = ref(false);
const confirmDomainInput = ref('');
const isDeletingSite = ref(false);

const openDeleteSiteModal = () => {
    confirmDomainInput.value = '';
    isDeleteSiteModalOpen.value = true;
};

const deleteSite = () => {
    if (confirmDomainInput.value !== props.site.domain) {
        return;
    }

    router.delete(`/sites/${props.site.id}`, {
        onStart: () => {
            isDeletingSite.value = true;
        },
        onFinish: () => {
            isDeletingSite.value = false;
        },
        onSuccess: () => {
            toast.success(`Site ${props.site.domain} deleted successfully`);
        },
        onError: () => {
            toast.error('Failed to delete site');
        },
    });
};
</script>

<template>
    <!-- Danger Zone: Delete Site Card -->
    <div
        class="overflow-hidden rounded-xl border border-destructive/50 bg-card shadow-xs dark:border-destructive/40"
    >
        <div class="space-y-4 p-6">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h3 class="text-lg font-medium text-destructive">
                        Danger Zone
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Permanently delete
                        <strong>{{ site.domain }}</strong> and all of its
                        collected analytics data and goals.
                    </p>
                </div>
                <Button variant="destructive" @click="openDeleteSiteModal">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Site
                </Button>
            </div>
        </div>
    </div>

    <!-- Delete Site Confirmation Modal -->
    <Dialog v-model:open="isDeleteSiteModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="text-destructive">Delete Site</DialogTitle>
                <DialogDescription>
                    This action
                    <strong class="text-foreground">cannot</strong> be undone.
                    This will permanently delete
                    <strong>{{ site.domain }}</strong> and all recorded
                    pageviews, events, and goals.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-3 py-3">
                <Label
                    for="confirm-domain"
                    class="text-xs text-muted-foreground"
                >
                    Please type
                    <span
                        class="font-mono font-semibold text-foreground select-all"
                        >{{ site.domain }}</span
                    >
                    to confirm:
                </Label>
                <Input
                    id="confirm-domain"
                    v-model="confirmDomainInput"
                    :placeholder="site.domain"
                    class="font-mono text-sm"
                    @keyup.enter="deleteSite"
                />
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isDeleteSiteModalOpen = false"
                    >Cancel</Button
                >
                <Button
                    variant="destructive"
                    :disabled="
                        confirmDomainInput !== site.domain || isDeletingSite
                    "
                    @click="deleteSite"
                >
                    <Trash2 v-if="!isDeletingSite" class="mr-2 h-4 w-4" />
                    <span>{{
                        isDeletingSite
                            ? 'Deleting...'
                            : 'Permanently Delete Site'
                    }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
