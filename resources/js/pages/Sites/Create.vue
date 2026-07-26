<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/SiteController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const form = useForm({
    domain: '',
});

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Sites', href: '/sites' }, { title: 'Add Site', href: '/sites/create' }]">
        <Head title="Add Site" />

        <div class="px-4 py-6 md:px-8 max-w-2xl mx-auto space-y-6 w-full">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Add a new site</h2>
                <p class="text-muted-foreground">Enter the domain of the site you want to track.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 bg-card border rounded-lg p-6">
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
    </AppLayout>
</template>
