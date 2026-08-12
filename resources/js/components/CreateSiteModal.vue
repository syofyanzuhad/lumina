<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { store } from '@/actions/App/Http/Controllers/SiteController';
import InputError from '@/components/InputError.vue';
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

const isOpen = ref(false);

const form = useForm({
    domain: '',
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(store.url(), {
        onSuccess: () => {
            toast.success('Site added successfully');
            closeModal();
        },
    });
};

defineExpose({
    openModal,
    closeModal,
});
</script>

<template>
    <slot :open="openModal"></slot>

    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add a new site</DialogTitle>
                <DialogDescription>
                    Enter the domain of the site you want to track. Do not
                    include http:// or www.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="modal-domain">Domain Name</Label>
                    <Input
                        id="modal-domain"
                        v-model="form.domain"
                        type="text"
                        placeholder="example.com"
                        autofocus
                    />
                    <InputError :message="form.errors.domain" />
                </div>

                <DialogFooter class="pt-4">
                    <Button type="button" variant="outline" @click="closeModal">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Add Site
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
