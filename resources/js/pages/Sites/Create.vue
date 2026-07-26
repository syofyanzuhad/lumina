<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/SiteController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Sites',
                href: '/sites',
            },
            {
                title: 'Add Site',
                href: '/sites/create',
            },
        ],
    },
});

const form = useForm({
    domain: '',
});

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <Head title="Add Site" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <Heading title="Add a new site" description="Enter the domain of the site you want to track." />

        <div class="max-w-2xl">
            <form @submit.prevent="submit" class="space-y-4 bg-card border border-sidebar-border/70 dark:border-sidebar-border rounded-xl p-6">
                <div class="space-y-2">
                    <Label for="domain">Domain Name</Label>
                    <Input 
                        id="domain" 
                        v-model="form.domain" 
                        type="text" 
                        placeholder="example.com" 
                        autofocus
                    />
                    <p class="text-sm text-muted-foreground">Do not include http:// or www.</p>
                    <InputError :message="form.errors.domain" />
                </div>

                <div class="flex justify-end pt-4">
                    <Button type="submit" :disabled="form.processing">
                        Add Site
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
