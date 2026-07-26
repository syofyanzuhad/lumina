<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Plus, Trash2, Globe } from '@lucide/vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { destroy } from '@/actions/App/Http/Controllers/SiteController';

const props = defineProps<{
    sites: {
        id: number;
        domain: string;
        created_at: string;
    }[];
}>();

const deleteSite = (site: { id: number; domain: string }) => {
    if (confirm(`Are you sure you want to delete ${site.domain}? All data will be permanently removed.`)) {
        router.delete(destroy.url({ site: site.id }), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Sites', href: '/sites' }]">
        <Head title="Sites" />

        <div class="px-4 py-6 md:px-8 max-w-7xl mx-auto space-y-6 w-full">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Sites</h2>
                    <p class="text-muted-foreground">Manage your analytics sites and domains.</p>
                </div>
                <Button as-child>
                    <Link href="/sites/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Site
                    </Link>
                </Button>
            </div>

            <div v-if="sites.length === 0" class="flex flex-col items-center justify-center p-8 text-center border rounded-lg bg-card/50">
                <Globe class="w-12 h-12 text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold">No sites found</h3>
                <p class="text-sm text-muted-foreground mt-1 mb-4">Get started by registering a new site to track analytics.</p>
                <Button as-child variant="outline">
                    <Link href="/sites/create">Add your first site</Link>
                </Button>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="site in sites" :key="site.id" class="flex flex-col">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg flex items-center justify-between">
                            <Link :href="`/sites/${site.id}`" class="hover:underline">
                                {{ site.domain }}
                            </Link>
                        </CardTitle>
                        <CardDescription>
                            Added {{ new Date(site.created_at).toLocaleDateString() }}
                        </CardDescription>
                    </CardHeader>
                    <CardFooter class="mt-auto pt-4 flex justify-between">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="`/sites/${site.id}`">View Details</Link>
                        </Button>
                        <Button variant="ghost" size="sm" class="text-destructive hover:bg-destructive/10 hover:text-destructive" @click="deleteSite(site)">
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Delete site</span>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
