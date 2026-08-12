<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Trash2, Globe } from '@lucide/vue';
import { destroy, show } from '@/actions/App/Http/Controllers/SiteController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
    CardFooter,
} from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Sites',
                href: '/sites',
            },
        ],
    },
});

defineProps<{
    sites: {
        id: number;
        domain: string;
        created_at: string;
    }[];
}>();

const deleteSite = (site: { id: number; domain: string }) => {
    if (
        confirm(
            `Are you sure you want to delete ${site.domain}? All data will be permanently removed.`,
        )
    ) {
        router.delete(destroy.url({ site: site.id }), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Sites" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between">
            <Heading
                title="Sites"
                description="Manage your analytics sites and domains."
            />
            <Button as-child>
                <Link href="/sites/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Site
                </Link>
            </Button>
        </div>

        <div
            v-if="sites.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <Globe class="mb-4 h-12 w-12 text-muted-foreground" />
            <h3 class="text-lg font-semibold">No sites found</h3>
            <p class="mt-1 mb-4 text-sm text-muted-foreground">
                Get started by registering a new site to track analytics.
            </p>
            <Button as-child variant="outline">
                <Link href="/sites/create">Add your first site</Link>
            </Button>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card v-for="site in sites" :key="site.id" class="flex flex-col">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center justify-between text-lg"
                    >
                        <Link
                            :href="show.url({ site: site.id })"
                            class="hover:underline"
                        >
                            {{ site.domain }}
                        </Link>
                    </CardTitle>
                    <CardDescription>
                        Added
                        {{ new Date(site.created_at).toLocaleDateString() }}
                    </CardDescription>
                </CardHeader>
                <CardFooter class="mt-auto flex justify-between pt-4">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="show.url({ site: site.id })"
                            >View Details</Link
                        >
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                        @click="deleteSite(site)"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Delete site</span>
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
